<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Estudiante</title>
</head>
<body>

<h1>Editar Estudiante</h1>

<?php foreach ($errores as $e): ?>
<p style="color:red;">
    <?= htmlspecialchars($e) ?>
</p>
<?php endforeach; ?>

<form method="POST">

<input
    type="hidden"
    name="id_estudiante"
    value="<?= $estudiante['id_estudiante'] ?>"
>

<label>
Nombre:
<input
    type="text"
    name="nombre"
    value="<?= htmlspecialchars($estudiante['nombre']) ?>"
    required
>
</label>

<br><br>

<label>
Apellido:
<input
    type="text"
    name="apellido"
    value="<?= htmlspecialchars($estudiante['apellido']) ?>"
    required
>
</label>

<br><br>

<label>
Correo:
<input
    type="email"
    name="correo"
    value="<?= htmlspecialchars($estudiante['correo']) ?>"
    required
>
</label>

<br><br>

<label>
Usuario:
<input
    type="text"
    name="usuario"
    value="<?= htmlspecialchars($estudiante['usuario']) ?>"
    required
>
</label>

<br><br>

<label>
Carrera:

<select name="id_carrera">

<?php foreach ($carreras as $c): ?>

<option
value="<?= $c['id_carrera'] ?>"
<?= $c['id_carrera'] == $estudiante['id_carrera']
? 'selected'
: '' ?>
>

<?= htmlspecialchars($c['nombre_carrera']) ?>

</option>

<?php endforeach; ?>

</select>

</label>

<br><br>

<label>

Semestre:

<input
type="number"
name="semestre"
min="1"
max="12"
value="<?= $estudiante['semestre'] ?>"
required
>

</label>

<br><br>

<p>

<strong>RU:</strong>

<?= htmlspecialchars(
$estudiante['registro_universitario']
) ?>

</p>

<button type="submit">
Actualizar
</button>

<a href="estudiantes_listar.php">
Cancelar
</a>

</form>

</body>
</html>