<?php

$titulo_pagina = 'Pago de Inscripción';
$menu_activo = 'inscripciones';

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

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id']);
$curso_id = (int)($_GET['id'] ?? 0);

$conexion = obtener_conexion();
$uid_seguro = intval($usuario_id);
$cid_seguro = intval($curso_id);

if ($curso_id <= 0) {
    $resPend = mysqli_query($conexion, "
        SELECT curso_id FROM Inscripcion 
        WHERE usuario_id = $uid_seguro AND estado = 'pendiente' 
        ORDER BY fecha_inscripcion DESC LIMIT 1
    ");
    $pendRow = $resPend ? mysqli_fetch_assoc($resPend) : null;
    $primeraPend = $pendRow ? $pendRow['curso_id'] : null;

    if ($primeraPend) {
        header('Location: pagar.php?id=' . (int)$primeraPend);
        exit;
    }

    header('Location: mis-inscripciones.php');
    exit;
}

// consultar curso
$resCurso = mysqli_query($conexion, "
    SELECT c.*, COALESCE(cat.nombre, c.categoria) AS categoria_nombre, cat.icono AS categoria_icono,
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.email AS docente_email, u.avatar AS docente_avatar,
           i.id AS inscripcion_id, i.estado AS estado_inscripcion, i.comprobante, i.fecha_comprobante, i.mensaje_pago, i.fecha_inscripcion
    FROM Curso c
    LEFT JOIN Categoria cat ON (c.id_categoria = cat.id OR c.categoria = cat.nombre)
    JOIN Usuario u ON c.docente_id = u.id
    LEFT JOIN Inscripcion i ON i.curso_id = c.id AND i.usuario_id = $uid_seguro
    WHERE c.id = $cid_seguro
    LIMIT 1
");
$curso = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

if (!$curso) {
    $_SESSION['error'] = 'El curso solicitado no existe.';
    header('Location: mis-inscripciones.php');
    exit;
}


if ((float)$curso['precio'] <= 0) {
    header('Location: ver.php?id=' . $curso_id);
    exit;
}

// estado pendiente
if (empty($curso['inscripcion_id'])) {
    $es_pro = usuario_es_pro($usuario_id);
    if (!$es_pro && contar_inscripciones_activas_alumno($usuario_id) >= 3) {
        $_SESSION['error'] = 'Alcanzaste el límite de 3 cursos activos simultáneos del Plan Gratuito. Mejorá a Plan PRO para anotarte a nuevos cursos.';
        header('Location: ver.php?id=' . $curso_id);
        exit;
    }

    mysqli_query($conexion, "
        INSERT INTO Inscripcion (usuario_id, curso_id, estado, progreso_porcentaje, fecha_inscripcion)
        VALUES ($uid_seguro, $cid_seguro, 'pendiente', 0, NOW())
    ");
    $nuevoId = intval(mysqli_insert_id($conexion));
    $curso['inscripcion_id'] = $nuevoId;
    $curso['estado_inscripcion'] = 'pendiente';
    $curso['comprobante'] = null;
    $curso['fecha_comprobante'] = null;
    $curso['mensaje_pago'] = null;
    $curso['fecha_inscripcion'] = date('Y-m-d H:i:s');
} elseif ($curso['estado_inscripcion'] === 'cancelada') {
    $iid_seguro = intval($curso['inscripcion_id']);
    mysqli_query($conexion, "
        UPDATE Inscripcion 
        SET estado = 'pendiente', fecha_inscripcion = NOW(), comprobante = NULL, fecha_comprobante = NULL, mensaje_pago = NULL
        WHERE id = $iid_seguro
    ");
    $curso['estado_inscripcion'] = 'pendiente';
    $curso['comprobante'] = null;
    $curso['fecha_comprobante'] = null;
    $curso['mensaje_pago'] = null;
    $curso['fecha_inscripcion'] = date('Y-m-d H:i:s');
}

$es_activa = ($curso['estado_inscripcion'] === 'activa');
$tiene_comprobante = !empty($curso['comprobante']);

$titular_cuenta = $curso['docente_nombre'] . ' ' . $curso['docente_apellido'];
$alias_docente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $curso['docente_nombre'] . '.' . $curso['docente_apellido'])) . '.CURSOS';
$cbu_docente = '0720123488000034567891';
$banco_nombre = 'Banco Santander Argentina';

require_once __DIR__ . '/../includes/header.php';
?>

<style>

.pagar-contenedor-centrado {
    max-width: 1040px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.pagar-grid-distribucion {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 26px;
    align-items: start;
}

.pagar-columna-info {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.pagar-columna-pago {
    position: sticky;
    top: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.tarjeta-pago-bloque {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-card, 16px);
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}

.pago-card-cabecera {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border-color);
}

.pago-badge-tipo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: var(--color-primary-light);
    color: var(--color-primary);
    border: 1px solid rgba(166, 109, 114, 0.25);
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}

.pago-inscripcion-meta {
    font-size: 0.8rem;
    color: var(--color-text-muted);
}

.pago-curso-fila {
    display: flex;
    gap: 18px;
    align-items: center;
}

.pago-curso-thumb {
    width: 90px;
    height: 70px;
    border-radius: 10px;
    overflow: hidden;
    background: #f5f5f4;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
}

.pago-curso-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.pago-curso-info {
    flex: 1;
    min-width: 0;
}

.pago-curso-titulo {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--color-text-main);
    line-height: 1.35;
    margin: 0 0 6px 0;
}

.pago-curso-docente {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.86rem;
    color: var(--color-text-muted);
}

.pago-monto-bloque {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px dashed var(--border-color);
    display: flex;
    align-items: baseline;
    justify-content: space-between;
}

.pago-monto-label {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--color-text-body);
}

.pago-monto-cifra {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.pago-monto-valor {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--color-primary);
    letter-spacing: -0.5px;
}

.pago-monto-moneda {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--color-text-muted);
}

/* caja datos transferencia */
.pago-banco-cabecera {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.pago-banco-cabecera h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--color-text-main);
}

.pago-banco-cabecera i {
    font-size: 1.2rem;
    color: var(--color-primary);
}

.pago-banco-subtexto {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    margin: 0 0 16px 0;
}

.pago-banco-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.caja-banco-item {
    background: #fafaf9;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.caja-banco-item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.caja-banco-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    color: var(--color-text-muted);
    font-weight: 700;
    letter-spacing: 0.4px;
}

.caja-banco-valor {
    font-size: 0.95rem;
    color: var(--color-text-main);
    font-weight: 700;
    word-break: break-all;
}

.btn-copiar-dato {
    background: #ffffff;
    border: 1px solid var(--border-color);
    color: var(--color-primary);
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-copiar-dato:hover {
    background: var(--color-primary-light);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.pago-seguridad-aviso {
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: var(--color-text-muted);
}

.pago-seguridad-aviso i {
    color: var(--color-primary);
    font-size: 0.95rem;
}

/* pasos */
.pasos-pago-guia {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #ffffff;
    border: 1px solid var(--border-color);
    padding: 16px 18px;
    border-radius: 12px;
}

.paso-pago-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.85rem;
    color: var(--color-text-main);
}

.paso-pago-numero {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--color-primary-light);
    color: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 700;
    flex-shrink: 0;
}

.caja-formulario-comprobante {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-card, 16px);
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}

.caja-formulario-comprobante h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--color-text-main);
    margin: 0 0 4px 0;
}

@media (max-width: 960px) {
    .pagar-grid-distribucion {
        grid-template-columns: 1fr;
    }
    .pagar-columna-pago {
        position: static;
    }
}
</style>

<div class="dashboard-contenedor">

    <!-- barra lateral -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <!-- contenedor centrado -->
        <div class="pagar-contenedor-centrado">

            <!-- enlace para volver -->
            <div>
                <a href="mis-inscripciones.php" class="enlace-ver-todos">
                    <i class="bi bi-arrow-left"></i> Volver a Mis Inscripciones
                </a>
            </div>

            <header class="dashboard-cabecera" style="margin-bottom: 0;">
                <div>
                    <h1>Pago de Inscripción</h1>
                    <p>Completá la transferencia bancaria para activar tu cursada en Cursolandia.</p>
                </div>
                <div class="dashboard-cabecera-acciones">
                    <?php if ($es_activa): ?>
                        <span class="badge-estado publicado" style="font-size: 0.88rem; padding: 6px 14px;">
                            <i class="bi bi-check-circle-fill"></i> Inscripción Activa
                        </span>
                    <?php elseif ($tiene_comprobante): ?>
                        <span class="badge-estado" style="background: rgba(166, 148, 151, 0.2); color: var(--color-text-main); border: 1px solid rgba(166, 148, 151, 0.3); font-size: 0.88rem; padding: 6px 14px;">
                            <i class="bi bi-hourglass-split"></i> Comprobante en Revisión
                        </span>
                    <?php else: ?>
                        <span class="badge-estado borrador" style="background: var(--color-secondary-soft); color: var(--color-text-main); border: 1px solid rgba(242, 165, 148, 0.4); font-size: 0.88rem; padding: 6px 14px;">
                            <i class="bi bi-clock-history"></i> Pago Pendiente
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- alertas -->
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

            <?php if (!empty($_SESSION['info'])): ?>
                <div class="alerta-flash info">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?></span>
                </div>
            <?php endif; ?>

            <div class="pagar-grid-distribucion">


                <div class="pagar-columna-info">

                    <div class="tarjeta-pago-bloque">
                        <div class="pago-card-cabecera">
                            <span class="pago-badge-tipo">
                                <i class="bi bi-mortarboard-fill"></i> Curso Arancelado
                            </span>
                            <span class="pago-inscripcion-meta">
                                Inscripción #<?php echo $curso['inscripcion_id']; ?> · <?php echo date('d/m/Y', strtotime($curso['fecha_inscripcion'])); ?>
                            </span>
                        </div>

                        <div class="pago-curso-fila">
                            <div class="pago-curso-thumb">
                                <img src="<?php echo htmlspecialchars(obtener_url_imagen($curso['imagen'])); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80';">
                            </div>
                            <div class="pago-curso-info">
                                <h3 class="pago-curso-titulo"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                <div class="pago-curso-docente">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Docente: <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" style="color: inherit; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($curso['docente_nombre'] . ' ' . $curso['docente_apellido']); ?></a></span>
                                </div>
                            </div>
                        </div>

                        <!-- monto -->
                        <div class="pago-monto-bloque">
                            <span class="pago-monto-label">Monto total a transferir:</span>
                            <div class="pago-monto-cifra">
                                <span class="pago-monto-valor">$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></span>
                                <span class="pago-monto-moneda">ARS</span>
                            </div>
                        </div>
                    </div>

                    <!-- caja con datos de transferencia -->
                    <div class="tarjeta-pago-bloque">
                        <div class="pago-banco-cabecera">
                            <i class="bi bi-bank2"></i>
                            <h4>Datos para la Transferencia Bancaria</h4>
                        </div>
                        <p class="pago-banco-subtexto">
                            Efectuá la transferencia desde tu Home Banking o billetera virtual por el importe exacto:
                        </p>

                        <div class="pago-banco-lista">
                            <div class="caja-banco-item">
                                <div class="caja-banco-item-info">
                                    <span class="caja-banco-label">Titular de la cuenta</span>
                                    <span class="caja-banco-valor"><?php echo htmlspecialchars($titular_cuenta); ?></span>
                                </div>
                            </div>

                            <div class="caja-banco-item">
                                <div class="caja-banco-item-info">
                                    <span class="caja-banco-label">Entidad Bancaria</span>
                                    <span class="caja-banco-valor"><?php echo htmlspecialchars($banco_nombre); ?></span>
                                </div>
                            </div>

                            <div class="caja-banco-item">
                                <div class="caja-banco-item-info">
                                    <span class="caja-banco-label">CBU</span>
                                    <span class="caja-banco-valor" id="val-cbu"><?php echo htmlspecialchars($cbu_docente); ?></span>
                                </div>
                                <button type="button" class="btn-copiar-dato" onclick="copiarAlPortapapeles('<?php echo $cbu_docente; ?>', this)">
                                    <i class="bi bi-clipboard"></i> Copiar CBU
                                </button>
                            </div>

                            <div class="caja-banco-item">
                                <div class="caja-banco-item-info">
                                    <span class="caja-banco-label">Alias</span>
                                    <span class="caja-banco-valor" id="val-alias"><?php echo htmlspecialchars($alias_docente); ?></span>
                                </div>
                                <button type="button" class="btn-copiar-dato" onclick="copiarAlPortapapeles('<?php echo $alias_docente; ?>', this)">
                                    <i class="bi bi-clipboard"></i> Copiar Alias
                                </button>
                            </div>
                        </div>

                        <div class="pago-seguridad-aviso">
                            <i class="bi bi-shield-check"></i>
                            <span>Transferencia directa y segura sin comisiones intermedias.</span>
                        </div>
                    </div>

                </div>


                <div class="pagar-columna-pago">

                    <?php if ($es_activa): ?>

                        <div class="suscripcion-activa-aviso" style="padding: 24px; font-size: 1rem; border-radius: 14px; gap: 12px; flex-direction: column; text-align: center; background: var(--color-primary-light); border: 1.5px solid rgba(166, 109, 114, 0.4);">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="bi bi-patch-check-fill" style="font-size: 1.8rem; color: var(--color-primary);"></i>
                                <strong style="font-size: 1.15rem; color: var(--color-text-main);">¡Inscripción Confirmada!</strong>
                            </div>
                            <p style="margin: 4px 0 16px 0; font-size: 0.88rem; color: var(--color-text-body); font-weight: normal; line-height: 1.45;">
                                Tu pago ha sido validado correctamente. Ya tenés acceso completo a las clases y contenidos del curso.
                            </p>
                            <a href="ver.php?id=<?php echo $curso_id; ?>" class="btn-primario" style="padding: 11px 24px; width: 100%; justify-content: center;">
                                <i class="bi bi-play-circle-fill"></i> Ir al Curso Ahora
                            </a>
                        </div>
                    <?php else: ?>

                        <div class="pasos-pago-guia">
                            <div class="paso-pago-item">
                                <span class="paso-pago-numero">1</span>
                                <span>Realizá la transferencia por <strong>$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></strong></span>
                            </div>
                            <div class="paso-pago-item">
                                <span class="paso-pago-numero">2</span>
                                <span>Adjuntá tu comprobante (PDF o imagen)</span>
                            </div>
                            <div class="paso-pago-item">
                                <span class="paso-pago-numero">3</span>
                                <span>El docente valida tu pago y activa el curso</span>
                            </div>
                        </div>

                        <?php if ($tiene_comprobante): ?>

                            <div style="background: var(--color-secondary-soft); border: 1px solid rgba(242, 165, 148, 0.4); border-radius: 12px; padding: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="bi bi-file-earmark-check-fill" style="font-size: 1.8rem; color: var(--color-primary);"></i>
                                        <div>
                                            <h5 style="margin: 0; font-size: 0.98rem; font-weight: 700; color: var(--color-text-main);">
                                                Comprobante en revisión
                                            </h5>
                                            <span style="font-size: 0.78rem; color: var(--color-text-muted);">
                                                Enviado el <?php echo date('d/m/Y H:i', strtotime($curso['fecha_comprobante'])); ?> hs.
                                            </span>
                                        </div>
                                    </div>
                                    <a href="<?php echo $base_url . htmlspecialchars($curso['comprobante']); ?>" target="_blank" class="btn-accion-secundaria" style="padding: 6px 12px; font-size: 0.8rem; background: #ffffff;">
                                        <i class="bi bi-box-arrow-up-right"></i> Ver archivo
                                    </a>
                                </div>

                                <?php if (!empty($curso['mensaje_pago'])): ?>
                                    <div style="margin-top: 12px; background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(166, 148, 151, 0.3); font-size: 0.82rem; color: var(--color-text-main);">
                                        <strong>Nota:</strong> "<?php echo htmlspecialchars($curso['mensaje_pago']); ?>"
                                    </div>
                                <?php endif; ?>

                                <p style="margin: 12px 0 0 0; font-size: 0.82rem; color: var(--color-text-main); line-height: 1.4;">
                                    <i class="bi bi-hourglass-split"></i> El docente está revisando tu transferencia. Podés reenviar un nuevo comprobante abajo si hiciste correcciones:
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="caja-formulario-comprobante">
                            <h4><?php echo $tiene_comprobante ? 'Reenviar Comprobante' : 'Adjuntar Comprobante de Pago'; ?></h4>
                            <p class="texto-ayuda" style="margin-bottom: 18px; font-size: 0.82rem;">
                                Subí la captura o archivo PDF para que el docente pueda acreditar tu inscripción.
                            </p>

                            <form action="../controladores/inscripcion_controlador.php?accion=subir_comprobante" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">

                                <div class="campo-grupo">
                                    <label for="comprobante" style="font-size: 0.86rem; font-weight: 600; margin-bottom: 6px; display: block;">
                                        Archivo del comprobante <span class="campo-obligatorio">*</span>
                                    </label>
                                    <input type="file" id="comprobante" name="comprobante" class="input-archivo-estilizado" accept=".pdf,image/*" required>
                                    <span class="texto-ayuda" style="font-size: 0.76rem; display: block; margin-top: 4px;">Formatos válidos: PDF, JPG, PNG, WebP (máx. 10 MB).</span>
                                </div>

                                <div class="campo-grupo" style="margin-top: 14px;">
                                    <label for="mensaje_pago" style="font-size: 0.86rem; font-weight: 600; margin-bottom: 6px; display: block;">Nota o referencia adicional (opcional)</label>
                                    <input type="text" id="mensaje_pago" name="mensaje_pago" class="input-control" placeholder="Ej: Transferencia desde Mercado Pago de Juan...">
                                </div>

                                <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                                    <button type="submit" class="btn-primario" style="width: 100%; justify-content: center; padding: 12px; font-size: 0.92rem; cursor: pointer; border: none;">
                                        <i class="bi bi-upload"></i> <?php echo $tiene_comprobante ? 'Actualizar Comprobante' : 'Enviar Comprobante de Pago'; ?>
                                    </button>
                                    <a href="mis-inscripciones.php" class="btn-accion-secundaria" style="width: 100%; justify-content: center; font-size: 0.85rem; text-align: center;">
                                        Volver a Mis Inscripciones
                                    </a>
                                </div>
                            </form>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>

<script>
// copiar al portapapeles
function copiarAlPortapapeles(texto, boton) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(() => {
            mostrarCopiado(boton);
        }).catch(() => {
            copiarFallback(texto, boton);
        });
    } else {
        copiarFallback(texto, boton);
    }
}

function copiarFallback(texto, boton) {
    const tempInput = document.createElement('input');
    tempInput.value = texto;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    mostrarCopiado(boton);
}

function mostrarCopiado(boton) {
    const textoOriginal = boton.innerHTML;
    boton.innerHTML = '<i class="bi bi-check2"></i> ¡Copiado!';
    boton.style.background = 'var(--color-primary-light)';
    boton.style.borderColor = 'var(--color-primary)';
    boton.style.color = 'var(--color-primary)';

    setTimeout(() => {
        boton.innerHTML = textoOriginal;
        boton.style.background = '';
        boton.style.borderColor = '';
        boton.style.color = '';
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
