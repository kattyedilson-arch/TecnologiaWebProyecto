<?php
// =========================================================
// MODELO: AVALES DE DECLARACIÓN (AvalModel.php)
// ---------------------------------------------------------
// Acceso a 'avales_declaracion'. Cuando el coordinador aprueba
// una declaración se crean automáticamente los avales estándar;
// el equipo MG los ajusta y registra la entrega de cada uno.
// =========================================================
class AvalModel
{
    private $pdo; // Conexión PDO compartida

    /** Avales estándar que se crean al aprobar una declaración */
    const AVALES_DEFECTO = [
        ['Aval de la empresa u organización', 'Si la modalidad se realiza en una institución.'],
        ['Carta de compromiso del tutor facultativo', 'Documento firmado por el docente tutor.'],
        ['Boleta de pago de la modalidad', 'Comprobante emitido por tesorería.'],
        ['Copia escaneada del registro universitario', 'Fotocopia legible del RU vigente.'],
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Avales de una declaración.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Avales ordenados por estado y fecha
     */
    public function obtenerPorDeclaracion($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM avales_declaracion
             WHERE id_declaracion = :id
             ORDER BY (estado = 'pendiente') DESC, fecha_creacion ASC"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetchAll();
    }

    /**
     * Conteos (pendiente/entregado/observado) de una declaración.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Conteos por estado
     */
    public function contarPorDeclaracion($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT estado, COUNT(*) AS total FROM avales_declaracion
             WHERE id_declaracion = :id GROUP BY estado"
        );
        $stmt->execute([':id' => $id_declaracion]);
        $conteo = ['pendiente' => 0, 'entregado' => 0, 'observado' => 0, 'total' => 0];
        foreach ($stmt->fetchAll() as $fila) {
            $conteo[$fila['estado']] = (int)$fila['total'];
        }
        $conteo['total'] = array_sum($conteo);
        return $conteo;
    }

    /**
     * Crea los avales estándar si la declaración aún no tiene ninguno.
     * @param int $id_declaracion Identificador de la declaración
     * @return int Cantidad de avales creados
     */
    public function crearDefectoSiVacio($id_declaracion)
    {
        if ((int)$this->contarPorDeclaracion($id_declaracion)['total'] > 0) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO avales_declaracion (id_declaracion, nombre, descripcion)
             VALUES (:id, :nombre, :descripcion)"
        );
        $creados = 0;
        foreach (self::AVALES_DEFECTO as $aval) {
            $stmt->execute([
                ':id'           => $id_declaracion,
                ':nombre'       => $aval[0],
                ':descripcion'  => $aval[1],
            ]);
            $creados++;
        }
        return $creados;
    }

    /**
     * Agrega un ítem manual al checklist.
     * @param int $id_declaracion Identificador de la declaración
     * @param string $nombre Nombre del aval
     * @param string $descripcion Detalle opcional
     * @return bool True si se insertó
     */
    public function agregarItem($id_declaracion, $nombre, $descripcion = '')
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO avales_declaracion (id_declaracion, nombre, descripcion)
             VALUES (:id, :nombre, :descripcion)"
        );
        return $stmt->execute([
            ':id'           => $id_declaracion,
            ':nombre'       => trim($nombre),
            ':descripcion'  => trim($descripcion),
        ]);
    }

    /**
     * Elimina un ítem del checklist de la declaración.
     * @param int $id_aval Identificador del aval
     * @param int $id_declaracion Para no borrar de otra declaración
     * @return bool True si se eliminó
     */
    public function eliminarItem($id_aval, $id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM avales_declaracion WHERE id_aval = :id AND id_declaracion = :declaracion"
        );
        return $stmt->execute([':id' => $id_aval, ':declaracion' => $id_declaracion]);
    }

    /**
     * Guarda la referencia del documento digital adjunto a un aval.
     * @param int $id_aval Identificador del aval
     * @param int $id_declaracion Seguridad: pertenencia
     * @param string $archivo Ruta web del archivo
     * @param string $archivo_nombre Nombre original para la descarga
     * @return bool True si se actualizó
     */
    public function adjuntarArchivo($id_aval, $id_declaracion, $archivo, $archivo_nombre)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE avales_declaracion
             SET archivo = :archivo, archivo_nombre = :archivo_nombre
             WHERE id_aval = :id AND id_declaracion = :declaracion"
        );
        return $stmt->execute([
            ':archivo'        => $archivo,
            ':archivo_nombre' => $archivo_nombre,
            ':id'             => $id_aval,
            ':declaracion'    => $id_declaracion,
        ]);
    }

    /**
     * Quita la referencia del archivo adjunto de un aval.
     * @param int $id_aval Identificador del aval
     * @param int $id_declaracion Seguridad: pertenencia
     * @return bool True si se actualizó
     */
    public function quitarArchivo($id_aval, $id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE avales_declaracion SET archivo = NULL, archivo_nombre = NULL
             WHERE id_aval = :id AND id_declaracion = :declaracion"
        );
        return $stmt->execute([':id' => $id_aval, ':declaracion' => $id_declaracion]);
    }

    /**
     * Actualiza el estado de un aval (entregado / observado en devolución).
     * @param int $id_aval Identificador del aval
     * @param int $id_declaracion Seguridad: pertenencia
     * @param string $estado entregado|observado|pendiente
     * @param string|null $observacion Comentario del equipo MG
     * @return bool True si se actualizó
     */
    public function actualizarEstado($id_aval, $id_declaracion, $estado, $observacion = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE avales_declaracion
             SET estado = :estado_set,
                 observacion = :observacion,
                 fecha_entrega = IF(:estado_check = 'pendiente', NULL, NOW())
             WHERE id_aval = :id AND id_declaracion = :declaracion"
        );
        return $stmt->execute([
            ':estado_set'   => $estado,
            ':estado_check' => $estado,
            ':observacion'  => trim($observacion ?? '') ?: null,
            ':id'           => $id_aval,
            ':declaracion'  => $id_declaracion,
        ]);
    }
}