<?php
$titulo_pagina = 'Mis Inscripciones';
$menu_activo = 'inscripciones';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();
$conexion = obtener_conexion();

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver tus inscripciones.';
    header('Location: ../auth/ingresar.php');
    exit;
}
$es_pro = usuario_es_pro($usuario_id);

$mensaje_exito = '';
$metodo_peticion = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// baja de inscripcion
if ($metodo_peticion === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'dar_baja') {
    $curso_id = isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0;
    if ($curso_id > 0 && $usuario_id > 0) {
        $uid_seguro = intval($usuario_id);
        mysqli_query($conexion, "UPDATE Inscripcion SET estado = 'cancelada' WHERE curso_id = $curso_id AND usuario_id = $uid_seguro");
        $mensaje_exito = 'Te diste de baja del curso correctamente. Se liberó tu cupo de cursada.';
    }
}

// carga inscripciones desde la bd
$mis_inscripciones_db = [];
if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    $resInscripciones = mysqli_query($conexion, "
        SELECT i.id AS inscripcion_id, i.estado, i.progreso_porcentaje, i.fecha_inscripcion,
               c.id AS curso_id, c.docente_id, c.titulo, c.precio, c.imagen AS imagen, c.fecha_inicio, c.fecha_fin, c.etiquetas,
               c.categoria AS categoria_nombre, c.icono AS categoria_icono,
               u.nombre AS doc_nombre, u.apellido AS doc_apellido, u.email AS doc_email
        FROM Inscripcion i
        JOIN Curso c ON i.curso_id = c.id
        LEFT JOIN Usuario u ON c.docente_id = u.id
        WHERE i.usuario_id = $uid_seguro AND i.estado != 'cancelada'
        ORDER BY i.id DESC
    ");
    $rows = [];
    if ($resInscripciones) {
        while ($r = mysqli_fetch_assoc($resInscripciones)) {
            $rows[] = $r;
        }
    }
    foreach ($rows as $row) {
        $cid = (int)$row['curso_id'];
            $tags = array_filter(array_map('trim', explode(',', $row['etiquetas'] ?? '')));
            if (empty($tags)) $tags = ['#Cátedra'];

            $mis_inscripciones_db[$cid] = [
                'id'                 => $cid,
                'docente_id'         => (int)($row['docente_id'] ?? 0),
                'titulo'             => $row['titulo'],
                'docente'            => trim($row['doc_nombre'] . ' ' . $row['doc_apellido']),
                'email_docente'      => $row['doc_email'],
                'categoria'          => $row['categoria_nombre'] ?? 'General',
                'imagen'             => $row['imagen'] ?? '',
                'icono'              => !empty($row['categoria_icono']) ? $row['categoria_icono'] : 'bi-journal-bookmark',
                'tipo'               => ((float)$row['precio'] > 0) ? 'pago' : 'gratis',
                'precio'             => (float)$row['precio'],
                'estado'             => $row['estado'],
                'estado_pago'        => ($row['estado'] === 'activa') ? (((float)$row['precio'] > 0) ? 'confirmado' : 'gratuito') : 'pendiente',
                'fecha_inscripcion'  => date('d/m/Y', strtotime($row['fecha_inscripcion'])),
                'periodo'            => date('d M', strtotime($row['fecha_inicio'])) . ' - ' . date('d M', strtotime($row['fecha_fin'])),
                'progreso'           => (int)$row['progreso_porcentaje'],
                'clase_actual'       => ($row['estado'] === 'activa') ? 'Acceso habilitado al aula y materiales' : 'Esperando verificación del pago por el docente',
                'tags'               => $tags,
                'numero_comprobante' => 'INS-' . date('Y') . '-' . str_pad($row['inscripcion_id'], 4, '0', STR_PAD_LEFT)
            ];
        }
    }

$_SESSION['inscripciones'] = $mis_inscripciones_db;

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$total_todas = 0;
$total_activas = 0;
$total_pendientes = 0;
$total_completadas = 0;

foreach ($_SESSION['inscripciones'] as $ins) {
    if ($ins['estado'] === 'cancelada') {
        continue;
    }
    $total_todas++;
    if ($ins['estado'] === 'activa') {
        $total_activas++;
    } elseif ($ins['estado'] === 'pendiente') {
        $total_pendientes++;
    } elseif ($ins['estado'] === 'completada') {
        $total_completadas++;
    }
}

// filtro
$inscripciones_visibles = array_filter($_SESSION['inscripciones'], function ($ins) use ($filtro) {
    if ($ins['estado'] === 'cancelada') {
        return false;
    }
    if ($filtro === 'activas') {
        return $ins['estado'] === 'activa';
    }
    if ($filtro === 'pendientes') {
        return $ins['estado'] === 'pendiente';
    }
    if ($filtro === 'completadas') {
        return $ins['estado'] === 'completada';
    }
    return true;
});

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div class="mini-banner-cabecera">
            <div class="mini-banner-contenido">
                <div class="mini-banner-icono-caja icono-inscripciones">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div>
                    <div class="mini-banner-migas">
                        <a href="<?php echo $base_url; ?>panel/index.php"><i class="bi bi-house-door"></i> Inicio</a>
                        <span class="miga-separador">/</span>
                        <span>Estudiante</span>
                        <span class="miga-separador">/</span>
                        <span class="miga-actual">Mis Inscripciones</span>
                    </div>
                    <h1>Mis Inscripciones</h1>
                    <p>Gestioná tus cursos inscriptos, accedé a las aulas virtuales y consultá el avance de tus clases.</p>
                </div>
            </div>
            <div class="mini-banner-acciones">
                <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario">
                    <i class="bi bi-search"></i> Explorar Más Cursos
                </a>
            </div>
        </div>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo $mensaje_exito; ?></span>
            </div>
        <?php endif; ?>

        <div class="inscripciones-aviso-cupo">
            <div class="aviso-cupo-info">
                <div class="aviso-cupo-texto">
                    <?php if ($es_pro): ?>
                        <strong>Plan PRO Activo · Inscripciones ilimitadas</strong>
                        <p>Podés inscribirte a todos los cursos que quieras sin límite simultáneo.</p>
                    <?php else: 
                        $cupos_restantes_ins = max(0, 3 - $total_activas);
                        if ($cupos_restantes_ins === 1) {
                            $frase_cupos_ins = 'Te queda 1 cupo disponible.';
                        } elseif ($cupos_restantes_ins === 0) {
                            $frase_cupos_ins = 'No te quedan cupos disponibles.';
                        } else {
                            $frase_cupos_ins = "Te quedan {$cupos_restantes_ins} cupos disponibles.";
                        }
                    ?>
                        <strong>Cupos de cursada: <?php echo $total_activas; ?> de 3 cursos activos utilizados</strong>
                        <p>En el Plan Gratuito podés cursar hasta 3 materias al mismo tiempo. <?php echo $frase_cupos_ins; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$es_pro): ?>
                <a href="<?php echo $base_url; ?>perfil/index.php#seccion-plan-pro" class="btn-cupo-pro">
                    Mejorar a Plan PRO
                </a>
            <?php endif; ?>
        </div>

        <nav class="inscripciones-filtros" aria-label="Filtro de inscripciones">
            <a href="?filtro=todas" class="filtro-item <?php echo ($filtro === 'todas') ? 'activo' : ''; ?>">
                Todas <span class="filtro-contador"><?php echo $total_todas; ?></span>
            </a>
            <a href="?filtro=activas" class="filtro-item <?php echo ($filtro === 'activas') ? 'activo' : ''; ?>">
                En curso / Activas <span class="filtro-contador"><?php echo $total_activas; ?></span>
            </a>
            <a href="?filtro=pendientes" class="filtro-item <?php echo ($filtro === 'pendientes') ? 'activo' : ''; ?>">
                Pendientes de pago <span class="filtro-contador"><?php echo $total_pendientes; ?></span>
            </a>
            <a href="?filtro=completadas" class="filtro-item <?php echo ($filtro === 'completadas') ? 'activo' : ''; ?>">
                Finalizadas <span class="filtro-contador"><?php echo $total_completadas; ?></span>
            </a>
        </nav>

        <?php if (empty($inscripciones_visibles)): ?>
            <div class="inscripciones-vacio">
                <div class="vacio-icono">
                    <i class="bi bi-journal-x"></i>
                </div>
                <h3>No tenés cursos en esta sección</h3>
                <p>Explorá el catálogo de Cursolandia para encontrar nuevos cursos afines a tus intereses.</p>
                <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-primario">Ver catálogo de cursos</a>
            </div>
        <?php else: ?>
            <div class="grilla-cursos">
                <?php 
                foreach ($inscripciones_visibles as $curso_id => $ins): 
                    $img_src = obtener_portada_curso($ins['imagen'] ?? '', $ins['categoria'] ?? '', (int)($ins['id'] ?? $curso_id));
                ?>
                    <article class="tarjeta-curso">

                        <div class="curso-portada-card">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($ins['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($img_src); ?>';">
                            <?php if ($ins['estado'] === 'activa'): ?>
                                <span class="badge-destacado" style="background: var(--color-primary-light); color: var(--color-primary); border-color: rgba(166, 109, 114, 0.3);">
                                    <i class="bi bi-check-circle-fill"></i> En curso
                                </span>
                            <?php elseif ($ins['estado'] === 'pendiente'): ?>
                                <span class="badge-recomendado" style="background: var(--color-secondary-soft); color: var(--color-text-main); border-color: rgba(242, 165, 148, 0.4);">
                                    <i class="bi bi-hourglass-split"></i> Pendiente
                                </span>
                            <?php elseif ($ins['estado'] === 'completada'): ?>
                                <span class="badge-destacado" style="background: rgba(166, 148, 151, 0.2); color: var(--color-text-main); border-color: rgba(166, 148, 151, 0.3);">
                                    <i class="bi bi-award-fill"></i> Completado
                                </span>
                            <?php endif; ?>

                            <a href="inscripcion-detalle.php?id=<?php echo $curso_id; ?>" class="btn-guardar" title="Ver ficha técnica">
                                <i class="bi bi-file-earmark-text"></i> Ficha
                            </a>
                        </div>

                        <div class="curso-info-card">
                            <div class="curso-autor-meta">
                                <?php if (!empty($ins['docente_id'])): ?>
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$ins['docente_id']; ?>" class="curso-autor-nombre" style="text-decoration: none; color: inherit;">Prof. <?php echo htmlspecialchars($ins['docente']); ?></a>
                                <?php else: ?>
                                    <strong class="curso-autor-nombre">Prof. <?php echo htmlspecialchars($ins['docente']); ?></strong>
                                <?php endif; ?>
                                <span class="curso-separador">•</span>
                                <span class="curso-fecha"><?php echo htmlspecialchars($ins['periodo']); ?></span>
                            </div>

                            <h3 class="curso-titulo">
                                <?php $enlaceCurso = ($ins['estado'] === 'activa') ? ($base_url . 'cursos/aula.php?id=' . $curso_id) : ($base_url . 'cursos/ver.php?id=' . $curso_id); ?>
                                <a href="<?php echo $enlaceCurso; ?>">
                                    <?php echo htmlspecialchars($ins['titulo']); ?>
                                </a>
                            </h3>

                            <?php if ($ins['estado'] === 'activa'): ?>
                                <div style="margin-bottom: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; margin-bottom: 5px;">
                                        <span style="color: var(--color-text-muted); font-size: 0.78rem;"><?php echo htmlspecialchars($ins['clase_actual']); ?></span>
                                        <strong style="color: var(--color-primary);"><?php echo $ins['progreso']; ?>%</strong>
                                    </div>
                                    <div class="barra-progreso-contenedor" style="margin: 0; height: 6px;">
                                        <div class="barra-progreso-relleno" style="width: <?php echo $ins['progreso']; ?>%;"></div>
                                    </div>
                                </div>
                            <?php elseif ($ins['estado'] === 'pendiente'): ?>
                                <div style="margin-bottom: 14px; background: var(--color-secondary-soft); border: 1px solid rgba(242, 165, 148, 0.4); padding: 8px 12px; border-radius: 10px; font-size: 0.78rem; color: var(--color-text-main);">
                                    <i class="bi bi-info-circle-fill"></i> Falta confirmar la transferencia bancaria.
                                </div>
                            <?php endif; ?>

                            <div class="curso-pildoras">
                                <?php foreach ($ins['tags'] as $tag): ?>
                                    <span class="pildora-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="curso-pie-card">
                            <div class="curso-precio-bloque">
                                <span class="curso-precio <?php echo ($ins['tipo'] === 'gratis') ? 'gratis' : ''; ?>">
                                    <?php echo ($ins['tipo'] === 'gratis') ? 'Gratis' : ('$ ' . number_format($ins['precio'], 0, ',', '.')); ?>
                                </span>
                                <span class="curso-precio-sub">
                                    <?php echo ($ins['estado'] === 'activa') ? 'Acceso activo' : 'Esperando pago'; ?>
                                </span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if ($ins['estado'] === 'activa'): ?>
                                    <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>" class="btn-ver-curso" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-mortarboard-fill"></i> Ir al Aula →
                                    </a>
                                <?php elseif ($ins['estado'] === 'pendiente'): ?>
                                    <a href="pagar.php?id=<?php echo $curso_id; ?>" class="btn-ver-curso">
                                        Pagar →
                                    </a>
                                <?php endif; ?>

                                <form action="" method="POST" onsubmit="return confirm('¿Confirmás la baja de este curso? Se liberará tu cupo.');" style="margin: 0;">
                                    <input type="hidden" name="accion" value="dar_baja">
                                    <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                    <button type="submit" class="btn-guardar" title="Darse de baja" style="color: #b91c1c; padding: 6px 10px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
