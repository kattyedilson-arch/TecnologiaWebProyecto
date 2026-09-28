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
#
# Regla: si introduces una migración nueva, usa el siguiente
# número libre en SECUENCIA (no reordenes las aplicadas).