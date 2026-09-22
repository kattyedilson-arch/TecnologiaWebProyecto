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
     * Verifica si ya existe una materia equivalente en la MISMA carrera
     * (evita materias repetidas dentro de una carrera), ignorando
     * mayúsculas/minúsculas, tildes y espacios extra. Además detecta typos
     * o variaciones mínimas ("Base de Datos I" ~ "Bases de Datos L") usando
     * similitud de texto, pero respetando niveles legítimos ("Programación I"
     * vs "Programación II" NO son duplicados).
     * La misma materia puede existir en carreras DIFERENTES (el duplicado
     * solo se valida dentro de la carrera elegida).
     * @param string $nombre Nombre de la materia a verificar
     * @param int|null $excluirId Si se indica, esa materia no cuenta (para ediciones)
     * @param int|null $id_carrera Carrera en la que se valida el duplicado
     * @return bool True si ya existe otra materia equivalente en esa carrera
     */
    public function existeNombre($nombre, $excluirId = null, $id_carrera = null)
    {
        $condiciones = ['id_carrera ' . ($id_carrera === null ? 'IS NULL' : '= :carrera')];
        $params = [];
        if ($id_carrera !== null) {
            $params[':carrera'] = $id_carrera;
        }
        if ($excluirId !== null) {
            $condiciones[] = 'id_materia <> :excluir_id';
            $params[':excluir_id'] = $excluirId;
        }

        $sql = "SELECT id_materia, nombre_materia FROM materias WHERE " . implode(' AND ', $condiciones);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $fila) {
            if ($this->esEquivalente($nombre, $fila['nombre_materia'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compara dos nombres de materia y determina si representan la misma
     * materia (duplicado) o no, usando normalización + similitud de texto.
     * @param string $nuevo Nombre nuevo/escrito a comparar
     * @param string $existente Nombre ya guardado en el catálogo
     * @return bool True si se consideran la misma materia
     */
    public function esEquivalente($nuevo, $existente)
    {
        $n = $this->normalizarNombre($nuevo);
        $e = $this->normalizarNombre($existente);
        if ($n === $e) {
            return true;
        }

        list($baseNueva, $nivelNuevo) = $this->separarNivel($n);
        list($baseExistente, $nivelExistente) = $this->separarNivel($e);

        // Si solo cambia el nivel (I, II, III...) son materias distintas y legítimas
        $nivelesDiferentes = $this->esNivelValido($nivelNuevo)
            && $this->esNivelValido($nivelExistente)
            && $this->valorNivel($nivelNuevo) !== $this->valorNivel($nivelExistente);

        if ($baseNueva === $baseExistente) {
            return !$nivelesDiferentes;
        }

        // Similitud alta: probable typo o variante (Base/Bases, I/L, acentos...)
        similar_text($n, $e, $pct);
        if ($pct >= 87.0) {
            return !$nivelesDiferentes;
        }
        return false;
    }

    /**
     * Separa la base del nombre de un posible sufijo de nivel (I, II, 1, 2...).
     * @param string $texto Nombre normalizado
     * @return array [base, nivel] donde nivel es '' si no hay sufijo
     */
    private function separarNivel($texto)
    {
        // El sufijo de nivel puede ser romano (I, II...), número (1, 2...) o la
        // letra "l" (typo frecuente de "I", p. ej. "Programacion l").
        if (preg_match('/^(.*?)\s+(x{0,3}(?:ix|iv|v?i{0,3})|l|\d{1,2})$/u', $texto, $m) && trim($m[1]) !== '') {
            return [rtrim($m[1]), mb_strtolower($m[2], 'UTF-8')];
        }
        return [$texto, ''];
    }

    /**
     * True si el sufijo es un nivel válido (romano I..X, "l" o número 1..99).
     */
    private function esNivelValido($nivel)
    {
        return $nivel !== '' && preg_match('/^(x{0,3}(?:ix|iv|v?i{0,3})|l|\d{1,2})$/u', $nivel) === 1;
    }

    /**
     * Convierte un nivel a su valor arábigo para comparar ("I"=1, "1"=1, "l"=1).
     */
    private function valorNivel($nivel)
    {
        if (preg_match('/^\d{1,2}$/', $nivel)) {
            return (int)$nivel;
        }
        $mapa = ['ix' => 9, 'iv' => 4, 'x' => 10, 'v' => 5, 'i' => 1, 'l' => 1];
        $nivel = strtolower($nivel);
        $suma = 0;
        $i = 0;
        $len = strlen($nivel);
        while ($i < $len) {
            $dos = substr($nivel, $i, 2);
            if (isset($mapa[$dos])) {
                $suma += $mapa[$dos];
                $i += 2;
                continue;
            }
            $uno = substr($nivel, $i, 1);
            if (isset($mapa[$uno])) {
                $suma += $mapa[$uno];
                $i += 1;
                continue;
            }
            $i++;
        }
        return $suma;
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