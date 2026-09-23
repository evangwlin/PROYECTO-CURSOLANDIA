<?php

if (!isset($base_url)) {
    $base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/PROYECTO-CURSOLANDIA/';
}
?>
<footer>
    <div class="footer-contenido">
        <div class="footer-col">
            <h4>Cursolandia</h4>
            <ul>
                <li><a href="<?php echo $base_url; ?>paginas/acerca.php">Sobre nosotros</a></li>
                <li><a href="<?php echo $base_url; ?>paginas/terminos.php">Términos y Condiciones</a></li>
                <li><a href="<?php echo $base_url; ?>paginas/privacidad.php">Privacidad</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Cursos</h4>
            <ul>
                <li><a href="<?php echo $base_url; ?>cursos/index.php">Explorar Catálogo</a></li>
                <li><a href="<?php echo $base_url; ?><?php echo (isset($_SESSION['usuario_id']) || isset($_SESSION['usuario'])) ? 'panel/index.php' : 'index.php'; ?>">Cursos Destacados</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Ayuda & Soporte</h4>
            <ul>
                <li><a href="<?php echo $base_url; ?>paginas/faq.php">Preguntas Frecuentes (FAQ)</a></li>
                <li><a href="<?php echo $base_url; ?>paginas/contacto.php">Contacto y Soporte</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-copyright">
        &copy; 2026 Cursolandia · Tecnicatura Universitaria en Web · Programación III
    </div>
</footer>

</body>
</html>