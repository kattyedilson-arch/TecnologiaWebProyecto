<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Tutoría</title>
</head>
<body>

<h1>Registrar Tutoría</h1>

<?php foreach ($errores as $e): ?>
    <p style="color:red;">
        <?= htmlspecialchars($e) ?>
    </p>
<?php endforeach; ?>

<form method="POST">

    <label>
        Estudiante:
        <select name="id_estudiante" required>

            <option value="">Seleccione</option>

            <?php foreach ($estudiantes as $e): ?>

                <option value="<?= $e['id_estudiante'] ?>">

                    <?= htmlspecialchars(
                        $e['nombre'] . ' ' . $e['apellido']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>
    </label>

    <br><br>

    <label>
        Tutor:
        <select name="id_tutor" required>

            <option value="">Seleccione</option>

            <?php foreach ($tutores as $t): ?>

                <option value="<?= $t['id_tutor'] ?>">

                    <?= htmlspecialchars(
                        $t['nombre'] . ' ' . $t['apellido']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>
    </label>

    <br><br>

    <label>
        Materia:
        <select name="id_materia" required>

            <option value="">Seleccione</option>

            <?php foreach ($materias as $m): ?>

                <option value="<?= $m['id_materia'] ?>">

                    <?= htmlspecialchars(
                        $m['nombre_materia']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>
    </label>

    <br><br>

    <label>
        Fecha:
        <input type="date" name="fecha" required>
    </label>

    <br><br>

    <label>
        Hora inicio:
        <input type="time" name="hora_inicio" required>
    </label>

    <br><br>

    <label>
        Hora fin:
        <input type="time" name="hora_fin" required>
    </label>

    <br><br>

    <label>
        Modalidad:
        <select name="modalidad" required>
            <option value="presencial">Presencial</option>
            <option value="virtual">Virtual</option>
        </select>
    </label>

    <br><br>

    <label>
        Lugar o enlace:
        <input
            type="text"
            name="lugar_o_enlace"
            maxlength="255"
        >
    </label>

    <br><br>

    <label>
        Observaciones:
        <br>
        <textarea
            name="observaciones"
            rows="4"
            cols="50"
        ></textarea>
    </label>

    <br><br>

    <button type="submit">
        Guardar
    </button>

    <a href="tutorias_listar.php">
        Cancelar
    </a>

</form>

</body>
</html>