<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
$es_pro = false;
if ($usuario_id > 0) {
    $es_pro = usuario_es_pro($usuario_id);
}
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : [];

$conexion = obtener_conexion();

$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$cid_seguro = intval($curso_id);
$resCurso = mysqli_query($conexion, "
    SELECT c.*, 
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.email AS docente_email, 
           u.biografia AS docente_bio, u.avatar AS docente_avatar, u.plan AS docente_plan,
           cat.nombre AS categoria_nombre, cat.icono AS categoria_icono
    FROM curso c
    LEFT JOIN usuario u ON c.docente_id = u.id
    LEFT JOIN categoria cat ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug))
    WHERE c.id = $cid_seguro
    LIMIT 1
");
$cursoBD = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

if ($cursoBD) {
    $tagsRaw = '';
    if (isset($cursoBD['etiquetas'])) {
        $tagsRaw = $cursoBD['etiquetas'];
    }
    $tagsArr = [];
    if (!empty($tagsRaw)) {
        $partesTags = explode(',', $tagsRaw);
        foreach ($partesTags as $pTag) {
            $pTag = trim($pTag);
            if ($pTag !== '') {
                $tagsArr[] = $pTag;
            }
        }
    }
    if (empty($tagsArr)) {
        $tagsArr = ['#Cátedra', '#Cursolandia'];
    }

  
    $resMat = mysqli_query($conexion, "SELECT * FROM material WHERE curso_id = $cid_seguro ORDER BY id ASC");
    $materialesDB = [];
    if ($resMat) {
        while ($mRow = mysqli_fetch_assoc($resMat)) {
            $materialesDB[] = $mRow;
        }
    }
    $materialesFormateados = [];
    foreach ($materialesDB as $m) {
        $mArch = isset($m['nombre_archivo']) ? $m['nombre_archivo'] : (isset($m['ruta_archivo']) ? $m['ruta_archivo'] : '');
        $ext = strtolower(pathinfo($mArch, PATHINFO_EXTENSION));
        $clase_ico = 'material-icono-pdf';
        $ico = 'bi-file-earmark-text';
        if ($ext === 'pdf') {
            $clase_ico = 'material-icono-pdf';
            $ico = 'bi-file-earmark-pdf-fill';
        } elseif ($ext === 'zip' || $ext === 'rar' || $ext === 'tar') {
            $clase_ico = 'material-icono-zip';
            $ico = 'bi-file-earmark-zip-fill';
        } elseif ($ext === 'sql') {
            $clase_ico = 'material-icono-txt';
            $ico = 'bi-database-fill';
        } elseif ($ext === 'xls' || $ext === 'xlsx') {
            $clase_ico = 'material-icono-excel';
            $ico = 'bi-file-earmark-excel-fill';
        }

        $mTam = isset($m['tamano_mb']) ? $m['tamano_mb'] : 1.5;
        $pesoMB = $mTam . ' MB';

        $materialesFormateados[] = [
            'id'          => $m['id'],
            'nombre'      => $m['nombre_archivo'],
            'descripcion' => 'Material oficial de la cátedra.',
            'tipo'        => strtoupper($ext) . ' Documento',
            'clase_ico'   => $clase_ico,
            'icono'       => $ico,
            'peso'        => $pesoMB,
            'fecha'       => date('d M Y', strtotime(isset($m['fecha_subida']) ? $m['fecha_subida'] : 'now')),
            'ruta'        => $m['ruta_archivo']
        ];
    }

    $dNom = isset($cursoBD['docente_nombre']) ? $cursoBD['docente_nombre'] : '';
    $dApe = isset($cursoBD['docente_apellido']) ? $cursoBD['docente_apellido'] : '';
    $docenteNombreCompleto = trim($dNom . ' ' . $dApe);
    if (empty($docenteNombreCompleto)) $docenteNombreCompleto = 'Docente Cursolandia';

    $objetivosCurso = [];
    if (!empty($cursoBD['objetivos'])) {
        $lineasObj = explode("\n", $cursoBD['objetivos']);
        foreach ($lineasObj as $lObj) {
            $lObj = trim($lObj);
            if ($lObj !== '') {
                $objetivosCurso[] = $lObj;
            }
        }
    }
    if (empty($objetivosCurso)) {
        $objetivosCurso = [
            'Dominar los conceptos teóricos y prácticos del temario de la cátedra.',
            'Desarrollar proyectos aplicados con buenas prácticas profesionales.',
            'Participar en la comunidad y foros de debate resolviendo casos reales.'
        ];
    }

    $modulosCurso = [];
    if (!empty($cursoBD['modulos_contenido'])) {
        $parsedMods = json_decode($cursoBD['modulos_contenido'], true);
        if (is_array($parsedMods)) {
            $modulosCurso = $parsedMods;
        }
    }
    if (empty($modulosCurso)) {
        $modulosCurso = [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Introducción y Fundamentos',
                'duracion' => '2h 00m',
                'clases'   => [
                    ['nombre' => 'Presentación de la cátedra y objetivos de formación', 'duracion' => '45 min', 'gratis' => true],
                    ['nombre' => 'Configuración del entorno de trabajo y herramientas', 'duracion' => '75 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Práctica Aplicada y Proyecto Final',
                'duracion' => '2h 30m',
                'clases'   => [
                    ['nombre' => 'Desarrollo guiado del trabajo práctico integrador', 'duracion' => '90 min', 'gratis' => false],
                    ['nombre' => 'Conclusiones y próximos pasos profesionales', 'duracion' => '60 min', 'gratis' => false]
                ]
            ]
        ];
    }

    $requisitosCurso = [];
    if (!empty($cursoBD['requisitos'])) {
        $lineasReq = explode("\n", $cursoBD['requisitos']);
        foreach ($lineasReq as $lReq) {
            $lReq = trim($lReq);
            if ($lReq !== '') {
                $requisitosCurso[] = $lReq;
            }
        }
    }
    if (empty($requisitosCurso)) {
        $requisitosCurso = [
            'Conocimientos básicos del área.',
            'Computadora con conexión a internet y software necesario para las prácticas.'
        ];
    }

    $docenteId = (int)$cursoBD['docente_id'];


    $resTotalCursos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM curso WHERE docente_id = $docenteId AND estado != 'eliminado'");
    $filaCursos = $resTotalCursos ? mysqli_fetch_assoc($resTotalCursos) : null;
    $totalCursosDocente = $filaCursos ? intval($filaCursos['total']) : 0;


    $resTotalAlumnos = mysqli_query($conexion, "
        SELECT COUNT(DISTINCT i.usuario_id) AS total 
        FROM inscripcion i 
        JOIN curso c ON i.curso_id = c.id 
        WHERE c.docente_id = $docenteId 
          AND i.usuario_id != $docenteId 
          AND i.estado IN ('activa', 'pendiente')
    ");
    $filaAlumnos = $resTotalAlumnos ? mysqli_fetch_assoc($resTotalAlumnos) : null;
    $totalAlumnosDocente = $filaAlumnos ? intval($filaAlumnos['total']) : 0;

    $docenteRating = 0;

    $resPregDoc = mysqli_query($conexion, "
        SELECT COUNT(*) AS total 
        FROM pregunta p 
        JOIN curso c ON p.curso_id = c.id 
        WHERE c.docente_id = $docenteId AND p.usuario_id != $docenteId
    ");
    $filaPregDoc = $resPregDoc ? mysqli_fetch_assoc($resPregDoc) : null;
    $totalPreguntasDocente = $filaPregDoc ? intval($filaPregDoc['total']) : 0;

    $tasaRespuestaDocente = '0%';
    if ($totalPreguntasDocente > 0) {
        $resRespDoc = mysqli_query($conexion, "
            SELECT COUNT(DISTINCT p.id) AS respondidas 
            FROM pregunta p 
            JOIN curso c ON p.curso_id = c.id 
            JOIN respuesta r ON r.pregunta_id = p.id 
            WHERE c.docente_id = $docenteId 
              AND p.usuario_id != $docenteId 
              AND r.usuario_id = $docenteId
        ");
        $filaRespDoc = $resRespDoc ? mysqli_fetch_assoc($resRespDoc) : null;
        $respondidas = $filaRespDoc ? intval($filaRespDoc['respondidas']) : 0;

        $tasaRespuestaDocente = round(($respondidas / $totalPreguntasDocente) * 100) . '%';
    }

    $curso = [
        'id'               => (int)$cursoBD['id'],
        'titulo'           => $cursoBD['titulo'],
        'docente_id'       => (int)$cursoBD['docente_id'],
        'docente'          => $docenteNombreCompleto,
        'docente_avatar'   => !empty($cursoBD['docente_avatar']) ? $cursoBD['docente_avatar'] : null,
        'docente_titulo'   => ($cursoBD['docente_plan'] === 'pro') ? 'Docente Titular · Plan PRO' : 'Docente Titular',
        'docente_bio'      => !empty($cursoBD['docente_bio']) ? $cursoBD['docente_bio'] : 'Docente titular en Cursolandia enfocado en la enseñanza práctica de competencias profesionales de vanguardia.',
        'docente_stats'    => [
            'rating'     => $docenteRating,
            'alumnos'    => $totalAlumnosDocente,
            'cursos'     => $totalCursosDocente,
            'respuestas' => $tasaRespuestaDocente
        ],
        'categoria'        => !empty($cursoBD['categoria']) ? $cursoBD['categoria'] : (isset($cursoBD['categoria_nombre']) ? $cursoBD['categoria_nombre'] : 'general'),
        'categoria_nombre' => !empty($cursoBD['categoria']) ? $cursoBD['categoria'] : (isset($cursoBD['categoria_nombre']) ? $cursoBD['categoria_nombre'] : 'General'),
        'icono'            => !empty($cursoBD['icono']) ? $cursoBD['icono'] : (!empty($cursoBD['categoria_icono']) ? $cursoBD['categoria_icono'] : 'bi-journal-code'),
        'imagen'           => obtener_portada_curso(isset($cursoBD['imagen']) ? $cursoBD['imagen'] : '', isset($cursoBD['categoria']) ? $cursoBD['categoria'] : '', (int)$cursoBD['id']),
        'nivel'            => 'Todos los niveles',
        'modalidad'        => ($cursoBD['tipo_acceso'] === 'privado') ? 'Acceso Privado' : 'Abierto a la Comunidad',
        'fecha'            => date('d/m/Y', strtotime($cursoBD['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($cursoBD['fecha_fin'])),
        'fecha_inicio'     => $cursoBD['fecha_inicio'],
        'fecha_fin'        => $cursoBD['fecha_fin'],
        'horario'          => 'Cursada Online Asincrónica',
        'duracion'         => '8 semanas (32 horas cátedra)',
        'inscriptos'       => 0,
        'cupo_max'         => (int)$cursoBD['cupo_max'],
        'precio'           => (float)$cursoBD['precio'],
        'tipo_precio'      => ((float)$cursoBD['precio'] > 0) ? 'pago' : 'gratis',
        'rating'           => 0,
        'resenas_cant'     => 0,
        'tags'             => $tagsArr,
        'descripcion'      => $cursoBD['descripcion'],
        'descripcion_larga'=> $cursoBD['descripcion'],
        'objetivos'        => $objetivosCurso,
        'requisitos'       => $requisitosCurso,
        'modulos'          => $modulosCurso,
        'materiales'       => $materialesFormateados
    ];
} else {
    $_SESSION['error'] = 'El curso solicitado no existe.';
    header('Location: ' . $base_url . 'cursos/index.php');
    exit;
}


$cidReal = intval($curso['id']);
$resCountIns = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM inscripcion WHERE curso_id = $cidReal AND estado IN ('activa', 'pendiente')");
$countRow = $resCountIns ? mysqli_fetch_assoc($resCountIns) : null;
$curso['inscriptos'] = $countRow ? intval($countRow['total']) : 0;


$esta_inscripto = false;
$estado_inscripcion = null;
if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    $resCheck = mysqli_query($conexion, "SELECT id, estado FROM inscripcion WHERE usuario_id = $uid_seguro AND curso_id = $cidReal AND estado != 'cancelada' LIMIT 1");
    $insRow = $resCheck ? mysqli_fetch_assoc($resCheck) : null;
    if ($insRow) {
        $esta_inscripto = true;
        $estado_inscripcion = $insRow['estado'];
    }
}

$rol_actual = '';
if (isset($_SESSION['usuario']['rol'])) {
    $rol_actual = $_SESSION['usuario']['rol'];
} elseif (isset($_SESSION['usuario_rol'])) {
    $rol_actual = $_SESSION['usuario_rol'];
}
$es_admin = ($rol_actual === 'admin');
$es_creador = ($usuario_id > 0 && isset($curso['docente_id']) && $usuario_id === (int)$curso['docente_id']);

$estado_curso = isset($curso['estado']) ? $curso['estado'] : 'publicado';
if ($estado_curso !== 'publicado' && !$es_admin && !$es_creador) {
    $_SESSION['error'] = 'Este curso se encuentra en proceso de revisión por el administrador y aún no está publicado.';
    header('Location: ' . $base_url . 'cursos/index.php');
    exit;
}

// Procesar aprobación/rechazo directo si quien actúa es el administrador
if ($es_admin && isset($_GET['accion_admin'])) {
    $accion_admin = trim($_GET['accion_admin']);
    $cid_admin = intval($curso['id']);
    if ($accion_admin === 'aprobar' && $cid_admin > 0) {
        mysqli_query($conexion, "UPDATE curso SET estado = 'publicado' WHERE id = $cid_admin");
        if (!empty($curso['docente_id'])) {
            enviar_notificacion(
                intval($curso['docente_id']),
                '¡Excelente noticia! El administrador aprobó tu curso "' . $curso['titulo'] . '" y ya se encuentra publicado en el catálogo.',
                'cursos/ver.php?id=' . $cid_admin
            );
        }
        $_SESSION['exito'] = 'El curso "' . htmlspecialchars($curso['titulo']) . '" fue aprobado exitosamente y ya se encuentra publicado.';
        header('Location: ver.php?id=' . $cid_admin);
        exit;
    } elseif ($accion_admin === 'rechazar' && $cid_admin > 0) {
        mysqli_query($conexion, "UPDATE curso SET estado = 'rechazado' WHERE id = $cid_admin");
        if (!empty($curso['docente_id'])) {
            enviar_notificacion(
                intval($curso['docente_id']),
                'Tu curso "' . $curso['titulo'] . '" fue rechazado. Podés editarlo para volver a enviarlo a revisión.',
                'cursos/editar.php?id=' . $cid_admin
            );
        }
        $_SESSION['exito'] = 'La propuesta del curso fue rechazada.';
        header('Location: ver.php?id=' . $cid_admin);
        exit;
    }
}

if (!isset($menu_activo)) {
    if ($es_creador) {
        $menu_activo = 'cursos';
    } elseif ($esta_inscripto) {
        $menu_activo = 'inscripciones';
    } else {
        $menu_activo = 'cursos';
    }
}

$total_modulos = count($curso['modulos']);
$total_clases = 0;
foreach ($curso['modulos'] as $m) {
    if (isset($m['clases']) && is_array($m['clases'])) {
        $total_clases += count($m['clases']);
    }
}

// tab activa (en la ficha pública solo se muestra temario e info del docente)
$tab_activa = isset($_GET['tab']) ? trim($_GET['tab']) : 'info';
if (!in_array($tab_activa, ['info', 'docente'])) {
    $tab_activa = 'info';
}

$cupos_restantes = $curso['cupo_max'] - $curso['inscriptos'];
if ($cupos_restantes < 0) {
    $cupos_restantes = 0;
}
$porcentaje_cupo = 0;
if ($curso['cupo_max'] > 0) {
    $porcentaje_cupo = round(($curso['inscriptos'] / $curso['cupo_max']) * 100);
    if ($porcentaje_cupo > 100) {
        $porcentaje_cupo = 100;
    }
}

$usuario_logueado = ($usuario_id > 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido">

        <div class="mini-banner-migas curso-migas-mb">
            <a href="<?php echo $base_url; ?><?php echo $usuario_logueado ? 'panel/index.php' : 'index.php'; ?>"><i class="bi bi-house-door"></i> Inicio</a>
            <span class="miga-separador">/</span>
            <a href="<?php echo $base_url; ?>cursos/index.php">Catálogo de Cursos</a>
            <span class="miga-separador">/</span>
            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=<?php echo urlencode($curso['categoria']); ?>"><?php echo htmlspecialchars($curso['categoria_nombre']); ?></a>
            <span class="miga-separador">/</span>
            <span class="miga-actual"><?php echo htmlspecialchars($curso['titulo']); ?></span>
        </div>

        <?php if (!empty($_SESSION['exito'])): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alerta-flash error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($estado_curso === 'pendiente'): ?>
            <div class="alerta-flash alerta-revision">
                <div class="alerta-contenido-izq">
                    <i class="bi bi-hourglass-split alerta-icono-revision"></i>
                    <div>
                        <strong class="alerta-titulo-revision">Curso en Proceso de Revisión</strong>
                        <p>
                            <?php if ($es_admin): ?>
                                Este curso fue presentado por el docente y está pendiente de tu aprobación para poder ser publicado en el catálogo de Cursolandia.
                            <?php else: ?>
                                Tu propuesta fue enviada exitosamente. El administrador la revisará a la brevedad para habilitar su publicación en el catálogo.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($es_admin): ?>
                    <div class="alerta-acciones-fila">
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=aprobar" class="btn-primario btn-alerta-accion" onclick="return confirm('¿Aprobar y publicar este curso en el catálogo ahora?');">
                            <i class="bi bi-check2-circle"></i> Aprobar Curso
                        </a>
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=rechazar" class="btn-secundario btn-alerta-peligro" onclick="return confirm('¿Rechazar esta propuesta de curso?');">
                            <i class="bi bi-x-circle"></i> Rechazar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($estado_curso === 'rechazado'): ?>
            <div class="alerta-flash alerta-rechazada">
                <div class="alerta-contenido-izq">
                    <i class="bi bi-x-octagon-fill alerta-icono-rechazada"></i>
                    <div>
                        <strong class="alerta-titulo-rechazada">Propuesta de curso rechazada</strong>
                        <p>
                            El administrador rechazó la propuesta de publicación. Podés editar el temario o los requisitos y volver a solicitar su revisión.
                        </p>
                    </div>
                </div>
                <div class="alerta-acciones-fila">
                    <?php if ($es_creador): ?>
                        <a href="<?php echo $base_url; ?>cursos/editar.php?id=<?php echo $curso['id']; ?>" class="btn-secundario btn-alerta-accion">
                            <i class="bi bi-pencil"></i> Modificar y Reenviar
                        </a>
                    <?php endif; ?>
                    <?php if ($es_admin): ?>
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=aprobar" class="btn-primario btn-alerta-accion" onclick="return confirm('¿Reconsiderar y publicar este curso en el catálogo?');">
                            <i class="bi bi-check2-circle"></i> Aprobar Curso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="curso-ver-layout">

            <div class="curso-col-principal">

                <article class="curso-hero-card">
                    <div class="curso-hero-pildoras-fila">
                        <span class="badge-tag-cat">
                            <i class="bi <?php echo htmlspecialchars($curso['icono']); ?>"></i>
                            <?php echo htmlspecialchars($curso['categoria_nombre']); ?>
                        </span>
                        <span class="badge-tag-nivel">
                            <i class="bi bi-reception-3"></i> Nivel <?php echo htmlspecialchars($curso['nivel']); ?>
                        </span>
                        <span class="badge-tag-modalidad">
                            <i class="bi bi-broadcast"></i> <?php echo htmlspecialchars($curso['modalidad']); ?>
                        </span>
                        <?php if (!empty($curso['resenas_cant']) && (int)$curso['resenas_cant'] > 0): ?>
                            <span class="badge-tag-rating">
                                <i class="bi bi-star-fill"></i> <?php echo htmlspecialchars($curso['rating']); ?>
                                <span class="rating-opiniones">(<?php echo htmlspecialchars($curso['resenas_cant']); ?> opiniones)</span>
                            </span>
                        <?php else: ?>
                            <span class="badge-tag-rating">
                                <i class="bi bi-star"></i> 0 <span class="rating-opiniones">(0 opiniones)</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="curso-hero-titulo"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                    <p class="curso-hero-desc"><?php echo htmlspecialchars($curso['descripcion']); ?></p>

                    <div class="curso-hero-meta-horizontal">
                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="curso-docente-chip">
                            <div class="docente-avatar-chip">
                                <?php if (!empty($curso['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($curso['docente_avatar'])); ?>" alt="Docente">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($curso['docente'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="docente-chip-info">
                                <span>Creador del curso</span>
                                <strong><?php echo htmlspecialchars($curso['docente']); ?></strong>
                            </div>
                        </a>

                        <div class="meta-item-pildora">
                            <i class="bi bi-calendar3"></i>
                            <span><strong>Período:</strong> <?php echo htmlspecialchars($curso['fecha']); ?></span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-clock-history"></i>
                            <span><strong>Duración:</strong> <?php echo htmlspecialchars($curso['duracion']); ?></span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-globe"></i>
                            <span>Español</span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-patch-check-fill icono-primario"></i>
                            <span>Certificado incluido</span>
                        </div>
                    </div>
                </article>

                <?php if (!empty($curso['objetivos'])): ?>
                    <section class="caja-que-aprenderas">
                        <h3><i class="bi bi-bullseye"></i> Lo que vas a aprender en este curso</h3>
                        <div class="grid-objetivos-moderno">
                            <?php foreach ($curso['objetivos'] as $obj): ?>
                                <div class="item-objetivo-fila">
                                    <span class="check-icono-circulo"><i class="bi bi-check2"></i></span>
                                    <span><?php echo htmlspecialchars($obj); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="curso-tabs-contenedor">
                    <nav class="curso-tabs-nav" aria-label="Navegación de secciones del curso">
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'info') ? 'activa' : ''; ?>" onclick="cambiarPestana('info', this)">
                            <i class="bi bi-journals"></i> Programa & Temario
                        </button>
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'docente') ? 'activa' : ''; ?>" onclick="cambiarPestana('docente', this)">
                            <i class="bi bi-person-badge"></i> Creador del Curso
                        </button>
                    </nav>

                    <div id="tab-panel-info" class="curso-tab-panel <?php echo ($tab_activa === 'info') ? 'activo' : ''; ?>">
                        
                        <div class="temario-resumen-barra">
                            <span class="temario-resumen-texto">
                                <strong><?php echo $total_modulos; ?> módulos</strong> · 
                                <strong><?php echo $total_clases; ?> clases en total</strong> · 
                                <?php echo htmlspecialchars($curso['duracion']); ?>
                            </span>
                            <button type="button" id="btn-toggle-todos" class="btn-toggle-todos" onclick="toggleTodosModulos()">
                                Expandir todos los módulos
                            </button>
                        </div>

                        <?php if (!empty($curso['modulos'])): ?>
                            <div class="temario-acordeon">
                                <?php foreach ($curso['modulos'] as $idx => $mod): ?>
                                    <article class="modulo-item">
                                        <button type="button" class="modulo-cabecera" onclick="toggleModulo(this)">
                                            <div class="modulo-titulo-lado">
                                                <i class="bi bi-chevron-down flecha-modulo" style="<?php echo ($idx === 0) ? 'transform: rotate(180deg);' : ''; ?>"></i>
                                                <span class="modulo-badge"><?php echo htmlspecialchars($mod['numero']); ?></span>
                                                <strong class="modulo-nombre"><?php echo htmlspecialchars($mod['titulo']); ?></strong>
                                            </div>
                                            <div class="modulo-meta-lado">
                                                <span><?php echo count($mod['clases']); ?> clases</span>
                                                <span>•</span>
                                                <span><?php echo htmlspecialchars(isset($mod['duracion']) ? $mod['duracion'] : '2h'); ?></span>
                                            </div>
                                        </button>
                                        <div class="modulo-cuerpo" style="<?php echo ($idx === 0) ? 'display: block;' : 'display: none;'; ?>">
                                            <ul class="modulo-lista-clases">
                                                <?php foreach ($mod['clases'] as $clase): ?>
                                                    <li class="clase-fila">
                                                        <div class="clase-info-izq">
                                                            <i class="bi bi-play-circle-fill clase-icono"></i>
                                                            <span class="clase-nombre"><?php echo htmlspecialchars($clase['nombre']); ?></span>
                                                            <?php if (!empty($clase['gratis'])): ?>
                                                                <span class="badge-clase-gratis"><i class="bi bi-unlock"></i> Clase Abierta</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="clase-duracion">
                                                            <i class="bi bi-clock"></i> <?php echo htmlspecialchars($clase['duracion']); ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="modulo-vacio-aviso">
                                <i class="bi bi-journal-text"></i>
                                <strong>El temario detallado de este curso se publicará próximamente.</strong>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($curso['requisitos'])): ?>
                            <div class="requisitos-caja">
                                <h4><i class="bi bi-list-check"></i> Requisitos previos recomendados</h4>
                                <ul class="requisitos-lista">
                                    <?php foreach ($curso['requisitos'] as $req): ?>
                                        <li><i class="bi bi-check-circle-fill"></i> <span><?php echo htmlspecialchars($req); ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($curso['descripcion_larga'])): ?>
                            <div class="curso-seccion-separador">
                                <h4><i class="bi bi-card-text"></i> Metodología y Proyecto Final</h4>
                                <p><?php echo htmlspecialchars($curso['descripcion_larga']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="curso-banner-aula-info">
                            <div class="alerta-contenido-izq">
                                <div class="icono-caja">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div>
                                    <strong>Materiales Descargables y Foro de Consultas</strong>
                                    <span>El contenido completo, guías de estudio, descargas y el foro de debate con el profesor están disponibles en el <strong>Aula Virtual</strong> una vez confirmada tu inscripción.</span>
                                </div>
                            </div>
                            <?php if ($esta_inscripto && $estado_inscripcion === 'activa'): ?>
                                <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-primario btn-aula-invitacion">
                                    <i class="bi bi-play-circle-fill"></i> Ir al Aula Virtual →
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div id="tab-panel-docente" class="curso-tab-panel <?php echo ($tab_activa === 'docente') ? 'activo' : ''; ?>">
                        <article class="docente-tarjeta-perfil">
                            <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="docente-perfil-avatar">
                                <?php if (!empty($curso['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($curso['docente_avatar'])); ?>" alt="Docente">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($curso['docente'], 0, 2)); ?>
                                <?php endif; ?>
                            </a>
                            <div class="docente-perfil-cuerpo">
                                <h4>
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="autor-nombre-link">
                                        <?php echo htmlspecialchars($curso['docente']); ?>
                                    </a>
                                </h4>
                                <span class="docente-perfil-sub"><?php echo htmlspecialchars($curso['docente_titulo']); ?></span>
                                
                                <div class="docente-perfil-stats">
                                    <span><i class="bi bi-star-fill"></i> <strong><?php echo isset($curso['docente_stats']['rating']) ? $curso['docente_stats']['rating'] : 0; ?></strong> Calificación del instructor</span>
                                    <span><i class="bi bi-people-fill"></i> <strong><?php echo number_format(isset($curso['docente_stats']['alumnos']) ? $curso['docente_stats']['alumnos'] : 0, 0, ',', '.'); ?></strong> Alumnos</span>
                                    <span><i class="bi bi-collection-play-fill"></i> <strong><?php echo (int)(isset($curso['docente_stats']['cursos']) ? $curso['docente_stats']['cursos'] : 0); ?></strong> Cursos</span>
                                    <span><i class="bi bi-chat-left-dots-fill"></i> <strong><?php echo isset($curso['docente_stats']['respuestas']) ? $curso['docente_stats']['respuestas'] : '0%'; ?></strong> Tasa de respuesta</span>
                                </div>

                                <p class="docente-perfil-bio"><?php echo htmlspecialchars($curso['docente_bio']); ?></p>

                                <div class="docente-acciones-wrap">
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="btn-secundario btn-alerta-accion">
                                        <i class="bi bi-person-badge"></i> Ver Perfil Completo del Instructor
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                </div>

            </div>

            <aside class="curso-col-sidebar">
                
        
                <div class="card-sidebar-matricula">
                    <div class="curso-sidebar-portada">
                        <?php 
            $vImg = isset($curso['imagen']) ? $curso['imagen'] : '';
            $vCat = isset($curso['categoria']) ? $curso['categoria'] : '';
            $imgVer = obtener_portada_curso($vImg, $vCat, (int)$curso['id']); 
            ?>
                        <img src="<?php echo htmlspecialchars($imgVer); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="curso-sidebar-img" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgVer); ?>';">
                        <div class="play-overlay-btn" title="Ver presentación del curso">
                            <i class="bi bi-play-fill"></i>
                        </div>
                        <div class="trailer-pill-inferior">
                            <i class="bi bi-play-circle-fill icono-primario"></i> Vista previa de la clase
                        </div>
                    </div>

                    <div class="sidebar-precio-caja">
                        <?php if ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0): ?>
                            <span class="precio-grande es-gratis">Gratis</span>
                            <span class="precio-sub-aclaracion">Acceso libre inmediato para toda la comunidad</span>
                        <?php else: ?>
                            <div class="sidebar-precio-fila">
                                <span class="precio-grande">$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></span>
                            </div>
                            <span class="precio-sub-aclaracion">Pago único · Acceso completo a todas las clases y materiales</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($es_creador): ?>
                        <div class="sidebar-docente-box">
                            <span class="sidebar-docente-badge">
                                <i class="bi bi-person-workspace"></i> Sos el creador de este curso
                            </span>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-docente-aula">
                                <i class="bi bi-mortarboard-fill"></i> Ir al Aula Virtual
                            </a>
                            <div class="sidebar-docente-acciones">
                                <a href="<?php echo $base_url; ?>cursos/alumnos.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora">
                                    <i class="bi bi-people"></i> Alumnos
                                </a>
                                <a href="<?php echo $base_url; ?>cursos/editar.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </div>
                        </div>
                    <?php elseif ($esta_inscripto): ?>
                        <?php if ($estado_inscripcion === 'pendiente'): ?>
                            <div class="sidebar-pendiente-box">
                                <span class="sidebar-pendiente-badge">
                                    <i class="bi bi-hourglass-split"></i> Inscripción Pendiente de Confirmación
                                </span>
                                <span class="sidebar-pendiente-texto">
                                    El docente confirmará tu pago para habilitar el acceso al Aula Virtual, materiales y foros.
                                </span>
                                <div class="pagar-acciones-grupo">
                                    <a href="<?php echo $base_url; ?>cursos/pagar.php?id=<?php echo $curso['id']; ?>" class="btn-primario btn-pago-accion-full">
                                        <i class="bi bi-credit-card"></i> Pagar / Subir Comprobante →
                                    </a>
                                    <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario btn-ver-inscripciones-sidebar">
                                        Ver en Mis Inscripciones
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="badge-inscripto-activo">
                                <i class="bi bi-patch-check-fill"></i> ¡Ya sos alumno de este curso!
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-primario btn-entrar-aula-sidebar">
                                <i class="bi bi-mortarboard-fill"></i> Entrar al Aula Virtual →
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario btn-ver-inscripciones-sidebar">
                                <i class="bi bi-journal-bookmark"></i> Ver en Mis Inscripciones
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($es_admin): ?>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-docente-aula">
                                <i class="bi bi-shield-lock-fill"></i> Acceder al Aula (Admin)
                            </a>
                        <?php elseif ($estado_curso !== 'publicado'): ?>
                            <div class="caja-no-disponible-sidebar">
                                <i class="bi bi-hourglass-split"></i>
                                <strong>Inscripciones no disponibles</strong>
                                <span class="texto-ayuda">El curso se encuentra en revisión y aún no está abierto al público.</span>
                            </div>
                        <?php elseif (!$usuario_logueado): ?>
                            <a href="<?php echo $base_url; ?>auth/registrarse.php" class="btn-inscribirme-accion">
                                <i class="bi bi-mortarboard-fill"></i>
                                <?php echo ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0) ? 'Inscribirme Gratis' : 'Inscribirme al Curso ($ ' . number_format($curso['precio'], 0, ',', '.') . ')'; ?>
                            </a>
                        <?php else: ?>
                            <form action="<?php echo $base_url; ?>controladores/inscripcion_controlador.php?accion=inscribir" method="POST">
                                <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
                                <button type="submit" class="btn-inscribirme-accion">
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <?php echo ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0) ? 'Inscribirme Gratis' : 'Inscribirme al Curso ($ ' . number_format($curso['precio'], 0, ',', '.') . ')'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="sidebar-cupo-bloque">
                        <div class="sidebar-cupo-fila">
                            <span>Inscriptos: <strong><?php echo $curso['inscriptos']; ?> / <?php echo $curso['cupo_max']; ?> alumnos</strong></span>
                            <span class="cupo-estado-tag">
                                <?php echo ($cupos_restantes > 0) ? 'Últimos lugares' : 'Agotado'; ?>
                            </span>
                        </div>
                        <div class="sidebar-cupo-barra">
                            <div class="sidebar-cupo-fill" style="width: <?php echo $porcentaje_cupo; ?>%;"></div>
                        </div>
                        <span class="cupo-ayuda-texto">
                            <?php if ($cupos_restantes > 0): ?>
                                ¡Quedan sólo <strong><?php echo $cupos_restantes; ?> vacantes disponibles</strong> para esta cohorte!
                            <?php else: ?>
                                Cupos agotados para esta cohorte.
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="garantia-bloque">
                        <i class="bi bi-shield-check"></i>
                        <span>Garantía de satisfacción de 7 días o reembolso total</span>
                    </div>
                </div>

                <div class="card-sidebar-detalles">
                    <h4><i class="bi bi-info-circle icono-primario"></i> Información de la Cursada</h4>
                    <ul class="ficha-tecnica-lista">
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-calendar3"></i> Período</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['fecha']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-clock"></i> Horarios</span>
                            <?php $cHorario = isset($curso['horario']) ? $curso['horario'] : 'A coordinar'; ?>
                    <span class="ficha-valor"><?php echo htmlspecialchars($cHorario); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-broadcast"></i> Modalidad</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['modalidad']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-stopwatch"></i> Carga Horaria</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['duracion']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-folder-check"></i> Recursos y Foro</span>
                            <span class="ficha-valor">Incluidos en Aula Virtual</span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-award"></i> Certificación</span>
                            <span class="ficha-valor">Oficial con validez</span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-infinity"></i> Acceso</span>
                            <span class="ficha-valor">Ilimitado de por vida</span>
                        </li>
                    </ul>

                    <div class="sidebar-compartir-wrap">
                        <button type="button" class="btn-sidebar-compartir" onclick="compartirCurso()">
                            <i class="bi bi-share-fill icono-primario"></i> Compartir este curso
                        </button>
                    </div>
                </div>

            </aside>

        </div>

    </main>

</div>

<script>
function cambiarPestana(idTab, boton) {
    var botones = document.querySelectorAll('.curso-tab-btn');
    for (var i = 0; i < botones.length; i++) {
        botones[i].classList.remove('activa');
    }
    
    var paneles = document.querySelectorAll('.curso-tab-panel');
    for (var j = 0; j < paneles.length; j++) {
        paneles[j].classList.remove('activo');
    }
    
    boton.classList.add('activa');
    var panelActivo = document.getElementById('tab-panel-' + idTab);
    if (panelActivo) {
        panelActivo.classList.add('activo');
    }
}

function toggleModulo(cabecera) {
    var cuerpo = cabecera.nextElementSibling;
    var flecha = cabecera.querySelector('.flecha-modulo');
    if (cuerpo.style.display === 'none' || cuerpo.style.display === '') {
        cuerpo.style.display = 'block';
        if (flecha) {
            flecha.style.transform = 'rotate(180deg)';
        }
    } else {
        cuerpo.style.display = 'none';
        if (flecha) {
            flecha.style.transform = 'rotate(0deg)';
        }
    }
}

var todosExpandidos = false;

function toggleTodosModulos() {
    todosExpandidos = !todosExpandidos;
    var cuerpos = document.querySelectorAll('.modulo-cuerpo');
    var flechas = document.querySelectorAll('.flecha-modulo');
    var btn = document.getElementById('btn-toggle-todos');
    
    for (var i = 0; i < cuerpos.length; i++) {
        if (todosExpandidos) {
            cuerpos[i].style.display = 'block';
        } else {
            cuerpos[i].style.display = 'none';
        }
    }
    
    for (var j = 0; j < flechas.length; j++) {
        if (todosExpandidos) {
            flechas[j].style.transform = 'rotate(180deg)';
        } else {
            flechas[j].style.transform = 'rotate(0deg)';
        }
    }
    
    if (btn) {
        if (todosExpandidos) {
            btn.textContent = 'Colapsar todos los módulos';
        } else {
            btn.textContent = 'Expandir todos los módulos';
        }
    }
}

function compartirCurso() {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(window.location.href);
    }
    alert('¡Enlace del curso copiado al portapapeles!');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
