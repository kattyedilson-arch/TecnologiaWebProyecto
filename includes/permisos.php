<?php
// =========================================================
// CONTROL DE PERMISOS POR ROL (permisos.php)
// ---------------------------------------------------------
// Matriz de permisos del módulo "Modalidades de Grado" y del
// sistema (HU-042). Centraliza QUÉ puede hacer cada rol:
//   administrador    -> todo (soporte y gestión total)
//   coordinador_mg   -> gestiona y aprueba modalidades
//   auxiliar_mg      -> operación diaria (avales, evaluación, actas)
//   tutor            -> agenda y dictamina según su materia
//   estudiante       -> declara su modalidad y hace seguimiento
//
// Uso:
//   require_once __DIR__ . '/permisos.php';
//   requerirPermiso('aprobar_modalidad_mg');
//   if (tienePermiso('ver_reportes_mg')) { ... }
// =========================================================

$GLOBALS['PERMISOS_MG'] = [

    // ---------- Núcleo compartido ----------
    'acceder_sistema'           => ['administrador', 'coordinador_mg', 'auxiliar_mg', 'tutor', 'estudiante'],
    'ver_dashboard_admin'       => ['administrador'],

    // ---------- Gestión de usuarios y catálogos (soporte) ----------
    'gestionar_usuarios'        => ['administrador'],
    'gestionar_tutores'         => ['administrador'],
    'gestionar_estudiantes'     => ['administrador'],
    'gestionar_materias'        => ['administrador'],
    'gestionar_carreras'        => ['administrador'],
    'gestionar_turnos'          => ['administrador'],
    'gestionar_ofertas'         => ['administrador'],
    'gestionar_tutorias'        => ['administrador'],

    // ---------- Módulo Modalidades de Grado ----------
    // Panel MG y configuración
    'ver_panel_mg'              => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'gestionar_roles_mg'        => ['administrador'],
    'gestionar_periodos'        => ['administrador', 'coordinador_mg'],
    'gestionar_parametros_mg'   => ['administrador', 'coordinador_mg'],
    'gestionar_cohortes_mg'     => ['administrador', 'coordinador_mg'],
    'gestionar_calendario_mg'   => ['administrador', 'coordinador_mg'],

    // Expedientes MG (Sprint 3, HU-023..HU-027)
    'importar_padron_mg'        => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'ver_expediente_mg'         => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'gestionar_expedientes_mg'  => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'gestionar_etapas_mg'       => ['administrador', 'coordinador_mg'],
    'asignar_tutor_mg'          => ['administrador', 'coordinador_mg'],
    'cambiar_tutor_mg'          => ['administrador', 'coordinador_mg'],
    'gestionar_documentos_mg'   => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'gestionar_plantillas_mg'   => ['administrador', 'coordinador_mg'],

    // Registro de modalidades (administración del catálogo)
    'registrar_modalidad_mg'    => ['administrador', 'coordinador_mg'],
    'editar_modalidad_mg'       => ['administrador', 'coordinador_mg'],
    'publicar_modalidad_mg'     => ['administrador', 'coordinador_mg'],

    // Declaración (estudiante) y revisión (equipo MG)
    'declarar_modalidad'        => ['estudiante'],
    'revisar_declaraciones_mg'  => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'asignar_avales_mg'         => ['administrador', 'coordinador_mg'],
    'gestionar_avales_mg'       => ['administrador', 'coordinador_mg', 'auxiliar_mg'],

    // Bandeja y aprobación
    'tramitar_modalidad_mg'     => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'aprobar_modalidad_mg'      => ['administrador', 'coordinador_mg'],

    // Evaluación de avances y productos
    'evaluar_productos_mg'      => ['administrador', 'coordinador_mg', 'auxiliar_mg'],

    // Actas de defensa
    'gestionar_actas_mg'        => ['administrador', 'coordinador_mg', 'auxiliar_mg'],
    'registrar_acta_mg'         => ['administrador', 'coordinador_mg', 'auxiliar_mg'],

    // Notificaciones
    'ver_notificaciones'        => ['administrador', 'coordinador_mg', 'auxiliar_mg', 'tutor', 'estudiante'],
    'gestionar_notificaciones'  => ['administrador'],

    // Reportes y configuración del sistema
    'ver_reportes_mg'           => ['administrador', 'coordinador_mg'],
    'exportar_reportes_mg'      => ['administrador', 'coordinador_mg'],
    'gestionar_sistema'         => ['administrador'],
];

// ¿El rol de la sesión tiene el permiso indicado?
function tienePermiso($permiso)
{
    $rol = $_SESSION['rol'] ?? '';
    $permitidos = $GLOBALS['PERMISOS_MG'][$permiso] ?? [];
    return in_array($rol, $permitidos, true);
}

// Exige el permiso: si no lo tiene, responde con la página 403
// y detiene la ejecución.
function requerirPermiso($permiso)
{
    if (empty($_SESSION['id_usuario'])) {
        header('Location: /views/login/login.php');
        exit;
    }
    if (tienePermiso($permiso)) {
        return;
    }
    http_response_code(403);
    require_once __DIR__ . '/../views/error/403.php';
    exit;
}