<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function requerir_login(?string $url_base = null): void {
    if ($url_base === null) {
        $url_base = obtener_base_url();
    }
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . $url_base . 'auth/ingresar.php');
        exit;
    }
}


function es_pro(): bool {
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


function es_admin(): bool {
    return !empty($_SESSION['usuario_es_admin']);
}


function usuario_id(): ?int {
    return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
}


function usuario_nombre(): string {
    return $_SESSION['usuario_nombre'] ?? '';
}


function iniciar_sesion_usuario(array $usuario): void {
    $_SESSION['usuario_id']          = (int)$usuario['id'];
    $_SESSION['usuario_nombre']      = $usuario['nombre'];
    $_SESSION['usuario_email']       = $usuario['email'];
    $_SESSION['usuario_plan']        = $usuario['plan'];
    $_SESSION['usuario_plan_vence']  = $usuario['plan_vence_en'] ?? null;
    $_SESSION['usuario_es_admin']    = (bool)$usuario['es_admin'];
    $_SESSION['usuario_foto']        = $usuario['foto'] ?? null;
    $_SESSION['usuario']             = $usuario['nombre'];
}


function cerrar_sesion(): void {
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


function flash(string $tipo, string $mensaje): void {
    $_SESSION['flash'][$tipo][] = $mensaje;
}


function obtener_flash(): array {
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}
