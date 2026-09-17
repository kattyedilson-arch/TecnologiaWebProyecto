<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tutores</title>
</head>
<body>

<h1>Listado de Tutores</h1>

<p>
    <a href="tutores_crear.php">
        + Nuevo Tutor
    </a>
</p>

<table border="1" cellpadding="6">

<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Apellido</th>
    <th>Correo</th>
    <th>Usuario</th>
    <th>Especialidad</th>
    <th>Biografía</th>
    <th>Acciones</th>
</tr>

<?php foreach ($tutores as $t): ?>

<tr>
    <td><?= $t['id_tutor'] ?></td>
    <td><?= htmlspecialchars($t['nombre']) ?></td>
    <td><?= htmlspecialchars($t['apellido']) ?></td>
    <td><?= htmlspecialchars($t['correo']) ?></td>
    <td><?= htmlspecialchars($t['usuario']) ?></td>
    <td><?= htmlspecialchars($t['especialidad']) ?></td>
    <td><?= htmlspecialchars($t['biografia']) ?></td>

    <td>
        <a href="tutores_editar.php?id=<?= $t['id_tutor'] ?>">
            Editar
        </a>
        |
        <a
            href="tutores_eliminar.php?id=<?= $t['id_tutor'] ?>"
            onclick="return confirm('¿Eliminar tutor?');"
        >
            Eliminar
        </a>
    </td>
</tr>

<?php endforeach; ?>

<?php if (empty($tutores)): ?>

<tr>
    <td colspan="8">
        No existen tutores registrados.
    </td>
</tr>

<?php endif; ?>

</table>

</body>
</html>