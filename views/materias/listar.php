<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Materias</title>
</head>
<body>

<h1>Listado de Materias</h1>

<p>
<a href="materias_crear.php">
+ Nueva Materia
</a>
</p>

<table border="1" cellpadding="6">

<tr>
<th>ID</th>
<th>Materia</th>
<th>Carrera</th>
<th>Acciones</th>
</tr>

<?php foreach ($materias as $m): ?>

<tr>

<td><?= $m['id_materia'] ?></td>

<td><?= htmlspecialchars($m['nombre_materia']) ?></td>

<td><?= htmlspecialchars($m['nombre_carrera']) ?></td>

<td>

<a href="materias_editar.php?id=<?= $m['id_materia'] ?>">
Editar
</a>

|

<a
href="materias_eliminar.php?id=<?= $m['id_materia'] ?>"
onclick="return confirm('¿Eliminar materia?');"
>
Eliminar
</a>

</td>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>