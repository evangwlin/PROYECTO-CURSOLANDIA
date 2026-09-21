<?php

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requerir_login($url_base = null) {
    if ($url_base === null) {
        $url_base = obtener_base_url();
    }
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . $url_base . 'auth/ingresar.php');
        exit;
    }
}

function es_pro() {
    if (!isset($_SESSION['usuario_plan'])) {
        return false;
    }
    if ($_SESSION['usuario_plan'] !== 'pro') {
        return false;
    }
    
    if (!empty($_SESSION['usuario_plan_vence']) && strtotime($_SESSION['usuario_plan_vence']) < time()) {
        return false;
    }
    return true;
}

function es_admin() {
    return !empty($_SESSION['usuario_es_admin']);
}

function usuario_id() {
    return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
}

function usuario_nombre() {
    return isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '';
}

function iniciar_sesion_usuario($usuario) {
    $_SESSION['usuario_id']          = (int)$usuario['id'];
    $_SESSION['usuario_nombre']      = $usuario['nombre'];
    $_SESSION['usuario_email']       = $usuario['email'];
    $_SESSION['usuario_plan']        = $usuario['plan'];
    $_SESSION['usuario_plan_vence']  = isset($usuario['plan_vence_en']) ? $usuario['plan_vence_en'] : null;
    $_SESSION['usuario_es_admin']    = (bool)$usuario['es_admin'];
    $_SESSION['usuario_foto']        = isset($usuario['foto']) ? $usuario['foto'] : null;
    $_SESSION['usuario']             = $usuario['nombre'];
}

function cerrar_sesion() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function flash($tipo, $mensaje) {
    $_SESSION['flash'][$tipo][] = $mensaje;
}

function obtener_flash() {
    $mensajes = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $mensajes;
}
