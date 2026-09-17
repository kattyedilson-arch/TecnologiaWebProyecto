<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estudiantes</title>
</head>
<body>

<h1>Listado de Estudiantes</h1>
<p>
    <a href="estudiantes_crear.php">
        + Nuevo Estudiante
    </a>
</p>

<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Apellido</th>
        <th>Usuario</th>
        <th>Carrera</th>
        <th>Semestre</th>
        <th>Registro Universitario</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($estudiantes as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['id_estudiante']) ?></td>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= htmlspecialchars($e['apellido']) ?></td>
            <td><?= htmlspecialchars($e['usuario']) ?></td>
            <td><?= htmlspecialchars($e['nombre_carrera']) ?></td>
            <td><?= htmlspecialchars($e['semestre']) ?></td>
            <td><?= htmlspecialchars($e['registro_universitario']) ?></td>
            <td>
                <a href="estudiantes_editar.php?id=<?= $e['id_estudiante'] ?>">
                    Editar
                </a>
                |
                <a href="estudiantes_eliminar.php?id=<?= $e['id_estudiante'] ?>" onclick="return confirm('¿Eliminar este estudiante?');">
                    Eliminar
                </a>
            </td>
        </tr>
    <?php endforeach; ?>

    <?php if (empty($estudiantes)): ?>
        <tr>
            <td colspan="8">No hay estudiantes registrados.</td>
        </tr>
    <?php endif; ?>

</table>

</body>
</html>