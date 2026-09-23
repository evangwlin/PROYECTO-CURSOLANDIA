<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/funciones.php';

if (!isset($base_url) || empty($base_url)) {
    $base_url = function_exists('obtener_base_url') ? obtener_base_url() : '/';
}


$titulo = 'Cursolandia · Cursos Online';
if (isset($titulo_pagina)) {
    $titulo = $titulo_pagina . ' · Cursolandia';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/estilo.css?v=<?php echo time(); ?>">
</head>
<body>

<?php 
require_once __DIR__ . '/nav.php'; 
?>
