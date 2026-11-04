<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario']['id'])) {
    $_SESSION['error'] = 'Debés iniciar sesión para participar en el foro.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id']);
$nombre_usuario = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario']['nombre'] ?? 'Un usuario';
$accion = $_GET['accion'] ?? '';
$conexion = obtener_conexion();

// =============================================================
// CREAR PREGUNTA
// =============================================================
if ($accion === 'crear_pregunta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id  = (int)($_POST['curso_id'] ?? 0);
    $titulo    = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? $_POST['cuerpo'] ?? '');

    if ($curso_id <= 0 || empty($titulo) || empty($contenido)) {
        $_SESSION['error'] = 'Por favor, completá el título y detalle de tu pregunta.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro');
        exit;
    }

    $titulo_esc = mysqli_real_escape_string($conexion, $titulo);
    $cont_esc   = mysqli_real_escape_string($conexion, $contenido);

    $sqlInsert = "
        INSERT INTO Pregunta (curso_id, usuario_id, titulo, contenido, destacada, cerrada, fecha_creacion)
        VALUES ($curso_id, $usuario_id, '$titulo_esc', '$cont_esc', 0, 0, NOW())
    ";
    $ok = mysqli_query($conexion, $sqlInsert);

    if ($ok) {
        $preguntaId = (int)mysqli_insert_id($conexion);

        // desactiva notis a autopreguntas
        $resDoc = mysqli_query($conexion, "SELECT docente_id FROM Curso WHERE id = $curso_id LIMIT 1");
        $rowDoc = $resDoc ? mysqli_fetch_assoc($resDoc) : null;
        $docenteCursoId = (int)($rowDoc['docente_id'] ?? 0);

        $excluirCreacion = array_unique(array_filter([$usuario_id, ($usuario_id === $docenteCursoId ? $docenteCursoId : null)]));

        notificar_usuarios_curso(
            $curso_id,
            $nombre_usuario . ' publicó una nueva pregunta: "' . mb_substr($titulo, 0, 60) . '..."',
            'cursos/aula.php?id=' . $curso_id . '&tab=foro&pregunta=' . $preguntaId,
            $excluirCreacion
        );

        $_SESSION['exito'] = 'Tu pregunta fue publicada en el foro del curso.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $preguntaId);
        exit;
    } else {
        $_SESSION['error'] = 'No se pudo publicar la pregunta.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro');
        exit;
    }
}

// =============================================================
// RESPONDER PREGUNTA
// =============================================================
if ($accion === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pregunta_id = (int)($_POST['pregunta_id'] ?? 0);
    $curso_id    = (int)($_POST['curso_id'] ?? 0);
    $contenido   = trim($_POST['contenido'] ?? '');

    if ($pregunta_id <= 0 || empty($contenido)) {
        $_SESSION['error'] = 'La respuesta no puede estar vacía.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
        exit;
    }

    $resPreg = mysqli_query($conexion, "SELECT cerrada, titulo FROM Pregunta WHERE id = $pregunta_id LIMIT 1");
    $pregunta = $resPreg ? mysqli_fetch_assoc($resPreg) : null;

    if (!$pregunta || (int)$pregunta['cerrada'] === 1) {
        $_SESSION['error'] = 'Esta pregunta ya está cerrada y no admite nuevas respuestas.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
        exit;
    }

    // anti spam
    $resUltima = mysqli_query($conexion, "
        SELECT usuario_id, id 
        FROM Respuesta 
        WHERE pregunta_id = $pregunta_id 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $ultimaRespuesta = $resUltima ? mysqli_fetch_assoc($resUltima) : null;

    if ($ultimaRespuesta && (int)$ultimaRespuesta['usuario_id'] === $usuario_id) {
        $_SESSION['error'] = 'No podés responder dos veces seguidas. Podés editar tu última respuesta o esperar a que alguien más responda.';
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
        exit;
    }

    $cont_esc = mysqli_real_escape_string($conexion, $contenido);
    $insOk = mysqli_query($conexion, "
        INSERT INTO Respuesta (pregunta_id, usuario_id, contenido, es_aceptada, fecha_creacion)
        VALUES ($pregunta_id, $usuario_id, '$cont_esc', 0, NOW())
    ");

    if ($insOk) {
        // desactiva notis a autorespuestas
        $resDoc = mysqli_query($conexion, "SELECT docente_id FROM Curso WHERE id = $curso_id LIMIT 1");
        $rowDoc = $resDoc ? mysqli_fetch_assoc($resDoc) : null;
        $docenteCursoId = (int)($rowDoc['docente_id'] ?? 0);

        $excluirResp = array_unique(array_filter([$usuario_id, ($usuario_id === $docenteCursoId ? $docenteCursoId : null)]));

        notificar_participantes_pregunta(
            $pregunta_id,
            $nombre_usuario . ' respondió en: "' . mb_substr($pregunta['titulo'], 0, 50) . '..."',
            'cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id,
            $excluirResp
        );

        $_SESSION['exito'] = 'Tu respuesta fue enviada.';
    } else {
        $_SESSION['error'] = 'No se pudo enviar la respuesta.';
    }

    header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
    exit;
}

// =============================================================
// EDITAR ÚLTIMA RESPUESTA
// =============================================================
if ($accion === 'editar_respuesta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $respuesta_id = (int)($_POST['respuesta_id'] ?? 0);
    $curso_id     = (int)($_POST['curso_id'] ?? 0);
    $contenido    = trim($_POST['contenido'] ?? '');

    if ($respuesta_id <= 0 || empty($contenido)) {
        header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro');
        exit;
    }

    $cont_esc = mysqli_real_escape_string($conexion, $contenido);
    $updOk = mysqli_query($conexion, "
        UPDATE Respuesta 
        SET contenido = '$cont_esc', fecha_edicion = NOW() 
        WHERE id = $respuesta_id AND usuario_id = $usuario_id
    ");

    if ($updOk) {
        $_SESSION['exito'] = 'Respuesta actualizada correctamente.';
    } else {
        $_SESSION['error'] = 'No se pudo editar la respuesta.';
    }

    header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro');
    exit;
}

// =============================================================
// MARCAR RESPUESTA COMO ACEPTADA
// =============================================================
if ($accion === 'aceptar_respuesta') {
    $respuesta_id = (int)($_GET['respuesta_id'] ?? 0);
    $pregunta_id  = (int)($_GET['pregunta_id'] ?? 0);
    $curso_id     = (int)($_GET['curso_id'] ?? 0);

    $resPreg = mysqli_query($conexion, "
        SELECT p.usuario_id AS autor_pregunta, c.docente_id, p.titulo
        FROM Pregunta p
        JOIN Curso c ON p.curso_id = c.id
        WHERE p.id = $pregunta_id
        LIMIT 1
    ");
    $pregData = $resPreg ? mysqli_fetch_assoc($resPreg) : null;

    if ($pregData && ($usuario_id === (int)$pregData['autor_pregunta'] || $usuario_id === (int)$pregData['docente_id'])) {
        $docenteId = (int)$pregData['docente_id'];

        mysqli_query($conexion, "UPDATE Respuesta SET es_aceptada = 1 WHERE id = $respuesta_id");

        mysqli_query($conexion, "
            UPDATE Pregunta 
            SET cerrada = 1, id_respuesta_aceptada = $respuesta_id 
            WHERE id = $pregunta_id
        ");

        $resResp = mysqli_query($conexion, "SELECT usuario_id FROM Respuesta WHERE id = $respuesta_id LIMIT 1");
        $rowResp = $resResp ? mysqli_fetch_assoc($resResp) : null;
        $autorRespuesta = (int)($rowResp['usuario_id'] ?? 0);

        if ($autorRespuesta > 0 && $autorRespuesta !== $usuario_id && $autorRespuesta !== $docenteId) {
            enviar_notificacion(
                $autorRespuesta,
                '¡Tu respuesta fue marcada como ACEPTADA en: "' . mb_substr($pregData['titulo'], 0, 50) . '"!',
                'cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id
            );
        }

        $excluirCierre = array_unique(array_filter([
            $usuario_id,
            $autorRespuesta,
            ($usuario_id === $docenteId ? $docenteId : null)
        ]));

        notificar_participantes_pregunta(
            $pregunta_id,
            'Se aceptó una respuesta y se cerró el tema: "' . mb_substr($pregData['titulo'], 0, 50) . '"',
            'cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id,
            $excluirCierre
        );

        $_SESSION['exito'] = 'Respuesta aceptada y tema cerrado.';
    } else {
        $_SESSION['error'] = 'No tenés permisos para cerrar esta pregunta.';
    }

    header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
    exit;
}

// =============================================================
// DESTACAR PREGUNTA
// =============================================================
if ($accion === 'destacar_pregunta') {
    $pregunta_id = (int)($_GET['pregunta_id'] ?? 0);
    $curso_id    = (int)($_GET['curso_id'] ?? 0);

    $resDoc = mysqli_query($conexion, "SELECT docente_id FROM Curso WHERE id = $curso_id LIMIT 1");
    $rowDoc = $resDoc ? mysqli_fetch_assoc($resDoc) : null;

    if ($rowDoc && (int)$rowDoc['docente_id'] === $usuario_id) {
        mysqli_query($conexion, "UPDATE Pregunta SET destacada = 1 - destacada WHERE id = $pregunta_id");
        $_SESSION['exito'] = 'Estado de pregunta destacada actualizado.';
    }

    header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro#pregunta-' . $pregunta_id);
    exit;
}

// =============================================================
// ELIMINAR PREGUNTA
// =============================================================
if ($accion === 'eliminar_pregunta') {
    $pregunta_id = (int)($_GET['pregunta_id'] ?? 0);
    $curso_id    = (int)($_GET['curso_id'] ?? 0);

    $resDoc = mysqli_query($conexion, "
        SELECT p.usuario_id, c.docente_id, p.titulo 
        FROM Pregunta p 
        JOIN Curso c ON p.curso_id = c.id 
        WHERE p.id = $pregunta_id
        LIMIT 1
    ");
    $row = $resDoc ? mysqli_fetch_assoc($resDoc) : null;

    if ($row && (int)$row['docente_id'] === $usuario_id) {
        $autorPregunta = (int)$row['usuario_id'];
        $docenteId = (int)$row['docente_id'];
        mysqli_query($conexion, "DELETE FROM Pregunta WHERE id = $pregunta_id");

        if ($autorPregunta > 0 && $autorPregunta !== $usuario_id && $autorPregunta !== $docenteId) {
            enviar_notificacion(
                $autorPregunta,
                'El docente eliminó tu pregunta: "' . mb_substr($row['titulo'], 0, 50) . '" del foro.',
                'cursos/aula.php?id=' . $curso_id . '&tab=foro'
            );
        }

        $_SESSION['exito'] = 'Pregunta eliminada.';
    }

    header('Location: ../cursos/aula.php?id=' . $curso_id . '&tab=foro');
    exit;
}

header('Location: ../cursos/index.php');
exit;
