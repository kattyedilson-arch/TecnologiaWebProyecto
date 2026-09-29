# =========================================================
# MIGRACIONES DE BASE DE DATOS (README)
# ---------------------------------------------------------
# El esquema evoluciona con archivos versionados en esta
# carpeta. El runner las aplica en orden y anota cada versión
# en la tabla `schema_migrations` (idempotente):
#
#     php scripts/migrar.php        # dentro del contenedor web
#
# Los archivos DEBEN seguir el patrón: NNN_descripcion.sql
# (prefijo numérico de 3 dígitos) y NO deben incluir
# CREATE DATABASE / USE (el runner conecta con la BD de .env).
# =========================================================

# Numeración vigente
# ------------------
# Fase adeudada del Sprint 1 (baseline). Se creó con el prefijo
# 001 aunque el proyecto ya venía funcionando, para que el
# runner de migraciones quede operativo sobre la BD existente.
#
# El plan del módulo asumía los Sprint 1-2 previos; por eso el
# rango 002-008 quedó reservado para demoras de ese periodo. NO
# aplicadas aún:
#   002  -> notificaciones (Pendiente Sprint 2)
#   003  -> periodos académicos (Pendiente Sprint 2)
#   004-008 -> reservado (pendientes del Sprint 2 original)
#
# El módulo "Modalidades de Grado" (MG) continúa desde 009,
# siguiendo la numeración de la guía oficial de implementación:
#   009  -> roles MG (coordinador_mg/auxiliar_mg)
#   010  -> modalidades de grado
#   011  -> declaración y avales
#   012  -> asignaciones/bandeja
#   013  -> evaluación de productos / ajustes
#   014  -> actas de defensa final
#   015  -> cohortes y calendario
#   016  -> sprint 3 del módulo MG
#   017  -> fichas de tutor
#   018  -> atributos institucionales de carrera
#   019  -> sesiones grupales (cupo por oferta)
#   021  -> solicitudes_interes: tabla de espera por materia
#   022  -> solicitudes_interes: FK a ofertas_admin por oferta
#
# Regla: si introduces una migración nueva, usa el siguiente
# número libre en SECUENCIA (no reordenes las aplicadas).

# 021_solicitudes_interes.sql — tabla de espera por materia
# ---------------------------------------------------------
# El archivo faltaba en el repositorio aunque la tabla ya
# existía en la BD (había quedado registrada en
# `schema_migrations` sin fuente versionada). Se recreó con
# `CREATE TABLE IF NOT EXISTS` y el DDL vigente, de modo que
# una instalación limpia la construya y la existente no se
# toque. Se aplica antes que 022, que la altera.
#
# Guarda el "interés" del estudiante por una materia cuando
# el horario no tiene docente asignado: NO crea una fila en
# `tutorias` (su `id_tutor` es NOT NULL, no admite NULL).
#
# Estados: pendiente -> atendida (el tutor aceptó la oferta)
#          cancelada (el estudiante o el cierre la retiraron).

# 022_solicitudes_interes_oferta.sql — la Demanda es por OFERTA
# ------------------------------------------------------
# `id_oferta` nullable añade el vínculo real con `ofertas_admin`
# y la FK `fk_interes_oferta` con ON DELETE CASCADE: así una
# oferta que se elimina se lleva sus solicitudes y el panel
# del estudiante no muestra referencias huérfanas.
#
# Cambia la restricción de unicidad:
#   antes: (id_estudiante, id_materia)  -> un pedido por MATERIA
#   ahora: (id_estudiante, id_oferta)   -> un pedido por HORARIO
#
# Rellena `id_oferta` de las filas existentes emparejando
# `id_materia + id_turno + id_modalidad + nivel_academico`; las
# que no encuentran coincidencia quedan en NULL (se conservan
# para no perder historial) y por eso el índice único ignora NULL.
#
# `idx_interes_oferta (id_oferta, estado)` acelera el conteo de
# interesados que ve el administrador y el listado de
# pendientes de un tutor al aceptar.

# Sin migración: filtro de Sistema de Estudio
# ------------------------------------------
# El filtro de "Materias Disponibles" es de presentación, así que
# no requiere migración. `ofertas_admin.modalidad` ya es un
# `enum('presencial','virtual')` y la vista lo expone en
# `data-modalidad` de cada fila; el controlador agrupa solo
# materias con ofertas reales. Si alguna vez el enum cambia,
# hay que revisar `$sistemasEstudio` en
# `controllers/tutorias_solicitar.php`.