<?php

$titulo_pagina = 'Alumnos Inscriptos';
$menu_activo = 'cursos';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/ingresar.php');
    exit;
}

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($curso_id <= 0) {
    header('Location: mis-cursos.php');
    exit;
}

$conexion = obtener_conexion();

$cid_seguro = intval($curso_id);
$uid_seguro = intval($usuario_id);
$resCurso = mysqli_query($conexion, "SELECT * FROM curso WHERE id = $cid_seguro AND docente_id = $uid_seguro LIMIT 1");
$curso = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

if (!$curso) {
    $_SESSION['error'] = 'No se encontró el curso o no tenés permisos para gestionarlo.';
    header('Location: mis-cursos.php');
    exit;
}


$resAlumnos = mysqli_query($conexion, "
    SELECT i.id AS inscripcion_id, i.estado AS estado_inscripcion, i.progreso_porcentaje, i.fecha_inscripcion,
           i.comprobante, i.fecha_comprobante, i.mensaje_pago,
           u.id AS usuario_id, u.nombre, u.apellido, u.email, u.avatar
    FROM inscripcion i
    JOIN usuario u ON i.usuario_id = u.id
    WHERE i.curso_id = $cid_seguro
    ORDER BY i.fecha_inscripcion DESC
");
$alumnos = [];
if ($resAlumnos) {
    while ($fila = mysqli_fetch_assoc($resAlumnos)) {
        $alumnos[] = $fila;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <header class="dashboard-cabecera">
            <div>
                <div class="mini-banner-migas">
                    <a href="mis-cursos.php"><i class="bi bi-arrow-left"></i> Volver a mis cursos</a>
                </div>
                <h1>Alumnos inscriptos</h1>
                <p>Gestión de estudiantes y confirmación de pagos para: <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong></p>
            </div>
            <div class="cabecera-acciones">
                <span class="kpi-chip kpi-chip-rosa">
                    <?php echo count($alumnos); ?> alumno(s) registrado(s)
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

        <section class="tarjeta-panel gestion-cursos-card">
            <?php if (!empty($alumnos)): ?>
                <div class="tabla-responsive">
                    <table class="tabla-gestion">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Email</th>
                                <th>Fecha de Inscripción</th>
                                <th>Progreso</th>
                                <th>Estado</th>
                                <th class="celda-acciones-der">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $a): ?>
                                <tr>
                                    <td>
                                        <div class="curso-celda-titulo">
                                            <a href="../perfil/index.php?id=<?php echo (int)$a['usuario_id']; ?>" class="alumno-fila-enlace">
                                                <div class="header-avatar avatar-alumno-mini">
                                                    <?php if (!empty($a['avatar'])): ?>
                                                        <img src="<?php echo htmlspecialchars(obtener_url_avatar($a['avatar'])); ?>" alt="Avatar">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($a['nombre'], 0, 1) . substr($a['apellido'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <strong class="curso-nombre-tabla-link"><?php echo htmlspecialchars($a['nombre'] . ' ' . $a['apellido']); ?></strong>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span><?php echo htmlspecialchars($a['email']); ?></span>
                                    </td>
                                    <td>
                                        <span><?php echo date('d/m/Y', strtotime($a['fecha_inscripcion'])); ?></span>
                                    </td>
                                    <td>
                                        <span><?php echo (int)$a['progreso_porcentaje']; ?>%</span>
                                    </td>
                                    <td>
                                        <?php if ($a['estado_inscripcion'] === 'activa'): ?>
                                            <span class="badge-estado publicado">
                                                <i class="bi bi-check-circle-fill"></i> Activo
                                            </span>
                                        <?php elseif ($a['estado_inscripcion'] === 'pendiente'): ?>
                                            <span class="badge-estado pendiente">
                                                <i class="bi bi-hourglass-split"></i> Pago Pendiente
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado cancelada">
                                                Cancelada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="celda-acciones-der">
                                        <?php if (!empty($a['comprobante'])): ?>
                                            <a href="<?php echo $base_url . htmlspecialchars($a['comprobante']); ?>" target="_blank" class="btn-tabla-pildora btn-ver-comprobante-alumno" title="<?php echo !empty($a['mensaje_pago']) ? htmlspecialchars('Nota: ' . $a['mensaje_pago']) : 'Ver comprobante adjuntado por el alumno'; ?>">
                                                <i class="bi bi-file-earmark-check"></i> Ver Comprobante
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($a['estado_inscripcion'] === 'pendiente'): ?>
                                            <a href="../controladores/curso_controlador.php?accion=confirmar_pago&inscripcion_id=<?php echo $a['inscripcion_id']; ?>&curso_id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora btn-confirmar-pago-alumno" title="Confirmar recepción de pago y habilitar curso">
                                                <i class="bi bi-check2-circle"></i> Confirmar Pago
                                            </a>
                                        <?php else: ?>
                                            <span class="texto-ayuda">Habilitado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="caja-vacia">
                    <i class="bi bi-people"></i>
                    <h4>Aún no hay alumnos inscriptos</h4>
                    <p>Cuando los estudiantes se anoten a tu curso, los verás listados acá con su estado de cursada.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
