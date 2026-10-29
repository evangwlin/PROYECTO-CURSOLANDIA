<?php
$titulo_pagina = 'Detalle de Inscripción';
$menu_activo = 'inscripciones';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver el detalle de inscripción.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$es_pro = usuario_es_pro($usuario_id);

$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($curso_id <= 0) {
    header('Location: mis-inscripciones.php');
    exit;
}

$conexion = obtener_conexion();
$uid_seguro = intval($usuario_id);
$cid_seguro = intval($curso_id);

$resIns = mysqli_query($conexion, "
    SELECT i.id AS inscripcion_id, i.estado, i.progreso_porcentaje, i.fecha_inscripcion,
           c.id AS curso_id, c.docente_id, c.titulo, c.precio, c.imagen AS imagen, c.fecha_inicio, c.fecha_fin, c.etiquetas,
           c.categoria AS categoria_nombre, c.icono AS categoria_icono,
           u.nombre AS doc_nombre, u.apellido AS doc_apellido, u.email AS doc_email
    FROM inscripcion i
    JOIN curso c ON i.curso_id = c.id
    LEFT JOIN usuario u ON c.docente_id = u.id
    WHERE i.usuario_id = $uid_seguro AND c.id = $cid_seguro AND i.estado != 'cancelada'
    LIMIT 1
");

$row = $resIns ? mysqli_fetch_assoc($resIns) : null;
if (!$row) {
    $_SESSION['error'] = 'No se encontró la inscripción para este curso.';
    header('Location: mis-inscripciones.php');
    exit;
}

$rEtiq = isset($row['etiquetas']) ? $row['etiquetas'] : '';
        $tags = [];
if (!empty($rEtiq)) {
    $partesEtiq = explode(',', $rEtiq);
    foreach ($partesEtiq as $pEtiq) {
        $pEtiq = trim($pEtiq);
        if ($pEtiq !== '') $tags[] = $pEtiq;
    }
}
if (empty($tags)) $tags = ['#Cátedra'];

$dNom = isset($row['doc_nombre']) ? $row['doc_nombre'] : '';
        $dApe = isset($row['doc_apellido']) ? $row['doc_apellido'] : '';
        $docente_nombre = trim($dNom . ' ' . $dApe);

$ins = [
    'id'                 => $curso_id,
    'docente_id'         => isset($row['docente_id']) ? (int)$row['docente_id'] : 0,
    'titulo'             => $row['titulo'],
    'docente'            => $docente_nombre,
    'email_docente'      => isset($row['doc_email']) ? $row['doc_email'] : '',
    'categoria'          => isset($row['categoria_nombre']) ? $row['categoria_nombre'] : 'General',
    'imagen'             => isset($row['imagen']) ? $row['imagen'] : '',
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
    'numero_comprobante' => 'INS-' . date('Y') . '-' . str_pad($row['inscripcion_id'], 4, '0', STR_PAD_LEFT),
    'datos_transferencia' => [
        'titular' => $docente_nombre,
        'banco'   => 'Banco Santander',
        'cbu'     => '0720123488000034567891',
        'alias'   => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (isset($row['doc_nombre']) ? $row['doc_nombre'] : '') . '.' . (isset($row['doc_apellido']) ? $row['doc_apellido'] : ''))) . '.CURSOS'
    ]
];

$resU = mysqli_query($conexion, "SELECT nombre, apellido, email FROM usuario WHERE id = $uid_seguro LIMIT 1");
$rowU = $resU ? mysqli_fetch_assoc($resU) : null;
$uNom = isset($rowU['nombre']) ? $rowU['nombre'] : '';
        $uApe = isset($rowU['apellido']) ? $rowU['apellido'] : '';
        $nombre_estudiante = trim($uNom . ' ' . $uApe);
$email_estudiante  = isset($rowU['email']) ? $rowU['email'] : '';

$notificacion_enviada = false;
$metodo_peticion = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if ($metodo_peticion === 'POST' && isset($_POST['notificar_pago'])) {
    $notificacion_enviada = true;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div class="aula-header-migas">
            <a href="mis-inscripciones.php" class="enlace-ver-todos">
                ← Volver a Mis Inscripciones
            </a>
        </div>

        <header class="dashboard-cabecera">
            <div>
                <h1>Ficha de Inscripción</h1>
                <p>Comprobante oficial de matriculación y estado académico en Cursolandia.</p>
            </div>
            <div class="dashboard-cabecera-acciones">
                <button type="button" onclick="window.print();" class="btn-accion-secundaria">
                    <i class="bi bi-printer"></i> Imprimir Ficha
                </button>
                <?php if ($ins['estado'] === 'activa'): ?>
                    <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>" class="btn-primario">
                        <i class="bi bi-mortarboard-fill"></i> Ir al Aula Virtual
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($notificacion_enviada): ?>
            <div class="alerta-flash exito">
                <i class="bi bi-check-circle-fill"></i>
                <span>¡Aviso de transferencia enviado al docente! En breve verificará la acreditación para darte el alta.</span>
            </div>
        <?php endif; ?>

        <div class="detalle-inscripcion-grid">

            <div class="detalle-columna-gap">

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-file-earmark-check" class="icono-primario"></i>
                        <h3>Datos de la Matriculación</h3>
                    </div>

                    <div class="detalle-ficha-datos">
                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Comprobante N°</span>
                            <span class="detalle-dato-valor">#<?php echo htmlspecialchars($ins['numero_comprobante']); ?></span>
                        </div>

                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Fecha de Solicitud</span>
                            <span class="detalle-dato-valor"><?php echo htmlspecialchars($ins['fecha_inscripcion']); ?></span>
                        </div>

                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Estudiante</span>
                            <span class="detalle-dato-valor"><?php echo htmlspecialchars($nombre_estudiante); ?></span>
                        </div>

                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Email del Alumno</span>
                            <span class="detalle-dato-valor"><?php echo htmlspecialchars($email_estudiante); ?></span>
                        </div>

                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Tipo de Curso</span>
                            <span class="detalle-dato-valor">
                                <?php echo ($ins['tipo'] === 'gratis') ? 'Gratuito (Inscripción directa)' : 'Curso Arancelado'; ?>
                            </span>
                        </div>

                        <div class="detalle-dato-item">
                            <span class="detalle-dato-label">Arancel / Costo</span>
                            <span class="detalle-dato-valor">
                                <?php echo ($ins['tipo'] === 'gratis') ? 'Sin costo ($ 0)' : ('$ ' . number_format($ins['precio'], 0, ',', '.')); ?>
                            </span>
                        </div>
                    </div>
                </section>

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-mortarboard" class="icono-primario"></i>
                        <h3>Información de la Materia</h3>
                    </div>

                    <div class="detalle-columna-gap">
                        <div>
                            <h2 class="detalle-curso-titulo">
                                <?php echo htmlspecialchars($ins['titulo']); ?>
                            </h2>
                            <p class="detalle-curso-desc">
                                Categoría: <strong><?php echo htmlspecialchars($ins['categoria']); ?></strong>
                            </p>
                        </div>

                        <div class="detalle-ficha-datos">
                            <div class="detalle-dato-item">
                                <span class="detalle-dato-label">Docente a Cargo</span>
                                <span class="detalle-dato-valor">
                                    <?php if (!empty($ins['docente_id'])): ?>
                                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$ins['docente_id']; ?>" class="autor-nombre-link"><strong>Prof. <?php echo htmlspecialchars($ins['docente']); ?></strong></a>
                                    <?php else: ?>
                                        Prof. <?php echo htmlspecialchars($ins['docente']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="detalle-dato-item">
                                <span class="detalle-dato-label">Contacto Docente</span>
                                <span class="detalle-dato-valor"><?php echo htmlspecialchars($ins['email_docente']); ?></span>
                            </div>

                            <div class="detalle-dato-item">
                                <span class="detalle-dato-label">Período de Clases</span>
                                <span class="detalle-dato-valor"><?php echo htmlspecialchars($ins['periodo']); ?></span>
                            </div>

                            <div class="detalle-dato-item">
                                <span class="detalle-dato-label">Modalidad</span>
                                <span class="detalle-dato-valor">Online asincrónica + Foro</span>
                            </div>
                        </div>

                        <div class="modalidad-etiquetas">
                            <?php foreach ($ins['tags'] as $tag): ?>
                                <span class="pildora-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-chat-left-text" class="icono-primario"></i>
                        <h3>Foro y Recursos de la Cátedra</h3>
                    </div>

                    <p class="texto-ayuda">
                        Cada curso cuenta con un foro exclusivo para intercambiar preguntas con el docente y compañeros. Recordá las pautas de participación: no se puede responder dos veces consecutivas sin una respuesta intermedia, y las mejores soluciones pueden ser marcadas como "Aceptadas".
                    </p>

                    <div class="seccion-cabecera-flex">
                        <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>&tab=foro" class="btn-secundario">
                            <i class="bi bi-chat-dots"></i> Ingresar al Foro de Dudas
                        </a>
                        <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>&tab=materiales" class="btn-accion-secundaria">
                            <i class="bi bi-folder2-open"></i> Material de Cátedra (PDFs y Guías)
                        </a>
                    </div>
                </section>

            </div>

            <div class="detalle-columna-gap">

                <section class="tarjeta-panel">
                    <h3>Estado Académico</h3>

                    <div class="detalle-columna-gap">
                        <div>
                            <span class="detalle-dato-label">Estado de la Inscripción</span>
                            <?php if ($ins['estado'] === 'activa'): ?>
                                <span class="badge-estado-inscripcion activa" >
                                    <i class="bi bi-check-circle-fill"></i> Regular / En curso
                                </span>
                            <?php elseif ($ins['estado'] === 'pendiente'): ?>
                                <span class="badge-estado-inscripcion pendiente" >
                                    <i class="bi bi-hourglass-split"></i> Pendiente de pago
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($ins['estado'] === 'activa'): ?>
                            <div>
                                <span class="detalle-dato-label">Porcentaje de Avance</span>
                                <strong class="cupo-estado-tag"><?php echo $ins['progreso']; ?>%</strong>
                                <div class="barra-progreso-contenedor">
                                    <div class="barra-progreso-relleno" style="width: <?php echo $ins['progreso']; ?>%;"></div>
                                </div>
                            </div>
                            <p class="cupo-ayuda-texto">
                                <?php echo htmlspecialchars($ins['clase_actual']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($ins['estado'] === 'pendiente'): ?>
                    <section class="tarjeta-panel" id="datos-pago">
                        <div class="caja-transferencia">
                            <h4><i class="bi bi-bank"></i> Datos para Transferir</h4>
                            <p class="texto-ayuda">
                                El pago se coordina directamente con el docente creador según el reglamento:
                            </p>
                            <ul class="transferencia-lista">
                                <li><strong>Titular:</strong> <?php echo isset($ins['datos_transferencia']['titular']) ? $ins['datos_transferencia']['titular'] : $ins['docente']; ?></li>
                                <li><strong>Banco:</strong> <?php echo isset($ins['datos_transferencia']['banco']) ? $ins['datos_transferencia']['banco'] : 'Banco Santander'; ?></li>
                                <li><strong>CBU:</strong> <?php echo isset($ins['datos_transferencia']['cbu']) ? $ins['datos_transferencia']['cbu'] : '0720123488000034567891'; ?></li>
                                <li><strong>Alias:</strong> <?php echo htmlspecialchars($ins['datos_transferencia']['alias']); ?></li>
                                <li><strong>Monto exacto:</strong> $ <?php echo number_format($ins['precio'], 0, ',', '.'); ?></li>
                            </ul>
                        </div>

                        <a href="<?php echo $base_url; ?>cursos/pagar.php?id=<?php echo $curso_id; ?>" class="auth-btn-submit" class="auth-btn-submit btn-pago-accion-full">
                            <i class="bi bi-upload"></i> Subir Comprobante de Pago
                        </a>
                    </section>
                <?php endif; ?>

                <section class="tarjeta-panel seccion-cancelar-inscripcion">
                    <h4>
                        ¿Necesitás darte de baja?
                    </h4>
                    <p class="texto-ayuda">
                        Si cancelás tu inscripción, se liberará el cupo en tu plan para que puedas sumarte a otra materia.
                    </p>
                    <form action="mis-inscripciones.php" method="POST" onsubmit="return confirm('¿Confirmás la baja de este curso? Se liberará tu cupo.');">
                        <input type="hidden" name="accion" value="dar_baja">
                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                        <button type="submit" class="btn-baja-curso btn-cancelar-inscripcion">
                            <i class="bi bi-x-circle"></i> Confirmar baja del curso
                        </button>
                    </form>
                </section>

            </div>

        </div>

    </main>

</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
