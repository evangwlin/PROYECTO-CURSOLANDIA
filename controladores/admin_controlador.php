<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = 'Acceso denegado. Se requieren permisos de administrador.';
    header('Location: ../panel/index.php');
    exit;
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$conexion = obtener_conexion();

if ($accion === 'aprobar_pro') {
    $solicitud_id = 0;
    if (isset($_POST['solicitud_id'])) {
        $solicitud_id = intval($_POST['solicitud_id']);
    } elseif (isset($_GET['solicitud_id'])) {
        $solicitud_id = intval($_GET['solicitud_id']);
    }
    $meses = isset($_POST['meses']) ? intval($_POST['meses']) : 12;
    $fecha_vence = isset($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : date('Y-m-d', strtotime('+' . $meses . ' months'));

    if ($solicitud_id <= 0) {
        header('Location: ../admin/solicitudes.php');
        exit;
    }

    $resSol = mysqli_query($conexion, "SELECT usuario_id FROM solicitudpro WHERE id = $solicitud_id");
    $rowSol = $resSol ? mysqli_fetch_assoc($resSol) : null;
    $uid = $rowSol ? intval($rowSol['usuario_id']) : 0;

    if ($uid > 0) {
        $fechaEsc = mysqli_real_escape_string($conexion, $fecha_vence);

        mysqli_query($conexion, "
            UPDATE usuario 
            SET plan = 'pro', fecha_vencimiento_pro = '$fechaEsc' 
            WHERE id = $uid
        ");

        mysqli_query($conexion, "UPDATE solicitudpro SET estado = 'aprobada' WHERE id = $solicitud_id");

        enviar_notificacion(
            $uid,
            '¡El administrador aprobó tu pago! Tu Plan PRO ha sido activado hasta el ' . date('d/m/Y', strtotime($fecha_vence)) . '.',
            'perfil/configuracion.php#seccion-plan'
        );

        $_SESSION['exito'] = 'Plan PRO activado exitosamente para el usuario.';
    }

    header('Location: ../admin/solicitudes.php');
    exit;
}

if ($accion === 'rechazar_pro') {
    $solicitud_id = isset($_GET['solicitud_id']) ? intval($_GET['solicitud_id']) : 0;
    $motivo = isset($_GET['motivo']) ? trim($_GET['motivo']) : 'Comprobante no válido o no acreditado.';

    $resSol = mysqli_query($conexion, "SELECT usuario_id FROM solicitudpro WHERE id = $solicitud_id");
    $rowSol = $resSol ? mysqli_fetch_assoc($resSol) : null;
    $uid = $rowSol ? intval($rowSol['usuario_id']) : 0;

    if ($uid > 0) {
        mysqli_query($conexion, "UPDATE solicitudpro SET estado = 'rechazada' WHERE id = $solicitud_id");

        enviar_notificacion(
            $uid,
            'Tu solicitud de Plan PRO no pudo ser aprobada: ' . $motivo,
            'perfil/configuracion.php#seccion-plan'
        );

        $_SESSION['exito'] = 'Solicitud rechazada.';
    }

    header('Location: ../admin/solicitudes.php?tab=pro');
    exit;
}

function resolver_redireccion_admin($redirect_param) {
    $redirect = isset($redirect_param) ? trim($redirect_param) : '';
    if (empty($redirect)) {
        return '../admin/solicitudes.php?tab=cursos';
    }
    if (preg_match('#^https?://#i', $redirect)) {
        return $redirect;
    }
    if (strpos($redirect, '../') === 0) {
        return $redirect;
    }
    if (strpos($redirect, '/') === 0) {
        return $redirect;
    }
    return '../' . ltrim($redirect, '/');
}

if ($accion === 'aprobar_curso') {
    $curso_id = 0;
    if (isset($_POST['curso_id'])) {
        $curso_id = intval($_POST['curso_id']);
    } elseif (isset($_GET['curso_id'])) {
        $curso_id = intval($_GET['curso_id']);
    }
    $redParam = '';
    if (isset($_POST['redirect'])) {
        $redParam = $_POST['redirect'];
    } elseif (isset($_GET['redirect'])) {
        $redParam = $_GET['redirect'];
    }
    $destino = resolver_redireccion_admin($redParam);

    if ($curso_id <= 0) {
        header('Location: ' . $destino);
        exit;
    }

    $res = mysqli_query($conexion, "SELECT id, docente_id, titulo FROM curso WHERE id = $curso_id LIMIT 1");
    $curso = $res ? mysqli_fetch_assoc($res) : null;

    if ($curso) {
        mysqli_query($conexion, "UPDATE curso SET estado = 'publicado' WHERE id = $curso_id");

        enviar_notificacion(
            intval($curso['docente_id']),
            '¡Excelente noticia! El administrador aprobó tu curso "' . $curso['titulo'] . '" y ya se encuentra publicado en el catálogo.',
            'cursos/ver.php?id=' . $curso_id
        );

        $_SESSION['exito'] = 'El curso "' . htmlspecialchars($curso['titulo']) . '" fue aprobado y ya está publicado.';
    } else {
        $_SESSION['error'] = 'No se encontró el curso a aprobar.';
    }

    header('Location: ' . $destino);
    exit;
}

if ($accion === 'rechazar_curso') {
    $curso_id = 0;
    if (isset($_POST['curso_id'])) {
        $curso_id = intval($_POST['curso_id']);
    } elseif (isset($_GET['curso_id'])) {
        $curso_id = intval($_GET['curso_id']);
    }
    $motivo = 'La propuesta no cumple con los estándares o requiere ajustes.';
    if (isset($_POST['motivo'])) {
        $motivo = trim($_POST['motivo']);
    } elseif (isset($_GET['motivo'])) {
        $motivo = trim($_GET['motivo']);
    }
    $redParam = '';
    if (isset($_POST['redirect'])) {
        $redParam = $_POST['redirect'];
    } elseif (isset($_GET['redirect'])) {
        $redParam = $_GET['redirect'];
    }
    $destino = resolver_redireccion_admin($redParam);

    if ($curso_id <= 0) {
        header('Location: ' . $destino);
        exit;
    }

    $res = mysqli_query($conexion, "SELECT id, docente_id, titulo FROM curso WHERE id = $curso_id LIMIT 1");
    $curso = $res ? mysqli_fetch_assoc($res) : null;

    if ($curso) {
        mysqli_query($conexion, "UPDATE curso SET estado = 'rechazado' WHERE id = $curso_id");

        enviar_notificacion(
            intval($curso['docente_id']),
            'Tu curso "' . $curso['titulo'] . '" fue rechazado: ' . $motivo . '. Podés editarlo para volver a enviarlo a revisión.',
            'cursos/editar.php?id=' . $curso_id
        );

        $_SESSION['exito'] = 'El curso "' . htmlspecialchars($curso['titulo']) . '" ha sido rechazado.';
    } else {
        $_SESSION['error'] = 'No se encontró el curso.';
    }

    header('Location: ' . $destino);
    exit;
}

header('Location: ../admin/solicitudes.php');
exit;
