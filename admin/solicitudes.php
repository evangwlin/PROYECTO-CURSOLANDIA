<?php

$titulo_pagina = 'Administración · Centro de Aprobaciones';
$menu_activo = 'admin_solicitudes';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = 'Acceso exclusivo para administradores de Cursolandia.';
    header('Location: ../panel/index.php');
    exit;
}

$conexion = obtener_conexion();

$accion_admin = '';
if (isset($_POST['accion'])) {
    $accion_admin = $_POST['accion'];
} elseif (isset($_GET['accion'])) {
    $accion_admin = $_GET['accion'];
}

if ($accion_admin === 'aprobar_curso') {
    $cid = 0;
    if (isset($_POST['curso_id'])) {
        $cid = intval($_POST['curso_id']);
    } elseif (isset($_GET['curso_id'])) {
        $cid = intval($_GET['curso_id']);
    }
    if ($cid > 0) {
        $res = mysqli_query($conexion, "SELECT id, docente_id, titulo FROM curso WHERE id = $cid LIMIT 1");
        $cRow = $res ? mysqli_fetch_assoc($res) : null;
        if ($cRow) {
            mysqli_query($conexion, "UPDATE curso SET estado = 'publicado' WHERE id = $cid");
            enviar_notificacion(
                intval($cRow['docente_id']),
                '¡Excelente noticia! El administrador aprobó tu curso "' . $cRow['titulo'] . '" y ya se encuentra publicado en el catálogo.',
                'cursos/ver.php?id=' . $cid
            );
            $_SESSION['exito'] = 'El curso "' . htmlspecialchars($cRow['titulo']) . '" fue aprobado exitosamente y ya está publicado.';
        } else {
            $_SESSION['error'] = 'No se encontró el curso a aprobar.';
        }
    }
    header('Location: solicitudes.php?tab=cursos');
    exit;
}

if ($accion_admin === 'rechazar_curso') {
    $cid = 0;
    if (isset($_POST['curso_id'])) {
        $cid = intval($_POST['curso_id']);
    } elseif (isset($_GET['curso_id'])) {
        $cid = intval($_GET['curso_id']);
    }
    $motivo = 'La propuesta requiere ajustes pedagógicos o de temario.';
    if (isset($_POST['motivo'])) {
        $motivo = trim($_POST['motivo']);
    } elseif (isset($_GET['motivo'])) {
        $motivo = trim($_GET['motivo']);
    }
    if ($cid > 0) {
        $res = mysqli_query($conexion, "SELECT id, docente_id, titulo FROM curso WHERE id = $cid LIMIT 1");
        $cRow = $res ? mysqli_fetch_assoc($res) : null;
        if ($cRow) {
            mysqli_query($conexion, "UPDATE curso SET estado = 'rechazado' WHERE id = $cid");
            enviar_notificacion(
                intval($cRow['docente_id']),
                'Tu curso "' . $cRow['titulo'] . '" fue rechazado: ' . $motivo . '. Podés editarlo para volver a enviarlo a revisión.',
                'cursos/editar.php?id=' . $cid
            );
            $_SESSION['exito'] = 'La propuesta del curso "' . htmlspecialchars($cRow['titulo']) . '" ha sido rechazada.';
        } else {
            $_SESSION['error'] = 'No se encontró el curso.';
        }
    }
    header('Location: solicitudes.php?tab=cursos');
    exit;
}

if ($accion_admin === 'aprobar_pro') {
    $solicitud_id = 0;
    if (isset($_POST['solicitud_id'])) {
        $solicitud_id = intval($_POST['solicitud_id']);
    } elseif (isset($_GET['solicitud_id'])) {
        $solicitud_id = intval($_GET['solicitud_id']);
    }
    $meses = isset($_POST['meses']) ? intval($_POST['meses']) : 12;
    $fecha_vence = isset($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : date('Y-m-d', strtotime('+' . $meses . ' months'));

    if ($solicitud_id > 0) {
        $resSol = mysqli_query($conexion, "SELECT usuario_id FROM solicitudpro WHERE id = $solicitud_id");
        $rowSol = $resSol ? mysqli_fetch_assoc($resSol) : null;
        $uid = $rowSol ? intval($rowSol['usuario_id']) : 0;

        if ($uid > 0) {
            $fechaEsc = mysqli_real_escape_string($conexion, $fecha_vence);
            mysqli_query($conexion, "UPDATE usuario SET plan = 'pro', fecha_vencimiento_pro = '$fechaEsc' WHERE id = $uid");
            mysqli_query($conexion, "UPDATE solicitudpro SET estado = 'aprobada' WHERE id = $solicitud_id");
            enviar_notificacion(
                $uid,
                '¡El administrador aprobó tu pago! Tu Plan PRO ha sido activado hasta el ' . date('d/m/Y', strtotime($fecha_vence)) . '.',
                'perfil/configuracion.php#seccion-plan'
            );
            $_SESSION['exito'] = 'Plan PRO activado exitosamente para el usuario.';
        }
    }
    header('Location: solicitudes.php?tab=pro');
    exit;
}

if ($accion_admin === 'rechazar_pro') {
    $solicitud_id = isset($_GET['solicitud_id']) ? intval($_GET['solicitud_id']) : 0;
    $motivo = isset($_GET['motivo']) ? trim($_GET['motivo']) : 'Comprobante no válido o no acreditado.';

    if ($solicitud_id > 0) {
        $resSol = mysqli_query($conexion, "SELECT usuario_id FROM solicitudpro WHERE id = $solicitud_id");
        $rowSol = $resSol ? mysqli_fetch_assoc($resSol) : null;
        $uid = $rowSol ? intval($rowSol['usuario_id']) : 0;

        if ($uid > 0) {
            mysqli_query($conexion, "UPDATE solicitudpro SET estado = 'rechazada' WHERE id = $solicitud_id");
            enviar_notificacion(
                $uid,
                'Tu solicitud de Plan PRO no pudo ser aprobada: ' . $motivo,
                'perfil/configuracion.php#seccion-plan'
            );
            $_SESSION['exito'] = 'Solicitud de Plan PRO rechazada.';
        }
    }
    header('Location: solicitudes.php?tab=pro');
    exit;
}

$resCursos = mysqli_query($conexion, "
    SELECT c.*, 
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.email AS docente_email, u.avatar AS docente_avatar, u.plan AS docente_plan,
           (SELECT COUNT(*) FROM inscripcion WHERE curso_id = c.id AND estado IN ('activa', 'pendiente')) AS inscriptos
    FROM curso c
    JOIN usuario u ON c.docente_id = u.id
    ORDER BY CASE 
        WHEN c.estado = 'pendiente' THEN 1 
        WHEN c.estado = 'rechazado' THEN 2 
        ELSE 3 
    END, c.id DESC
");
$cursosAdmin = [];
$totalCursosPendientes = 0;
if ($resCursos) {
    while ($filaC = mysqli_fetch_assoc($resCursos)) {
        $cursosAdmin[] = $filaC;
        if (isset($filaC['estado']) && $filaC['estado'] === 'pendiente') {
            $totalCursosPendientes++;
        }
    }
}

$res = mysqli_query($conexion, "
    SELECT s.id AS solicitud_id, s.comprobante, s.mensaje, s.estado, s.fecha_solicitud,
           u.id AS usuario_id, u.nombre, u.apellido, u.email, u.plan, u.fecha_vencimiento_pro
    FROM solicitudpro s
    JOIN usuario u ON s.usuario_id = u.id
    ORDER BY s.id DESC
");
$solicitudes = [];
$totalProPendientes = 0;
if ($res) {
    while ($fila = mysqli_fetch_assoc($res)) {
        $solicitudes[] = $fila;
        if (isset($fila['estado']) && $fila['estado'] === 'pendiente') {
            $totalProPendientes++;
        }
    }
}

$tab_activa = isset($_GET['tab']) ? trim($_GET['tab']) : 'cursos';
if (!in_array($tab_activa, ['cursos', 'pro'])) {
    $tab_activa = 'cursos';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <header class="dashboard-cabecera">
            <div>
                <h1>Centro de Aprobaciones</h1>
                <p>Revisá y aprobá las propuestas de nuevos cursos y las solicitudes de suscripción Plan PRO.</p>
            </div>
            <div class="cabecera-acciones">
                <span class="kpi-chip kpi-chip-rosa">
                    <i class="bi bi-mortarboard"></i> <?php echo $totalCursosPendientes; ?> curso(s) a revisar
                </span>
                <span class="kpi-chip kpi-chip-rosa">
                    <i class="bi bi-star"></i> <?php echo $totalProPendientes; ?> plan(es) PRO a revisar
                </span>
            </div>
        </header>

        <?php if (!empty($_SESSION['exito'])): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alerta-flash error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <nav class="curso-tabs-nav admin-tab-nav">
            <a href="solicitudes.php?tab=cursos" class="curso-tab-btn admin-tab-btn-enlace <?php echo ($tab_activa === 'cursos') ? 'activa' : ''; ?>">
                <i class="bi bi-journal-check"></i> Cursos para Aprobación
                <?php if ($totalCursosPendientes > 0): ?>
                    <span class="admin-tab-badge-primary">
                        <?php echo $totalCursosPendientes; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="solicitudes.php?tab=pro" class="curso-tab-btn admin-tab-btn-enlace <?php echo ($tab_activa === 'pro') ? 'activa' : ''; ?>">
                <i class="bi bi-shield-check"></i> Solicitudes Plan PRO
                <?php if ($totalProPendientes > 0): ?>
                    <span class="admin-tab-badge-secondary">
                        <?php echo $totalProPendientes; ?>
                    </span>
                <?php endif; ?>
            </a>
        </nav>

        <?php if ($tab_activa === 'cursos'): ?>

            <section class="tarjeta-panel gestion-cursos-card">
                <div class="panel-cabecera-icono">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <div>
                        <h3>Propuestas de Cursos Presentadas</h3>
                        <p class="panel-subtitulo-sec">Los cursos con estado 'Pendiente' no se muestran en el catálogo público hasta que sean aprobados por un administrador.</p>
                    </div>
                </div>

                <?php if (!empty($cursosAdmin)): ?>
                    <div class="tabla-responsive">
                        <table class="tabla-gestion">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Docente</th>
                                    <th>Precio & Cupo</th>
                                    <th>Fecha Envío</th>
                                    <th>Estado</th>
                                    <th class="celda-acciones-th">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursosAdmin as $c): 
                                    $estCurso = isset($c['estado']) ? $c['estado'] : 'publicado';
                                    $cImg = isset($c['imagen']) ? $c['imagen'] : '';
                                    $cCat = isset($c['categoria']) ? $c['categoria'] : '';
                                    $portadaThumb = obtener_portada_curso($cImg, $cCat, (int)$c['id']);
                                ?>
                                    <tr class="<?php echo ($estCurso === 'pendiente') ? 'fila-curso-pendiente' : ''; ?>">
                                        <td>
                                            <div class="display-flex-align-center gap-12">
                                                <img src="<?php echo htmlspecialchars($portadaThumb); ?>" alt="Portada" class="admin-curso-thumb" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($portadaThumb); ?>';">
                                                <div>
                                                    <strong class="admin-curso-titulo-tabla">
                                                        <?php echo htmlspecialchars($c['titulo']); ?>
                                                    </strong>
                                                    <span class="texto-muted-sm">
                                                        <?php echo htmlspecialchars($c['categoria']); ?> · ID #<?php echo (int)$c['id']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($c['docente_nombre'] . ' ' . $c['docente_apellido']); ?></strong>
                                            <div class="texto-muted-sm">
                                                <?php echo htmlspecialchars($c['docente_email']); ?>
                                                <?php if ($c['docente_plan'] === 'pro'): ?>
                                                    <span class="admin-docente-badge-pro">PRO</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ((float)$c['precio'] <= 0): ?>
                                                <span class="admin-precio-gratis">Gratis</span>
                                            <?php else: ?>
                                                <span class="admin-precio-monto">$ <?php echo number_format($c['precio'], 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                            <small class="texto-muted-bloque">Cupo: <?php echo (int)$c['cupo_max']; ?></small>
                                        </td>
                                        <td>
                                            <span class="texto-fecha-sm"><?php echo date('d/m/Y H:i', strtotime($c['fecha_creacion'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($estCurso === 'publicado'): ?>
                                                <span class="badge-estado publicado">
                                                    <i class="bi bi-check-circle-fill"></i> Publicado
                                                </span>
                                            <?php elseif ($estCurso === 'pendiente'): ?>
                                                <span class="badge-estado admin-badge-pendiente">
                                                    <i class="bi bi-clock-history"></i> Pendiente
                                                </span>
                                            <?php elseif ($estCurso === 'rechazado'): ?>
                                                <span class="badge-estado admin-badge-rechazada">
                                                    <i class="bi bi-x-circle-fill"></i> Rechazado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-estado borrador">
                                                    <i class="bi bi-pencil"></i> <?php echo htmlspecialchars(ucfirst($estCurso)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="celda-acciones-td">
                                            <div class="admin-acciones-contenedor">
                                                <a href="../cursos/ver.php?id=<?php echo $c['id']; ?>" target="_blank" class="btn-admin-ver" title="Previsualizar cómo se ve el curso completo">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>

                                                <?php if ($estCurso === 'pendiente'): ?>
                                                    <a href="solicitudes.php?accion=aprobar_curso&curso_id=<?php echo $c['id']; ?>&tab=cursos" class="btn-admin-aprobar" onclick="return confirm('¿Aprobar y publicar este curso en el catálogo?');" title="Aprobar curso">
                                                        <i class="bi bi-check2"></i> Aprobar
                                                    </a>
                                                    <a href="solicitudes.php?accion=rechazar_curso&curso_id=<?php echo $c['id']; ?>&tab=cursos" class="btn-admin-rechazar" onclick="return confirm('¿Rechazar esta propuesta de curso?');" title="Rechazar curso">
                                                        <i class="bi bi-x"></i> Rechazar
                                                    </a>
                                                <?php elseif ($estCurso === 'rechazado'): ?>
                                                    <a href="solicitudes.php?accion=aprobar_curso&curso_id=<?php echo $c['id']; ?>&tab=cursos" class="btn-admin-aprobar" onclick="return confirm('¿Reconsiderar y aprobar este curso?');" title="Reconsiderar y aprobar">
                                                        <i class="bi bi-check2"></i> Aprobar
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="caja-vacia">
                        <i class="bi bi-inbox"></i>
                        <h4>No hay cursos registrados</h4>
                        <p>Cuando los docentes creen propuestas de cursos, aparecerán aquí para su revisión y aprobación.</p>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>

            <section class="tarjeta-panel gestion-cursos-card">
                <div class="panel-cabecera-icono">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                    <div>
                        <h3>Comprobantes de Suscripción Plan PRO</h3>
                        <p class="panel-subtitulo-sec">Verificá los comprobantes bancarios y activá el Plan PRO con su fecha de vencimiento.</p>
                    </div>
                </div>

                <?php if (!empty($solicitudes)): ?>
                    <div class="tabla-responsive">
                        <table class="tabla-gestion">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Mensaje</th>
                                    <th>Comprobante</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Estado</th>
                                    <th class="celda-acciones-th">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes as $s): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($s['nombre'] . ' ' . $s['apellido']); ?></strong>
                                        </td>
                                        <td>
                                            <span><?php echo htmlspecialchars($s['email']); ?></span>
                                        </td>
                                        <td>
                                            <?php $sMsg = isset($s['mensaje']) ? $s['mensaje'] : 'Sin mensaje'; ?>
                                    <small><?php echo htmlspecialchars($sMsg); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($s['comprobante'])): ?>
                                                <a href="../<?php echo htmlspecialchars($s['comprobante']); ?>" target="_blank" class="btn-admin-comprobante" title="Ver comprobante de pago adjunto">
                                                    <i class="bi bi-file-earmark-arrow-down"></i> Ver Comprobante
                                                </a>
                                            <?php else: ?>
                                                <span class="texto-ayuda">Sin archivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span><?php echo date('d/m/Y H:i', strtotime($s['fecha_solicitud'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($s['estado'] === 'aprobada'): ?>
                                                <span class="badge-estado publicado">Aprobada (Vence: <?php echo date('d/m/Y', strtotime($s['fecha_vencimiento_pro'])); ?>)</span>
                                            <?php elseif ($s['estado'] === 'pendiente'): ?>
                                                <span class="badge-estado admin-badge-pendiente">Pendiente</span>
                                            <?php else: ?>
                                                <span class="badge-estado admin-badge-rechazada">Rechazada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="celda-acciones-td">
                                            <?php if ($s['estado'] === 'pendiente'): ?>
                                                <div class="admin-acciones-contenedor">
                                                    <form action="solicitudes.php?accion=aprobar_pro" method="POST" class="admin-form-activar-pro">
                                                        <input type="hidden" name="solicitud_id" value="<?php echo $s['solicitud_id']; ?>">
                                                        <input type="date" name="fecha_vencimiento" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" class="admin-date-input" title="Fecha de vencimiento del Plan PRO">
                                                        <button type="submit" class="btn-admin-activar-pro">
                                                            <i class="bi bi-check2"></i> Activar PRO
                                                        </button>
                                                    </form>
                                                    <a href="solicitudes.php?accion=rechazar_pro&solicitud_id=<?php echo $s['solicitud_id']; ?>" class="btn-admin-rechazar" onclick="return confirm('¿Rechazar esta solicitud?');" title="Rechazar solicitud">
                                                        <i class="bi bi-x"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="texto-ayuda">Procesada</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="caja-vacia">
                        <i class="bi bi-inbox"></i>
                        <h4>No hay solicitudes pendientes</h4>
                        <p>Cuando los usuarios envíen comprobantes de pago de Plan PRO, aparecerán aquí para su aprobación.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
