<?php
// =========================================================
// MODELO: ETAPAS DEL EXPEDIENTE MG (EtapasExpedienteModel.php)
// ---------------------------------------------------------
// HU-024. El expediente MG (declaraciones_modalidad) recorre
// etapas académicas: previa -> mg1 -> mg2 -> finalizado. Cada
// transición quedó registrada en 'etapas_expediente' (historial
// inmutable) y 'etapa_actual' es un espejo sobre la declaración
// para facilitar listados y filtros.
// =========================================================
class EtapasExpedienteModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Historial completo de etapas de un expediente.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Etapas de más reciente a más antigua
     */
    public function obtenerHistorial($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, u.nombre AS registrado_nombre, u.apellido AS registrado_apellido
             FROM etapas_expediente e
             LEFT JOIN usuarios u ON e.registrado_por = u.id_usuario
             WHERE e.id_declaracion = :id
             ORDER BY e.id_etapa DESC"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetchAll();
    }

    /**
     * La etapa en curso (sin fecha de fin) o la última registrada.
     * @param int $id_declaracion Identificador de la declaración
     * @return array|false Etapa actual o false
     */
    public function obtenerEtapaActual($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM etapas_expediente
             WHERE id_declaracion = :id
             ORDER BY (fecha_fin IS NULL) DESC, id_etapa DESC
             LIMIT 1"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetch();
    }

    /**
     * Abre una etapa nueva (inicio) cerrando la que esté abierta.
     * @param int $id_declaracion Identificador de la declaración
     * @param string $etapa previa|mg1|mg2|finalizado
     * @param string|null $resultado Resultado con el que se cierra la etapa anterior
     * @param string|null $observacion Observación del cambio de etapa
     * @param int $registradoPor Usuario que ejecuta la transición
     * @return bool True si la transición fue exitosa
     */
    public function transicionar($id_declaracion, $etapa, $resultado = null, $observacion = null, $registradoPor = null)
    {
        try {
            $this->pdo->beginTransaction();

            // Cierra la etapa abierta (si existe) con el resultado indicado
            $cierre = $this->pdo->prepare(
                "UPDATE etapas_expediente
                 SET fecha_fin = NOW(), resultado = :resultado
                 WHERE id_declaracion = :id AND fecha_fin IS NULL"
            );
            $cierre->execute([
                ':resultado' => $resultado ?: 'activo',
                ':id'        => $id_declaracion,
            ]);

            // Abre la etapa nueva
            $abre = $this->pdo->prepare(
                "INSERT INTO etapas_expediente (id_declaracion, etapa, fecha_inicio, resultado, observacion, registrado_por)
                 VALUES (:id, :etapa, NOW(), 'activo', :observacion, :registrado)"
            );
            $abre->execute([
                ':id'         => $id_declaracion,
                ':etapa'      => $etapa,
                ':observacion'=> $observacion ?: null,
                ':registrado' => $registradoPor ?: null,
            ]);

            // Espejo sobre la declaración
            $espejo = $this->pdo->prepare(
                "UPDATE declaraciones_modalidad SET etapa_actual = :etapa WHERE id_declaracion = :id"
            );
            $espejo->execute([':etapa' => $etapa, ':id' => $id_declaracion]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Cierra la etapa en curso indicando un resultado terminal.
     * @param int $id_declaracion Identificador de la declaración
     * @param string $resultado aprobado|reprobado|abandono|retirado
     * @param string|null $observacion Motivo del cierre
     * @param int $registradoPor Usuario que registra
     * @return bool True si se cerró
     */
    public function cerrarEtapaActual($id_declaracion, $resultado, $observacion, $registradoPor = null)
    {
        try {
            $this->pdo->beginTransaction();

            $cierre = $this->pdo->prepare(
                "UPDATE etapas_expediente
                 SET fecha_fin = NOW(), resultado = :resultado, observacion = :observacion
                 WHERE id_declaracion = :id AND fecha_fin IS NULL"
            );
            $cierre->execute([
                ':resultado'    => $resultado,
                ':observacion'  => $observacion ?: null,
                ':id'           => $id_declaracion,
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * RN-MG-22: cierre terminal (abandono/reprobado) en UNA transacción:
     * cierra la etapa en curso con resultado y motivo, y sincroniza el
     * estado de la declaración (nunca automático; lo registra Coordinación).
     * @param int $id_declaracion Identificador de la declaración
     * @param string $estadoBd abandono|reprobado (estado de la declaración)
     * @param string $resultadoEtapa abandono|reprobado|retirado
     * @param string $motivo Nota/motivo obligatorio (RN-MG-22)
     * @param int $registradoPor Usuario que registra
     * @return bool True si el cierre fue atómico y exitoso
     */
    public function cerrarExpediente($id_declaracion, $estadoBd, $resultadoEtapa, $motivo, $registradoPor = null)
    {
        try {
            $this->pdo->beginTransaction();

            $cierre = $this->pdo->prepare(
                "UPDATE etapas_expediente
                 SET fecha_fin = NOW(), resultado = :resultado, observacion = :motivo
                 WHERE id_declaracion = :id AND fecha_fin IS NULL"
            );
            $cierre->execute([
                ':resultado' => $resultadoEtapa,
                ':motivo'    => $motivo,
                ':id'        => $id_declaracion,
            ]);

            $decl = $this->pdo->prepare(
                "UPDATE declaraciones_modalidad
                 SET estado = :estado, observacion = :motivo, fecha_revision = NOW()
                 WHERE id_declaracion = :id"
            );
            $decl->execute([
                ':estado' => $estadoBd,
                ':motivo' => $motivo,
                ':id'     => $id_declaracion,
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}