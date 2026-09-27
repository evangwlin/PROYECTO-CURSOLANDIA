<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if ((isset($_SESSION['usuario_id']) || isset($_SESSION['usuario'])) && file_exists(__DIR__ . '/panel/index.php')) {
    header('Location: panel/index.php');
    exit;
}

$titulo_pagina = 'Cursolandia';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/funciones.php';
require_once __DIR__ . '/includes/header.php';

$conexion = obtener_conexion();


$cursosDestacados = [];
$resDest = mysqli_query($conexion, "
    SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
    FROM curso c
    LEFT JOIN usuario u ON c.docente_id = u.id
    WHERE c.estado = 'publicado'
    ORDER BY (u.plan = 'pro') DESC, c.id ASC
    LIMIT 3
");
if ($resDest) {
    while ($fila = mysqli_fetch_assoc($resDest)) {
        $cursosDestacados[] = $fila;
    }
}


$cursosRecomendados = [];
$idsDest = [];
if (!empty($cursosDestacados)) {
    foreach ($cursosDestacados as $cDest) {
        $idsDest[] = intval($cDest['id']);
    }
}
$idsDestacados = !empty($idsDest) ? implode(',', $idsDest) : '0';
$resRec = mysqli_query($conexion, "
    SELECT c.*, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.plan AS docente_plan
    FROM curso c
    LEFT JOIN usuario u ON c.docente_id = u.id
    WHERE c.estado = 'publicado' AND c.id NOT IN ($idsDestacados)
    ORDER BY c.id ASC
    LIMIT 3
");
if ($resRec) {
    while ($fila = mysqli_fetch_assoc($resRec)) {
        $cursosRecomendados[] = $fila;
    }
}
if (empty($cursosRecomendados)) {
    $cursosRecomendados = $cursosDestacados;
}
?>

<section class="hero">
    <h1>Aprendé y enseñá en Cursolandia</h1>
    <p>Cursos creados por la comunidad. Sumate como alumno o publicá tu propio curso.</p>
    <div class="hero-botones">
        <a href="<?php echo $base_url; ?>auth/registrarse.php" class="btn-primario">Crear cuenta gratis</a>
        <a href="<?php echo $base_url; ?>cursos/index.php" class="btn-secundario">Explorar cursos</a>
    </div>
</section>

<section class="seccion-destacados">
    <div class="contenedor">
        <div class="seccion-cabecera">
            <h2>Cursos Destacados</h2>
            <p>Los cursos más elegidos y mejor valorados por nuestra comunidad de estudiantes.</p>
        </div>

        <div class="grilla-cursos">
            <?php foreach ($cursosDestacados as $cd): 
                $docNombre = trim((isset($cd['docente_nombre']) ? $cd['docente_nombre'] : '') . ' ' . (isset($cd['docente_apellido']) ? $cd['docente_apellido'] : ''));
                if (empty($docNombre)) $docNombre = 'Docente';
                $cdEtiquetas = isset($cd['etiquetas']) ? $cd['etiquetas'] : '';
                $tags = [];
                if (!empty($cdEtiquetas)) {
                    $partesEt = explode(',', $cdEtiquetas);
                    foreach ($partesEt as $pEt) {
                        $pEt = trim($pEt);
                        if ($pEt !== '') $tags[] = $pEt;
                    }
                }
                $cdImagen = isset($cd['imagen']) ? $cd['imagen'] : '';
                $cdCat = isset($cd['categoria']) ? $cd['categoria'] : '';
                $imgPort = obtener_portada_curso($cdImagen, $cdCat, (int)$cd['id']);
                $fInicio = !empty($cd['fecha_inicio']) ? date('d M', strtotime($cd['fecha_inicio'])) : 'Próximamente';
                $fFin = !empty($cd['fecha_fin']) ? date('d M', strtotime($cd['fecha_fin'])) : '';
                $fechasStr = $fFin ? "{$fInicio} - {$fFin}" : $fInicio;
            ?>
                <article class="tarjeta-curso">
                    <div class="curso-portada-card">
                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cd['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                    </div>
                    <div class="curso-info-card">
                        <div class="curso-autor-meta">
                            <strong class="curso-autor-nombre"><?php echo htmlspecialchars($docNombre); ?></strong>
                            <span class="curso-separador">•</span>
                            <span class="curso-fecha"><?php echo htmlspecialchars($fechasStr); ?></span>
                        </div>
                        <h3 class="curso-titulo"><a href="cursos/ver.php?id=<?php echo $cd['id']; ?>"><?php echo htmlspecialchars($cd['titulo']); ?></a></h3>
                        <div class="curso-pildoras">
                            <?php foreach (array_slice($tags, 0, 2) as $t): ?>
                                <span class="pildora-tag">#<?php echo htmlspecialchars(ltrim($t, '#')); ?></span>
                            <?php endforeach; ?>
                            <?php if (!empty($cd['cupo_max'])): ?>
                                <span class="pildora-tag"><i class="bi bi-people"></i> Cupo: <?php echo $cd['cupo_max']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="curso-pie-card">
                        <div class="curso-precio-bloque">
                            <?php if ((float)$cd['precio'] <= 0): ?>
                                <span class="curso-precio gratis">Gratis</span>
                            <?php else: ?>
                                <span class="curso-precio">$ <?php echo number_format($cd['precio'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                            <span class="curso-precio-sub">Inscripción abierta</span>
                        </div>
                        <a href="cursos/ver.php?id=<?php echo $cd['id']; ?>" class="btn-ver-curso">Ver Curso</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion-recomendados">
    <div class="contenedor">
        <div class="seccion-cabecera">
            <h2>Cursos Recomendados</h2>
            <p>Propuestas formativas destacadas para comenzar a aprender hoy mismo en la comunidad.</p>
        </div>

        <div class="grilla-cursos">
            <?php foreach ($cursosRecomendados as $cr): 
                $docNombre = trim((isset($cr['docente_nombre']) ? $cr['docente_nombre'] : '') . ' ' . (isset($cr['docente_apellido']) ? $cr['docente_apellido'] : ''));
                if (empty($docNombre)) $docNombre = 'Docente';
                $crEtiquetas = isset($cr['etiquetas']) ? $cr['etiquetas'] : '';
                $tags = [];
                if (!empty($crEtiquetas)) {
                    $partesEt = explode(',', $crEtiquetas);
                    foreach ($partesEt as $pEt) {
                        $pEt = trim($pEt);
                        if ($pEt !== '') $tags[] = $pEt;
                    }
                }
                $crImagen = isset($cr['imagen']) ? $cr['imagen'] : '';
                $crCat = isset($cr['categoria']) ? $cr['categoria'] : '';
                $imgPort = obtener_portada_curso($crImagen, $crCat, (int)$cr['id']);
                $fInicio = !empty($cr['fecha_inicio']) ? date('d M', strtotime($cr['fecha_inicio'])) : 'Próximamente';
                $fFin = !empty($cr['fecha_fin']) ? date('d M', strtotime($cr['fecha_fin'])) : '';
                $fechasStr = $fFin ? "{$fInicio} - {$fFin}" : $fInicio;
            ?>
                <article class="tarjeta-curso">
                    <div class="curso-portada-card">
                        <img src="<?php echo htmlspecialchars($imgPort); ?>" alt="<?php echo htmlspecialchars($cr['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgPort); ?>';">
                    </div>
                    <div class="curso-info-card">
                        <div class="curso-autor-meta">
                            <strong class="curso-autor-nombre"><?php echo htmlspecialchars($docNombre); ?></strong>
                            <span class="curso-separador">•</span>
                            <span class="curso-fecha"><?php echo htmlspecialchars($fechasStr); ?></span>
                        </div>
                        <h3 class="curso-titulo"><a href="cursos/ver.php?id=<?php echo $cr['id']; ?>"><?php echo htmlspecialchars($cr['titulo']); ?></a></h3>
                        <div class="curso-pildoras">
                            <?php foreach (array_slice($tags, 0, 2) as $t): ?>
                                <span class="pildora-tag">#<?php echo htmlspecialchars(ltrim($t, '#')); ?></span>
                            <?php endforeach; ?>
                            <?php if (!empty($cr['cupo_max'])): ?>
                                <span class="pildora-tag"><i class="bi bi-people"></i> Cupo: <?php echo $cr['cupo_max']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="curso-pie-card">
                        <div class="curso-precio-bloque">
                            <?php if ((float)$cr['precio'] <= 0): ?>
                                <span class="curso-precio gratis">Gratis</span>
                            <?php else: ?>
                                <span class="curso-precio">$ <?php echo number_format($cr['precio'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                            <span class="curso-precio-sub">Acceso inmediato</span>
                        </div>
                        <a href="cursos/ver.php?id=<?php echo $cr['id']; ?>" class="btn-ver-curso">Ver Curso</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion-resenas">
    <div class="contenedor">
        <div class="resenas-cabecera">
            <h2>Únete a personas que están transformando sus proyectos a través del aprendizaje</h2>
        </div>

        <div class="grilla-resenas">
            <article class="tarjeta-resena">
                <span class="resena-comillas">“</span>
                <p class="resena-texto">
                    El curso de <strong>PHP y MySQL</strong> hizo un gran trabajo a la hora de explicar la arquitectura web. Las respuestas en el foro fueron clave para entender cómo modelar en 3FN para mi entrega.
                </p>
                <div class="resena-pie">
                    <div class="resena-autor">
                        <div class="autor-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="autor-datos">
                            <strong class="autor-nombre">Lucía M.</strong>
                            <span class="autor-cargo">Alumna de Desarrollo Web</span>
                        </div>
                    </div>
                    <a href="#" class="autor-curso-link">Ver curso sobre PHP y MySQL ›</a>
                </div>
            </article>

            <article class="tarjeta-resena">
                <span class="resena-comillas">“</span>
                <p class="resena-texto">
                    Cursolandia <strong>marcó un antes y un después</strong> en mi formación. Pude descargar todo el material del aula sin complicaciones y resolver mis dudas en el foro con el profesor.
                </p>
                <div class="resena-pie">
                    <div class="resena-autor">
                        <div class="autor-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="autor-datos">
                            <strong class="autor-nombre">Esteban Peralta</strong>
                            <span class="autor-cargo">Estudiante de Programación III</span>
                        </div>
                    </div>
                    <a href="#" class="autor-curso-link">Ver este curso sobre Frontend ›</a>
                </div>
            </article>

            <article class="tarjeta-resena">
                <span class="resena-comillas">“</span>
                <p class="resena-texto">
                    La plataforma te da la posibilidad de <strong>aprender de forma constante</strong>. Aprendí exactamente lo que necesitaba sobre maquetación responsive moderna con CSS para proyectos reales.
                </p>
                <div class="resena-pie">
                    <div class="resena-autor">
                        <div class="autor-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="autor-datos">
                            <strong class="autor-nombre">Martín R.</strong>
                            <span class="autor-cargo">Desarrollador Web Junior</span>
                        </div>
                    </div>
                    <a href="#" class="autor-curso-link">Ver este curso sobre CSS Grid ›</a>
                </div>
            </article>

            <article class="tarjeta-resena">
                <span class="resena-comillas">“</span>
                <p class="resena-texto">
                    Me encantó la dinámica de las preguntas destacadas y aceptadas. Es una herramienta muy útil tanto para alumnos como para docentes que queremos compartir conocimientos con la comunidad.
                </p>
                <div class="resena-pie">
                    <div class="resena-autor">
                        <div class="autor-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="autor-datos">
                            <strong class="autor-nombre">Valeria Rossi</strong>
                            <span class="autor-cargo">Docente de Arquitectura Web</span>
                        </div>
                    </div>
                    <a href="#" class="autor-curso-link">Ver curso de Ingeniería de Software ›</a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="seccion-faq" id="faq">
    <div class="contenedor">
        <div class="seccion-cabecera">
            <h2>Preguntas Frecuentes</h2>
            <p>Todo lo que necesitás saber sobre el funcionamiento de Cursolandia, inscripciones, foros y planes.</p>
        </div>

        <div class="faq-lista">
            <details class="faq-item">
                <summary class="faq-pregunta">
                    <span class="faq-texto">¿Cómo funciona la inscripción a los cursos gratuitos y pagos?</span>
                    <span class="faq-toggle-btn" aria-hidden="true"></span>
                </summary>
                <div class="faq-respuesta">
                    <p>En los <strong>cursos gratuitos</strong> la inscripción es inmediata y automática. En los <strong>cursos pagos</strong>, tu solicitud queda en estado <em>Pendiente</em> hasta que el docente creador confirme la recepción del pago, el cual se coordina por fuera de la plataforma.</p>
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-pregunta">
                    <span class="faq-texto">¿Cuántos cursos puedo tomar o dictar con el plan gratuito?</span>
                    <span class="faq-toggle-btn" aria-hidden="true"></span>
                </summary>
                <div class="faq-respuesta">
                    <p>Un usuario regular de plan gratuito puede dictar un máximo de <strong>hasta 3 cursos activos</strong> simultáneamente y estar inscripto como alumno en <strong>hasta 3 cursos activos</strong> a la vez. Los usuarios con <strong>Plan PRO</strong> no tienen límite alguno en creación ni en inscripciones.</p>
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-pregunta">
                    <span class="faq-texto">¿Cómo funciona el foro de dudas de cada curso?</span>
                    <span class="faq-toggle-btn" aria-hidden="true"></span>
                </summary>
                <div class="faq-respuesta">
                    <p>Dentro de cada curso, alumnos y docentes pueden abrir preguntas e intercambiar respuestas. Para fomentar la participación ordenada, <strong>un usuario no puede responder dos veces seguidas</strong> (debe editar su mensaje anterior o esperar una respuesta intermedia). Además, tanto el docente como el creador de la duda pueden marcar una respuesta como <strong>"Aceptada"</strong>, cerrando el debate.</p>
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-pregunta">
                    <span class="faq-texto">¿Cómo solicito y activo el Plan PRO?</span>
                    <span class="faq-toggle-btn" aria-hidden="true"></span>
                </summary>
                <div class="faq-respuesta">
                    <p>Desde la sección <em>Mi Perfil</em> podés enviar un mensaje adjuntando tu comprobante de pago para notificar al administrador. El administrador revisará el adjunto y activará tu Plan PRO asignándole una fecha de vencimiento.</p>
                </div>
            </details>

            <details class="faq-item">
                <summary class="faq-pregunta">
                    <span class="faq-texto">¿Cuáles son los límites para subir material de estudio?</span>
                    <span class="faq-toggle-btn" aria-hidden="true"></span>
                </summary>
                <div class="faq-respuesta">
                    <p>Los creadores con plan gratuito pueden subir hasta <strong>5 archivos adjuntos</strong> por curso, con un tope individual de <strong>5 MB por archivo</strong> y un total de <strong>10 MB</strong>. Los usuarios PRO tienen mayor capacidad y flexibilidad para sus materiales descargables.</p>
                </div>
            </details>
        </div>

        <div class="faq-pie">
            <p>¿Tenés alguna otra duda? <a href="<?php echo $base_url; ?>paginas/contacto.php">Contactanos →</a></p>
        </div>
    </div>
</section>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>