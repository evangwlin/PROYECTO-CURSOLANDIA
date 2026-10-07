<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario']['id'])) {
    $_SESSION['error'] = 'Debés iniciar sesión para modificar tu perfil.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id']);
$accion = $_GET['accion'] ?? '';
$conexion = obtener_conexion();

// =============================================================
// ACTUALIZAR INFO
// =============================================================
if ($accion === 'actualizar_perfil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $biografia = trim($_POST['biografia'] ?? $_POST['antecedentes'] ?? '');
    $intereses = trim($_POST['intereses'] ?? '');

    $username = preg_replace('/[^a-zA-Z0-9_.]/', '', ltrim($username, '@'));
    if (empty($username)) {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $nombre . $apellido));
    }

    if (empty($nombre) || empty($email)) {
        $_SESSION['error'] = 'El nombre y correo electrónico son obligatorios.';
        header('Location: ../perfil/configuracion.php#seccion-datos');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Ingresá un correo electrónico válido.';
        header('Location: ../perfil/configuracion.php#seccion-datos');
        exit;
    }

    $email_esc = mysqli_real_escape_string($conexion, $email);

    // verifica disponibilidad del mail
    $resCheck = mysqli_query($conexion, "SELECT id FROM Usuario WHERE email = '$email_esc' AND id != $usuario_id LIMIT 1");
    if ($resCheck && mysqli_fetch_assoc($resCheck)) {
        $_SESSION['error'] = 'El correo electrónico ya está en uso por otra cuenta.';
        header('Location: ../perfil/configuracion.php#seccion-datos');
        exit;
    }

    if (empty($apellido) && str_contains($nombre, ' ')) {
        $partesNom = explode(' ', $nombre, 2);
        $nombre = trim($partesNom[0]);
        $apellido = trim($partesNom[1]);
    }

    $avatarSql = "";
    $avatarRuta = null;

    $archivoAvatar = $_FILES['foto_perfil'] ?? $_FILES['avatar'] ?? null;
    if ($archivoAvatar && !empty($archivoAvatar['name'])) {
        if ($archivoAvatar['error'] === UPLOAD_ERR_INI_SIZE || $archivoAvatar['error'] === UPLOAD_ERR_FORM_SIZE) {
            $_SESSION['error'] = 'La foto de perfil es demasiado pesada. El tamaño máximo permitido es de 2 MB.';
            header('Location: ../perfil/configuracion.php#perfil');
            exit;
        } elseif ($archivoAvatar['error'] !== UPLOAD_ERR_OK && $archivoAvatar['error'] !== UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = 'No se pudo subir la foto de perfil (código de error: ' . $archivoAvatar['error'] . ').';
            header('Location: ../perfil/configuracion.php#perfil');
            exit;
        } elseif ($archivoAvatar['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($archivoAvatar['name'], PATHINFO_EXTENSION));
            $formatosValidos = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'avif'];
            if (!in_array($ext, $formatosValidos)) {
                $_SESSION['error'] = 'Formato de imagen no admitido. Utilizá JPG, PNG, WebP o GIF.';
                header('Location: ../perfil/configuracion.php#perfil');
                exit;
            }

            $dirAvatares = __DIR__ . '/../archivos/avatares/';
            if (!is_dir($dirAvatares)) mkdir($dirAvatares, 0777, true);
            $nomAvatar = 'avatar_' . $usuario_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($archivoAvatar['tmp_name'], $dirAvatares . $nomAvatar)) {
                $avatarRuta = 'archivos/avatares/' . $nomAvatar;
                $avatarRutaEsc = mysqli_real_escape_string($conexion, $avatarRuta);
                $avatarSql = ", avatar = '$avatarRutaEsc'";
                $_SESSION['usuario_avatar'] = $avatarRuta;
                if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
                    $_SESSION['usuario']['avatar'] = $avatarRuta;
                }

                // Sincronizar copia en directorio hermano si existe
                $dirHermano = (stripos(__DIR__, 'PROYECTO-CURSOLANDIA') !== false)
                    ? str_ireplace('PROYECTO-CURSOLANDIA', 'cursolandia', $dirAvatares)
                    : str_ireplace('cursolandia', 'PROYECTO-CURSOLANDIA', $dirAvatares);
                if (is_dir(dirname($dirHermano))) {
                    if (!is_dir($dirHermano)) @mkdir($dirHermano, 0777, true);
                    @copy($dirAvatares . $nomAvatar, $dirHermano . $nomAvatar);
                }
            } else {
                $_SESSION['error'] = 'No se pudo guardar la imagen en el servidor. Verificá los permisos de la carpeta.';
                header('Location: ../perfil/configuracion.php#perfil');
                exit;
            }
        }
    }

    $eliminarAvatar = (isset($_POST['eliminar_avatar']) && $_POST['eliminar_avatar'] === '1');
    if ($eliminarAvatar && empty($avatarSql)) {
        // Eliminar avatar anterior en disco
        $resViejo = mysqli_query($conexion, "SELECT avatar FROM Usuario WHERE id = $usuario_id LIMIT 1");
        $uViejo = $resViejo ? mysqli_fetch_assoc($resViejo) : null;
        if (!empty($uViejo['avatar'])) {
            @unlink(__DIR__ . '/../' . $uViejo['avatar']);
            $altViejo = (stripos(__DIR__, 'PROYECTO-CURSOLANDIA') !== false)
                ? str_ireplace('PROYECTO-CURSOLANDIA', 'cursolandia', __DIR__ . '/../' . $uViejo['avatar'])
                : str_ireplace('cursolandia', 'PROYECTO-CURSOLANDIA', __DIR__ . '/../' . $uViejo['avatar']);
            @unlink($altViejo);
        }
        $avatarSql = ", avatar = NULL";
        $_SESSION['usuario_avatar'] = null;
        if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
            $_SESSION['usuario']['avatar'] = null;
        }
    }

    $nombre_esc    = mysqli_real_escape_string($conexion, $nombre);
    $apellido_esc  = mysqli_real_escape_string($conexion, $apellido);
    $user_esc      = mysqli_real_escape_string($conexion, $username);
    $bio_esc       = mysqli_real_escape_string($conexion, $biografia);
    $intereses_esc = mysqli_real_escape_string($conexion, $intereses);

    $sqlUpd = "
        UPDATE Usuario 
        SET nombre = '$nombre_esc', 
            apellido = '$apellido_esc', 
            username = '$user_esc', 
            email = '$email_esc', 
            biografia = '$bio_esc', 
            intereses = '$intereses_esc' 
            $avatarSql
        WHERE id = $usuario_id
    ";

    $updOk = @mysqli_query($conexion, $sqlUpd);

    if (!$updOk) {
        // Resiliencia: si falla por alguna columna personalizada inexistente en la BD, actualizar campos esenciales y avatar
        $sqlFallback = "UPDATE Usuario SET nombre = '$nombre_esc', apellido = '$apellido_esc', email = '$email_esc' $avatarSql WHERE id = $usuario_id";
        $updOk = @mysqli_query($conexion, $sqlFallback);
    }

    if ($updOk) {
        $_SESSION['usuario_nombre']   = $nombre;
        $_SESSION['usuario_username'] = $username;
        $_SESSION['usuario_email']    = $email;
        if ($avatarRuta) {
            $_SESSION['usuario_avatar'] = $avatarRuta;
        }
        if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
            $_SESSION['usuario']['nombre']   = $nombre;
            $_SESSION['usuario']['apellido'] = $apellido;
            $_SESSION['usuario']['username'] = $username;
            $_SESSION['usuario']['email']    = $email;
            $_SESSION['usuario']['biografia'] = $biografia;
            $_SESSION['usuario']['intereses'] = $intereses;
            if ($avatarRuta) {
                $_SESSION['usuario']['avatar'] = $avatarRuta;
            }
        }

        $_SESSION['exito'] = 'Tus datos e intereses fueron actualizados correctamente.';
    } else {
        $_SESSION['error'] = 'Ocurrió un error al actualizar los datos en la base de datos.';
    }

    header('Location: ../perfil/configuracion.php#perfil');
    exit;
}

// =============================================================
// CAMBIAR PW
// =============================================================
if ($accion === 'cambiar_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['pass_actual'] ?? $_POST['password_actual'] ?? '';
    $password_nueva  = $_POST['pass_nueva'] ?? $_POST['password_nueva'] ?? '';
    $password_repita = $_POST['pass_confirmar'] ?? $_POST['password_repita'] ?? '';

    if (empty($password_actual) || empty($password_nueva)) {
        $_SESSION['error'] = 'Completá todos los campos de contraseña.';
        header('Location: ../perfil/configuracion.php#seccion-seguridad');
        exit;
    }

    if (strlen($password_nueva) < 6) {
        $_SESSION['error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        header('Location: ../perfil/configuracion.php#seccion-seguridad');
        exit;
    }

    if ($password_nueva !== $password_repita) {
        $_SESSION['error'] = 'La nueva contraseña y su confirmación no coinciden.';
        header('Location: ../perfil/configuracion.php#seccion-seguridad');
        exit;
    }

    $resPass = mysqli_query($conexion, "SELECT password FROM Usuario WHERE id = $usuario_id LIMIT 1");
    $rowPass = $resPass ? mysqli_fetch_assoc($resPass) : null;
    $hashActual = $rowPass['password'] ?? '';

    if (!$hashActual || !password_verify($password_actual, $hashActual)) {
        $_SESSION['error'] = 'La contraseña actual ingresada es incorrecta.';
        header('Location: ../perfil/configuracion.php#seccion-seguridad');
        exit;
    }

    $nuevoHash = password_hash($password_nueva, PASSWORD_DEFAULT);
    $nuevoHashEsc = mysqli_real_escape_string($conexion, $nuevoHash);

    $updOk = mysqli_query($conexion, "UPDATE Usuario SET password = '$nuevoHashEsc' WHERE id = $usuario_id");

    if ($updOk) {
        $_SESSION['exito'] = 'Tu contraseña ha sido actualizada con éxito.';
    } else {
        $_SESSION['error'] = 'No se pudo actualizar la contraseña.';
    }

    header('Location: ../perfil/configuracion.php#seccion-seguridad');
    exit;
}

// =============================================================
// NOTIFICAR AL ADMIN PAGO PLAN PRO
// =============================================================
if ($accion === 'notificar_pago_pro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim($_POST['mensaje_pro'] ?? $_POST['mensaje'] ?? 'Solicitud de activación de Plan PRO tras transferencia.');

    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Por favor, adjuntá el comprobante de pago de la transferencia.';
        header('Location: ../perfil/configuracion.php#seccion-plan');
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

    if (!in_array($ext, $permitidas)) {
        $_SESSION['error'] = 'El comprobante debe ser una imagen JPG, PNG o archivo PDF.';
        header('Location: ../perfil/configuracion.php#seccion-plan');
        exit;
    }

    $dirComp = __DIR__ . '/../archivos/comprobantes/';
    if (!is_dir($dirComp)) mkdir($dirComp, 0777, true);

    $nombreComp = 'pago_pro_' . $usuario_id . '_' . time() . '.' . $ext;
    $rutaRelativa = 'archivos/comprobantes/' . $nombreComp;

    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dirComp . $nombreComp)) {
        $compEsc = mysqli_real_escape_string($conexion, $rutaRelativa);
        $msgEsc  = mysqli_real_escape_string($conexion, $mensaje);

        $sqlSol = "
            INSERT INTO SolicitudPro (usuario_id, comprobante, mensaje, estado, fecha_solicitud)
            VALUES ($usuario_id, '$compEsc', '$msgEsc', 'pendiente', NOW())
        ";
        $okSol = mysqli_query($conexion, $sqlSol);

        if ($okSol) {

            $resAdmins = mysqli_query($conexion, "SELECT id FROM Usuario WHERE rol = 'admin'");
            $nombreSolicitante = $_SESSION['usuario_nombre'] ?? 'Un usuario';
            while ($rowAdm = mysqli_fetch_assoc($resAdmins)) {
                enviar_notificacion(
                    (int)$rowAdm['id'],
                    'Nueva solicitud de Plan PRO de ' . $nombreSolicitante . ' con comprobante adjunto.',
                    'admin/solicitudes.php'
                );
            }

            $_SESSION['exito'] = '¡Tu notificación de pago fue enviada con éxito al administrador! Se revisará a la brevedad para activar tu Plan PRO.';
        } else {
            $_SESSION['error'] = 'Ocurrió un error al enviar tu solicitud.';
        }
    } else {
        $_SESSION['error'] = 'No se pudo subir el archivo de comprobante.';
    }

    header('Location: ../perfil/configuracion.php#seccion-plan');
    exit;
}

header('Location: ../perfil/configuracion.php');
exit;
