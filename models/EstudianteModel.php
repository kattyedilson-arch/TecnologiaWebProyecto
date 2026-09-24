<?php
// =========================================================
// MODELO: ESTUDIANTES (EstudianteModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'estudiantes'. Un estudiante extiende un
// usuario con carrera, semestre y registro universitario (RU),
// y es quien solicita las tutorías académicas.
// =========================================================
class EstudianteModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista estudiantes con su carrera y cantidad de tutorías solicitadas.
     * IMPORTANTE: solo devuelve usuarios cuyo rol sea realmente 'estudiante'
     * (evita que perfiles "fantasma" de admin/tutor aparezcan en la lista).
     * @return array Estudiantes ordenados alfabéticamente
     */
    public function obtenerTodos()
    {
        $sql = "SELECT e.id_estudiante, e.id_usuario, e.id_carrera, e.semestre, e.registro_universitario,
                       u.nombre, u.apellido, u.correo, u.telefono, u.usuario, u.estado, u.foto_perfil,
                       c.nombre_carrera,
                       (SELECT COUNT(*) FROM tutorias t WHERE t.id_estudiante = e.id_estudiante) AS total_tutorias
                FROM estudiantes e
                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN carreras c ON e.id_carrera = c.id_carrera
                WHERE r.nombre_rol = 'estudiante'
                ORDER BY u.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca el perfil de estudiante de un usuario dado.
     * @param int $id_usuario Identificador del usuario en sesión
     * @return array|false Fila del estudiante o false si aún no tiene ficha
     */
    public function obtenerPorUsuario($id_usuario)
    {
        $sql = "SELECT e.*, u.nombre, u.apellido, u.correo, u.telefono, c.nombre_carrera
                FROM estudiantes e
                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                INNER JOIN carreras c ON e.id_carrera = c.id_carrera
                WHERE e.id_usuario = :id_usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetch();
    }

    /**
     * Inserta la ficha académica del estudiante o la actualiza si ya existe.
     * Usa ON DUPLICATE KEY (id_usuario es único).
     * @param int $id_usuario Identificador del usuario
     * @param int $id_carrera Carrera elegida
     * @param int $semestre Semestre cursado
     * @param string $registro_universitario Código RU del estudiante
     * @return bool True si el registro fue exitoso
     */
    public function guardarOActualizar($id_usuario, $id_carrera, $semestre, $registro_universitario)
    {
        $sql = "INSERT INTO estudiantes (id_usuario, id_carrera, semestre, registro_universitario)
                VALUES (:id_usuario, :id_carrera, :semestre, :ru)
                AS nuevo
                ON DUPLICATE KEY UPDATE id_carrera = nuevo.id_carrera, semestre = nuevo.semestre, registro_universitario = nuevo.registro_universitario";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_carrera' => $id_carrera,
            ':semestre'   => $semestre,
            ':ru'         => trim($registro_universitario)
        ]);
    }
}