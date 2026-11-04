<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/';

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
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

// carga curso de la bd
$resCurso = mysqli_query($conexion, "
    SELECT c.*, 
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, 
           u.avatar AS docente_avatar, u.biografia AS docente_bio, u.plan AS docente_plan,
           cat.nombre AS categoria_nombre, cat.icono AS categoria_icono
    FROM Curso c
    LEFT JOIN Usuario u ON c.docente_id = u.id
    LEFT JOIN Categoria cat ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug))
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
$es_admin = (($_SESSION['usuario']['rol'] ?? '') === 'admin');

$resIns = mysqli_query($conexion, "SELECT * FROM Inscripcion WHERE usuario_id = $uid_seguro AND curso_id = $cid_seguro LIMIT 1");
$miInscripcion = $resIns ? mysqli_fetch_assoc($resIns) : null;

$inscripcion_activa = ($miInscripcion && $miInscripcion['estado'] === 'activa');
$inscripcion_pendiente = ($miInscripcion && $miInscripcion['estado'] === 'pendiente');

if ($es_creador) {
    $menu_activo = 'cursos';
} elseif ($inscripcion_activa) {
    $menu_activo = 'inscripciones';
} else {
    $menu_activo = (($_SESSION['usuario']['rol'] ?? '') === 'docente') ? 'cursos' : 'inscripciones';
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

$docNombre = trim(($cursoBD['docente_nombre'] ?? '') . ' ' . ($cursoBD['docente_apellido'] ?? ''));
if (empty($docNombre)) $docNombre = 'Carlos Gómez';

$tags = array_filter(array_map('trim', explode(',', $cursoBD['etiquetas'] ?? '')));
if (empty($tags)) $tags = ['#Cátedra', '#AulaVirtual'];

// modulos y clases
$modulosCurso = [
    [
        'numero'   => 'Módulo 1',
        'titulo'   => 'Introducción y Fundamentos de la Materia',
        'duracion' => '2h 00m',
        'clases'   => [
            ['nombre' => 'Presentación de la cátedra y objetivos de formación', 'duracion' => '45 min', 'gratis' => true],
            ['nombre' => 'Instalación de herramientas y configuración del entorno de trabajo', 'duracion' => '75 min', 'gratis' => false]
        ]
    ],
    [
        'numero'   => 'Módulo 2',
        'titulo'   => 'Práctica Aplicada y Proyecto de Cátedra',
        'duracion' => '3h 30m',
        'clases'   => [
            ['nombre' => 'Desarrollo guiado del trabajo práctico integrador', 'duracion' => '90 min', 'gratis' => false],
            ['nombre' => 'Evaluación de casos de estudio y conclusiones finales', 'duracion' => '120 min', 'gratis' => false]
        ]
    ]
];
if (!empty($cursoBD['modulos_contenido'])) {
    $parsedMods = json_decode($cursoBD['modulos_contenido'], true);
    if (is_array($parsedMods) && !empty($parsedMods)) {
        $modulosCurso = $parsedMods;
    }
}

$total_clases = 0;
foreach ($modulosCurso as $m) {
    $total_clases += count($m['clases'] ?? []);
}

$resMat = mysqli_query($conexion, "SELECT * FROM Material WHERE curso_id = $cid_seguro ORDER BY id ASC");
$materialesDB = [];
if ($resMat) {
    while ($mRow = mysqli_fetch_assoc($resMat)) {
        $materialesDB[] = $mRow;
    }
}
$materiales = [];

foreach ($materialesDB as $m) {
    $ext = strtolower(pathinfo($m['nombre_archivo'] ?? $m['ruta_archivo'], PATHINFO_EXTENSION));
    $clase_ico = 'material-icono-pdf';
    $ico = 'bi-file-earmark-text';
    if (in_array($ext, ['pdf'])) { $clase_ico = 'material-icono-pdf'; $ico = 'bi-file-earmark-pdf-fill'; }
    elseif (in_array($ext, ['zip', 'rar', 'tar'])) { $clase_ico = 'material-icono-zip'; $ico = 'bi-file-earmark-zip-fill'; }
    elseif (in_array($ext, ['sql'])) { $clase_ico = 'material-icono-txt'; $ico = 'bi-database-fill'; }
    elseif (in_array($ext, ['xls', 'xlsx'])) { $clase_ico = 'material-icono-excel'; $ico = 'bi-file-earmark-excel-fill'; }

    $pesoMB = ($m['tamano_mb'] ?? 1.5) . ' MB';
    $materiales[] = [
        'id'          => $m['id'],
        'nombre'      => $m['nombre_archivo'],
        'descripcion' => 'Material oficial de la cátedra para clases y trabajos prácticos.',
        'tipo'        => strtoupper($ext) . ' Documento',
        'clase_ico'   => $clase_ico,
        'icono'       => $ico,
        'peso'        => $pesoMB,
        'fecha'       => date('d M Y', strtotime($m['fecha_subida'] ?? 'now')),
        'ruta'        => $m['ruta_archivo']
    ];
}

if (empty($materiales)) {
    $materiales = [
        [
            'id'          => 1,
            'nombre'      => 'Guia_Didactica_de_la_Materia.pdf',
            'descripcion' => 'Cronograma de cursada, metodología de evaluación y bibliografía obligatoria.',
            'tipo'        => 'PDF Documento',
            'clase_ico'   => 'material-icono-pdf',
            'icono'       => 'bi-file-earmark-pdf-fill',
            'peso'        => '2.4 MB',
            'fecha'       => 'Reciente',
            'ruta'        => '#'
        ],
        [
            'id'          => 2,
            'nombre'      => 'Ejercicios_Practicos_Modulo_1_y_2.zip',
            'descripcion' => 'Archivos de partida, código base y consignas de los trabajos prácticos.',
            'tipo'        => 'ZIP Archivo',
            'clase_ico'   => 'material-icono-zip',
            'icono'       => 'bi-file-earmark-zip-fill',
            'peso'        => '5.8 MB',
            'fecha'       => 'Reciente',
            'ruta'        => '#'
        ]
    ];
}

// consulta preguntas del foro en bd
$resPreg = mysqli_query($conexion, "
    SELECT p.*, u.nombre AS autor_nombre, u.apellido AS autor_apellido, u.avatar AS autor_avatar, u.rol AS autor_rol
    FROM Pregunta p
    LEFT JOIN Usuario u ON p.usuario_id = u.id
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
    $pids = implode(',', array_map('intval', array_column($preguntasBD, 'id')));
    $resResp = mysqli_query($conexion, "
        SELECT r.*, u.nombre AS autor_nombre, u.apellido AS autor_apellido, u.avatar AS autor_avatar, u.rol AS autor_rol
        FROM Respuesta r
        LEFT JOIN Usuario u ON r.usuario_id = u.id
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

$progreso_alumno = (int)($miInscripcion['progreso_porcentaje'] ?? 45);
if ($es_creador) $progreso_alumno = 100;

$descOriginal = trim($cursoBD['descripcion'] ?? '');
$descripcion_aula = $descOriginal;
if (empty($descripcion_aula) || strlen($descripcion_aula) < 140) {
    $extraDesc = " Este trayecto pedagógico profundiza en la práctica aplicada y la adquisición de competencias profesionales, brindando clases paso a paso, código de partida y acompañamiento continuo de la cátedra para el desarrollo de proyectos integradores.";
    $descripcion_aula = !empty($descripcion_aula) ? ($descripcion_aula . $extraDesc) : $extraDesc;
}

$titulo_pagina = 'Aula Virtual · ' . $cursoBD['titulo'];
$portada_curso = obtener_portada_curso($cursoBD['imagen'] ?? '', $cursoBD['categoria'] ?? '', $curso_id);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.aula-contenedor-centrado {
    max-width: 1000px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

@media (min-width: 1200px) {
    .dashboard-contenido.aula-contenido {
        padding: 36px 54px 60px 54px;
    }
}

@media (max-width: 768px) {
    .aula-contenedor-centrado {
        max-width: 100%;
        gap: 16px;
    }
}
</style>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido aula-contenido">

        <div class="aula-contenedor-centrado">

        <div class="mini-banner-migas" style="margin-bottom: 8px;">
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
            <strong style="color: var(--color-text-muted); font-weight: 700;"><i class="bi bi-mortarboard-fill"></i> Aula Virtual</strong>
        </div>

        <?php if (!empty($_SESSION['exito'])): ?>
            <div class="alerta-flash exito" style="margin-bottom: 16px;">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alerta-flash error" style="margin-bottom: 16px; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem; color: #ef4444;"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- header aula -->
        <header class="curso-hero-card aula-hero-card" style="padding: 28px 34px; margin-bottom: 24px; border-radius: 20px; background: #ffffff; border: 1px solid var(--border-color); box-shadow: 0 4px 20px rgba(28, 25, 23, 0.04); position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--gradient-brand);"></div>

            <!-- nav -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-bottom: 12px;">
                <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso_id; ?>" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-info-circle"></i> Ficha del Curso
                </a>
                <?php if ($menu_activo === 'cursos'): ?>
                    <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-arrow-left"></i> Mis Cursos
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-arrow-left"></i> Mis Inscripciones
                    </a>
                <?php endif; ?>
            </div>

            <h1 class="curso-hero-titulo" style="font-size: 2.15rem; font-weight: 800; color: var(--color-text-main); letter-spacing: -0.6px; line-height: 1.25; margin: 0 0 10px 0;">
                <?php echo htmlspecialchars($cursoBD['titulo']); ?>
            </h1>

            <?php if (!empty($cursoBD['subtitulo'])): ?>
                <p style="font-size: 1.12rem; font-weight: 600; color: var(--color-text-body, #57534e); margin: 0 0 14px 0; line-height: 1.5;">
                    <?php echo htmlspecialchars($cursoBD['subtitulo']); ?>
                </p>
            <?php endif; ?>

            <div style="font-size: 1.02rem; color: var(--color-text-body, #475569); line-height: 1.7; margin: 0 0 24px 0; max-width: 980px;">
                <p style="margin: 0;">
                    <?php echo nl2br(htmlspecialchars($descripcion_aula)); ?>
                </p>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
                <!-- docente -->
                <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$cursoBD['docente_id']; ?>" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    <div style="width: 46px; height: 46px; border-radius: 50%; overflow: hidden; background: var(--color-primary-soft, #fce7f3); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--color-primary, #ec4899); box-shadow: 0 2px 6px rgba(0,0,0,0.06); border: 2px solid #ffffff;">
                        <?php if (!empty($cursoBD['docente_avatar'])): ?>
                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($cursoBD['docente_avatar'])); ?>" alt="Profesor" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($docNombre, 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span style="font-size: 0.74rem; color: var(--color-text-muted); display: block; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Profesor Titular</span>
                        <strong style="font-size: 0.96rem; color: var(--color-text-main);"><?php echo htmlspecialchars($docNombre); ?></strong>
                    </div>
                </a>

                <div style="flex: 1; max-width: 280px; min-width: 200px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px;">
                        <span style="font-weight: 600; color: var(--color-text-muted);">Progreso de cursada</span>
                        <strong style="color: var(--color-primary); font-weight: 700;"><?php echo $progreso_alumno; ?>%</strong>
                    </div>
                    <div class="barra-progreso-contenedor" style="margin: 0; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                        <div class="barra-progreso-relleno" style="width: <?php echo $progreso_alumno; ?>%; height: 100%; background: var(--gradient-brand); border-radius: 999px; transition: width 0.4s ease;"></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="curso-ver-layout" style="display: block;">

            <div class="curso-col-principal">

                <!-- nav aula -->
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

                    <!-- tab 1 -->
                    <div id="tab-panel-contenido" class="curso-tab-panel <?php echo ($tab_activa === 'contenido') ? 'activo' : ''; ?>">
                        
                        <div class="temario-resumen-barra" style="margin-bottom: 20px;">
                            <span class="temario-resumen-texto">
                                <strong><?php echo count($modulosCurso); ?> módulos</strong> · 
                                <strong><?php echo $total_clases; ?> clases grabadas y sincrónicas</strong>
                            </span>
                            <button type="button" id="btn-toggle-todos" class="btn-toggle-todos" onclick="toggleTodosModulos()">
                                Expandir todos los módulos
                            </button>
                        </div>

                        <div style="background: #0f172a; border-radius: 14px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4); width: 100%;">
                            <div style="width: 100%; aspect-ratio: 16/9; min-height: 280px; max-height: 440px; background: #000; position: relative; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars($portada_curso); ?>" alt="Clase en curso" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.45; filter: blur(2px); display: block;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); -webkit-transform: translate(-50%, -50%); width: 90%; max-width: 600px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #ffffff; z-index: 2; margin: 0; padding: 0;">
                                    <div style="width: 72px; height: 72px; min-width: 72px; min-height: 72px; background: rgba(239, 68, 68, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 14px auto; cursor: pointer; transition: transform 0.2s, background-color 0.2s; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);" onmouseover="this.style.transform='scale(1.1)'; this.style.backgroundColor='#dc2626';" onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='rgba(239, 68, 68, 0.95)';" onclick="alert('Reproduciendo clase interactiva del aula virtual.');">
                                        <i class="bi bi-play-fill" style="margin-left: 4px; display: flex; align-items: center; justify-content: center; line-height: 1;"></i>
                                    </div>
                                    <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0 auto 8px auto; color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.85); width: 100%; max-width: 560px; line-height: 1.35; text-align: center;">Clase Activa: <?php echo htmlspecialchars($modulosCurso[0]['clases'][0]['nombre'] ?? 'Introducción a la materia'); ?></h3>
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.88rem; color: #f1f5f9; text-shadow: 0 1px 6px rgba(0,0,0,0.9); text-align: center; width: 100%;">
                                        <i class="bi bi-clock" style="font-size: 0.92rem;"></i>
                                        <span>Duración estimada: <?php echo htmlspecialchars($modulosCurso[0]['clases'][0]['duracion'] ?? '45 min'); ?> · Aula Virtual Cursolandia</span>
                                    </div>
                                </div>
                            </div>
                            <div style="padding: 14px 20px; display: flex; align-items: center; background: #1e293b; color: #f8fafc; gap: 10px;">
                                <span style="font-size: 0.82rem; background: #334155; padding: 4px 10px; border-radius: 6px; font-weight: 600;">Módulo 1</span>
                                <span style="font-size: 0.88rem; font-weight: 600;"><?php echo htmlspecialchars($modulosCurso[0]['clases'][0]['nombre'] ?? 'Contenido en curso'); ?></span>
                            </div>
                        </div>

                        <!-- modulos y clases -->
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
                                            <span style="color: #cbd5e1;">•</span>
                                            <span><?php echo htmlspecialchars($mod['duracion'] ?? '2h'); ?></span>
                                        </div>
                                    </button>
                                    <div class="modulo-cuerpo" style="<?php echo ($idx === 0) ? 'display: block;' : 'display: none;'; ?>">
                                        <ul class="modulo-lista-clases">
                                            <?php foreach ($mod['clases'] as $cIdx => $clase): ?>
                                                <li class="clase-fila" style="cursor: pointer;" onclick="alert('Cargando: <?php echo htmlspecialchars($clase['nombre']); ?>');">
                                                    <div class="clase-info-izq">
                                                        <i class="bi bi-play-circle-fill clase-icono" style="color: var(--color-primary);"></i>
                                                        <span class="clase-nombre"><?php echo htmlspecialchars($clase['nombre']); ?></span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <span class="clase-duracion">
                                                            <i class="bi bi-clock"></i> <?php echo htmlspecialchars($clase['duracion']); ?>
                                                        </span>
                                                        <button type="button" class="btn-tabla-pildora" style="font-size: 0.72rem; padding: 3px 8px;" onclick="event.stopPropagation(); alert('¡Clase completada!');">
                                                            <i class="bi bi-check2"></i>
                                                        </button>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- tab 2 -->
                    <div id="tab-panel-materiales" class="curso-tab-panel <?php echo ($tab_activa === 'materiales') ? 'activo' : ''; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0 0 4px 0;"><i class="bi bi-folder2-open" style="color: var(--color-primary);"></i> Materiales y Recursos Descargables</h3>
                                <p class="panel-subtitulo-sec" style="margin: 0;">Descargá las diapositivas, guías en PDF, ejercicios prácticos y archivos de código de cada clase.</p>
                            </div>
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--color-primary); background: var(--color-primary-light); padding: 5px 14px; border-radius: 999px;">
                                <i class="bi bi-shield-check"></i> Acceso Habilitado para Alumnos
                            </span>
                        </div>

                        <div class="materiales-grilla">
                            <?php foreach ($materiales as $mat): ?>
                                <article class="material-card-item">
                                    <div class="material-izq-bloque">
                                        <div class="material-icono-caja <?php echo htmlspecialchars($mat['clase_ico']); ?>">
                                            <i class="bi <?php echo htmlspecialchars($mat['icono']); ?>"></i>
                                        </div>
                                        <div class="material-info-textos">
                                            <strong><?php echo htmlspecialchars($mat['nombre']); ?></strong>
                                            <p><?php echo htmlspecialchars($mat['descripcion'] ?? $mat['tipo']); ?> · <span style="font-weight: 600;"><?php echo htmlspecialchars($mat['peso']); ?></span> · Subido el <?php echo htmlspecialchars($mat['fecha']); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if (!empty($mat['ruta']) && $mat['ruta'] !== '#'): ?>
                                            <a href="<?php echo $base_url . ltrim($mat['ruta'], '/'); ?>" download="<?php echo htmlspecialchars($mat['nombre']); ?>" class="btn-descargar-archivo">
                                                <i class="bi bi-download"></i> Descargar
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn-descargar-archivo" onclick="alert('Descargando archivo: <?php echo htmlspecialchars($mat['nombre']); ?>\n¡El material ya está disponible en tu equipo!'); return false;">
                                                <i class="bi bi-download"></i> Descargar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top: 24px; background: var(--bg-page); border: 1px dashed var(--border-color); border-radius: 12px; padding: 16px 20px; font-size: 0.85rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 14px;">
                            <i class="bi bi-cloud-arrow-down-fill" style="color: var(--color-primary); font-size: 1.5rem; flex-shrink: 0;"></i>
                            <span>Podés descargar todos los materiales y conservarlos para consulta offline en cualquier momento. Si necesitás un archivo adicional, solicitalo en el <strong>Foro de Consultas</strong>.</span>
                        </div>
                    </div>

                    <!-- tab 3 -->
                    <div id="tab-panel-foro" class="curso-tab-panel <?php echo ($tab_activa === 'foro') ? 'activo' : ''; ?>">
                        <div class="foro-consultas-wrap">
                            
                            <div>
                                <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0 0 4px 0;"><i class="bi bi-chat-square-dots-fill" style="color: var(--color-primary);"></i> Foro de Dudas y Debate</h3>
                                <p class="panel-subtitulo-sec" style="margin: 0;">Espacio exclusivo de intercambio para alumnos del curso y el docente. Hacé tus preguntas y resolvé dudas de las clases.</p>
                            </div>

                            <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=crear_pregunta" method="POST" class="form-nueva-pregunta" style="margin-top: 14px;">
                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                <h4><i class="bi bi-pencil-square" style="color: var(--color-primary);"></i> Publicar una nueva consulta en el foro</h4>
                                <div class="campo-grupo">
                                    <label for="titulo_pregunta">Título breve de tu duda</label>
                                    <input type="text" id="titulo_pregunta" name="titulo" class="input-control" placeholder="Ej: Error al conectar la base de datos en el Módulo 1" required>
                                </div>
                                <div class="campo-grupo">
                                    <label for="cuerpo_pregunta">Explicación detallada o fragmento de código</label>
                                    <textarea id="cuerpo_pregunta" name="cuerpo" class="input-control" rows="4" placeholder="Describí con detalle qué estás intentando hacer, qué resultado obtuviste y en qué clase te surgió la duda..." required></textarea>
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn-primario">
                                        <i class="bi bi-send-fill"></i> Publicar en el Foro
                                    </button>
                                </div>
                            </form>

                            <div class="lista-preguntas-foro" style="margin-top: 24px;">
                                <?php if (!empty($preguntasBD)): ?>
                                    <?php foreach ($preguntasBD as $preg): 
                                        $estaDestacada = !empty($preg['destacada']);
                                        $tieneAceptada = !empty($preg['id_respuesta_aceptada']) || !empty($preg['resuelta']);
                                        $resps = $respuestasBD[$preg['id']] ?? [];
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
                                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$preg['usuario_id']; ?>" class="autor-avatar-mini" style="overflow: hidden; border-radius: 50%; text-decoration: none; color: inherit;">
                                                        <?php if (!empty($preg['autor_avatar'])): ?>
                                                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($preg['autor_avatar'])); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <?php echo strtoupper(substr($preg['autor_nombre'] ?? 'U', 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </a>
                                                    <div>
                                                        <strong class="autor-nombre-txt">
                                                            <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$preg['usuario_id']; ?>" style="color: inherit; text-decoration: none;">
                                                                <?php echo htmlspecialchars(trim(($preg['autor_nombre'] ?? '') . ' ' . ($preg['autor_apellido'] ?? ''))); ?>
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
                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=destacar_pregunta&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" class="btn-foro-accion" title="<?php echo $estaDestacada ? 'Quitar destaque' : 'Destacar pregunta'; ?>" style="font-size: 0.78rem; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; color: #475569; text-decoration: none;">
                                                            <i class="bi bi-star"></i> <?php echo $estaDestacada ? 'Desmarcar' : 'Destacar'; ?>
                                                        </a>
                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=eliminar_pregunta&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" onclick="return confirm('¿Seguro que deseas eliminar esta pregunta?');" class="btn-foro-accion" title="Eliminar pregunta" style="font-size: 0.78rem; padding: 4px 8px; background: #fee2e2; border-radius: 6px; color: #b91c1c; text-decoration: none;">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <h4 class="pregunta-titulo-txt"><?php echo htmlspecialchars($preg['titulo']); ?></h4>
                                            <p class="pregunta-cuerpo-txt"><?php echo nl2br(htmlspecialchars($preg['contenido'] ?? $preg['cuerpo'] ?? '')); ?></p>

                                            <!-- respuestas -->
                                            <?php if (!empty($resps)): ?>
                                                <div class="respuestas-hilo">
                                                    <h5 style="font-size: 0.84rem; font-weight: 700; color: var(--color-text-muted); margin: 0 0 10px 0;">
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
                                                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$resp['usuario_id']; ?>" class="autor-avatar-mini" style="overflow: hidden; border-radius: 50%; width: 26px; height: 26px; font-size: 0.75rem; text-decoration: none; color: inherit;">
                                                                        <?php if (!empty($resp['autor_avatar'])): ?>
                                                                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($resp['autor_avatar'])); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                                                        <?php else: ?>
                                                                            <?php echo strtoupper(substr($resp['autor_nombre'] ?? 'U', 0, 1)); ?>
                                                                        <?php endif; ?>
                                                                    </a>
                                                                    <strong>
                                                                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$resp['usuario_id']; ?>" style="color: inherit; text-decoration: none;">
                                                                            <?php echo htmlspecialchars(trim(($resp['autor_nombre'] ?? '') . ' ' . ($resp['autor_apellido'] ?? ''))); ?>
                                                                        </a>
                                                                    </strong>
                                                                    <?php if ((int)$resp['usuario_id'] === (int)$cursoBD['docente_id']): ?>
                                                                        <span class="badge-autor-docente">Instructor</span>
                                                                    <?php endif; ?>
                                                                    <span class="pregunta-fecha-txt"><?php echo date('d M Y · H:i', strtotime($resp['fecha_creacion'])); ?></span>
                                                                </div>

                                                                <div style="display: flex; gap: 6px; align-items: center;">
                                                                    <?php if ($esAceptada): ?>
                                                                        <span class="badge-solucion-oficial">
                                                                            <i class="bi bi-check-circle-fill"></i> Solución Aceptada
                                                                        </span>
                                                                    <?php elseif (!$tieneAceptada && ($esCreadorDuda || $puedeModerar)): ?>
                                                                        <a href="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=aceptar_respuesta&respuesta_id=<?php echo $resp['id']; ?>&pregunta_id=<?php echo $preg['id']; ?>&curso_id=<?php echo $curso_id; ?>" class="btn-aceptar-solucion" style="font-size: 0.72rem; font-weight: 700; color: var(--color-primary); background: var(--color-primary-light); padding: 3px 8px; border-radius: 6px; text-decoration: none;" title="Aceptar esta respuesta como solución">
                                                                            <i class="bi bi-check2"></i> Marcar como Solución
                                                                        </a>
                                                                    <?php endif; ?>

                                                                    <?php if ($esPropia && !$tieneAceptada): ?>
                                                                        <button type="button" class="btn-foro-accion" onclick="document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display = (document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display === 'none' ? 'block' : 'none');" style="border: none; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; cursor: pointer;">
                                                                            <i class="bi bi-pencil"></i> Editar
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <p class="respuesta-cuerpo-txt"><?php echo nl2br(htmlspecialchars($resp['contenido'])); ?></p>

                                                            <?php if ($esPropia && !$tieneAceptada): ?>
                                                                <div id="edit-resp-<?php echo $resp['id']; ?>" style="display: none; margin-top: 10px; background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px;">
                                                                    <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=editar_respuesta" method="POST" style="margin: 0;">
                                                                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                                                        <input type="hidden" name="pregunta_id" value="<?php echo $preg['id']; ?>">
                                                                        <input type="hidden" name="respuesta_id" value="<?php echo $resp['id']; ?>">
                                                                        <textarea name="contenido" class="input-control" rows="2" required style="font-size: 0.85rem; margin-bottom: 6px;"><?php echo htmlspecialchars($resp['contenido']); ?></textarea>
                                                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                                            <button type="button" class="btn-secundario" style="font-size: 0.75rem; padding: 4px 10px; border: none; background: #fee2e2; color: #dc2626;" onclick="document.getElementById('edit-resp-<?php echo $resp['id']; ?>').style.display='none';">Cancelar</button>
                                                                            <button type="submit" class="btn-primario" style="font-size: 0.75rem; padding: 4px 12px;">Guardar cambios</button>
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
                                                    <div style="margin-top: 12px; font-size: 0.8rem; color: #b45309; background: #fffbeb; border: 1px solid #fef3c7; padding: 8px 12px; border-radius: 8px;">
                                                        <i class="bi bi-info-circle-fill"></i> Ya publicaste la última respuesta en este hilo. Podés editar tu respuesta previa o aguardar a que responda otro compañero o el docente.
                                                    </div>
                                                <?php else: ?>
                                                    <form action="<?php echo $base_url; ?>controladores/foro_controlador.php?accion=responder" method="POST" style="margin-top: 12px; display: flex; gap: 8px;">
                                                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                                        <input type="hidden" name="pregunta_id" value="<?php echo $preg['id']; ?>">
                                                        <input type="text" name="contenido" class="input-control" placeholder="Escribí una respuesta a esta duda..." required style="font-size: 0.86rem; padding: 8px 14px;">
                                                        <button type="submit" class="btn-primario" style="white-space: nowrap; font-size: 0.84rem; padding: 8px 16px;">
                                                            <i class="bi bi-reply-fill"></i> Responder
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </article>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 36px; color: var(--color-text-muted); font-size: 0.92rem; background: #ffffff; border: 1px dashed var(--border-color); border-radius: 12px;">
                                        <i class="bi bi-chat-dots" style="font-size: 2.4rem; display: block; margin-bottom: 8px; color: var(--color-primary);"></i>
                                        Todavía no hay consultas en el foro de este curso. ¡Sé el primero en iniciar una consulta!
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- tab 4 -->
                    <div id="tab-panel-info" class="curso-tab-panel <?php echo ($tab_activa === 'info') ? 'activo' : ''; ?>">
                        <article class="docente-tarjeta-perfil" style="margin-bottom: 24px;">
                            <div class="docente-perfil-avatar" style="overflow: hidden; border-radius: 50%;">
                                <?php if (!empty($cursoBD['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($cursoBD['docente_avatar'])); ?>" alt="Docente" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($docNombre, 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="docente-perfil-cuerpo">
                                <h4><?php echo htmlspecialchars($docNombre); ?></h4>
                                <span class="docente-perfil-sub"><?php echo ($cursoBD['docente_plan'] === 'pro') ? 'Docente Titular · Plan PRO' : 'Docente Titular'; ?></span>
                                <p class="docente-perfil-bio" style="margin-top: 10px;"><?php echo htmlspecialchars($cursoBD['docente_bio'] ?: 'Profesor titular con amplia trayectoria en la enseñanza práctica de competencias universitarias y profesionales.'); ?></p>
                            </div>
                        </article>

                        <div class="caja-que-aprenderas">
                            <h3><i class="bi bi-journal-text"></i> Acerca de este curso y lineamientos</h3>
                            <p style="font-size: 0.92rem; line-height: 1.6; color: var(--color-text-body); margin: 0 0 16px 0;"><?php echo nl2br(htmlspecialchars($cursoBD['descripcion'])); ?></p>
                            
                            <h4 style="font-size: 0.95rem; margin: 20px 0 12px 0; color: var(--color-text-main);"><i class="bi bi-calendar3"></i> Fechas y Cursada</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 20px;">
                                <div>
                                    <span style="font-size: 0.76rem; color: var(--color-text-muted); display: block; font-weight: 600;">Período Lectivo</span>
                                    <strong style="font-size: 0.9rem; color: var(--color-text-main);"><?php echo date('d/m/Y', strtotime($cursoBD['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($cursoBD['fecha_fin'])); ?></strong>
                                </div>
                                <div>
                                    <span style="font-size: 0.76rem; color: var(--color-text-muted); display: block; font-weight: 600;">Modalidad</span>
                                    <strong style="font-size: 0.9rem; color: var(--color-text-main);">Online Asincrónica</strong>
                                </div>
                                <div>
                                    <span style="font-size: 0.76rem; color: var(--color-text-muted); display: block; font-weight: 600;">Certificado Oficial</span>
                                    <strong style="font-size: 0.9rem; color: var(--color-text-main);">Al completar el 100%</strong>
                                </div>
                            </div>

                            <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso_id; ?>" class="btn-secundario" style="font-size: 0.85rem; display: inline-flex;">
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
    document.querySelectorAll('.curso-tab-btn').forEach(btn => btn.classList.remove('activa'));
    document.querySelectorAll('.curso-tab-panel').forEach(panel => panel.classList.remove('activo'));
    
    if (boton) {
        boton.classList.add('activa');
    } else {

        document.querySelectorAll('.curso-tab-btn').forEach(btn => {
            if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes("'" + idTab + "'")) {
                btn.classList.add('activa');
            }
        });
    }

    const panelActivo = document.getElementById('tab-panel-' + idTab);
    if (panelActivo) {
        panelActivo.classList.add('activo');
    }
}

function toggleModulo(cabecera) {
    const cuerpo = cabecera.nextElementSibling;
    const flecha = cabecera.querySelector('.flecha-modulo');
    if (cuerpo.style.display === 'none' || cuerpo.style.display === '') {
        cuerpo.style.display = 'block';
        if (flecha) flecha.style.transform = 'rotate(180deg)';
    } else {
        cuerpo.style.display = 'none';
        if (flecha) flecha.style.transform = 'rotate(0deg)';
    }
}

let todosExpandidos = false;
function toggleTodosModulos() {
    todosExpandidos = !todosExpandidos;
    const cuerpos = document.querySelectorAll('.modulo-cuerpo');
    const flechas = document.querySelectorAll('.flecha-modulo');
    const btn = document.getElementById('btn-toggle-todos');
    
    cuerpos.forEach(c => c.style.display = todosExpandidos ? 'block' : 'none');
    flechas.forEach(f => f.style.transform = todosExpandidos ? 'rotate(180deg)' : 'rotate(0deg)');
    if (btn) {
        btn.textContent = todosExpandidos ? 'Colapsar todos los módulos' : 'Expandir todos los módulos';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash && window.location.hash.startsWith('#pregunta-')) {
        const foroBtn = document.querySelectorAll('.curso-tab-btn')[2];
        if (foroBtn) cambiarPestana('foro', foroBtn);
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
