<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/funciones.php';

// verificar sesion
$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    header('Location: ../auth/ingresar.php');
    exit;
}

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '../panel/index.php';

switch ($accion) {
    case 'marcar_leida':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            marcar_notificacion_leida($id, $usuario_id);
        }
        $redirect = $_GET['redirect'] ?? '';
        if (!empty($redirect)) {
            header('Location: ' . $redirect);
            exit;
        }
        header('Location: ' . $referer);
        exit;

    case 'marcar_leidas':
        marcar_notificaciones_leidas($usuario_id);
        header('Location: ' . $referer);
        exit;

    case 'eliminar':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            eliminar_notificacion($id, $usuario_id);
        }
        header('Location: ' . $referer);
        exit;

    case 'limpiar_todas':
        limpiar_todas_notificaciones($usuario_id);
        header('Location: ' . $referer);
        exit;

    default:
        header('Location: ' . $referer);
        exit;
}
