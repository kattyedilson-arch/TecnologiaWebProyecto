<?php
// =========================================================
// MODELO: ACTA DE CALIFICACIÓN (ActaModel.php)
// ---------------------------------------------------------
// Acceso a 'actas_calificacion' y 'acta_calificaciones_jurado'.
// El coordinador MG abre un acta por declaración aprobada,
// registra las notas individuales de cada jurado y la NOTA FINAL
// es el promedio (escala 0-100, aprobado desde APROBADO_MIN).
// El acta pasa de 'abierta' (editable) a 'firmada' (imprimible
// y bloqueada) cuando el coordinador/admin la firma.
// =========================================================
require_once __DIR__ . '/ParametroModel.php';

class ActaModel
{
    /** Nota mínima para considerar la defensa aprobada (escala 0-100). */
    const APROBADO_MIN = 51;
    /** Nota máxima permitida en la escala. */
    const NOTA_MAX = 100; 

    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Nota máxima configurable de la escala (parametros_mg: NOTA_MAX).
     * @return float Máximo permitido
     */
    public function notaMax()
    {
        $valor = (new ParametroModel($this->pdo))->obtener('NOTA_MAX', null);
        return $valor !== null ? (float)$valor : (float)self::NOTA_MAX;
    }

    /**
     * Nota mínima configurable para aprobar (parametros_mg: APROBADO_MIN).
     * @return float Umbral de aprobación
     */
    public function aprobadoMin()
    {
        $valor = (new ParametroModel($this->pdo))->obtener('APROBADO_MIN', null);
        return $valor !== null ? (float)$valor : (float)self::APROBADO_MIN;
    }

    /**
     * Acta de una declaración junto a las notas de cada jurado.
     * @param int $id_declaracion Identificador de la declaración
     * @return array{'acta': array|null, 'notas': array}
     */
    public function obtenerPorDeclaracion($id_declaracion)
    {
        $acta = null;
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.nombre AS firmante_nombre, u.apellido AS firmante_apellido
             FROM actas_calificacion a
             LEFT JOIN usuarios u ON a.id_firmante = u.id_usuario
             WHERE a.id_declaracion = :id"
        );
        $stmt->execute([':id' => $id_declaracion]);
        if ($fila = $stmt->fetch()) {
            $acta = $fila;
            $stmtNotas = $this->pdo->prepare(
                "SELECT ac.id_jurado, ac.nota, ac.comentario, j.rol_jurado, j.id_usuario,
                        u.nombre, u.apellido
                 FROM acta_calificaciones_jurado ac
                 INNER JOIN jurados_declaracion j ON ac.id_jurado = j.id_jurado
                 INNER JOIN usuarios u ON j.id_usuario = u.id_usuario
                 INNER JOIN actas_calificacion a ON ac.id_acta = a.id_acta
                 WHERE a.id_declaracion = :id
                 ORDER BY FIELD(j.rol_jurado, 'presidente', 'titular', 'suplente'), u.nombre ASC"
            );
            $stmtNotas->execute([':id' => $id_declaracion]);
            $acta['notas'] = $stmtNotas->fetchAll();
        }
        return $acta;
    }

    /**
     * Crea el acta (abierta) de una declaración si aún no existe.
     * @param int $id_declaracion Identificador de la declaración
     * @return bool True si quedó disponible (ya existía o se creó)
     */
    public function abrirSiVacio($id_declaracion)
    {
        $this->pdo->prepare("INSERT IGNORE INTO actas_calificacion (id_declaracion) VALUES (:id)")
            ->execute([':id' => $id_declaracion]);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM actas_calificacion WHERE id_declaracion = :id"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Guarda/actualiza la nota de cada jurado y recalcula la nota final.
     * Solo permite editar actas abiertas.
     * @param int $id_acta Identificador del acta
     * @param array $porJurado Lista [id_jurado => ['nota' => float, 'comentario' => string]]
     * @param string|null $fechaDefensa Fecha/hora de la defensa (YYYY-MM-DD HH:MM)
     * @param string|null $lugar Dónde se realiza la defensa
     * @param string|null $observaciones Notas del acta
     * @return array|false ['nota_final' => float, 'resultado' => string] o false si está firmada
     */
    public function guardarNotas($id_acta, array $porJurado, $fechaDefensa, $lugar, $observaciones)
    {
        $estado = $this->estado($id_acta);
        if ($estado === 'firmada') {
            return false;
        }

        // Registra cada nota (clave primaria compuesta: acta + jurado)
        $stmt = $this->pdo->prepare(
            "INSERT INTO acta_calificaciones_jurado (id_acta, id_jurado, nota, comentario)
             VALUES (:acta, :jurado, :nota, :comentario)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota), comentario = VALUES(comentario)"
        );
        foreach ($porJurado as $idJurado => $datos) {
            $stmt->execute([
                ':acta'       => $id_acta,
                ':jurado'     => (int)$idJurado,
                ':nota'       => round((float)$datos['nota'], 2),
                ':comentario' => trim($datos['comentario'] ?? '') ?: null,
            ]);
        }

        // Recalcula nota_final = promedio de las notas registradas
        $stmtProm = $this->pdo->prepare(
            "SELECT ROUND(AVG(nota), 2) FROM acta_calificaciones_jurado WHERE id_acta = :acta"
        );
        $stmtProm->execute([':acta' => $id_acta]);
        $notaFinal = $stmtProm->fetchColumn();
        $notaFinal = $notaFinal === null ? null : (float)$notaFinal;

        $resultado = 'pendiente';
        if ($notaFinal !== null) {
            $resultado = $notaFinal >= $this->aprobadoMin() ? 'aprobado' : 'reprobado';
        }

        $stmtUpd = $this->pdo->prepare(
            "UPDATE actas_calificacion
             SET nota_final = :nota, resultado = :resultado,
                 fecha_defensa = :fecha, lugar = :lugar, observaciones = :obs
             WHERE id_acta = :acta"
        );
        $stmtUpd->execute([
            ':nota'     => $notaFinal,
            ':resultado'=> $resultado,
            ':fecha'    => trim($fechaDefensa ?? '') ?: null,
            ':lugar'    => trim($lugar ?? '') ?: null,
            ':obs'      => trim($observaciones ?? '') ?: null,
            ':acta'     => $id_acta,
        ]);

        return ['nota_final' => $notaFinal, 'resultado' => $resultado];
    }

    /**
     * Firma el acta: la bloquea y la deja lista para imprimir.
     * Exige tener al menos una nota registrada.
     * @param int $id_acta Identificador del acta
     * @param int $id_firmante Usuario que firma (coordinador/admin)
     * @param string|null $presidente Nombre del presidente del tribunal que da fe
     * @return bool True si se firmó
     */
    public function firmar($id_acta, $id_firmante, $presidente = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE actas_calificacion
             SET estado = 'firmada', id_firmante = :firmante, fecha_firma = NOW(),
                 presidente = :presidente
             WHERE id_acta = :acta
               AND estado = 'abierta'
               AND nota_final IS NOT NULL"
        );
        return $stmt->execute([
            ':acta'       => $id_acta,
            ':firmante'   => $id_firmante,
            ':presidente' => trim((string)$presidente) ?: null,
        ]);
    }

    /**
     * Estado actual de un acta.
     * @param int $id_acta Identificador del acta
     * @return string|null 'abierta', 'firmada' o null si no existe
     */
    private function estado($id_acta)
    {
        $stmt = $this->pdo->prepare("SELECT estado FROM actas_calificacion WHERE id_acta = :acta");
        $stmt->execute([':acta' => $id_acta]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : $valor;
    }
}