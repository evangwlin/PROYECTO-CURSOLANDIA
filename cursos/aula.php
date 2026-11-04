<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/';

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para acceder al aula virtual del curso.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$conexion = obtener_conexion();
$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($curso_id <= 0) {
    header('Location: mis-inscripciones.php');
    exit;
}

$cid_seguro = intval($curso_id);
$uid_seguro = intval($usuario_id);


$resCurso = mysqli_query($conexion, "
    SELECT c.*, 
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, 
           u.avatar AS docente_avatar, u.biografia AS docente_bio, u.plan AS docente_plan,
           cat.nombre AS categoria_nombre, cat.icono AS categoria_icono
    FROM curso c
    LEFT JOIN usuario u ON c.docente_id = u.id
    LEFT JOIN categoria cat ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug))
    WHERE c.id = $cid_seguro
    LIMIT 1
");
$cursoBD = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

if (!$cursoBD) {
    $_SESSION['error'] = 'El curso solicitado no existe o fue despublicado.';
    header('Location: index.php');
    exit;
}


$es_creador = ($usuario_id === (int)$cursoBD['docente_id']);
$rol_sesion = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : (isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '');
$es_admin = ($rol_sesion === 'admin');

$resIns = mysqli_query($conexion, "SELECT * FROM inscripcion WHERE usuario_id = $uid_seguro AND curso_id = $cid_seguro LIMIT 1");
$miInscripcion = $resIns ? mysqli_fetch_assoc($resIns) : null;

$inscripcion_activa = ($miInscripcion && $miInscripcion['estado'] === 'activa');
$inscripcion_pendiente = ($miInscripcion && $miInscripcion['estado'] === 'pendiente');

if ($es_creador) {
    $menu_activo = 'cursos';
} elseif ($inscripcion_activa) {
    $menu_activo = 'inscripciones';
} else {
    $menu_activo = ($rol_sesion === 'docente') ? 'cursos' : 'inscripciones';
}

if (!$es_creador && !$es_admin && !$inscripcion_activa) {
    if ($inscripcion_pendiente) {
        $_SESSION['error'] = 'Tu inscripción aún está pendiente de confirmación de pago por el docente para poder acceder al aula.';
        header('Location: pagar.php?id=' . $curso_id);
        exit;
    } else {
        $_SESSION['error'] = 'Debés inscribirte y ser aceptado en el curso para acceder al aula virtual, sus materiales y su foro.';
        header('Location: ver.php?id=' . $curso_id);
        exit;
    }
}

$dNom = isset($cursoBD['docente_nombre']) ? $cursoBD['docente_nombre'] : '';
    $dApe = isset($cursoBD['docente_apellido']) ? $cursoBD['docente_apellido'] : '';
    $docNombre = trim($dNom . ' ' . $dApe);
if (empty($docNombre)) $docNombre = 'Docente';

$cEtiq = isset($cursoBD['etiquetas']) ? $cursoBD['etiquetas'] : '';
    $tags = [];
if (!empty($cEtiq)) {
    $partesEtiq = explode(',', $cEtiq);
    foreach ($partesEtiq as $pEtiq) {
        $pEtiq = trim($pEtiq);
        if ($pEtiq !== '') {
            $tags[] = $pEtiq;
        }
    }
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

$total_clases = 0;
foreach ($modulosCurso as $m) {
    $mClases = (isset($m['clases']) && is_array($m['clases'])) ? $m['clases'] : [];
        $total_clases += count($mClases);
}

$resMat = mysqli_query($conexion, "SELECT * FROM material WHERE curso_id = $cid_seguro ORDER BY id ASC");
$materialesDB = [];
if ($resMat) {
    while ($mRow = mysqli_fetch_assoc($resMat)) {
        $materialesDB[] = $mRow;
    }
}
$materiales = [];

foreach ($materialesDB as $m) {
    $mArch = isset($m['nombre_archivo']) ? $m['nombre_archivo'] : (isset($m['ruta_archivo']) ? $m['ruta_archivo'] : '');
        $ext = strtolower(pathinfo($mArch, PATHINFO_EXTENSION));
    $clase_ico = 'material-icono-pdf';
    $ico = 'bi-file-earmark-text';
    if (in_array($ext, ['pdf'])) { $clase_ico = 'material-icono-pdf'; $ico = 'bi-file-earmark-pdf-fill'; }
    elseif (in_array($ext, ['zip', 'rar', 'tar'])) { $clase_ico = 'material-icono-zip'; $ico = 'bi-file-earmark-zip-fill'; }
    elseif (in_array($ext, ['sql'])) { $clase_ico = 'material-icono-txt'; $ico = 'bi-database-fill'; }
    elseif (in_array($ext, ['xls', 'xlsx'])) { $clase_ico = 'material-icono-excel'; $ico = 'bi-file-earmark-excel-fill'; }

    $mTam = isset($m['tamano_mb']) ? $m['tamano_mb'] : 1.5;
        $pesoMB = $mTam . ' MB';
    $materiales[] = [
        'id'          => $m['id'],
        'nombre'      => $m['nombre_archivo'],
        'descripcion' => 'Material oficial de la cátedra para clases y trabajos prácticos.',
        'tipo'        => strtoupper($ext) . ' Documento',
        'clase_ico'   => $clase_ico,
        'icono'       => $ico,
        'peso'        => $pesoMB,
        'fecha'       => date('d M Y', strtotime(isset($m['fecha_subida']) ? $m['fecha_subida'] : 'now')),
        'ruta'        => $m['ruta_archivo']
    ];
}




$resPreg = mysqli_query($conexion, "
    SELECT p.*, u.nombre AS autor_nombre, u.apellido AS autor_apellido, u.avatar AS autor_avatar, u.rol AS autor_rol
    FROM pregunta p
    LEFT JOIN usuario u ON p.usuario_id = u.id
    WHERE p.curso_id = $cid_seguro
    ORDER BY p.destacada DESC, p.fecha_creacion DESC
");
$preguntasBD = [];
if ($resPreg) {
    while ($pRow = mysqli_fetch_assoc($resPreg)) {
        $preguntasBD[] = $pRow;
    }
}

$respuestasBD = [];
if (!empty($preguntasBD)) {
    $idsList = [];
    foreach ($preguntasBD as $pregItem) {
        $idsList[] = intval($pregItem['id']);
    }
    $pids = implode(',', $idsList);
    $resResp = mysqli_query($conexion, "
        SELECT r.*, u.nombre AS autor_nombre, u.apellido AS autor_apellido, u.avatar AS autor_avatar, u.rol AS autor_rol
        FROM respuesta r
        LEFT JOIN usuario u ON r.usuario_id = u.id
        WHERE r.pregunta_id IN ($pids)
        ORDER BY r.fecha_creacion ASC
    ");
    if ($resResp) {
        while ($r = mysqli_fetch_assoc($resResp)) {
            $respuestasBD[$r['pregunta_id']][] = $r;
        }
    }
}

$tab_activa = isset($_GET['tab']) ? trim($_GET['tab']) : 'contenido';
if (!in_array($tab_activa, ['contenido', 'materiales', 'foro', 'info'])) {
    $tab_activa = 'contenido';
}

$progreso_alumno = isset($miInscripcion['progreso_porcentaje']) ? (int)$miInscripcion['progreso_porcentaje'] : 45;
if ($es_creador) $progreso_alumno = 100;

$descOriginal = isset($cursoBD['descripcion']) ? trim($cursoBD['descripcion']) : '';
$descripcion_aula = !empty($descOriginal) ? $descOriginal : 'Sin descripción disponible para este curso.';

$titulo_pagina = 'Aula Virtual · ' . $cursoBD['titulo'];
$cImg = isset($cursoBD['imagen']) ? $cursoBD['imagen'] : '';
    $cCat = isset($cursoBD['categoria']) ? $cursoBD['categoria'] : '';
    $portada_curso = obtener_portada_curso($cImg, $cCat, $curso_id);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido aula-contenido">

        <div class="aula-contenedor-centrado">

        <div class="mini-banner-migas aula-header-migas">
            <a href="<?php echo $base_url; ?>panel/index.php"><i class="bi bi-house-door"></i> Inicio</a>
            <span class="miga-separador">/</span>
            <?php if ($menu_activo === 'cursos'): ?>
                <a href="<?php echo $base_url; ?>cursos/mis-cursos.php">Mis Cursos</a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php">Mis Inscripciones</a>
            <?php endif; ?>
            <span class="miga-separador">/</span>
            <span class="miga-actual"><?php echo htmlspecialchars($cursoBD['titulo']); ?></span>
            <span class="miga-separador">/</span>
            <strong class="aula-migas-tag"><i class="bi bi-mortarboard-fill"></i> Aula Virtual</strong>
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

        <header class="curso-hero-card aula-hero-card">
            <div class="aula-hero-borde-superior"></div>

            <div class="aula-hero-nav">
                <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso_id; ?>" class="btn-secundario btn-alerta-accion">
                    <i class="bi bi-info-circle"></i> Ficha del Curso
                </a>
                <?php if ($menu_activo === 'cursos'): ?>
                    <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="btn-secundario btn-alerta-accion">
                        <i class="bi bi-arrow-left"></i> Mis Cursos
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario btn-alerta-accion">
                        <i class="bi bi-arrow-left"></i> Mis Inscripciones
                    </a>
                <?php endif; ?>
            </div>

            <h1 class="curso-hero-titulo aula-hero-titulo">
                <?php echo htmlspecialchars($cursoBD['titulo']); ?>
            </h1>

            <?php if (!empty($cursoBD['subtitulo'])): ?>
                <p class="aula-hero-subtitulo">
                    <?php echo htmlspecialchars($cursoBD['subtitulo']); ?>
                </p>
            <?php endif; ?>

            <div class="aula-hero-desc">
                <p>
                    <?php echo nl2br(htmlspecialchars($descripcion_aula)); ?>
                </p>
            </div>

            <div class="aula-profesor-fila">
                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cursoBD['docente_id']; ?>" class="aula-profesor-enlace">
                    <div class="aula-profesor-avatar">
                        <?php if (!empty($cursoBD['docente_avatar'])): ?>
                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($cursoBD['docente_avatar'])); ?>" alt="Profesor">
                        <?php else: ?>
                            <?php echo strtoupper(substr($docNombre, 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="aula-profesor-rol">Profesor Titular</span>
                        <strong class="aula-profesor-nombre"><?php echo htmlspecialchars($docNombre); ?></strong>
                    </div>
                </a>

                <div class="aula-progreso-caja">
                    <div class="aula-progreso-cabecera">
                        <span>Progreso de cursada</span>
                        <strong><?php echo $progreso_alumno; ?>%</strong>
                    </div>
                    <div class="barra-progreso-contenedor">
                        <div class="barra-progreso-relleno" style="width: <?php echo $progreso_alumno; ?>%;"></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="curso-ver-layout">

            <div class="curso-col-principal">


                <div class="curso-tabs-contenedor">
                    <nav class="curso-tabs-nav" aria-label="Secciones del Aula Virtual">
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'contenido') ? 'activa' : ''; ?>" onclick="cambiarPestana('contenido', this)">
                            <i class="bi bi-play-circle-fill"></i> Clases & Contenido
                        </button>
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'materiales') ? 'activa' : ''; ?>" onclick="cambiarPestana('materiales', this)">
                            <i class="bi bi-folder2-open"></i> Materiales Descargables
                        </button>
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'foro') ? 'activa' : ''; ?>" onclick="cambiarPestana('foro', this)">
                            <i class="bi bi-chat-square-dots-fill"></i> Foro de Consultas
                        </button>
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'info') ? 'activa' : ''; ?>" onclick="cambiarPestana('info', this)">
                            <i class="bi bi-info-circle-fill"></i> Info de la Cátedra
                        </button>
                    </nav>

                    <div id="tab-panel-contenido" class="curso-tab-panel <?php echo ($tab_activa === 'contenido') ? 'activo' : ''; ?>">
                        
                        <div class="temario-resumen-barra">
                            <span class="temario-resumen-texto">
                                <strong><?php echo count($modulosCurso); ?> módulos</strong> · 
                                <strong><?php echo $total_clases; ?> clases grabadas y sincrónicas</strong>
                            </span>
                            <button type="button" id="btn-toggle-todos" class="btn-toggle-todos" onclick="toggleTodosModulos()">
                                Expandir todos los módulos
                            </button>
                        </div>

                        <div class="aula-video-card">
                            <div class="aula-video-reproductor">
                                <img src="<?php echo htmlspecialchars($portada_curso); ?>" alt="Clase en curso" class="aula-video-portada-fondo">
                                <div class="aula-video-overlay">
                                    <div class="aula-video-play-btn">
                                        <i class="bi bi-play-fill"></i>
                                    </div>
                                    <?php $claseActivaNom = isset($modulosCurso[0]['clases'][0]['nombre']) ? $modulosCurso[0]['clases'][0]['nombre'] : 'Introducción a la materia'; ?>
                <h3 class="aula-video-titulo">Clase Activa: <?php echo htmlspecialchars($claseActivaNom); ?></h3>
                                    <div class="aula-video-meta">
                                        <i class="bi bi-clock"></i>
                                        <?php $claseActivaDur = isset($modulosCurso[0]['clases'][0]['duracion']) ? $modulosCurso[0]['clases'][0]['duracion'] : '45 min'; ?>
                <span>Duración estimada: <?php echo htmlspecialchars($claseActivaDur); ?> · Aula Virtual Cursolandia</span>
                                    </div>
                                </div>
                            </div>
                            <div class="aula-video-pie">
                                <?php 
                                $modNum = isset($modulosCurso[0]['numero']) ? $modulosCurso[0]['numero'] : 'Módulo 1';
                                $modClaseNom = isset($modulosCurso[0]['clases'][0]['nombre']) ? $modulosCurso[0]['clases'][0]['nombre'] : 'Contenido en curso';
                                ?>
                                <span class="aula-video-badge"><?php echo htmlspecialchars($modNum); ?></span>
                                <span class="aula-video-clase-nombre"><?php echo htmlspecialchars($modClaseNom); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($modulosCurso)): ?>
                            <div class="temario-acordeon">
                                <?php foreach ($modulosCurso as $idx => $mod): ?>
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
                                                <?php foreach ($mod['clases'] as $cIdx => $clase): ?>
                                                    <li class="clase-fila">
                                                        <div class="clase-info-izq">
                                                            <i class="bi bi-play-circle-fill clase-icono"></i>
                                                            <span class="clase-nombre"><?php echo htmlspecialchars($clase['nombre']); ?></span>
                                                        </div>
                                                        <div class="clase-meta-der">
                                                            <span class="clase-duracion">
                                                                <i class="bi bi-clock"></i> <?php echo htmlspecialchars($clase['duracion']); ?>
                                                            </span>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="aula-vacio-box">
                                <i class="bi bi-journal-text"></i>
                                <strong>El contenido de las clases se cargará próximamente.</strong>
                                <p>El docente está preparando el temario y los recursos de esta cátedra.</p>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div id="tab-panel-materiales" class="curso-tab-panel <?php echo ($tab_activa === 'materiales') ? 'activo' : ''; ?>">
                        <div class="seccion-cabecera-flex">
                            <div>
                                <h3><i class="bi bi-folder2-open"></i> Materiales y Recursos Descargables</h3>
                                <p class="panel-subtitulo-sec">Descargá las diapositivas, guías en PDF, ejercicios prácticos y archivos de código de cada clase.</p>
                            </div>
                            <span class="badge-acceso-alumnos">
                                <i class="bi bi-shield-check"></i> Acceso Habilitado para Alumnos
                            </span>
                        </div>

                        <?php if (!empty($materiales)): ?>
                            <div class="materiales-grilla">
                                <?php foreach ($materiales as $mat): ?>
                                    <article class="material-card-item">
                                        <div class="material-izq-bloque">
                                            <div class="material-icono-caja <?php echo htmlspecialchars($mat['clase_ico']); ?>">
                                                <i class="bi <?php echo htmlspecialchars($mat['icono']); ?>"></i>
                                            </div>
                                            <div class="material-info-textos">
                                                <strong><?php echo htmlspecialchars($mat['nombre']); ?></strong>
                                                <?php $matTextoDesc = isset($mat['descripcion']) ? $mat['descripcion'] : (isset($mat['tipo']) ? $mat['tipo'] : ''); ?>
                                                <p><?php echo htmlspecialchars($matTextoDesc); ?> · <strong><?php echo htmlspecialchars($mat['peso']); ?></strong> · Subido el <?php echo htmlspecialchars($mat['fecha']); ?></p>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if (!empty($mat['ruta']) && $mat['ruta'] !== '#'): ?>
                                                <a href="<?php echo $base_url . ltrim($mat['ruta'], '/'); ?>" download="<?php echo htmlspecialchars($mat['nombre']); ?>" class="btn-descargar-archivo">
                                                    <i class="bi bi-download"></i> Descargar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="aula-vacio-box">
                                <i class="bi bi-folder2-open"></i>
                                <strong>Aún no hay materiales subidos</strong>
                                <p>El docente aún no ha compartido archivos adjuntos para este curso.</p>
                            </div>
                        <?php endif; ?>

                        <div class="aula-caja-descarga-info">
                            <i class="bi bi-cloud-arrow-down-fill"></i>
                            <span>Podés descargar todos los materiales y conservarlos para consulta offline en cualquier momento. Si necesitás un archivo adicional, solicitalo en el <strong>Foro de Consultas</strong>.</span>
                        </div>
                    </div>

                    <div id="tab-panel-foro" class="curso-tab-panel <?php echo ($tab_activa === 'foro') ? 'activo' : ''; ?>">
                        <div class="foro-consultas-wrap">
                            
                            <div>
                                <h3><i class="bi bi-chat-square-dots-fill"></i> Foro de Dudas y Debate</h3>
                                <p class="panel-subtitulo-sec">Espacio exclusivo de intercambio para alumnos del curso y el docente. Hacé tus preguntas y resolvé dudas de las clases.</p>
                            </div>

                            <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=crear_pregunta" method="POST" class="form-nueva-pregunta">
                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                <h4><i class="bi bi-pencil-square"></i> Publicar una nueva consulta en el foro</h4>
                                <div class="campo-grupo">
                                    <label for="titulo_pregunta">Título breve de tu duda</label>
                                    <input type="text" id="titulo_pregunta" name="titulo" class="input-control" placeholder="Ej: Error al conectar la base de datos en el Módulo 1" required>
                                </div>
                                <div class="campo-grupo">
                                    <label for="cuerpo_pregunta">Explicación detallada o fragmento de código</label>
                                    <textarea id="cuerpo_pregunta" name="cuerpo" class="input-control" rows="4" placeholder="Describí con detalle qué estás intentando hacer, qué resultado obtuviste y en qué clase te surgió la duda..." required></textarea>
                                </div>
                                <div class="form-acciones-der">
                                    <button type="submit" class="btn-primario">
                                        <i class="bi bi-send-fill"></i> Publicar en el Foro
                                    </button>
                                </div>
                            </form>

                            <div class="lista-preguntas-foro">
                                <?php if (!empty($preguntasBD)): ?>
                                    <?php foreach ($preguntasBD as $preg): 
                                        $estaDestacada = !empty($preg['destacada']);
                                        $tieneAceptada = !empty($preg['id_respuesta_aceptada']) || !empty($preg['resuelta']);
                                        $resps = isset($respuestasBD[$preg['id']]) ? $respuestasBD[$preg['id']] : [];
                                        $esCreadorDuda = ($usuario_id === (int)$preg['usuario_id']);
                                        $puedeModerar = ($es_creador || $es_admin);
                                    ?>
                                        <article class="pregunta-item-card <?php echo $estaDestacada ? 'destacada-card' : ''; ?>" id="pregunta-<?php echo $preg['id']; ?>">
                                            
                                            <?php if ($estaDestacada): ?>
                                                <div class="badge-destacada-foro">
                                                    <i class="bi bi-star-fill"></i> Pregunta Destacada por la Cátedra
                                                </div>
                                            <?php endif; ?>

                                            <div class="pregunta-encabezado">
                                                <div class="pregunta-autor-info">
                                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$preg['usuario_id']; ?>" class="autor-avatar-mini">
                                                        <?php if (!empty($preg['autor_avatar'])): ?>
                                                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($preg['autor_avatar'])); ?>" alt="Avatar">
                                                        <?php else: ?>
                                                            <?php echo strtoupper(substr(isset($preg['autor_nombre']) ? $preg['autor_nombre'] : 'U', 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </a>
                                                    <div>
                                                        <strong class="autor-nombre-txt">
                                                            <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$preg['usuario_id']; ?>" class="autor-nombre-link">
                                                                <?php 
                                                        $pNom = isset($preg['autor_nombre']) ? $preg['autor_nombre'] : '';
                                                        $pApe = isset($preg['autor_apellido']) ? $preg['autor_apellido'] : '';
                                                        echo htmlspecialchars(trim($pNom . ' ' . $pApe)); 
                                                        ?>
                                                            </a>
                                                        </strong>
                                                        <?php if ((int)$preg['usuario_id'] === (int)$cursoBD['docente_id']): ?>
                                                            <span class="badge-autor-docente">Instructor</span>
                                                        <?php endif; ?>
                                                        <span class="pregunta-fecha-txt"><?php echo date('d M Y · H:i', strtotime($preg['fecha_creacion'])); ?></span>
                                                    </div>
                                                </div>

                                                <div class="pregunta-acciones-top">
                                                    <?php if ($tieneAceptada): ?>
                                                        <span class="badge-resuelta">
                                                            <i class="bi bi-check-circle-fill"></i> Resuelta
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if ($puedeModerar): ?>
                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=destacar_pregunta&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" class="btn-foro-accion btn-destacar-pregunta" title="<?php echo $estaDestacada ? 'Quitar destaque' : 'Destacar pregunta'; ?>">
                                                            <i class="bi bi-star"></i> <?php echo $estaDestacada ? 'Desmarcar' : 'Destacar'; ?>
                                                        </a>
                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=eliminar_pregunta&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" onclick="return confirm('¿Seguro que deseas eliminar esta pregunta?');" class="btn-foro-accion btn-eliminar-pregunta" title="Eliminar pregunta">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <h4 class="pregunta-titulo-txt"><?php echo htmlspecialchars($preg['titulo']); ?></h4>
                                            <?php $pTxt = isset($preg['contenido']) ? $preg['contenido'] : (isset($preg['cuerpo']) ? $preg['cuerpo'] : ''); ?>
                                            <p class="pregunta-cuerpo-txt"><?php echo nl2br(htmlspecialchars($pTxt)); ?></p>

                                            <?php if (!empty($resps)): ?>
                                                <div class="respuestas-hilo">
                                                    <h5 class="respuestas-hilo-titulo">
                                                        <i class="bi bi-reply-fill"></i> <?php echo count($resps); ?> <?php echo count($resps) === 1 ? 'Respuesta' : 'Respuestas'; ?>:
                                                    </h5>

                                                    <?php 
                                                    $ultimaResp = end($resps);
                                                    $ultimoUsuarioId = (int)$ultimaResp['usuario_id'];
                                                    foreach ($resps as $resp): 
                                                        $esAceptada = (bool)$resp['es_aceptada'];
                                                        $esPropia = ($usuario_id === (int)$resp['usuario_id']);
                                                    ?>
                                                        <div class="respuesta-item <?php echo $esAceptada ? 'respuesta-solucion-marcada' : ''; ?>" id="respuesta-<?php echo $resp['id']; ?>">
                                                            <div class="respuesta-header">
                                                                <div class="respuesta-autor">
                                                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$resp['usuario_id']; ?>" class="autor-avatar-mini avatar-resp-mini">
                                                                        <?php if (!empty($resp['autor_avatar'])): ?>
                                                                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($resp['autor_avatar'])); ?>" alt="Avatar">
                                                                        <?php else: ?>
                                                                            <?php echo strtoupper(substr(isset($resp['autor_nombre']) ? $resp['autor_nombre'] : 'U', 0, 1)); ?>
                                                                        <?php endif; ?>
                                                                    </a>
                                                                    <strong>
                                                                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$resp['usuario_id']; ?>" class="autor-nombre-link">
                                                                            <?php 
                                                                $rNom = isset($resp['autor_nombre']) ? $resp['autor_nombre'] : '';
                                                                $rApe = isset($resp['autor_apellido']) ? $resp['autor_apellido'] : '';
                                                                echo htmlspecialchars(trim($rNom . ' ' . $rApe)); 
                                                                ?>
                                                                        </a>
                                                                    </strong>
                                                                    <?php if ((int)$resp['usuario_id'] === (int)$cursoBD['docente_id']): ?>
                                                                        <span class="badge-autor-docente">Instructor</span>
                                                                    <?php endif; ?>
                                                                    <span class="pregunta-fecha-txt"><?php echo date('d M Y · H:i', strtotime($resp['fecha_creacion'])); ?></span>
                                                                </div>

                                                                <div class="respuesta-acciones-fila">
                                                                    <?php if ($esAceptada): ?>
                                                                        <span class="badge-solucion-oficial">
                                                                            <i class="bi bi-check-circle-fill"></i> Solución Aceptada
                                                                        </span>
                                                                    <?php elseif (!$tieneAceptada && ($esCreadorDuda || $puedeModerar)): ?>
                                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=aceptar_respuesta&respuesta_id=<?php echo $resp['id']; ?>&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" class="btn-aceptar-solucion" title="Aceptar esta respuesta como solución">
                                                                            <i class="bi bi-check2"></i> Marcar como Solución
                                                                        </a>
                                                                    <?php endif; ?>

                                                                    <?php if ($esPropia && !$tieneAceptada): ?>
                                                                        <button type="button" class="btn-foro-accion btn-destacar-pregunta" onclick="document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display = (document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display === 'none' ? 'block' : 'none');">
                                                                            <i class="bi bi-pencil"></i> Editar
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <p class="respuesta-cuerpo-txt"><?php echo nl2br(htmlspecialchars($resp['contenido'])); ?></p>

                                                            <?php if ($esPropia && !$tieneAceptada): ?>
                                                                <div id="edit-resp-<?php echo $resp['id']; ?>" class="form-editar-respuesta-box" style="display: none;">
                                                                    <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=editar_respuesta" method="POST">
                                                                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                                                        <input type="hidden" name="pregunta_id" value="<?php echo $preg['id']; ?>">
                                                                        <input type="hidden" name="respuesta_id" value="<?php echo $resp['id']; ?>">
                                                                        <textarea name="contenido" class="input-control input-editar-resp" rows="2" required><?php echo htmlspecialchars($resp['contenido']); ?></textarea>
                                                                        <div class="form-acciones-der">
                                                                            <button type="button" class="btn-secundario btn-editar-cancelar" onclick="document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display='none';">Cancelar</button>
                                                                            <button type="submit" class="btn-primario btn-editar-guardar">Guardar cambios</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php $ultimoUsuarioId = 0; ?>
                                            <?php endif; ?>

                                            <?php if (!$tieneAceptada): ?>
                                                <?php if ($ultimoUsuarioId === $usuario_id): ?>
                                                    <div class="aviso-duda-cerrada">
                                                        <i class="bi bi-info-circle-fill"></i> Ya publicaste la última respuesta en este hilo. Podés editar tu respuesta previa o aguardar a que responda otro compañero o el docente.
                                                    </div>
                                                <?php else: ?>
                                                    <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=responder" method="POST" class="form-responder-rapido">
                                                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                                        <input type="hidden" name="pregunta_id" value="<?php echo $preg['id']; ?>">
                                                        <input type="text" name="contenido" class="input-control input-responder-rapido" placeholder="Escribí una respuesta a esta duda..." required>
                                                        <button type="submit" class="btn-primario btn-responder-rapido">
                                                            <i class="bi bi-reply-fill"></i> Responder
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </article>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="aula-vacio-box">
                                        <i class="bi bi-chat-dots"></i>
                                        <strong>Todavía no hay consultas en el foro de este curso.</strong>
                                        <p>¡Sé el primero en iniciar una consulta para la comunidad y el docente!</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <div id="tab-panel-info" class="curso-tab-panel <?php echo ($tab_activa === 'info') ? 'activo' : ''; ?>">
                        <article class="docente-tarjeta-perfil">
                            <div class="docente-perfil-avatar">
                                <?php if (!empty($cursoBD['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($cursoBD['docente_avatar'])); ?>" alt="Docente">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($docNombre, 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="docente-perfil-cuerpo">
                                <h4><?php echo htmlspecialchars($docNombre); ?></h4>
                                <span class="docente-perfil-sub"><?php echo ($cursoBD['docente_plan'] === 'pro') ? 'Docente Titular · Plan PRO' : 'Docente Titular'; ?></span>
                                <p class="docente-perfil-bio"><?php echo htmlspecialchars($cursoBD['docente_bio'] ?: 'Profesor titular con amplia trayectoria en la enseñanza práctica de competencias universitarias y profesionales.'); ?></p>
                            </div>
                        </article>

                        <div class="caja-que-aprenderas">
                            <h3><i class="bi bi-journal-text"></i> Acerca de este curso y lineamientos</h3>
                            <p class="aula-desc-cursada"><?php echo nl2br(htmlspecialchars($cursoBD['descripcion'])); ?></p>
                            
                            <h4 class="subtitulo-seccion-cursada"><i class="bi bi-calendar3"></i> Fechas y Cursada</h4>
                            <div class="aula-docente-fechas-grid">
                                <div>
                                    <span>Período Lectivo</span>
                                    <strong><?php echo date('d/m/Y', strtotime($cursoBD['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($cursoBD['fecha_fin'])); ?></strong>
                                </div>
                                <div>
                                    <span>Modalidad</span>
                                    <strong>Online Asincrónica</strong>
                                </div>
                                <div>
                                    <span>Certificado Oficial</span>
                                    <strong>Al completar el 100%</strong>
                                </div>
                            </div>

                            <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso_id; ?>" class="btn-secundario btn-alerta-accion">
                                <i class="bi bi-arrow-up-right-square"></i> Ver Ficha Pública Informativa
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>

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
    
    if (boton) {
        boton.classList.add('activa');
    } else {
        for (var k = 0; k < botones.length; k++) {
            var onclickAttr = botones[k].getAttribute('onclick');
            if (onclickAttr && onclickAttr.indexOf("'" + idTab + "'") !== -1) {
                botones[k].classList.add('activa');
            }
        }
    }

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

window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash && window.location.hash.indexOf('#pregunta-') === 0) {
        var botones = document.querySelectorAll('.curso-tab-btn');
        if (botones.length > 2) {
            cambiarPestana('foro', botones[2]);
        }
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
