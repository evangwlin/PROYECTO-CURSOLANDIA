<?php
$titulo_pagina = 'Catálogo de Cursos';
$menu_activo = 'cursos';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/funciones.php';
require_once __DIR__ . '/../includes/header.php';


$categorias = [
    ''              => 'Todas las categorías',
    'programacion'  => 'Programación',
    'diseno'        => 'Arte & Diseño',
    'comunicacion'  => 'Comunicación',
    'marketing'     => 'Negocios & Marketing',
    'finanzas'      => 'Finanzas',
    'ciencia'       => 'Ciencia & Datos',
    'ia'            => 'Inteligencia Artificial',
    'idiomas'       => 'Idiomas',
];


$todos_los_cursos = [];
$conexion = obtener_conexion();
$resCursos = mysqli_query($conexion, "
        SELECT c.*, u.nombre AS doc_nombre, u.apellido AS doc_apellido, u.plan AS doc_plan,
               cat.slug AS cat_slug, cat.nombre AS cat_nombre_oficial
        FROM curso c
        JOIN usuario u ON c.docente_id = u.id
        LEFT JOIN categoria cat ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug))
        WHERE c.estado = 'publicado'
        ORDER BY (u.plan = 'pro') DESC, c.id DESC
    ");
    if ($resCursos) {
        while ($c = mysqli_fetch_assoc($resCursos)) {
            $tagsList = [];
            if (!empty($c['etiquetas'])) {
                $partesT = explode(',', $c['etiquetas']);
                foreach ($partesT as $pt) {
                    $pt = trim($pt);
                    if ($pt !== '') $tagsList[] = $pt;
                }
            }
            if (empty($tagsList)) $tagsList = ['#Formacion'];
            $cCat = isset($c['categoria']) ? $c['categoria'] : 'general';
            $catSlug = !empty($c['cat_slug']) ? $c['cat_slug'] : strtolower($cCat);
            $catNom = !empty($c['cat_nombre_oficial']) ? $c['cat_nombre_oficial'] : (!empty($c['categoria']) ? $c['categoria'] : 'General');
            $cImg = isset($c['imagen']) ? $c['imagen'] : '';
            $cCatNom = isset($c['categoria']) ? $c['categoria'] : '';
            $todos_los_cursos[] = [
                'id'               => (int)$c['id'],
                'docente_id'       => isset($c['docente_id']) ? (int)$c['docente_id'] : 0,
                'titulo'           => $c['titulo'],
                'docente'          => trim($c['doc_nombre'] . ' ' . $c['doc_apellido']),
                'categoria'        => $catSlug,
                'categoria_nombre' => $catNom,
                'icono'            => isset($c['icono']) ? $c['icono'] : 'bi-journal-bookmark',
                'imagen'           => obtener_portada_curso($cImg, $cCatNom, (int)$c['id']),
                'fecha'            => date('d M', strtotime($c['fecha_inicio'])) . ' - ' . date('d M', strtotime($c['fecha_fin'])),
                'cupo'             => ($c['cupo_max'] ? 'Cupo: ' . $c['cupo_max'] : 'Libre'),
                'tags'             => $tagsList,
                'precio'           => (float)$c['precio'],
                'tipo_precio'      => ((float)$c['precio'] > 0) ? 'pago' : 'gratis',
                'destacado'        => ($c['doc_plan'] === 'pro')
            ];
        }
    }


$categoria_seleccionada = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$filtro_precio = isset($_GET['precio']) ? trim($_GET['precio']) : '';
$orden_seleccionado = isset($_GET['orden']) ? trim($_GET['orden']) : 'destacados';
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$cursos_filtrados = [];
foreach ($todos_los_cursos as $curso) {
    if (!empty($categoria_seleccionada) && $curso['categoria'] !== $categoria_seleccionada) {
        continue;
    }
    if ($filtro_precio === 'gratis' && $curso['tipo_precio'] !== 'gratis') {
        continue;
    }
    if ($filtro_precio === 'pago' && $curso['tipo_precio'] !== 'pago') {
        continue;
    }
    if (!empty($busqueda)) {
        $en_titulo = stripos($curso['titulo'], $busqueda) !== false;
        $en_docente = stripos($curso['docente'], $busqueda) !== false;
        $en_tags = false;
        foreach ($curso['tags'] as $t) {
            if (stripos($t, $busqueda) !== false) {
                $en_tags = true;
                break;
            }
        }
        if (!$en_titulo && !$en_docente && !$en_tags) {
            continue;
        }
    }
    $cursos_filtrados[] = $curso;
}


if ($orden_seleccionado === 'precio_menor') {
    usort($cursos_filtrados, function($a, $b) {
        if ($a['precio'] == $b['precio']) {
            return 0;
        }
        return ($a['precio'] < $b['precio']) ? -1 : 1;
    });
} elseif ($orden_seleccionado === 'precio_mayor') {
    usort($cursos_filtrados, function($a, $b) {
        if ($a['precio'] == $b['precio']) {
            return 0;
        }
        return ($a['precio'] > $b['precio']) ? -1 : 1;
    });
}

$usuario_logueado = (!empty($_SESSION['usuario_id']) || !empty($_SESSION['usuario']['id']) || !empty($_SESSION['usuario']));
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido">

        <header class="dashboard-cabecera">
            <div>
                <h1>Catálogo de Cursos</h1>
                <p>Explorá cursos prácticos, talleres y especializaciones dictadas por la comunidad.</p>
            </div>
            <div class="catalogo-cabecera-meta">
                <span class="catalogo-conteo-badge">
                    <i class="bi bi-collection-play"></i> <strong><?php echo count($cursos_filtrados); ?></strong> cursos disponibles
                </span>
            </div>
        </header>

        <div class="catalogo-toolbar-filtros">
            <div class="toolbar-filtros-izq">
                <div class="toolbar-label-filtro">
                    <i class="bi bi-funnel"></i>
                    <span>Filtros:</span>
                </div>

                <div class="filtro-select-wrapper" title="Filtrar por categoría">
                    <i class="bi bi-grid filtro-icono-prefijo"></i>
                    <select id="select-categoria" class="filtro-select-custom" onchange="aplicarFiltro('categoria', this.value)">
                        <?php foreach ($categorias as $clave => $nombre_cat): ?>
                            <option value="<?php echo htmlspecialchars($clave); ?>" <?php echo ($categoria_seleccionada === $clave) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nombre_cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down filtro-icono-flecha"></i>
                </div>

                <div class="filtro-select-wrapper" title="Filtrar por modalidad / precio">
                    <i class="bi bi-tag filtro-icono-prefijo"></i>
                    <select id="select-precio" class="filtro-select-custom" onchange="aplicarFiltro('precio', this.value)">
                        <option value="" <?php echo empty($filtro_precio) ? 'selected' : ''; ?>>Todos los precios</option>
                        <option value="gratis" <?php echo ($filtro_precio === 'gratis') ? 'selected' : ''; ?>>Solo Gratuitos</option>
                        <option value="pago" <?php echo ($filtro_precio === 'pago') ? 'selected' : ''; ?>>Solo De Pago</option>
                    </select>
                    <i class="bi bi-chevron-down filtro-icono-flecha"></i>
                </div>

                <div class="filtro-select-wrapper" title="Ordenar resultados">
                    <i class="bi bi-arrow-down-up filtro-icono-prefijo"></i>
                    <select id="select-orden" class="filtro-select-custom" onchange="aplicarFiltro('orden', this.value)">
                        <option value="destacados" <?php echo ($orden_seleccionado === 'destacados') ? 'selected' : ''; ?>>Destacados</option>
                        <option value="precio_menor" <?php echo ($orden_seleccionado === 'precio_menor') ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                        <option value="precio_mayor" <?php echo ($orden_seleccionado === 'precio_mayor') ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                    </select>
                    <i class="bi bi-chevron-down filtro-icono-flecha"></i>
                </div>

                <?php if (!empty($categoria_seleccionada) || !empty($filtro_precio) || !empty($busqueda)): ?>
                    <a href="index.php" class="btn-reset-filtros" title="Quitar todos los filtros">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($busqueda)): ?>
                <div class="toolbar-busqueda-activa">
                    <span>Búsqueda: <strong>"<?php echo htmlspecialchars($busqueda); ?>"</strong></span>
                    <a href="index.php<?php echo (!empty($categoria_seleccionada) || !empty($filtro_precio)) ? '?categoria='.urlencode($categoria_seleccionada).'&precio='.urlencode($filtro_precio) : ''; ?>" class="btn-quitar-busqueda" title="Quitar término de búsqueda">×</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($cursos_filtrados)): ?>
            <div class="grilla-cursos">
                <?php foreach ($cursos_filtrados as $curso): ?>
                    <article class="tarjeta-curso">
                        <div class="curso-portada-card">
                            <?php 
                            $cImgTpl = isset($curso['imagen']) ? $curso['imagen'] : '';
                            $cCatTpl = isset($curso['categoria']) ? $curso['categoria'] : '';
                            $imgCat = obtener_portada_curso($cImgTpl, $cCatTpl, (int)$curso['id']); 
                            ?>
                            <img src="<?php echo htmlspecialchars($imgCat); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="curso-portada-img" loading="lazy" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgCat); ?>';">
                        </div>
                        <div class="curso-info-card">
                            <div class="curso-autor-meta">
                                <?php if (!empty($curso['docente_id'])): ?>
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="curso-autor-nombre"><?php echo htmlspecialchars($curso['docente']); ?></a>
                                <?php else: ?>
                                    <strong class="curso-autor-nombre"><?php echo htmlspecialchars($curso['docente']); ?></strong>
                                <?php endif; ?>
                                <span class="curso-separador">•</span>
                                <span class="curso-fecha"><?php echo htmlspecialchars($curso['fecha']); ?></span>
                            </div>
                            <h3 class="curso-titulo">
                                <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['titulo']); ?>
                                </a>
                            </h3>
                            <div class="curso-pildoras">
                                <?php foreach ($curso['tags'] as $tag): ?>
                                    <span class="pildora-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                                <span class="pildora-tag"><i class="bi bi-people"></i> <?php echo htmlspecialchars($curso['cupo']); ?></span>
                            </div>
                        </div>
                        <div class="curso-pie-card">
                            <div class="curso-precio-bloque">
                                <?php if ($curso['tipo_precio'] === 'gratis'): ?>
                                    <span class="curso-precio gratis">Gratis</span>
                                    <span class="curso-precio-sub">Acceso inmediato</span>
                                <?php else: ?>
                                    <span class="curso-precio">$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></span>
                                    <span class="curso-precio-sub">Inscripción abierta</span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/ver.php?id=<?php echo $curso['id']; ?>" class="btn-ver-curso">Ver Curso</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="catalogo-caja-vacia">
                <i class="bi bi-search catalogo-icono-vacio"></i>
                <h3>No encontramos cursos para los filtros seleccionados</h3>
                <p>Probá cambiando la categoría o la modalidad de precio desde los selectores desplegables.</p>
                <a href="index.php" class="btn-primario catalogo-btn-reset">Restablecer filtros</a>
            </div>
        <?php endif; ?>

    </main>

</div>

<script>
function aplicarFiltro(parametro, valor) {
    var params = new URLSearchParams(window.location.search);
    if (valor && valor !== '') {
        params.set(parametro, valor);
    } else {
        params.delete(parametro);
    }
    window.location.search = params.toString();
}
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
