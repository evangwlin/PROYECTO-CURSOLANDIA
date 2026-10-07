<?php
$titulo_pagina = 'Mi Perfil';
$menu_activo = 'perfil';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();
$conexion = obtener_conexion();

$mi_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
$id_solicitado = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_solicitado <= 0 && $mi_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver tu perfil.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = ($id_solicitado > 0) ? $id_solicitado : $mi_id;
$es_mi_perfil = ($mi_id > 0 && $usuario_id === $mi_id);

// cargar usuario de la bd
$usuarioBD = null;
if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    $resUser = mysqli_query($conexion, "SELECT * FROM Usuario WHERE id = $uid_seguro LIMIT 1");
    $usuarioBD = $resUser ? mysqli_fetch_assoc($resUser) : null;
}

if ($usuarioBD) {
    $nombre_completo = trim(($usuarioBD['nombre'] ?? '') . ' ' . ($usuarioBD['apellido'] ?? ''));
    $primer_nombre = $usuarioBD['nombre'] ?? $nombre_completo;
    $username = !empty($usuarioBD['username']) ? $usuarioBD['username'] : strtolower(str_replace(' ', '', ($usuarioBD['nombre'] ?? '') . ($usuarioBD['apellido'] ?? '')));
    $email = $usuarioBD['email'] ?? '';
    $es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : false;
    $biografia = !empty($usuarioBD['biografia']) ? $usuarioBD['biografia'] : 'Sin biografía cargada todavía.';
    $intereses_str = $usuarioBD['intereses'] ?? 'PHP, MySQL, Web';
    $avatar = $usuarioBD['avatar'] ?? null;
    $fecha_venc_pro = $usuarioBD['fecha_vencimiento_pro'] ?? null;
    $fecha_registro = date('d/m/Y', strtotime($usuarioBD['fecha_registro'] ?? 'now'));
    $rol_usuario = $usuarioBD['rol'] ?? 'usuario';
} else {
    $usuario = $_SESSION['usuario'] ?? [];
    $nombre_completo = $usuario['nombre'] ?? 'Alex Gómez';
    $primer_nombre = 'Alex';
    $username = $usuario['username'] ?? 'alexgomez';
    $email = $usuario['email'] ?? 'alex@ejemplo.com';
    $es_pro = (($usuario['plan'] ?? 'gratuito') === 'pro');
    $biografia = $usuario['antecedentes'] ?? 'Desarrollador web e instructor en Cursolandia.';
    $intereses_str = $usuario['intereses'] ?? 'PHP, MySQL, Desarrollo Web, APIs';
    $avatar = $_SESSION['usuario_avatar'] ?? $usuario['avatar'] ?? null;
    $fecha_venc_pro = null;
    $fecha_registro = 'Octubre 2026';
    $rol_usuario = $usuario['rol'] ?? 'usuario';
}

$titulo_pagina = $es_mi_perfil ? 'Mi Perfil' : 'Perfil de ' . $nombre_completo;

$iniciales = strtoupper(substr($nombre_completo, 0, 2));
$lista_intereses = array_filter(array_map('trim', explode(',', str_replace(['#', ';'], ',', $intereses_str))));

// conteo de actividad
$totalEnCurso = 0;
$totalCompletados = 0;
$totalCreados = ($usuario_id > 0) ? contar_cursos_activos_docente($usuario_id) : 0;
$horasReales = 0;
$cursosCompletados = [];
$cursosDocente = [];

if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    
    // cursos en proceso
    $resEnCurso = mysqli_query($conexion, "
        SELECT COUNT(*) AS total FROM Inscripcion 
        WHERE usuario_id = $uid_seguro AND estado IN ('activa', 'pendiente') AND progreso_porcentaje < 100
    ");
    $filaEC = $resEnCurso ? mysqli_fetch_assoc($resEnCurso) : null;
    $totalEnCurso = $filaEC ? intval($filaEC['total']) : 0;

    // certificados
    $resCerts = mysqli_query($conexion, "
        SELECT c.id, c.titulo, c.duracion_horas, i.fecha_inscripcion
        FROM Inscripcion i
        JOIN Curso c ON i.curso_id = c.id
        WHERE i.usuario_id = $uid_seguro AND (i.estado = 'completada' OR i.progreso_porcentaje >= 100)
        ORDER BY i.fecha_inscripcion DESC
    ");
    $cursosCompletados = [];
    if ($resCerts) {
        while ($fila = mysqli_fetch_assoc($resCerts)) {
            $cursosCompletados[] = $fila;
        }
    }
    $totalCompletados = count($cursosCompletados);

    // horas reales
    $resHoras = mysqli_query($conexion, "
        SELECT COALESCE(SUM(ROUND(c.duracion_horas * (i.progreso_porcentaje / 100))), 0) AS total_horas
        FROM Inscripcion i
        JOIN Curso c ON i.curso_id = c.id
        WHERE i.usuario_id = $uid_seguro AND i.estado IN ('activa', 'completada')
    ");
    $filaHoras = $resHoras ? mysqli_fetch_assoc($resHoras) : null;
    $horasReales = $filaHoras ? intval($filaHoras['total_horas']) : 0;

    // cursos dictados si es docente
    if ($totalCreados > 0) {
        $resCursosDoc = mysqli_query($conexion, "
            SELECT c.*, 
                   (SELECT COUNT(*) FROM Inscripcion WHERE curso_id = c.id AND estado IN ('activa', 'pendiente')) AS inscriptos
            FROM Curso c
            WHERE c.docente_id = $uid_seguro AND c.estado = 'publicado'
            ORDER BY c.id DESC
        ");
        $cursosDocente = [];
        if ($resCursosDoc) {
            while ($fila = mysqli_fetch_assoc($resCursosDoc)) {
                $cursosDocente[] = $fila;
            }
        }
    }
}

$certificadosCount = $totalCompletados;

$usuario_logueado = ($mi_id > 0);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido">

        <div class="perfil-card-hero">
            <div class="perfil-hero-portada"></div>
            
            <div class="perfil-hero-cuerpo">
                <div class="perfil-hero-avatar-wrapper">
                    <div class="perfil-hero-avatar" style="overflow: hidden;">
                        <?php if (!empty($avatar)): ?>
                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($avatar)); ?>?v=<?php echo time(); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <span><?php echo $iniciales; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="perfil-hero-datos">
                    <div class="perfil-hero-titulos">
                        <div class="perfil-hero-nombre-fila" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <h2><?php echo htmlspecialchars($nombre_completo); ?></h2>
                                <?php if ($rol_usuario === 'admin'): ?>
                                    <span class="badge-estado publicado" style="font-size: 0.8rem; padding: 4px 12px;">
                                        Administrador
                                    </span>
                                <?php endif; ?>
                                <?php if ($es_pro): ?>
                                    <span class="badge-recomendado" style="font-size: 0.72rem; padding: 3px 8px;">
                                        <i class="bi bi-star-fill"></i> PRO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($es_mi_perfil): ?>
                                <a href="<?php echo $base_url; ?>perfil/configuracion.php" class="btn-tabla-pildora" style="font-size: 0.82rem; padding: 6px 14px;">
                                    <i class="bi bi-gear"></i> Editar perfil
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="perfil-hero-badges-fila">
                            <span class="perfil-dato-usuario"><i class="bi bi-at"></i><?php echo htmlspecialchars($username); ?></span>
                            <span class="perfil-separador">•</span>
                            <span class="perfil-dato-meta"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($email); ?></span>
                            <span class="perfil-separador">•</span>
                            <span class="perfil-dato-meta"><i class="bi bi-calendar-check"></i> Miembro desde <?php echo $fecha_registro; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="perfil-vista-grid">

            <div class="perfil-vista-col-principal">

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-person-lines-fill"></i>
                        <h3><?php echo $es_mi_perfil ? 'Acerca de mí (Antecedentes)' : 'Acerca de ' . htmlspecialchars($primer_nombre); ?></h3>
                    </div>
                    <p class="perfil-bio-texto">
                        <?php echo nl2br(htmlspecialchars($biografia)); ?>
                    </p>
                </section>

                <?php if (!empty($cursosDocente)): ?>
                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-mortarboard-fill"></i>
                        <h3>Cursos Creados (<?php echo count($cursosDocente); ?>)</h3>
                    </div>
                    <p class="texto-ayuda" style="margin-bottom: 16px;">Propuestas formativas creadas por este usuario.</p>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($cursosDocente as $cd): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 12px; background: #ffffff;">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                    <i class="bi <?php echo htmlspecialchars($cd['icono'] ?? 'bi-journal-bookmark'); ?>" style="font-size: 1.4rem; color: var(--color-primary); flex-shrink: 0;"></i>
                                    <div style="min-width: 0;">
                                        <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>" class="curso-nombre-tabla-link" style="display: block; font-weight: 700; color: var(--color-text-main); text-decoration: none; font-size: 0.95rem;">
                                            <?php echo htmlspecialchars($cd['titulo']); ?>
                                        </a>
                                        <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                                            <?php echo htmlspecialchars($cd['categoria'] ?? 'General'); ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>" class="btn-tabla-pildora" style="flex-shrink: 0;">
                                    Ver Curso
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-patch-check"></i>
                        <h3>Certificados Oficiales</h3>
                    </div>
                    <p class="texto-ayuda" style="margin-bottom: 16px;">Títulos y credenciales validadas emitidas por Cursolandia.</p>
                    
                    <?php if (!empty($cursosCompletados)): ?>
                        <div class="grilla-certificados">
                            <?php foreach ($cursosCompletados as $cert): ?>
                                <div class="tarjeta-certificado">
                                    <div class="certificado-cabecera">
                                        <span class="badge-verificado">Verificado</span>
                                    </div>
                                    <h4><?php echo htmlspecialchars($cert['titulo']); ?></h4>
                                    <p class="cert-fecha">Acreditado: <?php echo date('d/m/Y', strtotime($cert['fecha_inscripcion'])); ?></p>
                                    <span class="cert-id">ID: CURS-<?php echo date('Y'); ?>-<?php echo str_pad($cert['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="caja-vacia" style="padding: 24px; text-align: center; border: 1px dashed var(--border-color); border-radius: 12px; background: var(--bg-page);">
                            <i class="bi bi-award" style="font-size: 2rem; color: var(--color-primary); margin-bottom: 8px; display: inline-block;"></i>
                            <h4 style="font-size: 0.95rem; margin-bottom: 4px; color: var(--color-text-title);">Aún no tiene certificados emitidos</h4>
                            <p class="texto-ayuda" style="margin: 0;">Al completar el 100% de los cursos se obtienen diplomas oficiales de Cursolandia.</p>
                        </div>
                    <?php endif; ?>
                </section>

            </div>

            <aside class="perfil-vista-col-lateral">

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-tags-fill"></i>
                        <h3>Intereses</h3>
                    </div>
                    <p class="texto-ayuda" style="margin-bottom: 12px;">Temas favoritos del usuario.</p>
                    <div class="perfil-tags-pildoras">
                        <?php foreach ($lista_intereses as $tag): ?>
                            <?php if (!empty($tag)): ?>
                                <span class="perfil-tag-chip">#<?php echo htmlspecialchars(ltrim($tag, '#')); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-graph-up"></i>
                        <h3 style="margin: 0;"><?php echo $es_mi_perfil ? 'Mi Actividad' : 'Actividad'; ?></h3>
                    </div>
                    <div class="stat-items-grid" style="margin-top: 12px;">
                        <?php if ($es_mi_perfil): ?>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="stat-item-box" style="text-decoration: none; color: inherit;" title="Ver cursos en proceso">
                                <span class="stat-num"><?php echo $totalEnCurso; ?></span>
                                <span class="stat-lbl">En proceso</span>
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="stat-item-box" style="text-decoration: none; color: inherit;" title="Ver cursos completados">
                                <span class="stat-num"><?php echo $totalCompletados; ?></span>
                                <span class="stat-lbl">Completados</span>
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="stat-item-box" style="text-decoration: none; color: inherit;" title="Ver mis cursos creados">
                                <span class="stat-num"><?php echo $totalCreados; ?></span>
                                <span class="stat-lbl">Mis Cursos</span>
                            </a>
                        <?php else: ?>
                            <div class="stat-item-box">
                                <span class="stat-num"><?php echo $totalEnCurso; ?></span>
                                <span class="stat-lbl">En proceso</span>
                            </div>
                            <div class="stat-item-box">
                                <span class="stat-num"><?php echo $totalCompletados; ?></span>
                                <span class="stat-lbl">Completados</span>
                            </div>
                            <div class="stat-item-box">
                                <span class="stat-num"><?php echo $totalCreados; ?></span>
                                <span class="stat-lbl">Cursos</span>
                            </div>
                        <?php endif; ?>
                        <div class="stat-item-box" title="Horas reales aprendidas acumuladas">
                            <span class="stat-num"><?php echo $horasReales; ?><small>hs</small></span>
                            <span class="stat-lbl">Aprendizaje</span>
                        </div>
                    </div>
                </section>

            </aside>

        </div>

    </main>

</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
