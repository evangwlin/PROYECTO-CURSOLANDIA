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

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$conexion = obtener_conexion();
$uid_seguro = intval($usuario_id);
$cid_seguro = intval($curso_id);

if ($curso_id <= 0) {
    $resPend = mysqli_query($conexion, "
        SELECT curso_id FROM inscripcion 
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


$resCurso = mysqli_query($conexion, "
    SELECT c.*, COALESCE(cat.nombre, c.categoria) AS categoria_nombre, cat.icono AS categoria_icono,
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.email AS docente_email, u.avatar AS docente_avatar,
           i.id AS inscripcion_id, i.estado AS estado_inscripcion, i.comprobante, i.fecha_comprobante, i.mensaje_pago, i.fecha_inscripcion
    FROM curso c
    LEFT JOIN categoria cat ON (c.id_categoria = cat.id OR c.categoria = cat.nombre)
    JOIN usuario u ON c.docente_id = u.id
    LEFT JOIN inscripcion i ON i.curso_id = c.id AND i.usuario_id = $uid_seguro
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


if (empty($curso['inscripcion_id'])) {
    $es_pro = usuario_es_pro($usuario_id);
    if (!$es_pro && contar_inscripciones_activas_alumno($usuario_id) >= 3) {
        $_SESSION['error'] = 'Alcanzaste el límite de 3 cursos activos simultáneos del Plan Gratuito. Mejorá a Plan PRO para anotarte a nuevos cursos.';
        header('Location: ver.php?id=' . $curso_id);
        exit;
    }

    mysqli_query($conexion, "
        INSERT INTO inscripcion (usuario_id, curso_id, estado, progreso_porcentaje, fecha_inscripcion)
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
        UPDATE inscripcion 
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

<div class="dashboard-contenedor">


    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div class="pagar-contenedor-centrado">

            <div>
                <a href="mis-inscripciones.php" class="enlace-ver-todos">
                    <i class="bi bi-arrow-left"></i> Volver a Mis Inscripciones
                </a>
            </div>

            <header class="dashboard-cabecera pagar-cabecera-mb">
                <div>
                    <h1>Pago de Inscripción</h1>
                    <p>Completá la transferencia bancaria para activar tu cursada en Cursolandia.</p>
                </div>
                <div class="dashboard-cabecera-acciones">
                    <?php if ($es_activa): ?>
                        <span class="badge-estado publicado">
                            <i class="bi bi-check-circle-fill"></i> Inscripción Activa
                        </span>
                    <?php elseif ($tiene_comprobante): ?>
                        <span class="badge-estado">
                            <i class="bi bi-hourglass-split"></i> Comprobante en Revisión
                        </span>
                    <?php else: ?>
                        <span class="badge-estado borrador">
                            <i class="bi bi-clock-history"></i> Pago Pendiente
                        </span>
                    <?php endif; ?>
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
                                    <span>Docente: <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="autor-nombre-link"><?php echo htmlspecialchars($curso['docente_nombre'] . ' ' . $curso['docente_apellido']); ?></a></span>
                                </div>
                            </div>
                        </div>


                        <div class="pago-monto-bloque">
                            <span class="pago-monto-label">Monto total a transferir:</span>
                            <div class="pago-monto-cifra">
                                <span class="pago-monto-valor">$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></span>
                                <span class="pago-monto-moneda">ARS</span>
                            </div>
                        </div>
                    </div>


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

                        <div class="pago-confirmado-caja">
                            <div>
                                <i class="bi bi-patch-check-fill"></i>
                                <strong>¡Inscripción Confirmada!</strong>
                            </div>
                            <p>
                                Tu pago ha sido validado correctamente. Ya tenés acceso completo a las clases y contenidos del curso.
                            </p>
                            <a href="ver.php?id=<?php echo $curso_id; ?>" class="btn-primario btn-pago-accion-full">
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

                            <div class="pago-comprobante-info">
                                <div class="pagar-comprobante-header">
                                    <div class="alerta-contenido-izq">
                                        <i class="bi bi-file-earmark-check-fill icono-primario"></i>
                                        <div>
                                            <h5 class="pagar-comprobante-titulo">
                                                Comprobante en revisión
                                            </h5>
                                            <span class="pagar-comprobante-sub">
                                                Enviado el <?php echo date('d/m/Y H:i', strtotime($curso['fecha_comprobante'])); ?> hs.
                                            </span>
                                        </div>
                                    </div>
                                    <a href="<?php echo $base_url . htmlspecialchars($curso['comprobante']); ?>" target="_blank" class="btn-accion-secundaria btn-ver-comprobante">
                                        <i class="bi bi-box-arrow-up-right"></i> Ver archivo
                                    </a>
                                </div>

                                <?php if (!empty($curso['mensaje_pago'])): ?>
                                    <div class="pagar-nota-comprobante">
                                        <strong>Nota:</strong> "<?php echo htmlspecialchars($curso['mensaje_pago']); ?>"
                                    </div>
                                <?php endif; ?>

                                <p class="pagar-nota-texto">
                                    <i class="bi bi-hourglass-split"></i> El docente está revisando tu transferencia. Podés reenviar un nuevo comprobante abajo si hiciste correcciones:
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="caja-formulario-comprobante">
                            <h4><?php echo $tiene_comprobante ? 'Reenviar Comprobante' : 'Adjuntar Comprobante de Pago'; ?></h4>
                            <p class="texto-ayuda">
                                Subí la captura o archivo PDF para que el docente pueda acreditar tu inscripción.
                            </p>

                            <form action="../controladores/inscripcion_controlador.php?accion=subir_comprobante" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">

                                <div class="campo-grupo">
                                    <label for="comprobante" class="pagar-label-campo">
                                        Archivo del comprobante <span class="campo-obligatorio">*</span>
                                    </label>
                                    <input type="file" id="comprobante" name="comprobante" class="input-archivo-estilizado" accept=".pdf,image/*" required>
                                    <span class="texto-ayuda">Formatos válidos: PDF, JPG, PNG, WebP (máx. 10 MB).</span>
                                </div>

                                <div class="campo-grupo">
                                    <label for="mensaje_pago" class="pagar-label-campo">Nota o referencia adicional (opcional)</label>
                                    <input type="text" id="mensaje_pago" name="mensaje_pago" class="input-control" placeholder="Ej: Transferencia desde Mercado Pago de Juan...">
                                </div>

                                <div class="pagar-acciones-grupo">
                                    <button type="submit" class="btn-primario btn-pago-accion-full">
                                        <i class="bi bi-upload"></i> <?php echo $tiene_comprobante ? 'Actualizar Comprobante' : 'Enviar Comprobante de Pago'; ?>
                                    </button>
                                    <a href="mis-inscripciones.php" class="btn-accion-secundaria btn-pago-accion-full">
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
function copiarAlPortapapeles(texto, boton) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(function() {
            mostrarCopiado(boton);
        }).catch(function() {
            copiarFallback(texto, boton);
        });
    } else {
        copiarFallback(texto, boton);
    }
}

function copiarFallback(texto, boton) {
    var tempInput = document.createElement('input');
    tempInput.value = texto;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    mostrarCopiado(boton);
}

function mostrarCopiado(boton) {
    var textoOriginal = boton.innerHTML;
    boton.innerHTML = '<i class="bi bi-check2"></i> ¡Copiado!';
    boton.style.background = 'var(--color-primary-light)';
    boton.style.borderColor = 'var(--color-primary)';
    boton.style.color = 'var(--color-primary)';

    setTimeout(function() {
        boton.innerHTML = textoOriginal;
        boton.style.background = '';
        boton.style.borderColor = '';
        boton.style.color = '';
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
