<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Tutoría</title>
</head>
<body>

<h1>Editar Tutoría</h1>

<?php foreach($errores as $e): ?>

<p style="color:red;">
    <?= htmlspecialchars($e) ?>
</p>

<?php endforeach; ?>

<form method="POST">

<input
type="hidden"
name="id_tutoria"
value="<?= $tutoria['id_tutoria'] ?>"
>

<label>

Fecha:

<input
type="date"
name="fecha"
value="<?= $tutoria['fecha'] ?>"
required>

</label>

<br><br>

<label>

Hora Inicio:

<input
type="time"
name="hora_inicio"
value="<?= $tutoria['hora_inicio'] ?>"
required>

</label>

<br><br>

<label>

Hora Fin:

<input
type="time"
name="hora_fin"
value="<?= $tutoria['hora_fin'] ?>"
required>

</label>

<br><br>

<label>

Modalidad:

<select name="modalidad">

<option value="presencial"
<?= $tutoria['modalidad']=='presencial' ? 'selected' : '' ?>>
Presencial
</option>

<option value="virtual"
<?= $tutoria['modalidad']=='virtual' ? 'selected' : '' ?>>
Virtual
</option>

</select>

</label>

<br><br>

<label>

Lugar o Enlace:

<input
type="text"
name="lugar_o_enlace"
value="<?= htmlspecialchars($tutoria['lugar_o_enlace']) ?>"
>

</label>

<br><br>

<label>

Observaciones:

<br>

<textarea
name="observaciones"
rows="5"
cols="50"><?= htmlspecialchars($tutoria['observaciones']) ?></textarea>

</label>

<br><br>

<button type="submit">
Actualizar
</button>

<a href="tutorias_listar.php">
Cancelar
</a>

</form>

</body>
</html>