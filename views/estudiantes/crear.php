<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Estudiante</title>
</head>
<body>

<h1>Registrar Estudiante</h1>

<?php foreach ($errores as $error): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
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
        <input type="password" name="clave" required minlength="6">
    </label>

    <br><br>

    <label>
        Carrera:

        <select name="id_carrera" required>

            <?php foreach ($carreras as $c): ?>

                <option value="<?= $c['id_carrera'] ?>">
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
            required
        >
    </label>

    <br><br>

    <button type="submit">
        Guardar
    </button>

    <a href="estudiantes_listar.php">
        Cancelar
    </a>

</form>

</body>
</html>