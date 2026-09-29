-- =========================================================
-- MIGRACIÓN 017: FICHAS DE TUTOR
-- ---------------------------------------------------------
-- El alta de usuarios creaba la ficha de 'estudiantes' pero no la
-- de 'tutores': un usuario registrado con rol 'tutor' quedaba en
-- 'usuarios' sin fila en 'tutores' y, por tanto, no aparecía en el
-- listado de la planta docente (que es tutores INNER JOIN usuarios).
--
-- Esta migración crea las fichas faltantes a partir de los usuarios
-- que tienen el rol 'tutor'. Es idempotente: no hace nada si ya
-- están todas, y se puede volver a ejecutar sin efectos.
--
-- NO se borran fichas de tutores que perdieron el rol: seis tablas
-- (tutorias, ofertas_admin, tutor_materia, disponibilidad_tutor,
-- oferta_respuesta, asignaciones_tutor) tienen clave foránea a
-- 'tutor' y su historial debe conservarse. El listado filtra por
-- roles.nombre_rol, de modo que un tutor degradado ya no se muestra.
-- =========================================================

INSERT INTO tutores (id_usuario)
SELECT u.id_usuario
FROM usuarios u
INNER JOIN roles r ON r.id_rol = u.id_rol
LEFT JOIN tutores t ON t.id_usuario = u.id_usuario
WHERE r.nombre_rol = 'tutor'
  AND t.id_tutor IS NULL;
