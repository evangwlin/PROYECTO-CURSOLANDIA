<?php

if (!headers_sent()) {
    @ob_start();
}

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$servidor = 'localhost';
if (isset($_SERVER['HTTP_HOST'])) {
    $servidor = $_SERVER['HTTP_HOST'];
} elseif (isset($_SERVER['SERVER_NAME'])) {
    $servidor = $_SERVER['SERVER_NAME'];
}
$es_local = in_array($servidor, ['localhost', '127.0.0.1', '::1', '[::1]'])
    || strpos($servidor, 'localhost:') === 0
    || strpos($servidor, '127.0.0.1:') === 0
    || (php_sapi_name() === 'cli' && !isset($_SERVER['INFINITYFREE']));

if ($es_local) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    $dbhost     = 'localhost';
    $dbusuario  = 'root';
    $dbpassword = '';
    $dbnombre   = 'cursolandia';
    
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $script_path = str_replace('\\', '/', $script_name);
    if (stripos($script_path, '/cursolandia') === 0) {
        $base_url = '/cursolandia/';
    } else {
        $base_url = '/PROYECTO-CURSOLANDIA/';
    }
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    $dbhost     = 'sql311.infinityfree.com';
    $dbusuario  = 'if0_43454552';
    $dbpassword = 'Cursolandia123';
    $dbnombre   = 'if0_43454552_Cursolandia';
    $base_url   = '/';
}

$conexion = @mysqli_connect($dbhost, $dbusuario, $dbpassword, $dbnombre);

if (!$conexion) {
    if ($es_local) {
        die('Error al conectar con la base de datos MySQL local: ' . mysqli_connect_error());
    } else {
        die('Lo sentimos, en este momento no pudimos conectar con la base de datos de InfinityFree. Por favor reintentá en unos instantes.');
    }
}

mysqli_set_charset($conexion, 'utf8mb4');

function obtener_base_url() {
    global $base_url;
    return $base_url;
}

function obtener_conexion() {
    global $conexion;
    return $conexion;
}

function escapar($valor) {
    global $conexion;
    return mysqli_real_escape_string($conexion, (string)$valor);
}
