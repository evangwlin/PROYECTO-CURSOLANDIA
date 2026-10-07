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

$mi_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $mi_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $mi_id = (int)$_SESSION['usuario']['id'];
}
$id_solicitado = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_solicitado <= 0 && $mi_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver tu perfil.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = ($id_solicitado > 0) ? $id_solicitado : $mi_id;
$es_mi_perfil = ($mi_id > 0 && $usuario_id === $mi_id);

// cargar usuario de la bd
$uid_seguro = intval($usuario_id);
$resUser = mysqli_query($conexion, "SELECT * FROM usuario WHERE id = $uid_seguro LIMIT 1");
$usuarioBD = $resUser ? mysqli_fetch_assoc($resUser) : null;

if (!$usuarioBD) {
    $_SESSION['error'] = 'El perfil solicitado no existe.';
    header('Location: ../panel/index.php');
    exit;
}

$uNom = isset($usuarioBD['nombre']) ? $usuarioBD['nombre'] : '';
$uApe = isset($usuarioBD['apellido']) ? $usuarioBD['apellido'] : '';
$nombre_completo = trim($uNom . ' ' . $uApe);
$primer_nombre = !empty($uNom) ? $uNom : $nombre_completo;
$username = !empty($usuarioBD['username']) ? $usuarioBD['username'] : strtolower(str_replace(' ', '', $uNom . $uApe));
$email = isset($usuarioBD['email']) ? $usuarioBD['email'] : '';
$es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : false;
$biografia = !empty($usuarioBD['biografia']) ? $usuarioBD['biografia'] : 'Sin biografía cargada todavía.';
$intereses_str = isset($usuarioBD['intereses']) ? $usuarioBD['intereses'] : 'PHP, MySQL, Web';
$avatar = isset($usuarioBD['avatar']) ? $usuarioBD['avatar'] : null;
$fecha_venc_pro = isset($usuarioBD['fecha_vencimiento_pro']) ? $usuarioBD['fecha_vencimiento_pro'] : null;
$fReg = isset($usuarioBD['fecha_registro']) ? $usuarioBD['fecha_registro'] : 'now';
$fecha_registro = date('d/m/Y', strtotime($fReg));
$rol_usuario = isset($usuarioBD['rol']) ? $usuarioBD['rol'] : 'usuario';

$titulo_pagina = $es_mi_perfil ? 'Mi Perfil' : 'Perfil de ' . $nombre_completo;

$iniciales = strtoupper(substr($nombre_completo, 0, 2));
$lista_intereses = [];
if (!empty($intereses_str)) {
    $limpios = str_replace(['#', ';'], ',', $intereses_str);
    foreach (explode(',', $limpios) as $pt) {
        $pt = trim($pt);
        if ($pt !== '') $lista_intereses[] = $pt;
    }
}

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
        SELECT COUNT(*) AS total FROM inscripcion 
        WHERE usuario_id = $uid_seguro AND estado IN ('activa', 'pendiente') AND progreso_porcentaje < 100
    ");
    $filaEC = $resEnCurso ? mysqli_fetch_assoc($resEnCurso) : null;
    $totalEnCurso = $filaEC ? intval($filaEC['total']) : 0;

    // certificados
    $resCerts = mysqli_query($conexion, "
        SELECT c.id, c.titulo, c.duracion_horas, i.fecha_inscripcion
        FROM inscripcion i
        JOIN curso c ON i.curso_id = c.id
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
        FROM inscripcion i
        JOIN curso c ON i.curso_id = c.id
        WHERE i.usuario_id = $uid_seguro AND i.estado IN ('activa', 'completada')
    ");
    $filaHoras = $resHoras ? mysqli_fetch_assoc($resHoras) : null;
    $horasReales = $filaHoras ? intval($filaHoras['total_horas']) : 0;

   
    if ($totalCreados > 0) {
        $resCursosDoc = mysqli_query($conexion, "
            SELECT c.*, 
                   (SELECT COUNT(*) FROM inscripcion WHERE curso_id = c.id AND estado IN ('activa', 'pendiente')) AS inscriptos
            FROM curso c
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
                    <div class="perfil-hero-avatar">
                        <?php if (!empty($avatar)): ?>
                            <img src="<?php echo htmlspecialchars(obtener_url_avatar($avatar)); ?>?v=<?php echo time(); ?>" alt="Avatar" >
                        <?php else: ?>
                            <span><?php echo $iniciales; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="perfil-hero-datos">
                    <div class="perfil-hero-titulos">
                        <div class="perfil-hero-nombre-fila perfil-hero-nombre-fila-flex">
                            <div class="perfil-hero-tags-fila">
                                <h2><?php echo htmlspecialchars($nombre_completo); ?></h2>
                                <?php if ($rol_usuario === 'admin'): ?>
                                    <span class="badge-estado publicado">
                                        Administrador
                                    </span>
                                <?php endif; ?>
                                <?php if ($es_pro): ?>
                                    <span class="badge-recomendado">
                                        <i class="bi bi-star-fill"></i> PRO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($es_mi_perfil): ?>
                                <a href="<?php echo $base_url; ?>perfil/configuracion.php" class="btn-tabla-pildora">
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
                    <p class="texto-ayuda">Propuestas formativas creadas por este usuario.</p>
                    <div class="perfil-cursos-creados-lista">
                        <?php foreach ($cursosDocente as $cd): ?>
                            <div class="perfil-curso-creado-item">
                                <div class="perfil-curso-info-lado">
                                    <i class="bi <?php echo isset($cd['icono']) ? htmlspecialchars($cd['icono']) : 'bi-journal-bookmark'; ?> perfil-icono-curso"></i>
                                    <div>
                                        <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>" class="curso-nombre-tabla-link">
                                            <?php echo htmlspecialchars($cd['titulo']); ?>
                                        </a>
                                        <span class="curso-meta-txt">
                                            <?php echo isset($cd['categoria']) ? htmlspecialchars($cd['categoria']) : 'General'; ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $cd['id']; ?>" class="btn-tabla-pildora">
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
                    <p class="texto-ayuda">Títulos y credenciales validadas emitidas por Cursolandia.</p>
                    
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
                        <div class="perfil-certificados-vacio">
                            <i class="bi bi-award"></i>
                            <h4>Aún no tiene certificados emitidos</h4>
                            <p class="texto-ayuda">Al completar el 100% de los cursos se obtienen diplomas oficiales de Cursolandia.</p>
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
                    <p class="texto-ayuda">Temas favoritos del usuario.</p>
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
                        <h3><?php echo $es_mi_perfil ? 'Mi Actividad' : 'Actividad'; ?></h3>
                    </div>
                    <div class="stat-items-grid">
                        <?php if ($es_mi_perfil): ?>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="stat-item-box" title="Ver cursos en proceso">
                                <span class="stat-num"><?php echo $totalEnCurso; ?></span>
                                <span class="stat-lbl">En proceso</span>
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="stat-item-box" title="Ver cursos completados">
                                <span class="stat-num"><?php echo $totalCompletados; ?></span>
                                <span class="stat-lbl">Completados</span>
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="stat-item-box" title="Ver mis cursos creados">
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
