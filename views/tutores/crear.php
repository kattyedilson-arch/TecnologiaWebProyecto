<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Tutor</title>
</head>
<body>

<h1>Registrar Tutor</h1>

<?php foreach ($errores as $e): ?>
<p style="color:red;">
    <?= htmlspecialchars($e) ?>
</p>
<?php endforeach; ?>

<form method="POST">

<label>
Nombre:
<input type="text" name="nombre" required>
</label>

<br><br>

<label>
Apellido:
<input type="text" name="apellido" required>
</label>

<br><br>

<label>
Correo:
<input type="email" name="correo" required>
</label>

<br><br>

<label>
Usuario:
<input type="text" name="usuario" required>
</label>

<br><br>

<label>
Contraseña:
<input type="password" name="clave" minlength="6" required>
</label>

<br><br>

<label>
Especialidad:
<input type="text" name="especialidad" required>
</label>

<br><br>

<label>
Biografía:
<br>
<textarea
name="biografia"
rows="5"
cols="50"></textarea>
</label>

<br><br>

<button type="submit">
Guardar
</button>

<a href="tutores_listar.php">
Cancelar
</a>

</form>

</body>
</html>