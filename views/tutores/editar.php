<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Tutor</title>
</head>
<body>

<h1>Editar Tutor</h1>

<?php foreach ($errores as $e): ?>
<p style="color:red;">
<?= htmlspecialchars($e) ?>
</p>
<?php endforeach; ?>

<form method="POST">

<input
type="hidden"
name="id_tutor"
value="<?= $tutor['id_tutor'] ?>"
>

<label>
Nombre:
<input type="text"
name="nombre"
value="<?= htmlspecialchars($tutor['nombre']) ?>"
required>
</label>

<br><br>

<label>
Apellido:
<input type="text"
name="apellido"
value="<?= htmlspecialchars($tutor['apellido']) ?>"
required>
</label>

<br><br>

<label>
Correo:
<input type="email"
name="correo"
value="<?= htmlspecialchars($tutor['correo']) ?>"
required>
</label>

<br><br>

<label>
Usuario:
<input type="text"
name="usuario"
value="<?= htmlspecialchars($tutor['usuario']) ?>"
required>
</label>

<br><br>

<label>
Especialidad:
<input type="text"
name="especialidad"
value="<?= htmlspecialchars($tutor['especialidad']) ?>"
required>
</label>

<br><br>

<label>
Biografía:
<br>
<textarea
name="biografia"
rows="5"
cols="50"><?= htmlspecialchars($tutor['biografia']) ?></textarea>
</label>

<br><br>

<button type="submit">
Actualizar
</button>

<a href="tutores_listar.php">
Cancelar
</a>

</form>

</body>
</html>