<?php

require_once __DIR__ . '/includes/verificar_sesion.php';

$rol = $_SESSION['id_rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Tutorías</title>
</head>
<body>

<h1>Sistema de Gestión de Tutorías</h1>

<p>
Bienvenido:
<strong>
<?= htmlspecialchars($_SESSION['nombre']) ?>
</strong>
</p>

<?php if ($rol == 1): ?>

<h2>Panel Administrador</h2>

<ul>

<li>
<a href="controllers/usuarios_listar.php">
Usuarios
</a>
</li>

<li>
<a href="controllers/estudiantes_listar.php">
Estudiantes
</a>
</li>

<li>
<a href="controllers/tutores_listar.php">
Tutores
</a>
</li>

<li>
<a href="controllers/materias_listar.php">
Materias
</a>
</li>

<li>
<a href="controllers/tutor_materia_listar.php">
Tutor - Materia
</a>
</li>

<li>
<a href="controllers/tutorias_listar.php">
Tutorías
</a>
</li>

</ul>

<?php elseif ($rol == 2): ?>

<h2>Panel Tutor</h2>

<ul>

<li>
<a href="controllers/tutorias_listar.php">
Mis Tutorías
</a>
</li>

</ul>

<?php elseif ($rol == 3): ?>

<h2>Panel Estudiante</h2>

<ul>

<li>
<a href="controllers/tutorias_crear.php">
Solicitar Tutoría
</a>
</li>

<li>
<a href="controllers/tutorias_listar.php">
Mis Tutorías
</a>
</li>

</ul>

<?php endif; ?>

<hr>

<p>
<a href="controllers/logout.php">
Cerrar Sesión
</a>
</p>

</body>
</html>