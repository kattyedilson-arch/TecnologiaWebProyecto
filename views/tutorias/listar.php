<?php require_once __DIR__ . '/../../includes/verificar_sesion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tutorías</title>
</head>
<body>

<h1>Tutorías Registradas</h1>

<p>
<a href="tutorias_crear.php">
+ Nueva Tutoría
</a>
</p>

<table border="1" cellpadding="6">

<tr>
<th>ID</th>
<th>Estudiante</th>
<th>Tutor</th>
<th>Materia</th>
<th>Fecha</th>
<th>Inicio</th>
<th>Fin</th>
<th>Modalidad</th>
<th>Estado</th>
<th>Acciones</th>
</tr>

<?php foreach($tutorias as $t): ?>

<tr>

<td><?= $t['id_tutoria'] ?></td>

<td>
<?= htmlspecialchars(
$t['estudiante_nombre'].' '.$t['estudiante_apellido']
) ?>
</td>

<td>
<?= htmlspecialchars(
$t['tutor_nombre'].' '.$t['tutor_apellido']
) ?>
</td>

<td><?= htmlspecialchars($t['nombre_materia']) ?></td>

<td><?= htmlspecialchars($t['fecha']) ?></td>

<td><?= htmlspecialchars($t['hora_inicio']) ?></td>

<td><?= htmlspecialchars($t['hora_fin']) ?></td>

<td><?= htmlspecialchars($t['modalidad']) ?></td>

<td><?= htmlspecialchars($t['estado']) ?></td>

<td>
    <a href="tutorias_estado.php?id=<?= $t['id_tutoria'] ?>&estado=realizada">
        Realizada
    </a>

    |

    <a href="tutorias_estado.php?id=<?= $t['id_tutoria'] ?>&estado=cancelada" onclick="return confirm('¿Cancelar tutoría?');">
        Cancelar
    </a>

    |

    <a href="tutorias_editar.php?id=<?= $t['id_tutoria'] ?>">
        Editar
    </a>
</td>

</tr>

<?php endforeach; ?>

<?php if(empty($tutorias)): ?>

<tr>
<td colspan="10">
No existen tutorías registradas.
</td>
</tr>

<?php endif; ?>

</table>

</body>
</html>