<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario']['id'])) {
    $_SESSION['error'] = 'Debés iniciar sesión para gestionar cursos.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
$es_pro = usuario_es_pro($usuario_id);
$accion = '';
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
} elseif (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
}
$conexion = obtener_conexion();

function procesar_modulos_formulario($modulosPost) {
    if (!is_array($modulosPost) || empty($modulosPost)) {
        return null;
    }
    $modulosFormateados = [];
    $num = 1;
    foreach ($modulosPost as $m) {
        $mTitulo = isset($m['titulo']) ? trim($m['titulo']) : '';
        $mDuracion = isset($m['duracion']) ? trim($m['duracion']) : '';
        $mClasesRaw = isset($m['clases']) ? trim($m['clases']) : '';
        
        if (empty($mTitulo) || empty($mDuracion) || empty($mClasesRaw)) {
            continue;
        }

        $lineasRaw = explode("\n", $mClasesRaw);
        $lineasClases = [];
        foreach ($lineasRaw as $lClase) {
            $lClase = trim($lClase);
            if ($lClase !== '') {
                $lineasClases[] = $lClase;
            }
        }
        if (empty($lineasClases)) {
            continue;
        }

        $clasesArr = [];
        foreach ($lineasClases as $cIdx => $lineaClase) {
            $clasesArr[] = [
                'nombre'   => $lineaClase,
                'duracion' => '45 min',
                'gratis'   => ($cIdx === 0 && $num === 1)
            ];
        }

        $modulosFormateados[] = [
            'numero'   => 'Módulo ' . $num,
            'titulo'   => $mTitulo,
            'duracion' => $mDuracion,
            'clases'   => $clasesArr
        ];
        $num++;
    }
    return !empty($modulosFormateados) ? json_encode($modulosFormateados, JSON_UNESCAPED_UNICODE) : null;
}

if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$es_pro) {
        $cursosActivos = contar_cursos_activos_docente($usuario_id);
        if ($cursosActivos >= 3) {
            $_SESSION['error'] = 'Alcanzaste el límite de 3 cursos activos del Plan Gratuito. Mejorá a Plan PRO para publicar sin límites.';
            header('Location: ../cursos/mis-cursos.php');
            exit;
        }
    }

    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
    $nivel = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $objetivos = isset($_POST['objetivos']) ? trim($_POST['objetivos']) : '';
    $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
    $modulosParam = isset($_POST['modulos']) ? $_POST['modulos'] : [];
    $modulosJson = procesar_modulos_formulario($modulosParam);
    $etiquetas = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
    $tipo_acceso = isset($_POST['tipo_acceso']) ? trim($_POST['tipo_acceso']) : 'publico';
    $cupo_raw = isset($_POST['cupo']) ? trim($_POST['cupo']) : '';
    $cupo_max     = ($cupo_raw !== '') ? (int)$cupo_raw : 0;
    $tipo_precio  = isset($_POST['tipo_precio']) ? trim($_POST['tipo_precio']) : 'pago';
    $precioPost = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $precio = ($tipo_precio === 'gratis') ? 0 : $precioPost;

    $errores = [];
    if (empty($titulo))       $errores[] = 'El título del curso es obligatorio.';
    if (empty($categoria))    $errores[] = 'La categoría es obligatoria.';
    if (empty($nivel))        $errores[] = 'El nivel de dificultad es obligatorio.';
    if (empty($descripcion))  $errores[] = 'La descripción general es obligatoria.';
    if (empty($objetivos))    $errores[] = 'Los objetivos de aprendizaje son obligatorios.';
    if (empty($requisitos))   $errores[] = 'Los requisitos previos del curso son obligatorios.';
    if (empty($etiquetas))    $errores[] = 'Las etiquetas (#Tags) son obligatorias.';
    if (empty($fecha_inicio)) $errores[] = 'La fecha de inicio es obligatoria.';
    if (empty($fecha_fin))    $errores[] = 'La fecha de finalización es obligatoria.';
    if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de finalización no puede ser anterior a la de inicio.';
    }
    if ($cupo_max <= 0)       $errores[] = 'El cupo máximo de estudiantes debe ser de al menos 1.';
    if ($tipo_precio === 'pago' && $precio <= 0) {
        $errores[] = 'Para cursos de pago debés especificar un precio mayor a 0.';
    }
    if (empty($_FILES['portada']['name']) || $_FILES['portada']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'La imagen de portada es obligatoria.';
    }
    if (empty($modulosJson)) {
        $errores[] = 'Debés definir al menos un módulo temático completo con título, duración estimada y clases.';
    }

    if (!empty($errores)) {
        $_SESSION['error'] = implode(' ', $errores);
        header('Location: ../cursos/crear.php');
        exit;
    }

    $iconosCategorias = [
        'programacion' => 'bi-code-slash',
        'diseno'       => 'bi-palette',
        'comunicacion' => 'bi-people',
        'marketing'    => 'bi-graph-up-arrow',
        'finanzas'     => 'bi-cash-stack',
        'ciencia'      => 'bi-diagram-3',
        'ia'           => 'bi-cpu',
        'idiomas'      => 'bi-translate'
    ];
    $catKey = strtolower($categoria);
    $icono = isset($iconosCategorias[$catKey]) ? $iconosCategorias[$catKey] : 'bi-journal-bookmark';

    $imagen = obtener_portada_curso('', $categoria);
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dir = __DIR__ . '/../archivos/portadas/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $nombreImg = 'curso_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['portada']['tmp_name'], $dir . $nombreImg)) {
                $imagen = 'archivos/portadas/' . $nombreImg;
            }
        }
    }

    $catEsc = mysqli_real_escape_string($conexion, ucfirst($categoria));
    $titEsc = mysqli_real_escape_string($conexion, $titulo);
    $subEsc = mysqli_real_escape_string($conexion, mb_substr($descripcion, 0, 100) . '...');
    $descEsc = mysqli_real_escape_string($conexion, $descripcion);
    $objEsc = mysqli_real_escape_string($conexion, $objetivos);
    $reqEsc = mysqli_real_escape_string($conexion, $requisitos);
    $modEsc = mysqli_real_escape_string($conexion, (string)$modulosJson);
    $tagsEsc = mysqli_real_escape_string($conexion, $etiquetas);
    $nivEsc = mysqli_real_escape_string($conexion, ucfirst($nivel));
    $finiEsc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $ffinEsc = mysqli_real_escape_string($conexion, $fecha_fin);
    $accEsc = mysqli_real_escape_string($conexion, $tipo_acceso);
    $imgEsc = mysqli_real_escape_string($conexion, $imagen);
    $icoEsc = mysqli_real_escape_string($conexion, $icono);

    $rol_creador = '';
            if (isset($_SESSION['usuario_rol'])) {
                $rol_creador = $_SESSION['usuario_rol'];
            } elseif (isset($_SESSION['usuario']['rol'])) {
                $rol_creador = $_SESSION['usuario']['rol'];
            }
    $estado_inicial = ($rol_creador === 'admin') ? 'publicado' : 'pendiente';

    $sqlInsert = "
        INSERT INTO curso (docente_id, categoria, titulo, subtitulo, descripcion, objetivos, requisitos, modulos_contenido, etiquetas, nivel, duracion_horas, precio, cupo_max, fecha_inicio, fecha_fin, tipo_acceso, imagen, icono, estado, fecha_creacion)
        VALUES ($usuario_id, '$catEsc', '$titEsc', '$subEsc', '$descEsc', '$objEsc', '$reqEsc', '$modEsc', '$tagsEsc', '$nivEsc', 30, $precio, $cupo_max, '$finiEsc', '$ffinEsc', '$accEsc', '$imgEsc', '$icoEsc', '$estado_inicial', NOW())
    ";
    
    if (mysqli_query($conexion, $sqlInsert)) {
        $cursoId = intval(mysqli_insert_id($conexion));

        if (isset($_FILES['materiales']) && is_array($_FILES['materiales']['name'])) {
            $totalArchivos = count($_FILES['materiales']['name']);
            $pesoTotalMb = 0;
            $archivosSubidos = 0;

            for ($i = 0; $i < $totalArchivos; $i++) {
                if ($_FILES['materiales']['error'][$i] === UPLOAD_ERR_OK) {
                    $tamanoMb = round($_FILES['materiales']['size'][$i] / (1024 * 1024), 2);

                    if (!$es_pro) {
                        if ($archivosSubidos >= 5) break;
                        if ($tamanoMb > 5.0) continue;
                        if (($pesoTotalMb + $tamanoMb) > 10.0) break;
                    }

                    $nombreOrig = $_FILES['materiales']['name'][$i];
                    $ext = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
                    $dirMat = __DIR__ . '/../archivos/materiales/';
                    if (!is_dir($dirMat)) mkdir($dirMat, 0777, true);

                    $nombreFinal = 'mat_' . $cursoId . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['materiales']['tmp_name'][$i], $dirMat . $nombreFinal)) {
                        $nomEsc = mysqli_real_escape_string($conexion, $nombreOrig);
                        $rutEsc = mysqli_real_escape_string($conexion, 'archivos/materiales/' . $nombreFinal);
                        mysqli_query($conexion, "
                            INSERT INTO material (curso_id, nombre_archivo, ruta_archivo, tamano_mb, fecha_subida)
                            VALUES ($cursoId, '$nomEsc', '$rutEsc', $tamanoMb, NOW())
                        ");
                        $pesoTotalMb += $tamanoMb;
                        $archivosSubidos++;
                    }
                }
            }
        }

        if ($estado_inicial === 'publicado') {
            $_SESSION['exito'] = '¡Tu curso fue publicado exitosamente!';
        } else {
            $resAdmins = mysqli_query($conexion, "SELECT id FROM usuario WHERE rol = 'admin'");
            if ($resAdmins) {
                $dNom = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '';
            $dApe = isset($_SESSION['usuario_apellido']) ? $_SESSION['usuario_apellido'] : '';
            $nombreDocente = trim($dNom . ' ' . $dApe);
                if (empty($nombreDocente)) $nombreDocente = 'Un docente';
                while ($adminRow = mysqli_fetch_assoc($resAdmins)) {
                    enviar_notificacion(
                        intval($adminRow['id']),
                        'Nueva propuesta de curso para revisión: "' . $titulo . '" por ' . $nombreDocente . '.',
                        'admin/solicitudes.php?tab=cursos'
                    );
                }
            }

            enviar_notificacion(
                $usuario_id,
                'Tu curso "' . $titulo . '" fue enviado a revisión. El administrador lo evaluará antes de publicarlo en el catálogo.',
                'cursos/mis-cursos.php'
            );

            $_SESSION['exito'] = '¡Tu propuesta de curso fue enviada exitosamente! Se encuentra pendiente de revisión por el administrador antes de ser publicada en el catálogo.';
        }

        header('Location: ../cursos/mis-cursos.php');
        exit;
    } else {
        $_SESSION['error'] = 'No se pudo guardar el curso. Intentá nuevamente.';
        header('Location: ../cursos/crear.php');
        exit;
    }
}

if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;

    if ($curso_id <= 0) {
        header('Location: ../cursos/mis-cursos.php');
        exit;
    }

    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
    $nivel = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
    $tipo_acceso = isset($_POST['tipo_acceso']) ? trim($_POST['tipo_acceso']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $objetivos = isset($_POST['objetivos']) ? trim($_POST['objetivos']) : '';
    $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
    $modulosParam = isset($_POST['modulos']) ? $_POST['modulos'] : [];
    $modulosJson = procesar_modulos_formulario($modulosParam);
    $etiquetas = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
    $cupo_raw = isset($_POST['cupo']) ? trim($_POST['cupo']) : '';
    $cupo_max     = ($cupo_raw !== '') ? (int)$cupo_raw : 0;
    $tipo_precio  = isset($_POST['tipo_precio']) ? trim($_POST['tipo_precio']) : 'pago';
    $precioPost = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $precio = ($tipo_precio === 'gratis') ? 0 : $precioPost;

    $errores = [];
    if (empty($titulo))       $errores[] = 'El título del curso es obligatorio.';
    if (empty($categoria))    $errores[] = 'La categoría es obligatoria.';
    if (empty($nivel))        $errores[] = 'El nivel de dificultad es obligatorio.';
    if (empty($tipo_acceso))  $errores[] = 'El tipo de acceso es obligatorio.';
    if (empty($descripcion))  $errores[] = 'La descripción es obligatoria.';
    if (empty($objetivos))    $errores[] = 'Los objetivos de aprendizaje son obligatorios.';
    if (empty($requisitos))   $errores[] = 'Los requisitos del curso son obligatorios.';
    if (empty($etiquetas))    $errores[] = 'Las etiquetas (#Tags) son obligatorias.';
    if (empty($fecha_inicio)) $errores[] = 'La fecha de inicio es obligatoria.';
    if (empty($fecha_fin))    $errores[] = 'La fecha de finalización es obligatoria.';
    if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de finalización no puede ser anterior a la de inicio.';
    }
    if ($cupo_max <= 0)       $errores[] = 'El cupo máximo de estudiantes debe ser de al menos 1.';
    if ($tipo_precio === 'pago' && $precio <= 0) {
        $errores[] = 'Para cursos de pago debés especificar un precio mayor a 0.';
    }
    if (empty($modulosJson)) {
        $errores[] = 'Debés definir al menos un módulo temático completo con título, duración estimada y clases.';
    }

    if (!empty($errores)) {
        $_SESSION['error'] = implode(' ', $errores);
        header('Location: ../cursos/editar.php?id=' . $curso_id);
        exit;
    }

    $titEsc = mysqli_real_escape_string($conexion, $titulo);
    $subEsc = mysqli_real_escape_string($conexion, mb_substr($descripcion, 0, 100) . '...');
    $catEsc = mysqli_real_escape_string($conexion, ucfirst($categoria));
    $nivEsc = mysqli_real_escape_string($conexion, ucfirst($nivel));
    $descEsc = mysqli_real_escape_string($conexion, $descripcion);
    $objEsc = mysqli_real_escape_string($conexion, $objetivos);
    $reqEsc = mysqli_real_escape_string($conexion, $requisitos);
    $modEsc = mysqli_real_escape_string($conexion, (string)$modulosJson);
    $tagsEsc = mysqli_real_escape_string($conexion, $etiquetas);
    $finiEsc = mysqli_real_escape_string($conexion, $fecha_inicio);
    $ffinEsc = mysqli_real_escape_string($conexion, $fecha_fin);
    $accEsc = mysqli_real_escape_string($conexion, $tipo_acceso);

    $sqlUpdate = "
        UPDATE curso 
        SET titulo = '$titEsc', subtitulo = '$subEsc', categoria = '$catEsc', nivel = '$nivEsc', descripcion = '$descEsc', objetivos = '$objEsc', requisitos = '$reqEsc', modulos_contenido = '$modEsc', etiquetas = '$tagsEsc',
            fecha_inicio = '$finiEsc', fecha_fin = '$ffinEsc', tipo_acceso = '$accEsc', cupo_max = $cupo_max, precio = $precio,
            estado = IF(estado = 'rechazado', 'pendiente', estado)
        WHERE id = $curso_id AND docente_id = $usuario_id
    ";
    mysqli_query($conexion, $sqlUpdate);

    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dir = __DIR__ . '/../archivos/portadas/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $nombreImg = 'curso_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['portada']['tmp_name'], $dir . $nombreImg)) {
                $nuevaImg = 'archivos/portadas/' . $nombreImg;
                $imgEsc = mysqli_real_escape_string($conexion, $nuevaImg);
                mysqli_query($conexion, "UPDATE curso SET imagen = '$imgEsc' WHERE id = $curso_id AND docente_id = $usuario_id");
            }
        }
    }

    if (isset($_FILES['materiales']) && is_array($_FILES['materiales']['name'])) {
        $resCountMat = mysqli_query($conexion, "SELECT COUNT(*) as cant, COALESCE(SUM(tamano_mb), 0) as total_mb FROM material WHERE curso_id = $curso_id");
        $matStats = $resCountMat ? mysqli_fetch_assoc($resCountMat) : ['cant' => 0, 'total_mb' => 0];
        $archivosActuales = isset($matStats['cant']) ? (int)$matStats['cant'] : 0;
        $pesoTotalMb = isset($matStats['total_mb']) ? (float)$matStats['total_mb'] : 0;

        $totalNuevos = count($_FILES['materiales']['name']);
        for ($i = 0; $i < $totalNuevos; $i++) {
            if ($_FILES['materiales']['error'][$i] === UPLOAD_ERR_OK) {
                $tamanoMb = round($_FILES['materiales']['size'][$i] / (1024 * 1024), 2);

                if (!$es_pro) {
                    if ($archivosActuales >= 5) break;
                    if ($tamanoMb > 5.0) continue;
                    if (($pesoTotalMb + $tamanoMb) > 10.0) break;
                }

                $nombreOrig = $_FILES['materiales']['name'][$i];
                $ext = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
                $dirMat = __DIR__ . '/../archivos/materiales/';
                if (!is_dir($dirMat)) mkdir($dirMat, 0777, true);

                $nombreFinal = 'mat_' . $curso_id . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['materiales']['tmp_name'][$i], $dirMat . $nombreFinal)) {
                    $nomEsc = mysqli_real_escape_string($conexion, $nombreOrig);
                    $rutEsc = mysqli_real_escape_string($conexion, 'archivos/materiales/' . $nombreFinal);
                    mysqli_query($conexion, "
                        INSERT INTO material (curso_id, nombre_archivo, ruta_archivo, tamano_mb, fecha_subida)
                        VALUES ($curso_id, '$nomEsc', '$rutEsc', $tamanoMb, NOW())
                    ");
                    $pesoTotalMb += $tamanoMb;
                    $archivosActuales++;
                }
            }
        }
    }

    $_SESSION['exito'] = 'Curso actualizado correctamente.';
    header('Location: ../cursos/mis-cursos.php');
    exit;
}

if ($accion === 'eliminar_material') {
    $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
    $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

    if ($material_id <= 0 || $curso_id <= 0) {
        header('Location: ../cursos/mis-cursos.php');
        exit;
    }

    $resMat = mysqli_query($conexion, "
        SELECT m.id, m.ruta_archivo, m.curso_id 
        FROM material m
        JOIN curso c ON m.curso_id = c.id
        WHERE m.id = $material_id AND c.docente_id = $usuario_id
    ");
    $matRow = $resMat ? mysqli_fetch_assoc($resMat) : null;

    if ($matRow) {
        $rutaFisica = __DIR__ . '/../' . ltrim($matRow['ruta_archivo'], '/');
        if (file_exists($rutaFisica) && !is_dir($rutaFisica)) {
            @unlink($rutaFisica);
        }
        mysqli_query($conexion, "DELETE FROM material WHERE id = $material_id");
        $_SESSION['exito'] = 'Material eliminado correctamente.';
    } else {
        $_SESSION['error'] = 'No tenés permisos para eliminar este material o no existe.';
    }

    header('Location: ../cursos/editar.php?id=' . $curso_id);
    exit;
}

if ($accion === 'confirmar_pago') {
    $inscripcion_id = isset($_GET['inscripcion_id']) ? intval($_GET['inscripcion_id']) : 0;
    $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

    $resCheck = mysqli_query($conexion, "
        SELECT i.id, i.usuario_id, c.titulo 
        FROM inscripcion i
        JOIN curso c ON i.curso_id = c.id
        WHERE i.id = $inscripcion_id AND c.docente_id = $usuario_id
    ");
    $row = $resCheck ? mysqli_fetch_assoc($resCheck) : null;

    if ($row) {
        mysqli_query($conexion, "UPDATE inscripcion SET estado = 'activa' WHERE id = $inscripcion_id");

        enviar_notificacion(
            intval($row['usuario_id']),
            '¡El docente confirmó tu pago! Ya estás formalmente inscripto a "' . $row['titulo'] . '".',
            'cursos/ver.php?id=' . $curso_id
        );

        $_SESSION['exito'] = 'Inscripción confirmada con éxito.';
    }

    header('Location: ../cursos/alumnos.php?id=' . $curso_id);
    exit;
}

if ($accion === 'eliminar') {
    $curso_id = 0;
    if (isset($_POST['curso_id'])) {
        $curso_id = intval($_POST['curso_id']);
    } elseif (isset($_GET['id'])) {
        $curso_id = intval($_GET['id']);
    }

    if ($curso_id <= 0) {
        header('Location: ../cursos/mis-cursos.php');
        exit;
    }

    $rol_usuario = '';
    if (isset($_SESSION['usuario_rol'])) {
        $rol_usuario = $_SESSION['usuario_rol'];
    } elseif (isset($_SESSION['usuario']['rol'])) {
        $rol_usuario = $_SESSION['usuario']['rol'];
    }
    $es_admin = ($rol_usuario === 'admin');
    $filtro_docente = $es_admin ? "" : "AND docente_id = $usuario_id";

    $resVerif = mysqli_query($conexion, "SELECT id, imagen FROM curso WHERE id = $curso_id $filtro_docente LIMIT 1");
    $cursoRow = $resVerif ? mysqli_fetch_assoc($resVerif) : null;

    if ($cursoRow) {
        $resMats = mysqli_query($conexion, "SELECT ruta_archivo FROM material WHERE curso_id = $curso_id");
        if ($resMats) {
            while ($m = mysqli_fetch_assoc($resMats)) {
                if (!empty($m['ruta_archivo'])) {
                    @unlink(__DIR__ . '/../' . ltrim($m['ruta_archivo'], '/'));
                }
            }
        }

        if (!empty($cursoRow['imagen']) && strpos($cursoRow['imagen'], 'placeholder') === false && strpos($cursoRow['imagen'], 'http') !== 0) {
            @unlink(__DIR__ . '/../' . ltrim($cursoRow['imagen'], '/'));
        }

        mysqli_query($conexion, "DELETE r FROM respuesta r JOIN pregunta p ON r.pregunta_id = p.id WHERE p.curso_id = $curso_id");
        mysqli_query($conexion, "DELETE FROM pregunta WHERE curso_id = $curso_id");
        mysqli_query($conexion, "DELETE FROM material WHERE curso_id = $curso_id");
        mysqli_query($conexion, "DELETE FROM inscripcion WHERE curso_id = $curso_id");
        mysqli_query($conexion, "DELETE FROM curso WHERE id = $curso_id");

        $_SESSION['exito'] = 'El curso ha sido eliminado con éxito.';
    } else {
        $_SESSION['error'] = 'No tenés permisos para eliminar este curso o ya no existe.';
    }

    header('Location: ../cursos/mis-cursos.php');
    exit;
}

header('Location: ../cursos/mis-cursos.php');
exit;
