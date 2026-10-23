<?php

$titulo_pagina = 'Editar Curso';
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

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id']);
$curso_id = (int)($_GET['id'] ?? 0);

if ($curso_id <= 0) {
    header('Location: mis-cursos.php');
    exit;
}

$conexion = obtener_conexion();
$cid_seguro = intval($curso_id);
$uid_seguro = intval($usuario_id);
$res = mysqli_query($conexion, "SELECT * FROM Curso WHERE id = $cid_seguro AND docente_id = $uid_seguro LIMIT 1");
$curso = $res ? mysqli_fetch_assoc($res) : null;

if (!$curso) {
    $_SESSION['error'] = 'No tenés permisos para editar este curso o no existe.';
    header('Location: mis-cursos.php');
    exit;
}

$es_pro = usuario_es_pro($usuario_id);

$resMat = mysqli_query($conexion, "SELECT * FROM Material WHERE curso_id = $cid_seguro ORDER BY fecha_subida ASC, id ASC");
$materialesExistentes = [];
$pesoTotalMateriales = 0;
if ($resMat) {
    while ($m = mysqli_fetch_assoc($resMat)) {
        $materialesExistentes[] = $m;
        $pesoTotalMateriales += (float)($m['tamano_mb'] ?? 0);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <header class="dashboard-cabecera">
            <div>
                <h1>Editar Curso</h1>
                <p>Modificá la información y condiciones de tu propuesta formativa.</p>
            </div>
            <div class="cabecera-acciones">
                <a href="mis-cursos.php" class="btn-pildora-todas">
                    <i class="bi bi-arrow-left"></i> Volver a mis cursos
                </a>
                <button type="button" class="btn-pildora-peligro" onclick="confirmarEliminarCurso()" title="Eliminar este curso permanentemente">
                    <i class="bi bi-trash3-fill"></i> Eliminar Curso
                </button>
            </div>
        </header>

        <div class="editar-curso-layout">
            <div class="crear-curso-col-principal">

                <?php if (!empty($_SESSION['exito'])): ?>
                    <div class="alerta-flash exito" style="margin-bottom: 20px;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alerta-flash error" style="margin-bottom: 20px;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="../controladores/curso_controlador.php?accion=editar" method="POST" enctype="multipart/form-data" class="formulario-crear-curso" onsubmit="return validarFormularioCurso(event);">
                    <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-pencil-square"></i>
                            <h3>Información del Curso</h3>
                        </div>

                        <div class="campo-grupo">
                            <label for="titulo">Título del curso <span class="campo-obligatorio">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="input-control" value="<?php echo htmlspecialchars($curso['titulo']); ?>" required>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="categoria">Categoría <span class="campo-obligatorio">*</span></label>
                                <select id="categoria" name="categoria" class="input-control select-control" required>
                                    <?php 
                                    $categorias = ['Programación', 'Arte & Diseño', 'Comunicación', 'Marketing', 'Finanzas', 'Ciencia & Datos', 'Inteligencia Artificial', 'Idiomas'];
                                    foreach ($categorias as $cat): 
                                        $selected = (strcasecmp($curso['categoria'], $cat) === 0) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="campo-grupo">
                                <label for="nivel">Nivel de dificultad <span class="campo-obligatorio">*</span></label>
                                <select id="nivel" name="nivel" class="input-control select-control" required>
                                    <option value="Todos" <?php echo (strcasecmp($curso['nivel'] ?? '', 'todos') === 0) ? 'selected' : ''; ?>>Todos los niveles</option>
                                    <option value="Inicial" <?php echo (strcasecmp($curso['nivel'] ?? '', 'inicial') === 0) ? 'selected' : ''; ?>>Inicial / Principiante</option>
                                    <option value="Intermedio" <?php echo (strcasecmp($curso['nivel'] ?? '', 'intermedio') === 0 || empty($curso['nivel'])) ? 'selected' : ''; ?>>Intermedio</option>
                                    <option value="Avanzado" <?php echo (strcasecmp($curso['nivel'] ?? '', 'avanzado') === 0) ? 'selected' : ''; ?>>Avanzado</option>
                                </select>
                            </div>

                            <div class="campo-grupo">
                                <label for="tipo_acceso">Tipo de Acceso <span class="campo-obligatorio">*</span></label>
                                <select id="tipo_acceso" name="tipo_acceso" class="input-control select-control" required>
                                    <option value="publico" <?php echo ($curso['tipo_acceso'] === 'publico') ? 'selected' : ''; ?>>Público (Visible en catálogo)</option>
                                    <option value="privado" <?php echo ($curso['tipo_acceso'] === 'privado') ? 'selected' : ''; ?>>Privado (Solo por enlace)</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label for="descripcion">Descripción del curso <span class="campo-obligatorio">*</span></label>
                            <textarea id="descripcion" name="descripcion" class="input-control textarea-control" rows="5" required><?php echo htmlspecialchars($curso['descripcion']); ?></textarea>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-bullseye"></i>
                            <div>
                                <h3>Lo que se va a aprender (Objetivos) <span class="campo-obligatorio">*</span></h3>
                                <p class="panel-subtitulo-sec">Definí los aprendizajes concretos y habilidades que adquirirá el estudiante.</p>
                            </div>
                        </div>
                        <div class="campo-grupo">
                            <label for="objetivos">Objetivos del curso (un ítem por renglón) <span class="campo-obligatorio">*</span></label>
                            <textarea id="objetivos" name="objetivos" class="input-control textarea-control" rows="5" placeholder="Ej:&#10;Construir aplicaciones web completas utilizando PHP y MySQL&#10;Dominar el patrón de arquitectura Modelo-Vista-Controlador (MVC)&#10;Modelar bases de datos relacionales normalizadas&#10;Gestionar sesiones de usuario y autenticación" required><?php echo htmlspecialchars($curso['objetivos'] ?? ''); ?></textarea>
                            <span class="texto-ayuda">Cada línea ingresada se mostrará como un ítem con tilde en la sección 'Lo que vas a aprender' del curso.</span>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-list-check"></i>
                            <div>
                                <h3>Requisitos previos del curso <span class="campo-obligatorio">*</span></h3>
                                <p class="panel-subtitulo-sec">Indicá qué conocimientos, herramientas o software previo necesita tener el estudiante.</p>
                            </div>
                        </div>
                        <div class="campo-grupo">
                            <label for="requisitos">Requisitos del curso (un requisito por línea) <span class="campo-obligatorio">*</span></label>
                            <textarea id="requisitos" name="requisitos" class="input-control textarea-control" rows="3" placeholder="Ej:&#10;Conocimientos básicos de HTML y CSS&#10;Computadora con acceso a Internet&#10;Editor de código (recomendado VS Code o similar)" required><?php echo htmlspecialchars($curso['requisitos'] ?? ''); ?></textarea>
                            <span class="texto-ayuda">Cada línea ingresada se mostrará con un tilde en la sección 'Requisitos previos' de la página del curso.</span>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-journals"></i>
                                <div>
                                    <h3>Módulos y Contenido del Temario <span class="campo-obligatorio">*</span></h3>
                                    <p class="panel-subtitulo-sec">Organizá los módulos de la cursada y las clases o temas incluidos en cada uno.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-secundario" onclick="agregarModuloUI()" style="font-size: 0.82rem; padding: 6px 14px;">
                                <i class="bi bi-plus-circle"></i> Agregar Módulo
                            </button>
                        </div>

                        <div id="contenedor-modulos-dinamicos" style="display: flex; flex-direction: column; gap: 16px; margin-top: 14px;">

                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-calendar-event-fill"></i>
                            <h3>Fechas, Cupo y Precio</h3>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="fecha_inicio">Fecha de inicio <span class="campo-obligatorio">*</span></label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" class="input-control" value="<?php echo htmlspecialchars($curso['fecha_inicio']); ?>" required>
                            </div>

                            <div class="campo-grupo">
                                <label for="fecha_fin">Fecha de finalización <span class="campo-obligatorio">*</span></label>
                                <input type="date" id="fecha_fin" name="fecha_fin" class="input-control" value="<?php echo htmlspecialchars($curso['fecha_fin']); ?>" required>
                            </div>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="cupo">Cupo máximo de estudiantes <span class="campo-obligatorio">*</span></label>
                                <input type="number" id="cupo" name="cupo" class="input-control" min="1" max="500" value="<?php echo htmlspecialchars($curso['cupo_max']); ?>" required>
                                <span class="texto-ayuda">Límite de alumnos para esta cursada.</span>
                            </div>

                            <div class="campo-grupo">
                                <label for="tipo_precio">Modalidad de precio <span class="campo-obligatorio">*</span></label>
                                <?php $esCursoPago = ((float)$curso['precio'] > 0); ?>
                                <div class="precio-selector-wrapper">
                                    <select id="tipo_precio" name="tipo_precio" class="input-control select-control" onchange="togglePrecioInputEditar(this.value)">
                                        <option value="pago" <?php echo $esCursoPago ? 'selected' : ''; ?>>Curso de pago</option>
                                        <option value="gratis" <?php echo !$esCursoPago ? 'selected' : ''; ?>>Gratis / Acceso libre</option>
                                    </select>
                                    <div id="contenedor-precio-monto" class="precio-input-caja" style="<?php echo !$esCursoPago ? 'display:none;' : 'display:flex;'; ?>">
                                        <span class="precio-moneda">$</span>
                                        <input type="number" id="precio" name="precio" class="input-control" min="0" step="any" value="<?php echo $esCursoPago ? (float)$curso['precio'] : '10000'; ?>" placeholder="Monto (ARS)" <?php echo $esCursoPago ? 'required' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label for="tags">Etiquetas e Intereses (#Tags) <span class="campo-obligatorio">*</span></label>
                            <input type="text" id="tags" name="tags" class="input-control" value="<?php echo htmlspecialchars($curso['etiquetas'] ?? ''); ?>" placeholder="Ej: #PHP, #SQL, #Web" required>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-image-fill"></i>
                            <h3>Portada del Curso</h3>
                        </div>

                        <div class="portada-editar-fila" style="display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;">
                            <div style="flex-shrink: 0;">
                                <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px; color: var(--color-text-main);">Portada actual</label>
                                <?php $imgActual = obtener_portada_curso($curso['imagen'] ?? '', $curso['categoria'] ?? '', (int)$curso_id); ?>
                                <img src="<?php echo htmlspecialchars($imgActual); ?>" alt="Portada actual" id="previa-imagen-editar" style="width: 170px; height: 105px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border-color); display: block; box-shadow: var(--shadow-sm);" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgActual); ?>';">
                            </div>
                            <div style="flex: 1; min-width: 260px;">
                                <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px; color: var(--color-text-main);">Cambiar imagen de portada (opcional)</label>
                                <label for="portada" class="subir-archivo-box" style="padding: 20px;">
                                    <i class="bi bi-cloud-arrow-up-fill" style="font-size: 1.8rem; color: var(--color-primary);"></i>
                                    <p><strong>Elegí una nueva imagen</strong> o arrastrala acá</p>
                                    <span class="texto-ayuda">JPG, PNG o WebP (Máx 2 MB)</span>
                                    <input type="file" id="portada" name="portada" accept="image/*" class="input-archivo-oculto">
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-folder-fill"></i>
                                <div>
                                    <h3>Materiales y Recursos Adjuntos</h3>
                                    <p class="panel-subtitulo-sec">Gestioná los documentos, guías en PDF o recursos descargables de tu curso.</p>
                                </div>
                            </div>
                            <span class="limite-plan-tag">
                                <?php echo $es_pro ? 'Plan Pro: Ilimitados' : 'Plan Gratuito: Hasta 5 archivos (máx 5 MB c/u, 10 MB total)'; ?>
                            </span>
                        </div>

                        <!-- Lista de materiales ya subidos -->
                        <div style="margin-top: 10px;">
                            <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 12px; color: var(--color-text-main);">
                                Archivos actualmente subidos (<?php echo count($materialesExistentes); ?>)
                            </label>

                            <?php if (!empty($materialesExistentes)): ?>
                                <div class="materiales-grilla" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 22px;">
                                    <?php foreach ($materialesExistentes as $mat): 
                                        $ext = strtolower(pathinfo($mat['nombre_archivo'] ?? $mat['ruta_archivo'], PATHINFO_EXTENSION));
                                        $clase_ico = 'material-icono-pdf';
                                        $ico = 'bi-file-earmark-text';
                                        if (in_array($ext, ['pdf'])) { $clase_ico = 'material-icono-pdf'; $ico = 'bi-file-earmark-pdf-fill'; }
                                        elseif (in_array($ext, ['zip', 'rar', 'tar', '7z'])) { $clase_ico = 'material-icono-zip'; $ico = 'bi-file-earmark-zip-fill'; }
                                        elseif (in_array($ext, ['sql', 'txt'])) { $clase_ico = 'material-icono-txt'; $ico = 'bi-database-fill'; }
                                        elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) { $clase_ico = 'material-icono-excel'; $ico = 'bi-file-earmark-excel-fill'; }
                                        elseif (in_array($ext, ['doc', 'docx'])) { $clase_ico = 'material-icono-pdf'; $ico = 'bi-file-earmark-word-fill'; }
                                        elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) { $clase_ico = 'material-icono-zip'; $ico = 'bi-file-earmark-image-fill'; }
                                    ?>
                                        <article class="material-card-item" style="padding: 14px 18px;">
                                            <div class="material-izq-bloque">
                                                <div class="material-icono-caja <?php echo $clase_ico; ?>">
                                                    <i class="bi <?php echo $ico; ?>"></i>
                                                </div>
                                                <div class="material-info-textos">
                                                    <strong style="font-size: 0.92rem;"><?php echo htmlspecialchars($mat['nombre_archivo']); ?></strong>
                                                    <p style="font-size: 0.8rem; margin: 2px 0 0 0; color: var(--color-text-muted);">
                                                        <?php echo strtoupper($ext); ?> · <span style="font-weight: 600;"><?php echo htmlspecialchars($mat['tamano_mb']); ?> MB</span> · Subido el <?php echo date('d/m/Y H:i', strtotime($mat['fecha_subida'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <?php if (!empty($mat['ruta_archivo'])): ?>
                                                    <a href="<?php echo $base_url . ltrim($mat['ruta_archivo'], '/'); ?>" download="<?php echo htmlspecialchars($mat['nombre_archivo']); ?>" class="btn-descargar-archivo" target="_blank" style="padding: 7px 14px; font-size: 0.82rem;" title="Descargar material">
                                                        <i class="bi bi-download"></i> Descargar
                                                    </a>
                                                <?php endif; ?>
                                                <a href="../controladores/curso_controlador.php?accion=eliminar_material&material_id=<?php echo (int)$mat['id']; ?>&curso_id=<?php echo (int)$curso['id']; ?>" class="btn-eliminar-material" onclick="return confirm('¿Seguro que querés eliminar el archivo \'<?php echo addslashes($mat['nombre_archivo']); ?>\'?');" title="Eliminar material">
                                                    <i class="bi bi-trash3-fill"></i> Eliminar
                                                </a>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="background: var(--color-surface); border: 1px dashed var(--border-color); border-radius: 12px; padding: 22px; text-align: center; color: var(--color-text-muted); font-size: 0.88rem; margin-bottom: 22px;">
                                    <i class="bi bi-folder2-open" style="font-size: 2rem; display: block; margin-bottom: 6px; opacity: 0.6; color: var(--color-primary);"></i>
                                    Este curso todavía no tiene archivos o recursos adjuntos subidos.
                                </div>
                            <?php endif; ?>

                            <!-- Subir nuevos archivos -->
                            <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px; color: var(--color-text-main);">
                                Subir nuevos archivos adjuntos (opcional)
                            </label>
                            <label for="materiales" class="subir-archivo-box" style="padding: 22px;">
                                <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: var(--color-secondary);"></i>
                                <p><strong>Elegí o arrastrá nuevos documentos</strong></p>
                                <span class="texto-ayuda">PDF, DOCX, ZIP, imágenes hasta 5 MB por archivo</span>
                                <input type="file" id="materiales" name="materiales[]" multiple class="input-archivo-oculto">
                            </label>
                        </div>
                    </section>

                    <div class="crear-curso-botones-fila">
                        <div class="botones-fila-acciones-izq">
                            <button type="submit" class="btn-primario btn-lg">
                                <i class="bi bi-check2"></i> Guardar Cambios
                            </button>
                            <a href="mis-cursos.php" class="btn-secundario btn-lg">Cancelar</a>
                        </div>
                        <button type="button" class="btn-peligro-outline btn-lg btn-eliminar-curso-final" onclick="confirmarEliminarCurso()" title="Eliminar este curso definitivamente">
                            <i class="bi bi-trash3-fill"></i> Eliminar Curso
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </main>

</div>

<?php
$modulosJson = '[]';
if (!empty($curso['modulos_contenido'])) {
    $decoded = json_decode($curso['modulos_contenido'], true);
    if (is_array($decoded)) {
        $modulosParaJs = [];
        foreach ($decoded as $m) {
            $clasesNombres = [];
            if (!empty($m['clases']) && is_array($m['clases'])) {
                foreach ($m['clases'] as $c) {
                    $clasesNombres[] = is_array($c) ? ($c['nombre'] ?? '') : (string)$c;
                }
            }
            $modulosParaJs[] = [
                'titulo'   => $m['titulo'] ?? '',
                'duracion' => $m['duracion'] ?? '2h 00m',
                'clases'   => implode("\n", $clasesNombres)
            ];
        }
        $modulosJson = json_encode($modulosParaJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
?>

<script>
function togglePrecioInputEditar(tipo) {
    const contenedor = document.getElementById('contenedor-precio-monto');
    const inputPrecio = document.getElementById('precio');
    if (tipo === 'gratis') {
        if (contenedor) contenedor.style.display = 'none';
        if (inputPrecio) {
            inputPrecio.value = '0';
            inputPrecio.removeAttribute('required');
        }
    } else {
        if (contenedor) contenedor.style.display = 'flex';
        if (inputPrecio) {
            inputPrecio.setAttribute('required', 'required');
            if (!inputPrecio.value || inputPrecio.value === '0') {
                inputPrecio.value = '10000';
            }
        }
    }
}

// =============================================================
// EDITAR MODULOS Y CONTENIDOS
// =============================================================
let contadorModulos = 0;

function agregarModuloUI(titulo = '', duracion = '2h 00m', clases = '') {
    contadorModulos++;
    const cont = document.getElementById('contenedor-modulos-dinamicos');
    if (!cont) return;
    
    const div = document.createElement('div');
    div.className = 'modulo-card-editor';
    div.id = 'modulo-card-' + contadorModulos;
    div.style.background = 'var(--color-bg-card, #ffffff)';
    div.style.border = '1px solid var(--color-border, #e2e8f0)';
    div.style.borderRadius = '10px';
    div.style.padding = '16px';
    div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
    div.style.display = 'flex';
    div.style.flexDirection = 'column';
    div.style.gap = '12px';

    div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border, #e2e8f0); padding-bottom: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge-modulo-num" style="background: var(--color-primary-light, #e0e7ff); color: var(--color-primary, #4338ca); font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem;">
                    Módulo
                </span>
                <strong style="color: var(--color-text-title, #1e293b); font-size: 0.95rem;">Configuración del Módulo</strong>
            </div>
            <button type="button" onclick="eliminarModuloUI(this)" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 4px;" title="Eliminar este módulo">
                <i class="bi bi-trash3-fill"></i> Eliminar
            </button>
        </div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
            <div class="campo-grupo" style="margin: 0;">
                <label style="font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; display: block;">Título del Módulo <span class="campo-obligatorio">*</span></label>
                <input type="text" name="modulos[${contadorModulos}][titulo]" value="${escapeHtml(titulo)}" class="input-control" placeholder="Ej: Fundamentos de la disciplina" required>
            </div>
            <div class="campo-grupo" style="margin: 0;">
                <label style="font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; display: block;">Duración Estimada <span class="campo-obligatorio">*</span></label>
                <input type="text" name="modulos[${contadorModulos}][duracion]" value="${escapeHtml(duracion)}" class="input-control" placeholder="Ej: 3h 30m" required>
            </div>
        </div>
        <div class="campo-grupo" style="margin: 0;">
            <label style="font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; display: block;">Contenido / Clases del Módulo (una clase por línea) <span class="campo-obligatorio">*</span></label>
            <textarea name="modulos[${contadorModulos}][clases]" class="input-control textarea-control" rows="3" placeholder="Clase 1: Introducción y conceptos clave&#10;Clase 2: Ejercicios prácticos guiados&#10;Clase 3: Evaluación y cierre" required>${escapeHtml(clases)}</textarea>
            <span class="texto-ayuda" style="font-size: 0.75rem; margin-top: 3px;">Escribí cada clase o lección en un renglón separado.</span>
        </div>
    `;

    cont.appendChild(div);
    renumerarModulos();
}

function validarFormularioCurso(e) {
    const cont = document.getElementById('contenedor-modulos-dinamicos');
    if (!cont || cont.children.length === 0) {
        alert('Debés incluir al menos un módulo temático completo para el curso.');
        if (e) e.preventDefault();
        return false;
    }
    return true;
}

function eliminarModuloUI(btn) {
    const card = btn.closest('.modulo-card-editor');
    if (card) {
        card.remove();
        renumerarModulos();
    }
}

function renumerarModulos() {
    const badges = document.querySelectorAll('#contenedor-modulos-dinamicos .badge-modulo-num');
    badges.forEach((b, idx) => {
        b.textContent = 'Módulo ' + (idx + 1);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', function() {
    const modulosExistentes = <?php echo !empty($modulosJson) ? $modulosJson : '[]'; ?>;
    if (Array.isArray(modulosExistentes) && modulosExistentes.length > 0) {
        modulosExistentes.forEach(m => {
            agregarModuloUI(m.titulo || '', m.duracion || '2h 00m', m.clases || '');
        });
    } else {
        agregarModuloUI('Módulo 1: Introducción y Fundamentos', '2h 00m', 'Presentación del temario\nConceptos iniciales');
    }

    const inputPortada = document.getElementById('portada');
    const previaImg = document.getElementById('previa-imagen-editar');
    if (inputPortada && previaImg) {
        inputPortada.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (file) {
                if (file.type && !file.type.startsWith('image/')) {
                    alert('Por favor, seleccioná un archivo de imagen válido.');
                    this.value = '';
                    return;
                }
                previaImg.src = URL.createObjectURL(file);
                const pBox = this.closest('.subir-archivo-box')?.querySelector('p');
                if (pBox) {
                    pBox.innerHTML = `<strong>${escapeHtml(file.name)}</strong> (${(file.size / 1024).toFixed(1)} KB)`;
                }
            }
        });
    }

    const inputMateriales = document.getElementById('materiales');
    if (inputMateriales) {
        inputMateriales.addEventListener('change', function() {
            const files = this.files;
            const box = this.closest('.subir-archivo-box');
            const pBox = box?.querySelector('p');
            const ayuda = box?.querySelector('.texto-ayuda');
            const icon = box?.querySelector('i');
            
            if (files && files.length > 0) {
                if (files.length === 1) {
                    const f = files[0];
                    const sizeMb = (f.size / (1024 * 1024)).toFixed(2);
                    if (pBox) pBox.innerHTML = `<strong style="color: var(--color-primary);"><i class="bi bi-file-earmark-check-fill"></i> ${escapeHtml(f.name)}</strong>`;
                    if (ayuda) ayuda.textContent = `${sizeMb} MB · Hacé clic para cambiar`;
                } else {
                    let totalMb = 0;
                    for (let i = 0; i < files.length; i++) {
                        totalMb += files[i].size / (1024 * 1024);
                    }
                    if (pBox) pBox.innerHTML = `<strong style="color: var(--color-primary);"><i class="bi bi-folder-check"></i> ${files.length} archivos seleccionados</strong>`;
                    if (ayuda) ayuda.textContent = `${totalMb.toFixed(2)} MB en total · Hacé clic para cambiar`;
                }
                if (icon) {
                    icon.className = 'bi bi-check-circle-fill';
                    icon.style.color = 'var(--color-primary)';
                }
            }
        });
    }
});

function confirmarEliminarCurso() {
    if (confirm('¿Estás seguro de que deseás eliminar este curso permanentemente?\n\nEsta acción borrará todos sus materiales, preguntas del foro e inscripciones asociadas y no se puede deshacer.')) {
        document.getElementById('form-eliminar-curso').submit();
    }
}
</script>

<form id="form-eliminar-curso" action="<?php echo $base_url; ?>controladores/curso_controlador.php" method="POST" class="display-none">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="curso_id" value="<?php echo (int)$curso['id']; ?>">
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
