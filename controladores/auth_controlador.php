<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/';
$accion = '';
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
} elseif (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
}
$conexion = obtener_conexion();


if ($accion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $recordarme = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Por favor, completá tu correo y contraseña.';
        header('Location: ' . $base_url . 'auth/ingresar.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'El correo electrónico no tiene un formato válido.';
        header('Location: ' . $base_url . 'auth/ingresar.php');
        exit;
    }

    $emailEsc = mysqli_real_escape_string($conexion, $email);
    $res = mysqli_query($conexion, "SELECT * FROM usuario WHERE email = '$emailEsc' LIMIT 1");
    $usuario = $res ? mysqli_fetch_assoc($res) : null;

    if ($usuario && password_verify($password, $usuario['password'])) {
        $rolUser = isset($usuario['rol']) ? $usuario['rol'] : 'usuario';
        $planUser = isset($usuario['plan']) ? $usuario['plan'] : 'gratuito';
        $avatarUser = isset($usuario['avatar']) ? $usuario['avatar'] : null;

        $_SESSION['usuario_id']     = (int)$usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_rol']    = $rolUser;
        $_SESSION['usuario_plan']   = $planUser;
        $_SESSION['usuario_avatar'] = $avatarUser;

        $_SESSION['usuario'] = [
            'id'     => (int)$usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol'    => $rolUser,
            'plan'   => $planUser,
            'avatar' => $avatarUser
        ];


        if ($recordarme) {
            setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/');
        }

        header('Location: ' . $base_url . 'panel/index.php');
        exit;
    } else {
        $_SESSION['error'] = 'Correo electrónico o contraseña incorrectos.';
        header('Location: ' . $base_url . 'auth/ingresar.php');
        exit;
    }
}


if ($accion === 'registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre          = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email           = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password        = isset($_POST['password']) ? $_POST['password'] : '';
    $password_repeat = isset($_POST['password_repeat']) ? $_POST['password_repeat'] : $password;
    $terminos        = isset($_POST['terminos']);


    if (empty($nombre) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Todos los campos son obligatorios.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Ingresá un correo electrónico válido.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }

    if ($password !== $password_repeat) {
        $_SESSION['error'] = 'Las contraseñas no coinciden.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }


    $emailEsc = mysqli_real_escape_string($conexion, $email);
    $resCheck = mysqli_query($conexion, "SELECT id FROM usuario WHERE email = '$emailEsc' LIMIT 1");
    if ($resCheck && mysqli_fetch_assoc($resCheck)) {
        $_SESSION['error'] = 'El correo electrónico ya está registrado.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }


    $nombreEsc = mysqli_real_escape_string($conexion, $nombre);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $passwordEsc = mysqli_real_escape_string($conexion, $passwordHash);

    $sqlInsert = "
        INSERT INTO usuario (nombre, email, password, rol, plan, fecha_registro) 
        VALUES ('$nombreEsc', '$emailEsc', '$passwordEsc', 'usuario', 'gratuito', NOW())
    ";
    if (mysqli_query($conexion, $sqlInsert)) {
        $nuevoId = intval(mysqli_insert_id($conexion));


        $_SESSION['usuario_id']     = $nuevoId;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_email']  = $email;
        $_SESSION['usuario_rol']    = 'usuario';
        $_SESSION['usuario_plan']   = 'gratuito';
        $_SESSION['usuario'] = [
            'id'     => $nuevoId,
            'nombre' => $nombre,
            'email'  => $email,
            'rol'    => 'usuario',
            'plan'   => 'gratuito'
        ];

        header('Location: ' . $base_url . 'panel/index.php');
        exit;
    } else {
        $_SESSION['error'] = 'No se pudo crear la cuenta. Por favor, intentá nuevamente.';
        header('Location: ' . $base_url . 'auth/registrarse.php');
        exit;
    }
}


if ($accion === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . $base_url . 'auth/ingresar.php');
    exit;
}


header('Location: ' . $base_url . 'index.php');
exit;
