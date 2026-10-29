<?php
$titulo_pagina = 'Detalle de Inscripción';
$menu_activo = 'inscripciones';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para ver el detalle de inscripción.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$es_pro = usuario_es_pro($usuario_id);

if (!isset($_SESSION['inscripciones'])) {
    $_SESSION['inscripciones'] = [
        1 => [
            'id' => 1,
            'titulo' => 'Desarrollo Web Fullstack con PHP',
            'docente' => 'Carlos Gómez',
            'email_docente' => 'carlos.gomez@cursolandia.com',
            'categoria' => 'Programación',
            'icono' => 'bi-code-slash',
            'tipo' => 'pago',
            'precio' => 12000,
            'estado' => 'activa',
            'estado_pago' => 'confirmado',
            'fecha_inscripcion' => '16/10/2026',
            'periodo' => '15 Oct - 20 Dic',
            'progreso' => 65,
            'clase_actual' => 'Clase 8 de 12: Arquitectura MVC y Conexión PDO',
            'tags' => ['#PHP', '#MySQL', '#Fullstack'],
            'numero_comprobante' => 'INS-2026-0041'
        ],
        4 => [
            'id' => 4,
            'titulo' => 'Diseño Web Moderno y Responsive',
            'docente' => 'Laura Martínez',
            'email_docente' => 'laura.martinez@cursolandia.com',
            'categoria' => 'Arte & Diseño',
            'icono' => 'bi-palette',
            'tipo' => 'gratis',
            'precio' => 0,
            'estado' => 'activa',
            'estado_pago' => 'gratuito',
            'fecha_inscripcion' => '02/11/2026',
            'periodo' => '01 Nov - 15 Dic',
            'progreso' => 30,
            'clase_actual' => 'Clase 3 de 10: CSS Grid y Layouts Flexibles',
            'tags' => ['#Frontend', '#CSS', '#Responsive'],
            'numero_comprobante' => 'INS-2026-0089'
        ],
        2 => [
            'id' => 2,
            'titulo' => 'Ingeniería de Software y Modelado 3FN',
            'docente' => 'Martín Ruiz',
            'email_docente' => 'martin.ruiz@cursolandia.com',
            'categoria' => 'Ciencia & Datos',
            'icono' => 'bi-diagram-3',
            'tipo' => 'pago',
            'precio' => 8500,
            'estado' => 'pendiente',
            'estado_pago' => 'pendiente',
            'fecha_inscripcion' => '12/11/2026',
            'periodo' => '20 Oct - 30 Nov',
            'progreso' => 0,
            'clase_actual' => 'Esperando verificación del pago por el docente',
            'tags' => ['#IngSoftware', '#Patrones', '#BasesDeDatos'],
            'numero_comprobante' => 'INS-2026-0105',
            'datos_transferencia' => [
                'titular' => 'Martín Ruiz',
                'cbu' => '0720123488000034567891',
                'alias' => 'MARTIN.RUIZ.CURSOS',
                'banco' => 'Banco Santander'
            ]
        ]
    ];
}

// id de curso
$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id > 0 && (!isset($_SESSION['inscripciones'][$curso_id]) || empty($_SESSION['inscripciones'][$curso_id]['titulo']))) {
    require_once __DIR__ . '/../config/database.php';
    $conexion = obtener_conexion();
    $uid_seguro = intval($usuario_id);
    $cid_seguro = intval($curso_id);
    $resIns = mysqli_query($conexion, "
        SELECT i.id AS inscripcion_id, i.estado, i.progreso_porcentaje, i.fecha_inscripcion,
               c.id AS curso_id, c.docente_id, c.titulo, c.precio, c.imagen AS imagen, c.fecha_inicio, c.fecha_fin, c.etiquetas,
               c.categoria AS categoria_nombre, c.icono AS categoria_icono,
               u.nombre AS doc_nombre, u.apellido AS doc_apellido, u.email AS doc_email
        FROM Inscripcion i
        JOIN Curso c ON i.curso_id = c.id
        LEFT JOIN Usuario u ON c.docente_id = u.id
        WHERE i.usuario_id = $uid_seguro AND c.id = $cid_seguro
        LIMIT 1
    ");
    $row = $resIns ? mysqli_fetch_assoc($resIns) : null;
    if ($row) {
            $tags = array_filter(array_map('trim', explode(',', $row['etiquetas'] ?? '')));
            if (empty($tags)) $tags = ['#Cátedra'];
            $_SESSION['inscripciones'][$curso_id] = [
                'id'                 => $curso_id,
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

if (!isset($_SESSION['inscripciones'][$curso_id]) || $_SESSION['inscripciones'][$curso_id]['estado'] === 'cancelada') {
    header('Location: mis-inscripciones.php');
    exit;
}

$ins = $_SESSION['inscripciones'][$curso_id];

$nombre_estudiante = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario']['nombre'] ?? 'Estudiante';
$email_estudiante  = $_SESSION['usuario_email'] ?? $_SESSION['usuario']['email'] ?? '';

if ($usuario_id > 0) {
    if (!isset($conexion)) {
        require_once __DIR__ . '/../config/database.php';
        $conexion = obtener_conexion();
    }
    $resU = mysqli_query($conexion, "SELECT nombre, apellido, email FROM Usuario WHERE id = " . intval($usuario_id) . " LIMIT 1");
    if ($resU && $rowU = mysqli_fetch_assoc($resU)) {
        $nombre_estudiante = trim($rowU['nombre'] . ' ' . ($rowU['apellido'] ?? ''));
        if (!empty($rowU['email'])) {
            $email_estudiante = $rowU['email'];
        }
    }
}

$notificacion_enviada = false;
$metodo_peticion = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo_peticion === 'POST' && isset($_POST['notificar_pago'])) {
    $notificacion_enviada = true;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div style="margin-bottom: 8px;">
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

            <div style="display: flex; flex-direction: column; gap: 20px;">

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-file-earmark-check" style="font-size: 1.3rem; color: var(--color-primary);"></i>
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
                        <i class="bi bi-mortarboard" style="font-size: 1.3rem; color: var(--color-primary);"></i>
                        <h3>Información de la Materia</h3>
                    </div>

                    <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <h2 style="font-size: 1.35rem; color: var(--color-text-main); margin-bottom: 4px;">
                                <?php echo htmlspecialchars($ins['titulo']); ?>
                            </h2>
                            <p style="font-size: 0.9rem; color: var(--color-text-muted);">
                                Categoría: <strong><?php echo htmlspecialchars($ins['categoria']); ?></strong>
                            </p>
                        </div>

                        <div class="detalle-ficha-datos">
                            <div class="detalle-dato-item">
                                <span class="detalle-dato-label">Docente a Cargo</span>
                                <span class="detalle-dato-valor">
                                    <?php if (!empty($ins['docente_id'])): ?>
                                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$ins['docente_id']; ?>" style="color: inherit; text-decoration: none; font-weight: 700;">Prof. <?php echo htmlspecialchars($ins['docente']); ?></a>
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

                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                            <?php foreach ($ins['tags'] as $tag): ?>
                                <span class="pildora-tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="tarjeta-panel">
                    <div class="panel-cabecera-icono">
                        <i class="bi bi-chat-left-text" style="font-size: 1.3rem; color: var(--color-primary);"></i>
                        <h3>Foro y Recursos de la Cátedra</h3>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--color-text-body); margin-top: 10px; line-height: 1.5;">
                        Cada curso cuenta con un foro exclusivo para intercambiar preguntas con el docente y compañeros. Recordá las pautas de participación: no se puede responder dos veces consecutivas sin una respuesta intermedia, y las mejores soluciones pueden ser marcadas como "Aceptadas".
                    </p>

                    <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>&tab=foro" class="btn-secundario">
                            <i class="bi bi-chat-dots"></i> Ingresar al Foro de Dudas
                        </a>
                        <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso_id; ?>&tab=materiales" class="btn-accion-secundaria">
                            <i class="bi bi-folder2-open"></i> Material de Cátedra (PDFs y Guías)
                        </a>
                    </div>
                </section>

            </div>

            <div style="display: flex; flex-direction: column; gap: 20px;">

                <section class="tarjeta-panel">
                    <h3>Estado Académico</h3>

                    <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <span class="detalle-dato-label">Estado de la Inscripción</span>
                            <?php if ($ins['estado'] === 'activa'): ?>
                                <span class="badge-estado-inscripcion activa" style="font-size: 0.9rem; padding: 6px 14px;">
                                    <i class="bi bi-check-circle-fill"></i> Regular / En curso
                                </span>
                            <?php elseif ($ins['estado'] === 'pendiente'): ?>
                                <span class="badge-estado-inscripcion pendiente" style="font-size: 0.9rem; padding: 6px 14px;">
                                    <i class="bi bi-hourglass-split"></i> Pendiente de pago
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($ins['estado'] === 'activa'): ?>
                            <div>
                                <span class="detalle-dato-label">Porcentaje de Avance</span>
                                <strong style="font-size: 1.2rem; color: var(--color-primary);"><?php echo $ins['progreso']; ?>%</strong>
                                <div class="barra-progreso-contenedor" style="margin-top: 6px;">
                                    <div class="barra-progreso-relleno" style="width: <?php echo $ins['progreso']; ?>%;"></div>
                                </div>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--color-text-muted);">
                                <?php echo htmlspecialchars($ins['clase_actual']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($ins['estado'] === 'pendiente'): ?>
                    <section class="tarjeta-panel" id="datos-pago">
                        <div class="caja-transferencia">
                            <h4><i class="bi bi-bank"></i> Datos para Transferir</h4>
                            <p style="font-size: 0.82rem; margin-bottom: 10px;">
                                El pago se coordina directamente con el docente creador según el reglamento:
                            </p>
                            <ul class="transferencia-lista">
                                <li><strong>Titular:</strong> <?php echo $ins['datos_transferencia']['titular'] ?? $ins['docente']; ?></li>
                                <li><strong>Banco:</strong> <?php echo $ins['datos_transferencia']['banco'] ?? 'Banco Santander'; ?></li>
                                <li><strong>CBU:</strong> <?php echo $ins['datos_transferencia']['cbu'] ?? '0720123488000034567891'; ?></li>
                                <li><strong>Alias:</strong> <?php echo $ins['datos_transferencia']['alias'] ?? 'MARTIN.RUIZ.CURSOS'; ?></li>
                                <li><strong>Monto exacto:</strong> $ <?php echo number_format($ins['precio'], 0, ',', '.'); ?></li>
                            </ul>
                        </div>

                        <form action="" method="POST" style="margin-top: 14px;">
                            <input type="hidden" name="notificar_pago" value="1">
                            <button type="submit" class="auth-btn-submit" style="width: 100%;">
                                <i class="bi bi-send-check"></i> Ya realicé la transferencia
                            </button>
                        </form>
                    </section>
                <?php endif; ?>

                <section class="tarjeta-panel" style="border-color: #fee2e2;">
                    <h4 style="font-size: 0.95rem; color: #991b1b; margin-bottom: 6px;">
                        ¿Necesitás darte de baja?
                    </h4>
                    <p style="font-size: 0.82rem; color: var(--color-text-muted); margin-bottom: 12px;">
                        Si cancelás tu inscripción, se liberará el cupo en tu plan para que puedas sumarte a otra materia.
                    </p>
                    <form action="mis-inscripciones.php" method="POST" onsubmit="return confirm('¿Confirmás la baja de este curso? Se liberará tu cupo.');">
                        <input type="hidden" name="accion" value="dar_baja">
                        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                        <button type="submit" class="btn-baja-curso" style="padding: 8px 12px; background: #fee2e2; border-radius: var(--radius-pill); font-weight: 700;">
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
