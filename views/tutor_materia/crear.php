<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Asignación</title>
</head>
<body>

<h1>Asignar Materia a Tutor</h1>

<?php foreach ($errores as $e): ?>

<p style="color:red;">
<?= htmlspecialchars($e) ?>
</p>

<?php endforeach; ?>

<form method="POST">

<label>

Tutor:

<select name="id_tutor" required>

<option value="">
Seleccione
</option>

<?php foreach($tutores as $t): ?>

<option
value="<?= $t['id_tutor'] ?>"
>

<?= htmlspecialchars(
$t['nombre'].' '.$t['apellido']
) ?>

</option>

<?php endforeach; ?>

</select>

</label>

<br><br>

<label>

Materia:

<select name="id_materia" required>

<option value="">
Seleccione
</option>

<?php foreach($materias as $m): ?>

<option
value="<?= $m['id_materia'] ?>"
>

<?= htmlspecialchars(
$m['nombre_materia']
) ?>

</option>

<?php endforeach; ?>

</select>

</label>

<br><br>

<button type="submit">
Guardar
</button>

<a href="tutor_materia_listar.php">
Cancelar
</a>

</form>

</body>
</html>