<?php


require_once __DIR__ . '/database.php';

if (!function_exists('obtener_base_url')) {
    function obtener_base_url(): string {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (stripos($host, 'cursolandia.xo.je') !== false || stripos($host, 'infinityfree') !== false || stripos($host, 'epizy.com') !== false) {
            return '/';
        }

        $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        
        // Si está en una subcarpeta (ej: /PROYECTO-CURSOLANDIA/...)
        if (preg_match('#^(/[^/]+)/(?:admin|archivos|assets|auth|config|controladores|cursos|includes|inscripciones|paginas|panel|perfil)/#i', $script_name, $m)) {
            return $m[1] . '/';
        }
        
        if (preg_match('#^(/[^/]+)/index\.php#i', $script_name, $m)) {
            $internas = ['admin', 'archivos', 'assets', 'auth', 'config', 'controladores', 'cursos', 'includes', 'inscripciones', 'paginas', 'panel', 'perfil'];
            $folder = strtolower(trim($m[1], '/'));
            if (!in_array($folder, $internas)) {
                return $m[1] . '/';
            }
        }

        if (stripos($script_name, '/PROYECTO-CURSOLANDIA') !== false) {
            return '/PROYECTO-CURSOLANDIA/';
        }

        return '/';
    }
}


function enviar_notificacion($usuario_id, $mensaje, $enlace = null) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $msg = mysqli_real_escape_string($conexion, $mensaje);
    $link = ($enlace !== null && $enlace !== '') ? "'" . mysqli_real_escape_string($conexion, $enlace) . "'" : "NULL";
    
    $sql = "INSERT INTO Notificacion (usuario_id, mensaje, enlace, leida, fecha)
            VALUES ($uid, '$msg', $link, 0, NOW())";
    return (bool)mysqli_query($conexion, $sql);
}


function marcar_notificaciones_leidas($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $sql = "UPDATE Notificacion SET leida = 1 WHERE usuario_id = $uid AND leida = 0";
    return (bool)mysqli_query($conexion, $sql);
}


function marcar_notificacion_leida($notificacion_id, $usuario_id) {
    $conexion = obtener_conexion();
    $nid = intval($notificacion_id);
    $uid = intval($usuario_id);
    $sql = "UPDATE Notificacion SET leida = 1 WHERE id = $nid AND usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function eliminar_notificacion($notificacion_id, $usuario_id) {
    $conexion = obtener_conexion();
    $nid = intval($notificacion_id);
    $uid = intval($usuario_id);
    $sql = "DELETE FROM Notificacion WHERE id = $nid AND usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function limpiar_todas_notificaciones($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $sql = "DELETE FROM Notificacion WHERE usuario_id = $uid";
    return (bool)mysqli_query($conexion, $sql);
}


function notificar_usuarios_curso($curso_id, $mensaje, $enlace = null, $excluir = null) {
    $conexion = obtener_conexion();
    $cid = intval($curso_id);
    
    // docente
    $resDoc = mysqli_query($conexion, "SELECT docente_id FROM Curso WHERE id = $cid");
    $docRow = $resDoc ? mysqli_fetch_assoc($resDoc) : null;
    $docenteId = $docRow ? intval($docRow['docente_id']) : 0;

    // alumnos
    $resAlum = mysqli_query($conexion, "SELECT usuario_id FROM Inscripcion WHERE curso_id = $cid AND estado = 'activa'");
    $alumnos = [];
    if ($resAlum) {
        while ($fila = mysqli_fetch_assoc($resAlum)) {
            $alumnos[] = intval($fila['usuario_id']);
        }
    }

    $destinatarios = array_unique(array_merge([$docenteId], $alumnos));
    $excluir_ids = is_array($excluir) ? array_map('intval', $excluir) : ($excluir !== null ? [intval($excluir)] : []);

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


    $resAutor = mysqli_query($conexion, "SELECT usuario_id FROM Pregunta WHERE id = $pid");
    $autorRow = $resAutor ? mysqli_fetch_assoc($resAutor) : null;
    $autorPregunta = $autorRow ? intval($autorRow['usuario_id']) : 0;

   
    $resResp = mysqli_query($conexion, "SELECT DISTINCT usuario_id FROM Respuesta WHERE pregunta_id = $pid");
    $autoresRespuestas = [];
    if ($resResp) {
        while ($fila = mysqli_fetch_assoc($resResp)) {
            $autoresRespuestas[] = intval($fila['usuario_id']);
        }
    }

    $participantes = array_unique(array_merge([$autorPregunta], $autoresRespuestas));
    $excluir_ids = is_array($excluir) ? array_map('intval', $excluir) : ($excluir !== null ? [intval($excluir)] : []);

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
    $res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Curso WHERE docente_id = $uid AND estado IN ('publicado', 'pendiente') AND fecha_fin >= CURDATE()");
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    return $fila ? intval($fila['total']) : 0;
}


function contar_inscripciones_activas_alumno($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Inscripcion i JOIN Curso c ON i.curso_id = c.id WHERE i.usuario_id = $uid AND i.estado IN ('activa', 'pendiente') AND c.fecha_fin >= CURDATE()");
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    return $fila ? intval($fila['total']) : 0;
}


function usuario_es_pro($usuario_id) {
    $conexion = obtener_conexion();
    $uid = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT plan, fecha_vencimiento_pro FROM Usuario WHERE id = $uid LIMIT 1");
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
        return !empty($default) ? $default : 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80';
    }
    if (preg_match('#^(https?:)?//#i', $imagen) || strpos($imagen, 'data:') === 0) {
        return $imagen;
    }
    $prefijo = (!empty($base_url) && $base_url !== '/') ? $base_url : (function_exists('obtener_base_url') ? obtener_base_url() : '/');
    return rtrim($prefijo, '/') . '/' . ltrim($imagen, '/');
}


function obtener_portada_curso($imagen = null, $categoria = '', $curso_id = 0) {
    $imgTrim = trim((string)$imagen);
    if (!empty($imgTrim)) {
        return obtener_url_imagen($imgTrim);
    }

    $portadas_por_id = [
        1  => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80',
        2  => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80',
        3  => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80',
        5  => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=600&q=80',
        6  => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
        9  => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
        11 => 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=600&q=80',
        15 => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80',
    ];

    if ($curso_id > 0 && isset($portadas_por_id[$curso_id])) {
        return $portadas_por_id[$curso_id];
    }

    $catSlug = strtolower(trim($categoria));
    $catSlug = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $catSlug);

    $portadas_por_categoria = [
        'programacion' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80',
        'react'        => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=600&q=80',
        'frontend'     => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=600&q=80',
        'backend'      => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80',
        'ciencia'      => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80',
        'datos'        => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80',
        'diseno'       => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
        'arte'         => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
        'ux'           => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
        'marketing'    => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
        'finanzas'     => 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=600&q=80',
        'ia'           => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80',
        'inteligencia' => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80',
        'idiomas'      => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80',
        'comunicacion' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80',
    ];

    foreach ($portadas_por_categoria as $clave => $url) {
        if (strpos($catSlug, $clave) !== false) {
            return $url;
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
    $prefijo = (!empty($base_url) && $base_url !== '/') ? $base_url : (function_exists('obtener_base_url') ? obtener_base_url() : '/');
    $ruta_limpia = ltrim($avatar, '/');
    $version = '';
    $ruta_fisica = __DIR__ . '/../' . $ruta_limpia;
    if (file_exists($ruta_fisica)) {
        $version = '?v=' . filemtime($ruta_fisica);
    }
    return rtrim($prefijo, '/') . '/' . $ruta_limpia . $version;
}
