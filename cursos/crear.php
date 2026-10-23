<?php
$titulo_pagina = 'Crear Nuevo Curso';
$menu_activo = 'cursos';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para crear un curso.';
    header('Location: ../auth/ingresar.php');
    exit;
}

$es_pro = usuario_es_pro($usuario_id);

// limita plan gratuito
if (!$es_pro && contar_cursos_activos_docente($usuario_id) >= 3) {
    $_SESSION['error'] = 'Alcanzaste el límite de 3 cursos activos del Plan Gratuito. Mejorá a Plan PRO para publicar nuevos cursos sin restricciones.';
    header('Location: mis-cursos.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <header class="dashboard-cabecera">
            <div>
                <h1>Crear Nuevo Curso</h1>
                <p>Completá los datos de tu curso para publicarlo en la comunidad de Cursolandia.</p>
            </div>
            <div class="cabecera-acciones">
                <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="btn-pildora-todas">
                    <i class="bi bi-arrow-left"></i> Volver a mis cursos
                </a>
            </div>
        </header>

        <div class="crear-curso-layout">

            <div class="crear-curso-col-principal">

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alerta-flash error" style="margin-bottom: 20px;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="../controladores/curso_controlador.php?accion=crear" method="POST" enctype="multipart/form-data" class="formulario-crear-curso" onsubmit="return validarFormularioCurso(event);">

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-info-circle-fill"></i>
                            <h3>Información Básica</h3>
                        </div>

                        <div class="campo-grupo">
                            <label for="titulo">Título del curso <span class="campo-obligatorio">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="input-control" placeholder="Ej: Desarrollo Web Fullstack con PHP y MySQL" required>
                            <span class="texto-ayuda">Un título claro y descriptivo ayuda a que más alumnos encuentren tu propuesta.</span>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="categoria">Categoría <span class="campo-obligatorio">*</span></label>
                                <select id="categoria" name="categoria" class="input-control select-control" required>
                                    <option value="" disabled selected>Seleccioná una categoría</option>
                                    <option value="programacion">Programación</option>
                                    <option value="diseno">Arte & Diseño</option>
                                    <option value="comunicacion">Comunicación</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="finanzas">Finanzas</option>
                                    <option value="ciencia">Ciencia & Datos</option>
                                    <option value="ia">Inteligencia Artificial</option>
                                    <option value="idiomas">Idiomas</option>
                                </select>
                            </div>

                            <div class="campo-grupo">
                                <label for="nivel">Nivel de dificultad <span class="campo-obligatorio">*</span></label>
                                <select id="nivel" name="nivel" class="input-control select-control" required>
                                    <option value="" disabled>Seleccioná un nivel</option>
                                    <option value="todos">Todos los niveles</option>
                                    <option value="inicial">Inicial / Principiante</option>
                                    <option value="intermedio" selected>Intermedio</option>
                                    <option value="avanzado">Avanzado</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label for="descripcion">Descripción general del curso <span class="campo-obligatorio">*</span></label>
                            <textarea id="descripcion" name="descripcion" class="input-control textarea-control" rows="3" placeholder="Explicá de qué trata el curso y a quién está dirigido..." required></textarea>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-bullseye"></i>
                            <div>
                                <h3>Lo que se va a aprender (Objetivos) <span class="campo-obligatorio">*</span></h3>
                                <p class="panel-subtitulo-sec">Definí los aprendizajes clave y competencias que adquirirá el estudiante.</p>
                            </div>
                        </div>
                        <div class="campo-grupo">
                            <label for="objetivos">Objetivos del curso (un aprendizaje por línea) <span class="campo-obligatorio">*</span></label>
                            <textarea id="objetivos" name="objetivos" class="input-control textarea-control" rows="4" placeholder="Ej:
Construir aplicaciones web completas utilizando PHP 8 y MySQL
Dominar el patrón de arquitectura Modelo-Vista-Controlador (MVC)
Modelar bases de datos relacionales normalizadas hasta 3FN
Gestionar sesiones de usuario, autenticación segura y subida de archivos" required></textarea>
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
                            <textarea id="requisitos" name="requisitos" class="input-control textarea-control" rows="3" placeholder="Ej:
Conocimientos básicos de HTML y CSS
Computadora con acceso a Internet
Editor de código (recomendado VS Code o similar)" required></textarea>
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
                            <h3>Fechas, Cupos y Precio</h3>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="fecha_inicio">Fecha de inicio <span class="campo-obligatorio">*</span></label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" class="input-control" required>
                            </div>

                            <div class="campo-grupo">
                                <label for="fecha_fin">Fecha de finalización <span class="campo-obligatorio">*</span></label>
                                <input type="date" id="fecha_fin" name="fecha_fin" class="input-control" required>
                            </div>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="cupo">Cupo máximo de estudiantes <span class="campo-obligatorio">*</span></label>
                                <input type="number" id="cupo" name="cupo" class="input-control" min="1" max="100" placeholder="Ej: 30" required>
                                <span class="texto-ayuda">Límite de alumnos que podrán inscribirse a la cursada.</span>
                            </div>

                            <div class="campo-grupo">
                                <label for="precio">Modalidad de precio <span class="campo-obligatorio">*</span></label>
                                <div class="precio-selector-wrapper">
                                    <select id="tipo_precio" name="tipo_precio" class="input-control select-control" onchange="togglePrecioInput(this.value)">
                                        <option value="pago" selected>Curso de pago</option>
                                        <option value="gratis">Gratis / Acceso libre</option>
                                    </select>
                                    <div id="contenedor-precio-monto" class="precio-input-caja">
                                        <span class="precio-moneda">$</span>
                                        <input type="number" id="precio" name="precio" class="input-control" min="0" step="any" value="10000" placeholder="Monto (ARS)" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-tags-fill"></i>
                            <h3>Etiquetas e Intereses (#Tags) <span class="campo-obligatorio">*</span></h3>
                        </div>
                        <p class="texto-ayuda" style="margin-bottom: 12px;">Ingresá hashtags separados por comas. Estas etiquetas son clave para que el algoritmo recomiende tu curso a los alumnos con intereses afines.</p>

                        <div class="campo-grupo">
                            <label for="tags">Etiquetas del curso <span class="campo-obligatorio">*</span></label>
                            <input type="text" id="tags" name="tags" class="input-control" placeholder="Ej: #PHP, #MySQL, #Fullstack, #Backend" required>
                        </div>

                        <div class="tags-sugerencias">
                            <span class="tag-sugerencia-label">Sugeridos:</span>
                            <button type="button" class="btn-sugerencia-tag" onclick="agregarTag('PHP')">+ #PHP</button>
                            <button type="button" class="btn-sugerencia-tag" onclick="agregarTag('MySQL')">+ #MySQL</button>
                            <button type="button" class="btn-sugerencia-tag" onclick="agregarTag('Web')">+ #Web</button>
                            <button type="button" class="btn-sugerencia-tag" onclick="agregarTag('Frontend')">+ #Frontend</button>
                            <button type="button" class="btn-sugerencia-tag" onclick="agregarTag('CSS')">+ #CSS</button>
                        </div>
                    </section>

                    <section class="tarjeta-panel">
                        <div class="panel-cabecera-icono">
                            <i class="bi bi-file-earmark-arrow-up-fill"></i>
                            <h3>Portada y Materiales</h3>
                        </div>

                        <div class="campo-grupo">
                            <label>Imagen de portada del curso <span class="campo-obligatorio">*</span></label>
                            <label for="portada" class="subir-archivo-box">
                                <i class="bi bi-image" style="font-size: 2rem; color: var(--color-primary);"></i>
                                <p><strong>Elegí una imagen</strong> o arrastrala acá</p>
                                <span class="texto-ayuda">Recomendado: 1200x630 px · JPG, PNG o WebP (Máx 2 MB)</span>
                                <input type="file" id="portada" name="portada" accept="image/*" class="input-archivo-oculto" required>
                            </label>
                        </div>

                        <div class="campo-grupo" style="margin-top: 14px;">
                            <div class="cabecera-archivos-limite">
                                <label>Archivos adjuntos iniciales (Programa, Guía, etc.)</label>
                                <span class="limite-plan-tag">
                                    <?php echo $es_pro ? 'Plan Pro: Ilimitados' : 'Plan Gratuito: Hasta 5 archivos (máx 10 MB c/u)'; ?>
                                </span>
                            </div>
                            <label for="materiales" class="subir-archivo-box">
                                <i class="bi bi-folder-plus" style="font-size: 2rem; color: var(--color-secondary);"></i>
                                <p><strong>Subir documentos o guías en PDF</strong></p>
                                <span class="texto-ayuda">PDF, DOCX, ZIP hasta 10 MB</span>
                                <input type="file" id="materiales" name="materiales[]" multiple class="input-archivo-oculto">
                            </label>
                        </div>
                    </section>

                    <div style="background: var(--color-surface); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 14px;">
                        <i class="bi bi-shield-check" style="font-size: 1.6rem; color: var(--color-primary); flex-shrink: 0;"></i>
                        <div style="font-size: 0.86rem; color: var(--color-text-main); line-height: 1.45;">
                            <strong>Proceso de Aprobación:</strong> Al enviar tu propuesta, el administrador revisará los contenidos y el temario. Una vez aprobado, el curso se publicará automáticamente en el catálogo y los alumnos podrán inscribirse.
                        </div>
                    </div>

                    <div class="crear-curso-botones-fila">
                        <button type="submit" class="btn-primario btn-lg">
                            <i class="bi bi-send-check"></i> Enviar Curso para Aprobación
                        </button>
                        <a href="<?php echo $base_url; ?>cursos/mis-cursos.php" class="btn-cancelar-enlace">Cancelar</a>
                    </div>

                </form>

            </div>

            <aside class="crear-curso-col-lateral">

                <div class="lateral-card">
                    <div class="lateral-cabecera-stat">
                        <h3 class="lateral-card-titulo">Vista Previa</h3>
                        <span class="badge-previa">Así lo verán los alumnos</span>
                    </div>

                    <article class="tarjeta-curso tarjeta-curso-previa">
                        <div class="curso-portada-card">
                            <img src="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80" alt="Vista previa" class="curso-portada-img" id="previa-imagen">
                        </div>
                        <div class="curso-info-card">
                            <div class="curso-autor-meta">
                                <strong class="curso-autor-nombre"><?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?></strong>
                                <span class="curso-separador">•</span>
                                <span class="curso-fecha" id="previa-fechas">Próximamente</span>
                            </div>
                            <h4 class="curso-titulo" id="previa-titulo">Título de tu nuevo curso</h4>
                            <div class="curso-pildoras" id="previa-tags">
                                <span class="pildora-tag">#Curso</span>
                                <span class="pildora-tag"><i class="bi bi-people"></i> 0 cupos</span>
                            </div>
                        </div>
                        <div class="curso-pie-card">
                            <div class="curso-precio-bloque">
                                <span class="curso-precio" id="previa-precio">$ 10.000</span>
                                <span class="curso-precio-sub">Inscripción abierta</span>
                            </div>
                            <span class="btn-ver-curso" style="pointer-events: none; opacity: 0.8;">Ver Curso</span>
                        </div>
                    </article>
                </div>

                <div class="lateral-card lateral-card-plan">
                    <div class="lateral-plan-cabecera">
                        <i class="bi bi-lightbulb-fill" style="color: var(--color-secondary);"></i>
                        <h3 class="lateral-card-titulo">Consejos para Creadores</h3>
                    </div>
                    <ul class="lateral-plan-lista">
                        <li><strong>Título directo:</strong> Enfocate en la habilidad concreta que aprenderá el estudiante.</li>
                        <li><strong>Tags precisos:</strong> Usá entre 3 y 5 etiquetas para maximizar la visibilidad en las recomendaciones.</li>
                        <li><strong>Cupo realista:</strong> Te permite dar un mejor seguimiento y respuesta en los foros.</li>
                    </ul>
                </div>

            </aside>

        </div>

    </main>

</div>

<script>
function togglePrecioInput(tipo) {
    const contenedor = document.getElementById('contenedor-precio-monto');
    const inputPrecio = document.getElementById('precio');
    const previaPrecio = document.getElementById('previa-precio');
    if (tipo === 'gratis') {
        if (contenedor) contenedor.style.display = 'none';
        if (inputPrecio) {
            inputPrecio.value = '0';
            inputPrecio.removeAttribute('required');
        }
        if (previaPrecio) {
            previaPrecio.textContent = 'Gratis';
            previaPrecio.classList.add('gratis');
        }
    } else {
        if (contenedor) contenedor.style.display = 'flex';
        if (inputPrecio) {
            inputPrecio.setAttribute('required', 'required');
            if (!inputPrecio.value || inputPrecio.value === '0') {
                inputPrecio.value = '10000';
            }
        }
        if (previaPrecio) {
            previaPrecio.classList.remove('gratis');
            previaPrecio.textContent = '$ ' + Number(inputPrecio.value).toLocaleString('es-AR');
        }
    }
}

function agregarTag(tag) {
    const inputTags = document.getElementById('tags');
    let actual = inputTags.value.trim();
    const tagConHash = '#' + tag;
    if (actual.includes(tagConHash)) return;
    if (actual.length > 0 && !actual.endsWith(',')) {
        actual += ', ';
    }
    inputTags.value = actual + tagConHash;
    actualizarPreviaTags();
}

// vista previa
document.getElementById('titulo').addEventListener('input', function() {
    const previa = document.getElementById('previa-titulo');
    previa.textContent = this.value.trim() || 'Título de tu nuevo curso';
});

document.getElementById('precio').addEventListener('input', function() {
    const previa = document.getElementById('previa-precio');
    previa.textContent = this.value ? '$ ' + Number(this.value).toLocaleString('es-AR') : '$ 0';
});

function actualizarPreviaTags() {
    const input = document.getElementById('tags').value;
    const cont = document.getElementById('previa-tags');
    const tags = input.split(',').map(t => t.trim()).filter(t => t.length > 0);
    if (tags.length === 0) {
        cont.innerHTML = '<span class="pildora-tag">#Curso</span><span class="pildora-tag"><i class="bi bi-people"></i> 0 cupos</span>';
    } else {
        let html = tags.slice(0, 3).map(t => `<span class="pildora-tag">${t.startsWith('#') ? t : '#' + t}</span>`).join('');
        cont.innerHTML = html;
    }
}

document.getElementById('tags').addEventListener('input', actualizarPreviaTags);

const inputPortada = document.getElementById('portada');
const inputMateriales = document.getElementById('materiales');
const previaImagen = document.getElementById('previa-imagen');
const selectCategoria = document.getElementById('categoria');

const portadasPorCategoria = {
    'programacion': 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80',
    'diseno': 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
    'comunicacion': 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80',
    'marketing': 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
    'negocios': 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
    'finanzas': 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=600&q=80',
    'ciencia': 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80',
    'ia': 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80',
    'idiomas': 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80'
};

if (selectCategoria && previaImagen) {
    selectCategoria.addEventListener('change', function() {
        if (!inputPortada || !inputPortada.files || inputPortada.files.length === 0) {
            const cat = (this.value || '').toLowerCase();
            if (portadasPorCategoria[cat]) {
                previaImagen.src = portadasPorCategoria[cat];
            }
        }
    });
}
if (inputPortada && previaImagen) {
    inputPortada.addEventListener('change', function() {
        const file = this.files && this.files[0];
        if (file) {
            if (file.type && !file.type.startsWith('image/')) {
                alert('Por favor, seleccioná un archivo de imagen válido (JPG, PNG o WebP).');
                this.value = '';
                return;
            }
            const objectUrl = URL.createObjectURL(file);
            previaImagen.src = objectUrl;
            
            const pBox = this.closest('.subir-archivo-box')?.querySelector('p');
            if (pBox) {
                pBox.innerHTML = `<strong>${escapeHtml(file.name)}</strong> (${(file.size / 1024).toFixed(1)} KB)`;
            }
        }
    });
}

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

// =============================================================
// MODULOS Y CONTENIDOS
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
        alert('Debés agregar al menos un módulo temático completo para el curso.');
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
    const cont = document.getElementById('contenedor-modulos-dinamicos');
    if (cont && cont.children.length === 0) {
        agregarModuloUI(
            'Introducción y Fundamentos',
            '2h 30m',
            'Presentación del curso y hoja de ruta\nConfiguración de las herramientas necesarias\nConceptos esenciales y vocabulario'
        );
        agregarModuloUI(
            'Práctica y Desarrollo del Proyecto',
            '4h 00m',
            'Arquitectura de la solución\nImplementación paso a paso\nBuenas prácticas y optimización'
        );
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
