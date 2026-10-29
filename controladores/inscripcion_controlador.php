<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario']['id'])) {
    $_SESSION['info'] = 'Para inscribirte a este curso, creá tu cuenta en Cursolandia.';
    header('Location: ../auth/registrarse.php');
    exit;
}

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
$nombre_usuario = 'Un alumno';
if (isset($_SESSION['usuario_nombre'])) {
    $nombre_usuario = $_SESSION['usuario_nombre'];
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $nombre_usuario = $_SESSION['usuario']['nombre'];
}
$es_pro = usuario_es_pro($usuario_id);
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$conexion = obtener_conexion();

if ($accion === 'inscribir') {
    $curso_id = 0;
    if (isset($_POST['curso_id'])) {
        $curso_id = (int)$_POST['curso_id'];
    } elseif (isset($_GET['curso_id'])) {
        $curso_id = (int)$_GET['curso_id'];
    }

    if ($curso_id <= 0) {
        $_SESSION['error'] = 'Curso no válido.';
        header('Location: ../cursos/index.php');
        exit;
    }

    $resCurso = mysqli_query($conexion, "
        SELECT id, titulo, cupo_max, precio, docente_id, fecha_inicio, fecha_fin, estado 
        FROM curso 
        WHERE id = $curso_id AND estado = 'publicado' 
        LIMIT 1
    ");
    $curso = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

    if (!$curso) {
        $_SESSION['error'] = 'El curso seleccionado no está disponible.';
        header('Location: ../cursos/index.php');
        exit;
    }

    $resRol = mysqli_query($conexion, "SELECT rol FROM usuario WHERE id = $usuario_id LIMIT 1");
    $rowRol = $resRol ? mysqli_fetch_assoc($resRol) : null;
    $rolUsuario = $rowRol ? $rowRol['rol'] : '';
    if ($rolUsuario === 'admin') {
        $_SESSION['error'] = 'Los administradores no se inscriben a cursos como alumnos. Tienen acceso directo a todas las aulas virtuales.';
        header('Location: ../cursos/ver.php?id=' . $curso_id);
        exit;
    }

    if ((int)$curso['docente_id'] === $usuario_id) {
        $_SESSION['error'] = 'No podés anotarte en tu propio curso.';
        header('Location: ../cursos/ver.php?id=' . $curso_id);
        exit;
    }

    $hoy = date('Y-m-d');
    if ($hoy > $curso['fecha_fin']) {
        $_SESSION['error'] = 'El periodo de cursada ya finalizó.';
        header('Location: ../cursos/ver.php?id=' . $curso_id);
        exit;
    }

    if (!$es_pro) {
        $inscripcionesActivas = contar_inscripciones_activas_alumno($usuario_id);
        if ($inscripcionesActivas >= 3) {
            $_SESSION['error'] = 'Alcanzaste el límite de 3 cursos activos simultáneos del Plan Gratuito. Mejorá a Plan PRO para anotarte a todos los que quieras.';
            header('Location: ../cursos/ver.php?id=' . $curso_id);
            exit;
        }
    }

    if (!empty($curso['cupo_max']) && (int)$curso['cupo_max'] > 0) {
        $resInscriptos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM inscripcion WHERE curso_id = $curso_id AND estado IN ('activa', 'pendiente')");
        $rowIns = $resInscriptos ? mysqli_fetch_assoc($resInscriptos) : null;
        $totalInscriptos = $rowIns ? intval($rowIns['total']) : 0;

        if ($totalInscriptos >= (int)$curso['cupo_max']) {
            $_SESSION['error'] = 'Lo sentimos, se han agotado los cupos para este curso.';
            header('Location: ../cursos/ver.php?id=' . $curso_id);
            exit;
        }
    }

    $resCheck = mysqli_query($conexion, "SELECT id, estado FROM inscripcion WHERE usuario_id = $usuario_id AND curso_id = $curso_id");
    $inscripcionExistente = $resCheck ? mysqli_fetch_assoc($resCheck) : null;

    $esPago = ((float)$curso['precio'] > 0);
    $nuevoEstado = $esPago ? 'pendiente' : 'activa';

    if ($inscripcionExistente) {
        if ($inscripcionExistente['estado'] === 'activa') {
            $_SESSION['error'] = 'Ya estás inscripto y activo en este curso.';
            header('Location: ../cursos/mis-inscripciones.php');
            exit;
        } elseif ($inscripcionExistente['estado'] === 'pendiente') {
            $_SESSION['info'] = 'Tu inscripción ya está pendiente de confirmación de pago por parte del docente.';
            header('Location: ../cursos/pagar.php?id=' . $curso_id);
            exit;
        } else {

            $exId = intval($inscripcionExistente['id']);
            mysqli_query($conexion, "UPDATE inscripcion SET estado = '$nuevoEstado', fecha_inscripcion = NOW() WHERE id = $exId");
        }
    } else {
        // nueva inscripcion
        mysqli_query($conexion, "
            INSERT INTO inscripcion (usuario_id, curso_id, estado, progreso_porcentaje, fecha_inscripcion)
            VALUES ($usuario_id, $curso_id, '$nuevoEstado', 0, NOW())
        ");
    }

    // notificacion al docente
    if ($esPago) {
        enviar_notificacion(
            (int)$curso['docente_id'],
            $nombre_usuario . ' solicitó inscribirse a tu curso pago "' . $curso['titulo'] . '" (Pago pendiente).',
            'cursos/alumnos.php?id=' . $curso_id
        );
        $_SESSION['exito'] = '¡Te anotaste al curso! Como es un curso arancelado, realizá la transferencia y adjuntá tu comprobante para que el docente active tu acceso.';
        header('Location: ../cursos/pagar.php?id=' . $curso_id);
        exit;
    } else {
        enviar_notificacion(
            (int)$curso['docente_id'],
            $nombre_usuario . ' se ha inscripto a tu curso "' . $curso['titulo'] . '".',
            'cursos/alumnos.php?id=' . $curso_id
        );
        $_SESSION['exito'] = '¡Inscripción exitosa! Ya tenés acceso completo al curso.';
        header('Location: ../cursos/mis-inscripciones.php');
        exit;
    }
}

// CANCELAR INSCRIPCIÓN
if ($accion === 'cancelar') {
    $curso_id = 0;
    if (isset($_POST['curso_id'])) {
        $curso_id = (int)$_POST['curso_id'];
    } elseif (isset($_GET['curso_id'])) {
        $curso_id = (int)$_GET['curso_id'];
    }

    if ($curso_id <= 0) {
        header('Location: ../cursos/mis-inscripciones.php');
        exit;
    }

    mysqli_query($conexion, "UPDATE inscripcion SET estado = 'cancelada' WHERE usuario_id = $usuario_id AND curso_id = $curso_id");
    $_SESSION['exito'] = 'Inscripción cancelada.';
    header('Location: ../cursos/mis-inscripciones.php');
    exit;
}

// SUBIR COMPROBANTE DE PAGO
if ($accion === 'subir_comprobante' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;
    $mensaje = '';
    if (isset($_POST['mensaje_pago'])) {
        $mensaje = trim($_POST['mensaje_pago']);
    } elseif (isset($_POST['mensaje'])) {
        $mensaje = trim($_POST['mensaje']);
    }

    if ($curso_id <= 0) {
        $_SESSION['error'] = 'Curso inválido.';
        header('Location: ../cursos/mis-inscripciones.php');
        exit;
    }

    $resCurso = mysqli_query($conexion, "SELECT id, titulo, docente_id FROM curso WHERE id = $curso_id LIMIT 1");
    $cData = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

    if (!$cData) {
        $_SESSION['error'] = 'El curso seleccionado no existe.';
        header('Location: ../cursos/mis-inscripciones.php');
        exit;
    }

    $resIns = mysqli_query($conexion, "
        SELECT id, estado FROM inscripcion 
        WHERE usuario_id = $usuario_id AND curso_id = $curso_id
        LIMIT 1
    ");
    $ins = $resIns ? mysqli_fetch_assoc($resIns) : null;

    if (!$ins) {
        mysqli_query($conexion, "
            INSERT INTO inscripcion (usuario_id, curso_id, estado, progreso_porcentaje, fecha_inscripcion)
            VALUES ($usuario_id, $curso_id, 'pendiente', 0, NOW())
        ");
        $insId = (int)mysqli_insert_id($conexion);
    } else {
        $insId = (int)$ins['id'];
    }

    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Por favor, adjuntá el archivo del comprobante de transferencia bancaria.';
        header('Location: ../cursos/pagar.php?id=' . $curso_id);
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

    if (!in_array($ext, $permitidas)) {
        $_SESSION['error'] = 'El comprobante debe ser un archivo de imagen (JPG, PNG, WebP) o documento PDF.';
        header('Location: ../cursos/pagar.php?id=' . $curso_id);
        exit;
    }

    $dirComp = __DIR__ . '/../archivos/comprobantes/';
    if (!is_dir($dirComp)) {
        mkdir($dirComp, 0777, true);
    }

    $nombreComp = 'pago_curso_' . $insId . '_' . time() . '.' . $ext;
    $rutaRelativa = 'archivos/comprobantes/' . $nombreComp;

    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $dirComp . $nombreComp)) {
        $msg_esc = mysqli_real_escape_string($conexion, $mensaje);
        $comp_esc = mysqli_real_escape_string($conexion, $rutaRelativa);
        
        $updOk = mysqli_query($conexion, "
            UPDATE inscripcion 
            SET comprobante = '$comp_esc', 
                mensaje_pago = '$msg_esc', 
                fecha_comprobante = NOW(),
                estado = 'pendiente'
            WHERE id = $insId
        ");

        if ($updOk) {

            enviar_notificacion(
                (int)$cData['docente_id'],
                $nombre_usuario . ' adjuntó el comprobante de pago para el curso "' . $cData['titulo'] . '". Podés verificarlo y confirmar el alta.',
                'cursos/alumnos.php?id=' . $curso_id
            );

            $_SESSION['exito'] = '¡Comprobante enviado con éxito! El docente verificará la transferencia y activará tu curso.';
            header('Location: ../cursos/pagar.php?id=' . $curso_id);
            exit;
        } else {
            $_SESSION['error'] = 'Ocurrió un error al registrar el comprobante en la base de datos.';
            header('Location: ../cursos/pagar.php?id=' . $curso_id);
            exit;
        }
    } else {
        $_SESSION['error'] = 'No se pudo subir el archivo de comprobante al servidor.';
        header('Location: ../cursos/pagar.php?id=' . $curso_id);
        exit;
    }
}

header('Location: ../cursos/mis-inscripciones.php');
exit;
