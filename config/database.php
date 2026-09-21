<?php

// Iniciar búfer de salida para proteger redirecciones contra "headers already sent"
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @ob_start();
}

// Detección de entorno (Local vs Producción en InfinityFree)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local  = in_array($http_host, ['localhost', '127.0.0.1', '::1'])
             || (strpos($http_host, 'localhost:') === 0)
             || (php_sapi_name() === 'cli');

if ($is_local) {
    // Entorno Local (XAMPP)
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    $dbhost     = 'localhost';
    $dbusuario  = 'root';
    $dbpassword = '';
    $dbnombre   = 'cursolandia';
    $dbpuerto   = 3306;
} else {
    // Entorno de Producción (InfinityFree)
    // Desactivar display_errors para evitar que notices rompan los headers HTTP
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    $dbhost     = 'sql311.infinityfree.com';
    $dbusuario  = 'if0_43454552';
    $dbpassword = 'Cursolandia123';
    $dbnombre   = 'if0_43454552_Cursolandia';
    $dbpuerto   = 3306;
}

// Desactivar excepciones estrictas de MySQLi para compatibilidad robusta en PHP 8.1+
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$conexion = @mysqli_connect($dbhost, $dbusuario, $dbpassword, $dbnombre, $dbpuerto);

if (!$conexion) {
    if ($is_local) {
        die('Error al conectar con la base de datos MySQL local: ' . mysqli_connect_error());
    } else {
        die('Lo sentimos, en este momento no pudimos conectar con la base de datos. Por favor reintentá en unos instantes.');
    }
}

mysqli_set_charset($conexion, 'utf8mb4');

/**
 * Auto-migración y auto-reparación del esquema de base de datos.
 * Garantiza que si la base de datos remota carece de tablas o columnas nuevas,
 * se creen automáticamente sin provocar errores HTTP 500 en producción.
 */
if (!function_exists('cursolandia_auto_migrar')) {
    function cursolandia_auto_migrar($cn) {
        static $ya_ejecutado = false;
        if ($ya_ejecutado || !$cn) {
            return;
        }
        $ya_ejecutado = true;

        try {
            // 1. Tabla Material
            $chkMat = @mysqli_query($cn, "SHOW TABLES LIKE 'material'");
            if (!$chkMat || mysqli_num_rows($chkMat) === 0) {
                @mysqli_query($cn, "
                    CREATE TABLE IF NOT EXISTS material (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        curso_id INT NOT NULL,
                        nombre_archivo VARCHAR(150) NOT NULL,
                        ruta_archivo VARCHAR(255) NOT NULL,
                        tamano_mb DECIMAL(5,2) NOT NULL,
                        fecha_subida DATETIME NOT NULL,
                        INDEX idx_curso_mat (curso_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }

            // 2. Tabla Pregunta
            $chkPreg = @mysqli_query($cn, "SHOW TABLES LIKE 'pregunta'");
            if (!$chkPreg || mysqli_num_rows($chkPreg) === 0) {
                @mysqli_query($cn, "
                    CREATE TABLE IF NOT EXISTS pregunta (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        curso_id INT NOT NULL,
                        usuario_id INT NOT NULL,
                        titulo VARCHAR(200) NOT NULL,
                        contenido TEXT NOT NULL,
                        destacada INT NOT NULL DEFAULT 0,
                        cerrada INT NOT NULL DEFAULT 0,
                        id_respuesta_aceptada INT NULL,
                        fecha_creacion DATETIME NOT NULL,
                        INDEX idx_curso_preg (curso_id),
                        INDEX idx_usr_preg (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }

            // 3. Tabla Respuesta
            $chkResp = @mysqli_query($cn, "SHOW TABLES LIKE 'respuesta'");
            if (!$chkResp || mysqli_num_rows($chkResp) === 0) {
                @mysqli_query($cn, "
                    CREATE TABLE IF NOT EXISTS respuesta (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        pregunta_id INT NOT NULL,
                        usuario_id INT NOT NULL,
                        contenido TEXT NOT NULL,
                        es_aceptada INT NOT NULL DEFAULT 0,
                        fecha_creacion DATETIME NOT NULL,
                        fecha_edicion DATETIME NULL,
                        INDEX idx_preg_resp (pregunta_id),
                        INDEX idx_usr_resp (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }

            // 4. Tabla Notificacion
            $chkNotif = @mysqli_query($cn, "SHOW TABLES LIKE 'notificacion'");
            if (!$chkNotif || mysqli_num_rows($chkNotif) === 0) {
                @mysqli_query($cn, "
                    CREATE TABLE IF NOT EXISTS notificacion (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        mensaje TEXT NOT NULL,
                        enlace VARCHAR(255) NULL,
                        leida INT NOT NULL DEFAULT 0,
                        fecha DATETIME NOT NULL,
                        INDEX idx_usr_notif (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }

            // 5. Tabla SolicitudPro
            $chkSol = @mysqli_query($cn, "SHOW TABLES LIKE 'solicitudpro'");
            if (!$chkSol || mysqli_num_rows($chkSol) === 0) {
                @mysqli_query($cn, "
                    CREATE TABLE IF NOT EXISTS solicitudpro (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        comprobante VARCHAR(255) NOT NULL,
                        mensaje TEXT NULL,
                        estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
                        fecha_solicitud DATETIME NOT NULL,
                        INDEX idx_usr_sol (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }

            // 6. Columnas en tabla usuario
            $colsUser = [];
            $resCU = @mysqli_query($cn, "SHOW COLUMNS FROM usuario");
            if ($resCU) {
                while ($c = mysqli_fetch_assoc($resCU)) {
                    $colsUser[strtolower($c['Field'])] = true;
                }
            }
            if (!empty($colsUser)) {
                if (!isset($colsUser['username'])) {
                    @mysqli_query($cn, "ALTER TABLE usuario ADD COLUMN username VARCHAR(50) NULL AFTER apellido");
                }
                if (!isset($colsUser['fecha_vencimiento_pro'])) {
                    @mysqli_query($cn, "ALTER TABLE usuario ADD COLUMN fecha_vencimiento_pro DATE NULL AFTER plan");
                }
                if (!isset($colsUser['avatar'])) {
                    @mysqli_query($cn, "ALTER TABLE usuario ADD COLUMN avatar VARCHAR(255) NULL AFTER fecha_vencimiento_pro");
                }
                if (!isset($colsUser['biografia'])) {
                    @mysqli_query($cn, "ALTER TABLE usuario ADD COLUMN biografia TEXT NULL AFTER avatar");
                }
                if (!isset($colsUser['intereses'])) {
                    @mysqli_query($cn, "ALTER TABLE usuario ADD COLUMN intereses VARCHAR(255) NULL AFTER biografia");
                }
            }

            // 7. Columnas en tabla curso
            $colsCurso = [];
            $resCC = @mysqli_query($cn, "SHOW COLUMNS FROM curso");
            if ($resCC) {
                while ($c = mysqli_fetch_assoc($resCC)) {
                    $colsCurso[strtolower($c['Field'])] = true;
                }
            }
            if (!empty($colsCurso)) {
                if (!isset($colsCurso['id_categoria'])) {
                    @mysqli_query($cn, "ALTER TABLE curso ADD COLUMN id_categoria INT NULL AFTER docente_id");
                }
                if (!isset($colsCurso['modulos_contenido'])) {
                    @mysqli_query($cn, "ALTER TABLE curso ADD COLUMN modulos_contenido TEXT NULL AFTER requisitos");
                }
            }

            // 8. Columnas en tabla inscripcion
            $colsIns = [];
            $resCI = @mysqli_query($cn, "SHOW COLUMNS FROM inscripcion");
            if ($resCI) {
                while ($c = mysqli_fetch_assoc($resCI)) {
                    $colsIns[strtolower($c['Field'])] = true;
                }
            }
            if (!empty($colsIns)) {
                if (!isset($colsIns['comprobante'])) {
                    @mysqli_query($cn, "ALTER TABLE inscripcion ADD COLUMN comprobante VARCHAR(255) NULL DEFAULT NULL AFTER fecha_inscripcion");
                }
                if (!isset($colsIns['fecha_comprobante'])) {
                    @mysqli_query($cn, "ALTER TABLE inscripcion ADD COLUMN fecha_comprobante DATETIME NULL DEFAULT NULL AFTER comprobante");
                }
                if (!isset($colsIns['mensaje_pago'])) {
                    @mysqli_query($cn, "ALTER TABLE inscripcion ADD COLUMN mensaje_pago TEXT NULL DEFAULT NULL AFTER fecha_comprobante");
                }
            }

        } catch (Throwable $e) {
            // Ignorar para no bloquear la ejecución de la página
        }
    }
}

// Ejecutar auto-reparación preventiva
cursolandia_auto_migrar($conexion);

/**
 * Detecta y devuelve la ruta base de la aplicación.
 * En local: /PROYECTO-CURSOLANDIA/
 * En InfinityFree / hosting raíz: /
 * 
 * @return string
 */
if (!function_exists('obtener_base_url')) {
    function obtener_base_url(): string {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (stripos($host, 'cursolandia.xo.je') !== false || stripos($host, 'infinityfree') !== false || stripos($host, 'epizy.com') !== false) {
            return '/';
        }

        $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        
        // Si está en una subcarpeta (ej: /PROYECTO-CURSOLANDIA/...)
        if (preg_match('#^(/[^/]+)/(?:admin|archivos|assets|auth|config|controladores|cursos|includes|inscripciones|paginas|panel|perfil)/#i', $script_name, $m)) {
            return $m[1] . '/';
        }
        
        if (preg_match('#^(/[^/]+)/index\.php#i', $script_name, $m)) {
            $internas = ['admin', 'archivos', 'assets', 'auth', 'config', 'controladores', 'cursos', 'includes', 'inscripciones', 'paginas', 'panel', 'perfil'];
            $folder = strtolower(trim($m[1], '/'));
            if (!in_array($folder, $internas)) {
                return $m[1] . '/';
            }
        }

        if (stripos($script_name, '/PROYECTO-CURSOLANDIA') !== false) {
            return '/PROYECTO-CURSOLANDIA/';
        }

        return '/';
    }
}

$base_url = obtener_base_url();

/**
 * Devuelve el enlace de conexión activo a MySQL.
 * Permite acceder a la conexión en funciones y controladores.
 * 
 * @return mysqli
 */
function obtener_conexion() {
    global $conexion;
    return $conexion;
}

/**
 * Escapa caracteres especiales en un string para usarlo en consultas SQL.
 * 
 * @param mixed $valor
 * @return string
 */
function escapar($valor) {
    global $conexion;
    return mysqli_real_escape_string($conexion, (string)$valor);
}
