<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tutor - Materia</title>
</head>
<body>

<h1>Asignación Tutor - Materia</h1>

<p>
<a href="tutor_materia_crear.php">
    + Nueva Asignación
</a>
</p>

<table border="1" cellpadding="6">

<tr>
    <th>ID</th>
    <th>Tutor</th>
    <th>Materia</th>
    <th>Acciones</th>
</tr>

<?php foreach($relaciones as $r): ?>

<tr>

<td>
    <?= $r['id_tutor'] ?> - <?= $r['id_materia'] ?>
</td>

<td>
<?= htmlspecialchars(
    $r['nombre'] . ' ' . $r['apellido']
) ?>
</td>

<td>
<?= htmlspecialchars($r['nombre_materia']) ?>
</td>

<td>

<a href="tutor_materia_eliminar.php?id_tutor=<?= $r['id_tutor'] ?>&id_materia=<?= $r['id_materia'] ?>" onclick="return confirm('¿Eliminar esta asignación?');">
Eliminar
</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>