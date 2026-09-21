<?php


require_once __DIR__ . '/database.php';

function enviar_notificacion($usuario_id, $mensaje, $enlace = null) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $msg = mysqli_real_escape_string($conexion, $mensaje);
    $link = ($enlace !== null && $enlace !== '') ? "'" . mysqli_real_escape_string($conexion, $enlace) . "'" : "NULL";
    
    $sql = "INSERT INTO notificacion (usuario_id, mensaje, enlace, leida, fecha)
            VALUES ($uid, '$msg', $link, 0, NOW())";
    return (bool)mysqli_query($conexion, $sql);
}


function marcar_notificaciones_leidas($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $sql = "UPDATE notificacion SET leida = 1 WHERE usuario_id = $uid AND leida = 0";
    return (bool)mysqli_query($conexion, $sql);
}


function marcar_notificacion_leida($notificacion_id, $usuario_id) {
    $conexion = obtener_conexion();
    $nid = intval($notificacion_id);
    $uid = intval($usuario_id);
    $sql = "UPDATE notificacion SET leida = 1 WHERE id = $nid AND usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function eliminar_notificacion($notificacion_id, $usuario_id) {
    $conexion = obtener_conexion();
    $nid = intval($notificacion_id);
    $uid = intval($usuario_id);
    $sql = "DELETE FROM notificacion WHERE id = $nid AND usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function limpiar_todas_notificaciones($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $sql = "DELETE FROM notificacion WHERE usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function notificar_usuarios_curso($curso_id, $mensaje, $enlace = null, $excluir = null) {
    $conexion = obtener_conexion();
    $cid = intval($curso_id);
    
    $resDoc = mysqli_query($conexion, "SELECT docente_id FROM curso WHERE id = $cid");
    $docRow = $resDoc ? mysqli_fetch_assoc($resDoc) : null;
    $docenteId = $docRow ? intval($docRow['docente_id']) : 0;

    $resAlum = mysqli_query($conexion, "SELECT usuario_id FROM inscripcion WHERE curso_id = $cid AND estado = 'activa'");
    $alumnos = [];
    if ($resAlum) {
        while ($fila = mysqli_fetch_assoc($resAlum)) {
            $alumnos[] = intval($fila['usuario_id']);
        }
    }

    $destinatarios = array_unique(array_merge([$docenteId], $alumnos));
    $excluir_ids = [];
    if (is_array($excluir)) {
        foreach ($excluir as $eVal) {
            $excluir_ids[] = intval($eVal);
        }
    } elseif ($excluir !== null) {
        $excluir_ids[] = intval($excluir);
    }

    foreach ($destinatarios as $uid) {
        $uid = intval($uid);
        if ($uid > 0 && !in_array($uid, $excluir_ids, true)) {
            enviar_notificacion($uid, $mensaje, $enlace);
        }
    }
}


function notificar_participantes_pregunta($pregunta_id, $mensaje, $enlace = null, $excluir = null) {
    $conexion = obtener_conexion();
    $pid = intval($pregunta_id);


    $resAutor = mysqli_query($conexion, "SELECT usuario_id FROM pregunta WHERE id = $pid");
    $autorRow = $resAutor ? mysqli_fetch_assoc($resAutor) : null;
    $autorPregunta = $autorRow ? intval($autorRow['usuario_id']) : 0;

   
    $resResp = mysqli_query($conexion, "SELECT DISTINCT usuario_id FROM respuesta WHERE pregunta_id = $pid");
    $autoresRespuestas = [];
    if ($resResp) {
        while ($fila = mysqli_fetch_assoc($resResp)) {
            $autoresRespuestas[] = intval($fila['usuario_id']);
        }
    }

    $participantes = array_unique(array_merge([$autorPregunta], $autoresRespuestas));
    $excluir_ids = [];
    if (is_array($excluir)) {
        foreach ($excluir as $eVal) {
            $excluir_ids[] = intval($eVal);
        }
    } elseif ($excluir !== null) {
        $excluir_ids[] = intval($excluir);
    }

    foreach ($participantes as $uid) {
        $uid = intval($uid);
        if ($uid > 0 && !in_array($uid, $excluir_ids, true)) {
            enviar_notificacion($uid, $mensaje, $enlace);
        }
    }
}


function contar_cursos_activos_docente($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM curso WHERE docente_id = $uid AND estado IN ('publicado', 'pendiente') AND fecha_fin >= CURDATE()");
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    return $fila ? intval($fila['total']) : 0;
}


function contar_inscripciones_activas_alumno($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM inscripcion i JOIN curso c ON i.curso_id = c.id WHERE i.usuario_id = $uid AND i.estado IN ('activa', 'pendiente') AND c.fecha_fin >= CURDATE()");
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    return $fila ? intval($fila['total']) : 0;
}


function usuario_es_pro($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT plan, fecha_vencimiento_pro FROM usuario WHERE id = $uid LIMIT 1");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if (!$row) return false;
    if ($row['plan'] !== 'pro') return false;
    if (!empty($row['fecha_vencimiento_pro']) && strtotime($row['fecha_vencimiento_pro']) < time()) {
        return false;
    }
    return true;
}


function obtener_url_imagen($imagen, $default = '') {
    global $base_url;
    if (empty($imagen)) {
        if (!empty($default)) {
            return $default;
        }
        return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80';
    }
    if (preg_match('#^(https?:)?//#i', $imagen) || strpos($imagen, 'data:') === 0) {
        return $imagen;
    }
    $prefijo = '/';
    if (!empty($base_url) && $base_url !== '/') {
        $prefijo = $base_url;
    } elseif (function_exists('obtener_base_url')) {
        $prefijo = obtener_base_url();
    }
    return rtrim($prefijo, '/') . '/' . ltrim($imagen, '/');
}


function obtener_portada_curso($imagen = null, $categoria = '', $curso_id = 0) {
    $imgTrim = trim((string)$imagen);
    if (!empty($imgTrim)) {
        if (preg_match('#^(https?:)?//#i', $imgTrim) || strpos($imgTrim, 'data:') === 0) {
            return $imgTrim;
        }
        $rutaFisica = __DIR__ . '/../' . ltrim($imgTrim, '/');
        if (file_exists($rutaFisica)) {
            return obtener_url_imagen($imgTrim);
        }
    }

    return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=600&q=80';
}


function obtener_url_avatar($avatar) {
    global $base_url;
    if (empty($avatar)) {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $avatar) || strpos($avatar, 'data:') === 0) {
        return $avatar;
    }
    $prefijo = '/';
    if (!empty($base_url) && $base_url !== '/') {
        $prefijo = $base_url;
    } elseif (function_exists('obtener_base_url')) {
        $prefijo = obtener_base_url();
    }
    $ruta_limpia = ltrim($avatar, '/');
    return rtrim($prefijo, '/') . '/' . $ruta_limpia;
}
