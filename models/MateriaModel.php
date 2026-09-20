<?php
// =========================================================
// MODELO: MATERIAS (MateriaModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'materias' de la base de datos.
// Una materia pertenece a una carrera (opcional) y puede ser
// impartida por uno o varios tutores académicos.
// =========================================================
class MateriaModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las materias junto a su carrera y cuántos tutores la imparten.
     * @return array Lista de materias del catálogo
     */
    public function obtenerTodas()
    {
        $sql = "SELECT m.id_materia, m.nombre_materia, m.id_carrera,
                       c.nombre_carrera,
                       (SELECT COUNT(*) FROM tutor_materia tm WHERE tm.id_materia = m.id_materia) AS total_tutores
                FROM materias m
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                ORDER BY m.nombre_materia ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca una materia por su identificador.
     * @param int $id Identificador de la materia
     * @return array|false Fila de la materia o false si no existe
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM materias WHERE id_materia = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Materias de una carrera específica, con su carrera (usado en formularios).
     * @param int $id_carrera Identificador de la carrera
     * @return array Materias de la carrera ordenadas alfabéticamente
     */
    public function obtenerPorCarrera($id_carrera)
    {
        $sql = "SELECT m.id_materia, m.nombre_materia, m.id_carrera,
                       c.nombre_carrera
                FROM materias m
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                WHERE m.id_carrera = :id_carrera
                ORDER BY m.nombre_materia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_carrera' => $id_carrera]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si ya existe una materia con el mismo nombre en la MISMA carrera
     * (evita materias repetidas dentro de una carrera), ignorando
     * mayúsculas/minúsculas, tildes y espacios extra.
     * @param string $nombre Nombre de la materia a verificar
     * @param int $idCarrera Carrera a la que pertenece la materia
     * @param int|null $excluirId Si se indica, esa materia no cuenta (para ediciones)
     * @return bool True si ya existe otra materia con nombre equivalente en esa carrera
     */
    public function existeEnCarrera($nombre, $idCarrera, $excluirId = null)
    {
        $nombreNorm = $this->normalizarNombre($nombre);
        $stmt = $this->pdo->prepare("SELECT id_materia, nombre_materia FROM materias WHERE id_carrera = :idc");
        $stmt->execute([':idc' => $idCarrera]);
        foreach ($stmt->fetchAll() as $fila) {
            if ($excluirId !== null && (int)$fila['id_materia'] === (int)$excluirId) {
                continue;
            }
            if ($this->normalizarNombre($fila['nombre_materia']) === $nombreNorm) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crea una nueva materia.
     * @param array $datos Arreglo con nombre_materia e id_carrera (opcional)
     * @return bool True si la inserción fue exitosa
     */
    public function crear($datos)
    {
        $stmt = $this->pdo->prepare("INSERT INTO materias (nombre_materia, id_carrera) VALUES (:nombre, :id_carrera)");
        return $stmt->execute([
            ':nombre'     => trim($datos['nombre_materia']),
            ':id_carrera' => !empty($datos['id_carrera']) ? $datos['id_carrera'] : null
        ]);
    }

    /**
     * Actualiza los datos de una materia existente.
     * @param int $id Identificador de la materia
     * @param array $datos Arreglo con nombre_materia e id_carrera
     * @return bool True si la actualización fue exitosa
     */
    public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare("UPDATE materias SET nombre_materia = :nombre, id_carrera = :id_carrera WHERE id_materia = :id");
        return $stmt->execute([
            ':nombre'     => trim($datos['nombre_materia']),
            ':id_carrera' => !empty($datos['id_carrera']) ? $datos['id_carrera'] : null,
            ':id'         => $id
        ]);
    }

    /**
     * Elimina una materia del catálogo.
     * @param int $id Identificador de la materia
     * @return bool True si la eliminación fue exitosa (fallará si hay tutorías/tutores asociados)
     */
    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM materias WHERE id_materia = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Normaliza un nombre para comparar equivalencias (minúsculas, sin tildes, sin espacios extra).
     * @param string $texto Nombre original
     * @return string Nombre normalizado
     */
    private function normalizarNombre($texto)
    {
        $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n'
        ]);
        return trim((string)preg_replace('/\s+/u', ' ', $texto));
    }
}