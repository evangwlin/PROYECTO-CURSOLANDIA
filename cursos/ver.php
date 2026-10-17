<?php
// detalle de curso

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
$es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : false;
$usuario = $_SESSION['usuario'] ?? [];

$conexion = obtener_conexion();

// catalogo
$catalogo_cursos = [
    1 => [
        'id'               => 1,
        'titulo'           => 'Desarrollo Web Fullstack con PHP y MySQL',
        'docente'          => 'Carlos Gómez',
        'docente_titulo'   => 'Ingeniero de Software & Tech Lead Backend',
        'docente_bio'      => 'Docente universitario con más de 8 años de experiencia en arquitectura web, desarrollo con PHP moderno, MySQL y diseño de APIs robustas en plataformas de alta concurrencia.',
        'docente_stats'    => ['rating' => 4.9, 'alumnos' => 1450, 'cursos' => 4, 'respuestas' => '98%'],
        'categoria'        => 'programacion',
        'categoria_nombre' => 'Programación',
        'icono'            => 'bi-code-slash',
        'imagen'           => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Intermedio',
        'modalidad'        => 'Clases en Vivo + Aula Virtual',
        'fecha'            => '15 Oct - 20 Dic 2026',
        'horario'          => 'Miércoles y Viernes · 19:00 a 21:00 hs',
        'duracion'         => '10 semanas (40 horas cátedra)',
        'inscriptos'       => 26,
        'cupo_max'         => 30,
        'precio'           => 12000,
        'tipo_precio'      => 'pago',
        'rating'           => '4.9',
        'resenas_cant'     => 142,
        'tags'             => ['#PHP', '#MySQL', '#Fullstack'],
        'descripcion'      => 'Aprendé a desarrollar aplicaciones web dinámicas y seguras desde cero. Dominá la conexión a bases de datos relacionales con MySQLi, autenticación de usuarios por sesión y subida de archivos.',
        'descripcion_larga'=> 'Este curso te llevará de los conceptos fundamentales de PHP hasta la construcción de una plataforma web completa lista para producción. Aprenderás a modelar datos relacionales normalizados, prevenir ataques SQL Injection con mysqli y sanitización, estructurar tu código de forma modular y crear páginas web interactivas.',
        'objetivos'        => [
            'Construir aplicaciones web completas utilizando PHP 8 moderno y MySQL.',
            'Dominar el patrón de arquitectura Modelo-Vista-Controlador (MVC) sin dependencias pesadas.',
            'Gestionar sesiones de usuario, hashing seguro con password_hash() y control estricto de roles.',
            'Modelar bases de datos relacionales normalizadas hasta Tercera Forma Normal (3FN).',
            'Diseñar e integrar APIs RESTful para interactuar con interfaces frontend responsivas.',
            'Implementar subida y validación segura de archivos en el servidor con manejo de excepciones.'
        ],
        'requisitos'       => [
            'Conocimientos básicos de maquetación HTML y estilos CSS.',
            'Computadora con XAMPP o similar instalado (Apache + MySQL + PHP 8.1 o superior).',
            'Editor de código fuente (recomendado: VS Code o PHPStorm).'
        ],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Fundamentos de PHP 8 y Entorno de Desarrollo',
                'duracion' => '2h 15m',
                'clases'   => [
                    ['nombre' => 'Introducción a la web dinámica y arquitectura cliente-servidor', 'duracion' => '45 min', 'gratis' => true],
                    ['nombre' => 'Sintaxis moderna de PHP 8: tipado estricto, match expressions y funciones', 'duracion' => '50 min', 'gratis' => false],
                    ['nombre' => 'Manejo de peticiones HTTP: GET, POST y sanitización de formularios', 'duracion' => '40 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Bases de Datos MySQL y Conexión con MySQLi',
                'duracion' => '3h 00m',
                'clases'   => [
                    ['nombre' => 'Diseño relacional y normalización hasta 3FN con claves foráneas', 'duracion' => '60 min', 'gratis' => false],
                    ['nombre' => 'Conexión MySQLi, consultas SELECT, INSERT, UPDATE y DELETE', 'duracion' => '60 min', 'gratis' => false],
                    ['nombre' => 'Operaciones CRUD completas y procesamiento con mysqli_fetch_assoc', 'duracion' => '60 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 3',
                'titulo'   => 'Autenticación, Sesiones y Seguridad Avanzada',
                'duracion' => '2h 30m',
                'clases'   => [
                    ['nombre' => 'Manejo de sesiones PHP: session_start(), persistencia y expiración', 'duracion' => '50 min', 'gratis' => false],
                    ['nombre' => 'Hashing de claves con BCRYPT y sistema de roles (Admin, Docente, Alumno)', 'duracion' => '50 min', 'gratis' => false],
                    ['nombre' => 'Protección contra vulnerabilidades críticas: CSRF, XSS y upload seguro', 'duracion' => '50 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 4',
                'titulo'   => 'Arquitectura MVC y Proyecto Final Integrador',
                'duracion' => '3h 15m',
                'clases'   => [
                    ['nombre' => 'Organización modular y separación de capas: Modelos, Vistas y Controladores', 'duracion' => '65 min', 'gratis' => false],
                    ['nombre' => 'Enrutamiento limpio y controladores dinámicos para peticiones asincrónicas', 'duracion' => '60 min', 'gratis' => false],
                    ['nombre' => 'Integración completa del sistema web, deploy local y entrega de cátedra', 'duracion' => '70 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Guia_Completa_PHP8_MySQL_Moderno.pdf',
                'descripcion' => 'Manual teórico y práctico con ejemplos de código de sintaxis moderna y buenas prácticas.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '3.8 MB',
                'fecha'       => '16 Oct 2026'
            ],
            [
                'nombre'      => 'Proyecto_Base_Arquitectura_MVC.zip',
                'descripcion' => 'Estructura inicial del proyecto con autoloading PSR-4, router básico y vistas modulares.',
                'tipo'        => 'Código Fuente / ZIP',
                'clase_ico'   => 'material-icono-zip',
                'icono'       => 'bi-file-earmark-zip-fill',
                'peso'        => '18.4 MB',
                'fecha'       => '24 Oct 2026'
            ],
            [
                'nombre'      => 'CheatSheet_Normalizacion_3FN_SQL.pdf',
                'descripcion' => 'Guía de referencia rápida de reglas de normalización y sentencias preparadas PDO.',
                'tipo'        => 'PDF Guía Rápida',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '1.2 MB',
                'fecha'       => '02 Nov 2026'
            ],
            [
                'nombre'      => 'Script_BD_Cursolandia_MySQL.sql',
                'descripcion' => 'Script de creación de tablas con claves foráneas, índices y datos de prueba completos.',
                'tipo'        => 'Script Base de Datos SQL',
                'clase_ico'   => 'material-icono-txt',
                'icono'       => 'bi-database-fill',
                'peso'        => '45 KB',
                'fecha'       => '10 Nov 2026'
            ]
        ],
        'foro'             => [
            [
                'autor'      => 'Matías Ramos',
                'iniciales'  => 'MR',
                'fecha'      => 'Hace 2 días',
                'titulo'     => '¿Qué versión de PHP recomiendan tener instalada en XAMPP para este módulo?',
                'pregunta'   => 'Hola profesor, tengo XAMPP con PHP 7.4 y al ejecutar match() me tira error de sintaxis en el código. ¿Conviene actualizar directo a PHP 8.2?',
                'respuesta'  => [
                    'docente'   => 'Carlos Gómez (Docente Titular)',
                    'fecha'     => 'Hace 1 día',
                    'texto'     => '¡Hola Matías! Exacto, la estructura match() y las propiedades tipadas fueron introducidas en PHP 8.0. Te recomiendo instalar XAMPP con PHP 8.1 o PHP 8.2 para poder seguir todas las prácticas del curso sin ningún inconveniente.'
                ]
            ],
            [
                'autor'      => 'Sofía Benítez',
                'iniciales'  => 'SB',
                'fecha'      => 'Hace 4 días',
                'titulo'     => 'Duda sobre el almacenamiento de fotos de perfil en la base de datos',
                'pregunta'   => '¿Es conveniente guardar la imagen en un campo BLOB de MySQL o es mejor guardar la ruta del archivo en disco como VARCHAR?',
                'respuesta'  => [
                    'docente'   => 'Carlos Gómez (Docente Titular)',
                    'fecha'     => 'Hace 3 días',
                    'texto'     => '¡Excelente pregunta Sofía! La mejor práctica estándar de la industria es guardar el archivo físico en el servidor (en una carpeta como /uploads/) y almacenar únicamente el nombre o ruta relativa en un campo VARCHAR de MySQL. Eso mantiene la base liviana y optimiza notablemente el rendimiento.'
                ]
            ]
        ]
    ],
    2 => [
        'id'               => 2,
        'titulo'           => 'Diseño Web Moderno, UI y Responsive',
        'docente'          => 'Laura Martínez',
        'docente_titulo'   => 'Diseñadora UX/UI Senior & Frontend Specialist',
        'docente_bio'      => 'Especialista en sistemas de diseño, accesibilidad web y maquetación fluida con CSS Grid y Flexbox.',
        'docente_stats'    => ['rating' => 4.95, 'alumnos' => 2100, 'cursos' => 5, 'respuestas' => '100%'],
        'categoria'        => 'diseno',
        'categoria_nombre' => 'Arte & Diseño',
        'icono'            => 'bi-palette',
        'imagen'           => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Inicial / Intermedio',
        'modalidad'        => 'Taller Práctico Online',
        'fecha'            => '01 Nov - 15 Dic 2026',
        'horario'          => 'Martes y Jueves · 18:30 a 20:30 hs',
        'duracion'         => '6 semanas (24 horas cátedra)',
        'inscriptos'       => 20,
        'cupo_max'         => 25,
        'precio'           => 0,
        'tipo_precio'      => 'gratis',
        'rating'           => '4.9',
        'resenas_cant'     => 89,
        'tags'             => ['#Frontend', '#CSS', '#UI', '#Responsive'],
        'descripcion'      => 'Aprendé a crear sitios web atractivos, modernos y accesibles que se adapten a cualquier dispositivo móvil o pantalla de escritorio.',
        'descripcion_larga'=> 'Diseñar para la web requiere equilibrio entre estética visual y rendimiento técnico. En este taller aprenderás a dominar CSS Grid y Flexbox sin depender de librerías pesadas, aplicar principios de accesibilidad WCAG y organizar sistemas de componentes en Figma para maquetar con máxima fidelidad.',
        'objetivos'        => [
            'Dominar CSS Grid y Flexbox para maquetar interfaces complejas y fluidas.',
            'Diseñar mockups interactivos y sistemas de componentes en Figma.',
            'Aplicar principios de diseño responsive con enfoque Mobile-First.',
            'Garantizar accesibilidad web según estándares internacionales WCAG 2.1.',
            'Optimizar tipografías, variables CSS nativas y modos oscuro/claro.',
            'Construir un portfolio profesional con microinteracciones y transiciones suaves.'
        ],
        'requisitos'       => [
            'Navegador web moderno (Chrome, Firefox o Edge).',
            'Conocimientos muy básicos de HTML y CSS.',
            'Cuenta gratuita de Figma para realizar las prácticas de diseño.'
        ],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Maquetación Moderna con Flexbox y CSS Grid',
                'duracion' => '2h 00m',
                'clases'   => [
                    ['nombre' => 'Caja flexible (Flexbox) a fondo: ejes, alineación y wrapping', 'duracion' => '45 min', 'gratis' => true],
                    ['nombre' => 'CSS Grid: áreas, columnas fraccionarias y layouts fluidos', 'duracion' => '45 min', 'gratis' => false],
                    ['nombre' => 'Media queries estratégicas y enfoque Mobile-First', 'duracion' => '30 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Sistemas de Diseño y Componentes en Figma',
                'duracion' => '2h 15m',
                'clases'   => [
                    ['nombre' => 'Creación de estilos tipográficos, escalas de color y sombras', 'duracion' => '45 min', 'gratis' => false],
                    ['nombre' => 'Componentes interactivos, auto-layout y variantes en Figma', 'duracion' => '50 min', 'gratis' => false],
                    ['nombre' => 'Exportación de assets optimizados en WebP y SVG vectorial', 'duracion' => '40 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Guia_CSS_Grid_Flexbox_CheatSheet.pdf',
                'descripcion' => 'Infografía descargable con todas las propiedades de Flexbox y CSS Grid explicadas visualmente.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '2.1 MB',
                'fecha'       => '01 Nov 2026'
            ],
            [
                'nombre'      => 'Figma_Kit_Componentes_UI.fig',
                'descripcion' => 'Librería de componentes UI reutilizables (botones, tarjetas, inputs, badges).',
                'tipo'        => 'Archivo Figma UI Kit',
                'clase_ico'   => 'material-icono-zip',
                'icono'       => 'bi-palette-fill',
                'peso'        => '8.5 MB',
                'fecha'       => '05 Nov 2026'
            ]
        ],
        'foro'             => [
            [
                'autor'      => 'Lucas Fernández',
                'iniciales'  => 'LF',
                'fecha'      => 'Ayer',
                'titulo'     => '¿Cuándo usar CSS Grid y cuándo Flexbox en una tarjeta?',
                'pregunta'   => 'Hola Laura, me cuesta decidir si maquetar la tarjeta de producto con Grid o Flexbox. ¿Cuál es la regla práctica?',
                'respuesta'  => [
                    'docente'   => 'Laura Martínez (Docente Titular)',
                    'fecha'     => 'Hace 12 horas',
                    'texto'     => '¡Hola Lucas! La regla de oro es: si pensás en una sola dimensión (fila o columna como una barra de botones), usá Flexbox. Si necesitás coordinar filas y columnas simultáneamente (layout bidimensional como la grilla del catálogo), Grid es la mejor opción.'
                ]
            ]
        ]
    ],
    3 => [
        'id'               => 3,
        'titulo'           => 'Ingeniería de Software y Modelado 3FN',
        'docente'          => 'Martín Ruiz',
        'docente_titulo'   => 'Arquitecto de Soluciones & Consultor de Software',
        'docente_bio'      => 'Especialista en metodologías ágiles, análisis de requerimientos, diagramas UML y modelado relacional riguroso.',
        'docente_stats'    => ['rating' => 4.85, 'alumnos' => 840, 'cursos' => 2, 'respuestas' => '96%'],
        'categoria'        => 'programacion',
        'categoria_nombre' => 'Programación',
        'icono'            => 'bi-diagram-3',
        'imagen'           => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Avanzado',
        'modalidad'        => 'Clases en Vivo + Casos de Estudio Reales',
        'fecha'            => '20 Oct - 30 Nov 2026',
        'horario'          => 'Lunes y Miércoles · 20:00 a 22:00 hs',
        'duracion'         => '8 semanas (32 horas cátedra)',
        'inscriptos'       => 10,
        'cupo_max'         => 25,
        'precio'           => 8500,
        'tipo_precio'      => 'pago',
        'rating'           => '4.8',
        'resenas_cant'     => 54,
        'tags'             => ['#IngSoftware', '#Patrones', '#UML', '#3FN'],
        'descripcion'      => 'Profundizá en los principios de ingeniería de software, arquitectura de sistemas limpios, diagramas UML y técnicas de modelado relacional sin redundancia hasta Tercera Forma Normal (3FN).',
        'descripcion_larga'=> 'El diseño antes de la codificación ahorra meses de refactorización. En esta cátedra aprenderás a traducir requerimientos de negocio en diagramas técnicos precisos, estructurar bases de datos robustas mediante 3FN y aplicar patrones de diseño para evitar el acoplamiento excesivo.',
        'objetivos'        => [
            'Diseñar arquitecturas de software escalables y desacopladas.',
            'Modelar entidades, relaciones y restricciones en diagramas DER y UML.',
            'Aplicar el proceso formal de normalización relacional 1FN, 2FN y 3FN.',
            'Evaluar patrones de diseño GoF en proyectos del mundo real.',
            'Documentar especificaciones técnicas para equipos de desarrollo multidisciplinarios.'
        ],
        'requisitos'       => ['Conocimientos previos de programación estructurada o POO'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Requerimientos, Casos de Uso y Modelado UML',
                'duracion' => '2h 00m',
                'clases'   => [
                    ['nombre' => 'Historias de usuario y diagramas de casos de uso', 'duracion' => '50 min', 'gratis' => true],
                    ['nombre' => 'Diagramas de clases y de secuencia interactivos', 'duracion' => '70 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Modelado de Datos Relacional y Normalización 3FN',
                'duracion' => '2h 15m',
                'clases'   => [
                    ['nombre' => 'Dependencias funcionales y anomalías de actualización', 'duracion' => '60 min', 'gratis' => false],
                    ['nombre' => 'Pasos metodológicos hacia 1FN, 2FN y 3FN', 'duracion' => '75 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Manual_Ingenieria_Software_UML.pdf',
                'descripcion' => 'Documento guía con ejemplos de modelado UML y buenas prácticas arquitectónicas.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '4.5 MB',
                'fecha'       => '21 Oct 2026'
            ]
        ],
        'foro'             => []
    ],
    4 => [
        'id'               => 4,
        'titulo'           => 'Bases de Datos Relacionales y SQL Avanzado',
        'docente'          => 'Alejandro Ruiz',
        'docente_titulo'   => 'Database Administrator (DBA) & Data Engineer',
        'docente_bio'      => 'Más de 10 años optimizando motores MySQL, PostgreSQL y diseñando data pipelines para plataformas de alto tráfico.',
        'docente_stats'    => ['rating' => 4.9, 'alumnos' => 1700, 'cursos' => 3, 'respuestas' => '99%'],
        'categoria'        => 'ciencia',
        'categoria_nombre' => 'Ciencia & Datos',
        'icono'            => 'bi-database',
        'imagen'           => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Intermedio / Avanzado',
        'modalidad'        => 'Prácticas con Laboratorios SQL',
        'fecha'            => '05 Nov - 20 Dic 2026',
        'horario'          => 'Jueves · 19:00 a 22:00 hs',
        'duracion'         => '7 semanas (28 horas cátedra)',
        'inscriptos'       => 18,
        'cupo_max'         => 30,
        'precio'           => 9800,
        'tipo_precio'      => 'pago',
        'rating'           => '4.9',
        'resenas_cant'     => 115,
        'tags'             => ['#SQL', '#BasesDeDatos', '#MySQL', '#Optimizacion'],
        'descripcion'      => 'Dominá las consultas complejas en SQL, índices B-Tree, análisis de planes de ejecución (EXPLAIN), procedimientos almacenados, triggers y transacciones ACID.',
        'descripcion_larga'=> 'El cuello de botella de casi cualquier sistema web reside en la base de datos. En este curso especializado aprenderás a diagnosticar consultas lentas, diseñar estrategias de indexación efectivas y garantizar consistencia transaccional bajo alta carga de usuarios.',
        'objetivos'        => [
            'Escribir subconsultas correlacionadas, CTEs y Window Functions avanzadas.',
            'Optimizar índices B-Tree para acelerar búsquedas en millones de registros.',
            'Analizar e interpretar planes de ejecución con EXPLAIN ANALYZE.',
            'Garantizar consistencia con niveles de aislamiento y transacciones ACID.',
            'Crear vistas materializadas, procedimientos almacenados y triggers.'
        ],
        'requisitos'       => ['Conocimientos previos de comandos SQL básicos (SELECT, INSERT, UPDATE, DELETE)'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Consultas Avanzadas, Joins y Subconsultas Complejas',
                'duracion' => '2h 15m',
                'clases'   => [
                    ['nombre' => 'INNER, LEFT, RIGHT y FULL OUTER JOINS en detalle analítico', 'duracion' => '60 min', 'gratis' => true],
                    ['nombre' => 'Common Table Expressions (WITH) y Window Functions (OVER, PARTITION)', 'duracion' => '75 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Estrategias de Indexación, EXPLAIN y Rendimiento',
                'duracion' => '2h 10m',
                'clases'   => [
                    ['nombre' => 'Estructura interna de índices B-Tree y Hash', 'duracion' => '55 min', 'gratis' => false],
                    ['nombre' => 'Interpretación del plan de ejecución EXPLAIN y optimizaciones de IO', 'duracion' => '75 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Guia_Consultas_SQL_Avanzadas_CheatSheet.pdf',
                'descripcion' => 'Ejemplos listos para copiar de CTEs, Window Functions y optimización de índices.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '3.2 MB',
                'fecha'       => '06 Nov 2026'
            ]
        ],
        'foro'             => []
    ],
    5 => [
        'id'               => 5,
        'titulo'           => 'Inteligencia Artificial Aplicada y Prompting',
        'docente'          => 'Federico Silva',
        'docente_titulo'   => 'AI Specialist & Prompt Engineer',
        'docente_bio'      => 'Pionero en integración de Modelos de Lenguaje (LLMs) y automatización de flujos de trabajo corporativos con IA.',
        'docente_stats'    => ['rating' => 4.95, 'alumnos' => 3200, 'cursos' => 4, 'respuestas' => '100%'],
        'categoria'        => 'ia',
        'categoria_nombre' => 'Inteligencia Artificial',
        'icono'            => 'bi-cpu',
        'imagen'           => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Todos los niveles',
        'modalidad'        => 'Talleres Prácticos Online',
        'fecha'            => '10 Nov - 20 Dic 2026',
        'horario'          => 'Sábados · 10:00 a 13:00 hs',
        'duracion'         => '6 semanas (18 horas cátedra)',
        'inscriptos'       => 12,
        'cupo_max'         => 20,
        'precio'           => 0,
        'tipo_precio'      => 'gratis',
        'rating'           => '4.9',
        'resenas_cant'     => 194,
        'tags'             => ['#IA', '#Prompts', '#Productividad', '#ChatGPT'],
        'descripcion'      => 'Descubrí cómo potenciar tu productividad laboral y de estudio utilizando inteligencia artificial generativa, técnicas de prompting avanzado, Few-Shot y cadenas de razonamiento.',
        'descripcion_larga'=> 'Aprenderás la ciencia detrás de los modelos de lenguaje: cómo estructurar directivas profesionales con rol, contexto, restricciones y salida estructurada en JSON. Convertirás a la IA en tu asistente personal para programar, analizar datos y redactar.',
        'objetivos'        => [
            'Escribir prompts estructurados y efectivos que devuelvan resultados precisos y consistentes.',
            'Automatizar tareas repetitivas de redacción, análisis y síntesis de datos.',
            'Aplicar técnicas Few-Shot y Chain-of-Thought para razonamiento complejo.',
            'Conocer las limitaciones, sesgos y buenas prácticas éticas de los modelos de IA generativa.'
        ],
        'requisitos'       => ['Computadora con conexión a Internet. No se requieren conocimientos de programación.'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Fundamentos de Modelos de Lenguaje y Prompting Básico',
                'duracion' => '1h 40m',
                'clases'   => [
                    ['nombre' => 'Cómo piensan y procesan los tokens los LLMs', 'duracion' => '45 min', 'gratis' => true],
                    ['nombre' => 'Estructura de un prompt profesional: Rol, Contexto, Tarea y Formato', 'duracion' => '55 min', 'gratis' => false]
                ]
            ],
            [
                'numero'   => 'Módulo 2',
                'titulo'   => 'Técnicas Avanzadas: Chain-of-Thought y Few-Shot',
                'duracion' => '1h 50m',
                'clases'   => [
                    ['nombre' => 'Ejemplos Few-Shot para formateo de datos y JSON estricto', 'duracion' => '50 min', 'gratis' => false],
                    ['nombre' => 'Razonamiento paso a paso y resolución de problemas complejos', 'duracion' => '60 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Manual_Prompt_Engineering_2026.pdf',
                'descripcion' => 'Compendio con más de 50 plantillas de prompts listos para productividad y desarrollo.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '2.7 MB',
                'fecha'       => '11 Nov 2026'
            ]
        ],
        'foro'             => []
    ],
    6 => [
        'id'               => 6,
        'titulo'           => 'Marketing Digital y Estrategia de Contenidos',
        'docente'          => 'Camila Torres',
        'docente_titulo'   => 'Directora de Crecimiento & Growth Marketer',
        'docente_bio'      => 'Consultora de marcas internacionales, especialista en embudos de conversión, SEO y estrategias de atracción de clientes.',
        'docente_stats'    => ['rating' => 4.8, 'alumnos' => 1100, 'cursos' => 3, 'respuestas' => '95%'],
        'categoria'        => 'marketing',
        'categoria_nombre' => 'Negocios & Marketing',
        'icono'            => 'bi-graph-up-arrow',
        'imagen'           => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Inicial / Intermedio',
        'modalidad'        => 'Online Asincrónico + Webinars Semanales',
        'fecha'            => '12 Nov - 28 Dic 2026',
        'horario'          => 'Miércoles · 18:00 a 20:00 hs',
        'duracion'         => '6 semanas (24 horas cátedra)',
        'inscriptos'       => 22,
        'cupo_max'         => 35,
        'precio'           => 7500,
        'tipo_precio'      => 'pago',
        'rating'           => '4.8',
        'resenas_cant'     => 76,
        'tags'             => ['#Marketing', '#Redes', '#Estrategia', '#SEO'],
        'descripcion'      => 'Creá estrategias de marketing digital que generen clientes reales. Aprendé sobre posicionamiento de marca, métricas clave (CAC, LTV, ROI) y campañas efectivas.',
        'descripcion_larga'=> 'El marketing efectivo se basa en métricas y psicología del consumidor. Aprenderás a construir embudos de ventas, crear contenido que enganche y medir el retorno de tu inversión publicitaria.',
        'objetivos'        => [
            'Diseñar un plan de marketing integral y definir el Buyer Persona.',
            'Comprender la pauta publicitaria en Meta Ads y Google Ads.',
            'Medir resultados a través de métricas y analítica web.'
        ],
        'requisitos'       => ['Ganas de aprender y una idea o negocio para aplicar las prácticas'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Estrategia, Audiencia y Propuesta de Valor',
                'duracion' => '1h 45m',
                'clases'   => [
                    ['nombre' => 'Definición de cliente ideal y propuesta única de valor', 'duracion' => '50 min', 'gratis' => true],
                    ['nombre' => 'Canales de distribución orgánicos y pagos', 'duracion' => '55 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Plantilla_Plan_Marketing_Estrategico.xlsx',
                'descripcion' => 'Planilla automatizada para proyectar ROI, CAC y presupuesto mensual publicitario.',
                'tipo'        => 'Planilla Excel',
                'clase_ico'   => 'material-icono-excel',
                'icono'       => 'bi-file-earmark-excel-fill',
                'peso'        => '1.5 MB',
                'fecha'       => '13 Nov 2026'
            ]
        ],
        'foro'             => []
    ],
    7 => [
        'id'               => 7,
        'titulo'           => 'Inglés Técnico para Desarrolladores de Software',
        'docente'          => 'Ana Valenzuela',
        'docente_titulo'   => 'Profesora de Inglés & Tech Career Coach',
        'docente_bio'      => 'Ayudó a más de 300 programadores de Latinoamérica a prepararse para entrevistas en inglés y conseguir empleos remotos en el exterior.',
        'docente_stats'    => ['rating' => 4.95, 'alumnos' => 950, 'cursos' => 2, 'respuestas' => '100%'],
        'categoria'        => 'idiomas',
        'categoria_nombre' => 'Idiomas',
        'icono'            => 'bi-translate',
        'imagen'           => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Intermedio (B1 / B2)',
        'modalidad'        => 'Conversaciones en Vivo y Roleplay',
        'fecha'            => '01 Nov - 18 Dic 2026',
        'horario'          => 'Lunes y Jueves · 19:30 a 21:00 hs',
        'duracion'         => '7 semanas (21 horas cátedra)',
        'inscriptos'       => 14,
        'cupo_max'         => 20,
        'precio'           => 6000,
        'tipo_precio'      => 'pago',
        'rating'           => '4.9',
        'resenas_cant'     => 68,
        'tags'             => ['#Ingles', '#Tech', '#Entrevistas', '#Remoto'],
        'descripcion'      => 'Superá la barrera del idioma en el mundo tecnológico. Aprendé vocabulario de arquitectura de software, cómo participar activamente en dailies y cómo afrontar entrevistas laborales en inglés.',
        'descripcion_larga'=> 'Trabajar para el exterior no exige hablar como nativo, sino comunicar soluciones técnicas con claridad. Practicaremos simulaciones reales de sprint plannings, code reviews y entrevistas de comportamiento.',
        'objetivos'        => [
            'Ganar fluidez y confianza para comunicar ideas técnicas en inglés.',
            'Preparar un CV, perfil de LinkedIn y pitch personal en inglés.',
            'Simular entrevistas técnicas y behavioral interviews con feedback en vivo.'
        ],
        'requisitos'       => ['Nivel de inglés básico o intermedio (poder leer documentación técnica)'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Dailies, Reuniones Ágiles y Vocabulario Tech',
                'duracion' => '1h 40m',
                'clases'   => [
                    ['nombre' => 'Cómo reportar progreso, bloqueos y estimaciones en la daily', 'duracion' => '50 min', 'gratis' => true],
                    ['nombre' => 'Vocabulario clave para code reviews y pull requests', 'duracion' => '50 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Guia_Vocabulario_Tech_English_Handbook.pdf',
                'descripcion' => 'Glosario con frases modelo para dailies, retrospectivas y negociación salarial.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '2.9 MB',
                'fecha'       => '02 Nov 2026'
            ]
        ],
        'foro'             => []
    ],
    8 => [
        'id'               => 8,
        'titulo'           => 'Comunicación Asertiva y Liderazgo de Equipos',
        'docente'          => 'Mariano Paz',
        'docente_titulo'   => 'Coach Organizacional & Facilitador de Equipos',
        'docente_bio'      => 'Especialista en dinámicas de equipo, resolución de conflictos y liderazgo empático en entornos remotos y presenciales.',
        'docente_stats'    => ['rating' => 4.85, 'alumnos' => 1350, 'cursos' => 3, 'respuestas' => '97%'],
        'categoria'        => 'comunicacion',
        'categoria_nombre' => 'Comunicación',
        'icono'            => 'bi-people',
        'imagen'           => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Inicial',
        'modalidad'        => 'Taller Vivencial Online',
        'fecha'            => '08 Nov - 15 Dic 2026',
        'horario'          => 'Martes · 19:00 a 21:00 hs',
        'duracion'         => '5 semanas (15 horas cátedra)',
        'inscriptos'       => 15,
        'cupo_max'         => 25,
        'precio'           => 0,
        'tipo_precio'      => 'gratis',
        'rating'           => '4.8',
        'resenas_cant'     => 82,
        'tags'             => ['#SoftSkills', '#Liderazgo', '#Comunicacion'],
        'descripcion'      => 'Desarrollá tus habilidades blandas para liderar con empatía, dar feedback constructivo y gestionar conversaciones difíciles sin fricción.',
        'descripcion_larga'=> 'Las habilidades técnicas te consiguen una entrevista; las habilidades humanas te hacen crecer como líder. En este curso aprenderás a escuchar activamente, alinear expectativas de equipo y resolver conflictos.',
        'objetivos'        => [
            'Dominar técnicas de escucha activa y comunicación no violenta.',
            'Aprender a dar y recibir feedback constructivo en el trabajo.',
            'Fortalecer el rol de liderazgo y la motivación grupal.'
        ],
        'requisitos'       => ['Abierto a todo público interesado en desarrollo personal y profesional'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Escucha Activa y Feedback Constructivo',
                'duracion' => '1h 40m',
                'clases'   => [
                    ['nombre' => 'Los 4 pilares de la comunicación asertiva', 'duracion' => '45 min', 'gratis' => true],
                    ['nombre' => 'Modelos de feedback efectivos (SBI: Situación, Comportamiento, Impacto)', 'duracion' => '55 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Guia_Practica_Liderazgo_Asertivo.pdf',
                'descripcion' => 'Guía con ejercicios prácticos para aplicar en reuniones y dinámicas de equipo.',
                'tipo'        => 'PDF Documento',
                'clase_ico'   => 'material-icono-pdf',
                'icono'       => 'bi-file-earmark-pdf-fill',
                'peso'        => '1.8 MB',
                'fecha'       => '09 Nov 2026'
            ]
        ],
        'foro'             => []
    ],
    9 => [
        'id'               => 9,
        'titulo'           => 'Finanzas Personales e Inversión para Creadores',
        'docente'          => 'Lucía Benítez',
        'docente_titulo'   => 'Asesora Financiera Certificada & Economista',
        'docente_bio'      => 'Especialista en finanzas personales, presupuestos familiares e instrumentos de inversión para freelancers y profesionales independientes.',
        'docente_stats'    => ['rating' => 4.9, 'alumnos' => 1600, 'cursos' => 3, 'respuestas' => '98%'],
        'categoria'        => 'finanzas',
        'categoria_nombre' => 'Finanzas',
        'icono'            => 'bi-cash-stack',
        'imagen'           => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?auto=format&fit=crop&w=800&q=80',
        'nivel'            => 'Inicial / Intermedio',
        'modalidad'        => 'Clases Prácticas con Planillas Interactivas',
        'fecha'            => '15 Nov - 30 Dic 2026',
        'horario'          => 'Viernes · 18:30 a 20:30 hs',
        'duracion'         => '6 semanas (18 horas cátedra)',
        'inscriptos'       => 19,
        'cupo_max'         => 30,
        'precio'           => 8200,
        'tipo_precio'      => 'pago',
        'rating'           => '4.9',
        'resenas_cant'     => 104,
        'tags'             => ['#Finanzas', '#Ahorro', '#Inversiones', '#Freelance'],
        'descripcion'      => 'Tomá el control total de tus ingresos y gastos. Aprendé a armar un fondo de emergencia, planificar tu ahorro y diversificar tus inversiones en instrumentos seguros y rentables.',
        'descripcion_larga'=> 'Diseñado especialmente para quienes generan ingresos variables o trabajan de forma independiente. Aprenderás a dominar el flujo de caja, pagar menos comisiones y hacer rendir tu dinero.',
        'objetivos'        => [
            'Armar un presupuesto personal y familiar inteligente.',
            'Calcular y construir un fondo de emergencia sólido.',
            'Conocer las opciones de inversión en bonos, acciones, CEDEARs y fondos comunes.'
        ],
        'requisitos'       => ['No se requieren conocimientos contables previos'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Diagnóstico Financiero y Presupuesto Realista',
                'duracion' => '1h 45m',
                'clases'   => [
                    ['nombre' => 'Identificación de gastos fijos, variables y de ocio', 'duracion' => '50 min', 'gratis' => true],
                    ['nombre' => 'La regla 50/30/20 adaptada a ingresos variables', 'duracion' => '55 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [
            [
                'nombre'      => 'Planilla_Presupuesto_Personal_Automatizada.xlsx',
                'descripcion' => 'Plantilla de control de ingresos, gastos y metas de inversión mensual.',
                'tipo'        => 'Planilla Excel',
                'clase_ico'   => 'material-icono-excel',
                'icono'       => 'bi-file-earmark-excel-fill',
                'peso'        => '2.2 MB',
                'fecha'       => '16 Nov 2026'
            ]
        ],
        'foro'             => []
    ]
];

// curso actual
$curso_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// consulta el curso en la bd
$cid_seguro = intval($curso_id);
$resCurso = mysqli_query($conexion, "
    SELECT c.*, 
           u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.email AS docente_email, 
           u.biografia AS docente_bio, u.avatar AS docente_avatar, u.plan AS docente_plan,
           cat.nombre AS categoria_nombre, cat.icono AS categoria_icono
    FROM Curso c
    LEFT JOIN Usuario u ON c.docente_id = u.id
    LEFT JOIN Categoria cat ON (c.id_categoria = cat.id OR LOWER(c.categoria) = LOWER(cat.nombre) OR LOWER(c.categoria) = LOWER(cat.slug))
    WHERE c.id = $cid_seguro
    LIMIT 1
");
$cursoBD = $resCurso ? mysqli_fetch_assoc($resCurso) : null;

if ($cursoBD) {
    $tagsRaw = $cursoBD['etiquetas'] ?? '';
    $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
    if (empty($tagsArr)) {
        $tagsArr = ['#Cátedra', '#Cursolandia'];
    }

  
    $resMat = mysqli_query($conexion, "SELECT * FROM Material WHERE curso_id = $cid_seguro ORDER BY id ASC");
    $materialesDB = [];
    if ($resMat) {
        while ($mRow = mysqli_fetch_assoc($resMat)) {
            $materialesDB[] = $mRow;
        }
    }
    $materialesFormateados = [];
    foreach ($materialesDB as $m) {
        $ext = strtolower(pathinfo($m['nombre_archivo'] ?? $m['ruta_archivo'], PATHINFO_EXTENSION));
        $clase_ico = 'material-icono-pdf';
        $ico = 'bi-file-earmark-text';
        if (in_array($ext, ['pdf'])) { $clase_ico = 'material-icono-pdf'; $ico = 'bi-file-earmark-pdf-fill'; }
        elseif (in_array($ext, ['zip', 'rar', 'tar'])) { $clase_ico = 'material-icono-zip'; $ico = 'bi-file-earmark-zip-fill'; }
        elseif (in_array($ext, ['sql'])) { $clase_ico = 'material-icono-txt'; $ico = 'bi-database-fill'; }
        elseif (in_array($ext, ['xls', 'xlsx'])) { $clase_ico = 'material-icono-excel'; $ico = 'bi-file-earmark-excel-fill'; }

        $pesoMB = ($m['tamano_mb'] ?? 1.5) . ' MB';

        $materialesFormateados[] = [
            'id'          => $m['id'],
            'nombre'      => $m['nombre_archivo'],
            'descripcion' => 'Material oficial de la cátedra.',
            'tipo'        => strtoupper($ext) . ' Documento',
            'clase_ico'   => $clase_ico,
            'icono'       => $ico,
            'peso'        => $pesoMB,
            'fecha'       => date('d M Y', strtotime($m['fecha_subida'] ?? 'now')),
            'ruta'        => $m['ruta_archivo']
        ];
    }

    $docenteNombreCompleto = trim(($cursoBD['docente_nombre'] ?? '') . ' ' . ($cursoBD['docente_apellido'] ?? ''));
    if (empty($docenteNombreCompleto)) $docenteNombreCompleto = 'Docente Cursolandia';


    $objetivosCurso = [
        'Dominar los conceptos teóricos y prácticos del temario de la cátedra.',
        'Desarrollar proyectos integradores con estándares profesionales y buenas prácticas.',
        'Participar en los foros de debate y consultas resolviendo problemas reales.'
    ];
    if (!empty($cursoBD['objetivos'])) {
        $parsedObj = array_filter(array_map('trim', explode("\n", $cursoBD['objetivos'])));
        if (!empty($parsedObj)) {
            $objetivosCurso = array_values($parsedObj);
        }
    }

    // modulos del curso
    $modulosCurso = [
        [
            'numero'   => 'Módulo 1',
            'titulo'   => 'Introducción y Fundamentos de la Materia',
            'duracion' => '2h 00m',
            'clases'   => [
                ['nombre' => 'Presentación de la cátedra y objetivos de formación', 'duracion' => '45 min', 'gratis' => true],
                ['nombre' => 'Instalación de herramientas y configuración del entorno de trabajo', 'duracion' => '75 min', 'gratis' => false]
            ]
        ],
        [
            'numero'   => 'Módulo 2',
            'titulo'   => 'Práctica Aplicada y Proyecto de Cátedra',
            'duracion' => '3h 30m',
            'clases'   => [
                ['nombre' => 'Desarrollo guiado del trabajo práctico integrador', 'duracion' => '90 min', 'gratis' => false],
                ['nombre' => 'Evaluación de casos de estudio y conclusiones finales', 'duracion' => '120 min', 'gratis' => false]
            ]
        ]
    ];
    if (!empty($cursoBD['modulos_contenido'])) {
        $parsedMods = json_decode($cursoBD['modulos_contenido'], true);
        if (is_array($parsedMods) && !empty($parsedMods)) {
            $modulosCurso = $parsedMods;
        }
    }

    $requisitosCurso = [
        'Conocimientos básicos del área.',
        'Computadora con conexión a Internet y software de desarrollo.'
    ];
    if (!empty($cursoBD['requisitos'])) {
        $parsedReq = array_filter(array_map('trim', explode("\n", $cursoBD['requisitos'])));
        if (!empty($parsedReq)) {
            $requisitosCurso = array_values($parsedReq);
        }
    }

    $docenteId = (int)$cursoBD['docente_id'];

    // Estadísticas reales del docente
    // 1. Cursos creados por el docente
    $resTotalCursos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Curso WHERE docente_id = $docenteId AND estado != 'eliminado'");
    $filaCursos = $resTotalCursos ? mysqli_fetch_assoc($resTotalCursos) : null;
    $totalCursosDocente = $filaCursos ? intval($filaCursos['total']) : 0;

    // 2. Alumnos únicos inscriptos en los cursos del docente
    $resTotalAlumnos = mysqli_query($conexion, "
        SELECT COUNT(DISTINCT i.usuario_id) AS total 
        FROM Inscripcion i 
        JOIN Curso c ON i.curso_id = c.id 
        WHERE c.docente_id = $docenteId 
          AND i.usuario_id != $docenteId 
          AND i.estado IN ('activa', 'pendiente')
    ");
    $filaAlumnos = $resTotalAlumnos ? mysqli_fetch_assoc($resTotalAlumnos) : null;
    $totalAlumnosDocente = $filaAlumnos ? intval($filaAlumnos['total']) : 0;

    // 3. Calificación del instructor (0 si no posee calificaciones en el sistema)
    $docenteRating = 0;

    // 4. Tasa de respuesta del docente a consultas de foros en sus cursos
    $resPregDoc = mysqli_query($conexion, "
        SELECT COUNT(*) AS total 
        FROM Pregunta p 
        JOIN Curso c ON p.curso_id = c.id 
        WHERE c.docente_id = $docenteId AND p.usuario_id != $docenteId
    ");
    $filaPregDoc = $resPregDoc ? mysqli_fetch_assoc($resPregDoc) : null;
    $totalPreguntasDocente = $filaPregDoc ? intval($filaPregDoc['total']) : 0;

    $preguntasRespondidasDocente = 0;
    if ($totalPreguntasDocente > 0) {
        $resRespDoc = mysqli_query($conexion, "
            SELECT COUNT(DISTINCT p.id) AS respondidas 
            FROM Pregunta p 
            JOIN Curso c ON p.curso_id = c.id 
            JOIN Respuesta r ON r.pregunta_id = p.id 
            WHERE c.docente_id = $docenteId 
              AND p.usuario_id != $docenteId 
              AND r.usuario_id = $docenteId
        ");
        $filaRespDoc = $resRespDoc ? mysqli_fetch_assoc($resRespDoc) : null;
        $preguntasRespondidasDocente = $filaRespDoc ? intval($filaRespDoc['respondidas']) : 0;
    }

    $tasaRespuestaDocente = ($totalPreguntasDocente > 0) 
        ? round(($preguntasRespondidasDocente / $totalPreguntasDocente) * 100) . '%' 
        : '0%';

    $curso = [
        'id'               => (int)$cursoBD['id'],
        'titulo'           => $cursoBD['titulo'],
        'docente_id'       => (int)$cursoBD['docente_id'],
        'docente'          => $docenteNombreCompleto,
        'docente_avatar'   => !empty($cursoBD['docente_avatar']) ? $cursoBD['docente_avatar'] : null,
        'docente_titulo'   => ($cursoBD['docente_plan'] === 'pro') ? 'Docente Titular · Plan PRO' : 'Docente Titular',
        'docente_bio'      => !empty($cursoBD['docente_bio']) ? $cursoBD['docente_bio'] : 'Docente titular en Cursolandia enfocado en la enseñanza práctica de competencias profesionales de vanguardia.',
        'docente_stats'    => [
            'rating'     => $docenteRating,
            'alumnos'    => $totalAlumnosDocente,
            'cursos'     => $totalCursosDocente,
            'respuestas' => $tasaRespuestaDocente
        ],
        'categoria'        => !empty($cursoBD['categoria']) ? $cursoBD['categoria'] : ($cursoBD['categoria_nombre'] ?? 'general'),
        'categoria_nombre' => !empty($cursoBD['categoria']) ? $cursoBD['categoria'] : ($cursoBD['categoria_nombre'] ?? 'General'),
        'icono'            => !empty($cursoBD['icono']) ? $cursoBD['icono'] : (!empty($cursoBD['categoria_icono']) ? $cursoBD['categoria_icono'] : 'bi-journal-code'),
        'imagen'           => obtener_portada_curso($cursoBD['imagen'] ?? '', $cursoBD['categoria'] ?? '', (int)$cursoBD['id']),
        'nivel'            => 'Todos los niveles',
        'modalidad'        => ($cursoBD['tipo_acceso'] === 'privado') ? 'Acceso Privado' : 'Abierto a la Comunidad',
        'fecha'            => date('d/m/Y', strtotime($cursoBD['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($cursoBD['fecha_fin'])),
        'fecha_inicio'     => $cursoBD['fecha_inicio'],
        'fecha_fin'        => $cursoBD['fecha_fin'],
        'horario'          => 'Cursada Online Asincrónica',
        'duracion'         => '8 semanas (32 horas cátedra)',
        'inscriptos'       => 0,
        'cupo_max'         => (int)$cursoBD['cupo_max'],
        'precio'           => (float)$cursoBD['precio'],
        'tipo_precio'      => ((float)$cursoBD['precio'] > 0) ? 'pago' : 'gratis',
        'rating'           => 0,
        'resenas_cant'     => 0,
        'tags'             => $tagsArr,
        'descripcion'      => $cursoBD['descripcion'],
        'descripcion_larga'=> $cursoBD['descripcion'],
        'objetivos'        => $objetivosCurso,
        'requisitos'       => $requisitosCurso,
        'modulos'          => $modulosCurso,
        'materiales'       => !empty($materialesFormateados) ? $materialesFormateados : (
            isset($catalogo_cursos[$curso_id]['materiales']) ? $catalogo_cursos[$curso_id]['materiales'] : []
        )
    ];
} elseif (isset($catalogo_cursos[$curso_id])) {
    $curso = $catalogo_cursos[$curso_id];
    $curso['docente_id'] = 1;
} else {
    // fallback
    $curso = [
        'id'               => $curso_id,
        'titulo'           => 'Curso Personalizado #' . $curso_id,
        'docente_id'       => 1,
        'docente'          => $usuario['nombre'] ?? 'Docente Cursolandia',
        'docente_titulo'   => 'Instructor Certificado',
        'docente_bio'      => 'Docente titular en Cursolandia enfocado en la enseñanza práctica de competencias profesionales de vanguardia.',
        'docente_stats'    => ['rating' => 0, 'alumnos' => 0, 'cursos' => 1, 'respuestas' => '0%'],
        'categoria'        => 'general',
        'categoria_nombre' => 'Cursos Generales',
        'icono'            => 'bi-journal-bookmark',
        'imagen'           => obtener_portada_curso('', 'general', (int)$curso_id),
        'nivel'            => 'Todos los niveles',
        'modalidad'        => 'Aula Virtual y Prácticas',
        'fecha'            => 'Cursada Vigente 2026',
        'fecha_inicio'     => date('Y-m-d'),
        'fecha_fin'        => date('Y-m-d', strtotime('+60 days')),
        'horario'          => 'Horarios flexibles online',
        'duracion'         => '6 semanas (24 horas)',
        'inscriptos'       => 0,
        'cupo_max'         => 30,
        'precio'           => 0,
        'tipo_precio'      => 'gratis',
        'rating'           => 0,
        'resenas_cant'     => 0,
        'tags'             => ['#Aprendizaje', '#Educacion'],
        'descripcion'      => 'Propuesta de formación orientada a proyectos prácticos con acompañamiento continuo del docente.',
        'descripcion_larga'=> 'Formación completa con enfoque pedagógico moderno y proyectos de aplicación directa.',
        'objetivos'        => ['Desarrollar competencias prácticas orientadas al mercado actual.'],
        'requisitos'       => ['Acceso a computadora y conexión a Internet.'],
        'modulos'          => [
            [
                'numero'   => 'Módulo 1',
                'titulo'   => 'Introducción y Conceptos Fundamentales',
                'duracion' => '1h 30m',
                'clases'   => [
                    ['nombre' => 'Bienvenida a la cursada y presentación del programa', 'duracion' => '40 min', 'gratis' => true],
                    ['nombre' => 'Guía de inicio rápido y configuración del entorno', 'duracion' => '50 min', 'gratis' => false]
                ]
            ]
        ],
        'materiales'       => [],
        'foro'             => []
    ];
}

// inscriptos reales desde la BD
$cidReal = intval($curso['id']);
$resCountIns = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Inscripcion WHERE curso_id = $cidReal AND estado IN ('activa', 'pendiente')");
$countRow = $resCountIns ? mysqli_fetch_assoc($resCountIns) : null;
$curso['inscriptos'] = $countRow ? intval($countRow['total']) : 0;

// estado de inscripcion
$esta_inscripto = false;
$estado_inscripcion = null;
if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    $resCheck = mysqli_query($conexion, "SELECT id, estado FROM Inscripcion WHERE usuario_id = $uid_seguro AND curso_id = $cidReal AND estado != 'cancelada' LIMIT 1");
    $insRow = $resCheck ? mysqli_fetch_assoc($resCheck) : null;
    if ($insRow) {
        $esta_inscripto = true;
        $estado_inscripcion = $insRow['estado'];
    }
}

$rol_actual = $_SESSION['usuario']['rol'] ?? $_SESSION['usuario_rol'] ?? '';
$es_admin = ($rol_actual === 'admin');
$es_creador = ($usuario_id > 0 && isset($curso['docente_id']) && $usuario_id === (int)$curso['docente_id']);

$estado_curso = $curso['estado'] ?? 'publicado';
if ($estado_curso !== 'publicado' && !$es_admin && !$es_creador) {
    $_SESSION['error'] = 'Este curso se encuentra en proceso de revisión por el administrador y aún no está publicado.';
    header('Location: ' . $base_url . 'cursos/index.php');
    exit;
}

// Procesar aprobación/rechazo directo si quien actúa es el administrador
if ($es_admin && isset($_GET['accion_admin'])) {
    $accion_admin = trim($_GET['accion_admin']);
    $cid_admin = intval($curso['id']);
    if ($accion_admin === 'aprobar' && $cid_admin > 0) {
        mysqli_query($conexion, "UPDATE Curso SET estado = 'publicado' WHERE id = $cid_admin");
        if (!empty($curso['docente_id'])) {
            enviar_notificacion(
                intval($curso['docente_id']),
                '¡Excelente noticia! El administrador aprobó tu curso "' . $curso['titulo'] . '" y ya se encuentra publicado en el catálogo.',
                'cursos/ver.php?id=' . $cid_admin
            );
        }
        $_SESSION['exito'] = 'El curso "' . htmlspecialchars($curso['titulo']) . '" fue aprobado exitosamente y ya se encuentra publicado.';
        header('Location: ver.php?id=' . $cid_admin);
        exit;
    } elseif ($accion_admin === 'rechazar' && $cid_admin > 0) {
        mysqli_query($conexion, "UPDATE Curso SET estado = 'rechazado' WHERE id = $cid_admin");
        if (!empty($curso['docente_id'])) {
            enviar_notificacion(
                intval($curso['docente_id']),
                'Tu curso "' . $curso['titulo'] . '" fue rechazado. Podés editarlo para volver a enviarlo a revisión.',
                'cursos/editar.php?id=' . $cid_admin
            );
        }
        $_SESSION['exito'] = 'La propuesta del curso fue rechazada.';
        header('Location: ver.php?id=' . $cid_admin);
        exit;
    }
}

if (!isset($menu_activo)) {
    if ($es_creador) {
        $menu_activo = 'cursos';
    } elseif ($esta_inscripto) {
        $menu_activo = 'inscripciones';
    } else {
        $menu_activo = (($_SESSION['usuario']['rol'] ?? '') === 'docente') ? 'cursos' : 'cursos';
    }
}

$total_modulos = count($curso['modulos']);
$total_clases = 0;
foreach ($curso['modulos'] as $m) {
    $total_clases += count($m['clases'] ?? []);
}

// tab activa (en la ficha pública solo se muestra temario e info del docente)
$tab_activa = isset($_GET['tab']) ? trim($_GET['tab']) : 'info';
if (!in_array($tab_activa, ['info', 'docente'])) {
    $tab_activa = 'info';
}

$cupos_restantes = max(0, $curso['cupo_max'] - $curso['inscriptos']);
$porcentaje_cupo = ($curso['cupo_max'] > 0) ? min(100, round(($curso['inscriptos'] / $curso['cupo_max']) * 100)) : 0;

$usuario_logueado = ($usuario_id > 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor <?php echo !$usuario_logueado ? 'sin-sidebar' : ''; ?>">

    <?php if ($usuario_logueado): ?>
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="dashboard-contenido">

        <div class="mini-banner-migas" style="margin-bottom: 4px;">
            <a href="<?php echo $base_url; ?><?php echo $usuario_logueado ? 'panel/index.php' : 'index.php'; ?>"><i class="bi bi-house-door"></i> Inicio</a>
            <span class="miga-separador">/</span>
            <a href="<?php echo $base_url; ?>cursos/index.php">Catálogo de Cursos</a>
            <span class="miga-separador">/</span>
            <a href="<?php echo $base_url; ?>cursos/index.php?categoria=<?php echo urlencode($curso['categoria']); ?>"><?php echo htmlspecialchars($curso['categoria_nombre']); ?></a>
            <span class="miga-separador">/</span>
            <span class="miga-actual"><?php echo htmlspecialchars($curso['titulo']); ?></span>
        </div>

        <?php if (!empty($_SESSION['exito'])): ?>
            <div class="alerta-flash exito" style="margin-bottom: 16px;">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alerta-flash error" style="margin-bottom: 16px; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem; color: #ef4444;"></i>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if (($curso['estado'] ?? 'publicado') === 'pendiente'): ?>
            <div class="alerta-flash" style="margin-bottom: 20px; background: #fffbeb; border: 1px solid #fde68a; border-left: 5px solid #f59e0b; color: #92400e; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="bi bi-hourglass-split" style="font-size: 1.6rem; color: #d97706; flex-shrink: 0;"></i>
                    <div>
                        <strong style="font-size: 0.95rem; display: block; color: #78350f;">Curso en Proceso de Revisión</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.85rem; color: #92400e;">
                            <?php if ($es_admin): ?>
                                Este curso fue presentado por el docente y está pendiente de tu aprobación para poder ser publicado en el catálogo de Cursolandia.
                            <?php else: ?>
                                Tu propuesta fue enviada exitosamente. El administrador la revisará a la brevedad para habilitar su publicación en el catálogo.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($es_admin): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=aprobar" class="btn-primario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none;" onclick="return confirm('¿Aprobar y publicar este curso en el catálogo ahora?');">
                            <i class="bi bi-check2-circle"></i> Aprobar Curso
                        </a>
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=rechazar" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; color: #dc2626; border-color: rgba(220,38,38,0.3); text-decoration: none;" onclick="return confirm('¿Rechazar esta propuesta de curso?');">
                            <i class="bi bi-x-circle"></i> Rechazar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (($curso['estado'] ?? 'publicado') === 'rechazado'): ?>
            <div class="alerta-flash error" style="margin-bottom: 20px; background: #fee2e2; border: 1px solid #fecaca; border-left: 5px solid #ef4444; color: #991b1b; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="bi bi-x-octagon-fill" style="font-size: 1.6rem; color: #dc2626; flex-shrink: 0;"></i>
                    <div>
                        <strong style="font-size: 0.95rem; display: block; color: #7f1d1d;">Propuesta de curso rechazada</strong>
                        <p style="margin: 2px 0 0 0; font-size: 0.85rem; color: #991b1b;">
                            El administrador rechazó la propuesta de publicación. Podés editar el temario o los requisitos y volver a solicitar su revisión.
                        </p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ($es_creador): ?>
                        <a href="<?php echo $base_url; ?>cursos/editar.php?id=<?php echo $curso['id']; ?>" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none;">
                            <i class="bi bi-pencil"></i> Modificar y Reenviar
                        </a>
                    <?php endif; ?>
                    <?php if ($es_admin): ?>
                        <a href="ver.php?id=<?php echo $curso['id']; ?>&accion_admin=aprobar" class="btn-primario" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none;" onclick="return confirm('¿Reconsiderar y publicar este curso en el catálogo?');">
                            <i class="bi bi-check2-circle"></i> Aprobar Curso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="curso-ver-layout">

            <div class="curso-col-principal">

                <!-- hero -->
                <article class="curso-hero-card">
                    <div class="curso-hero-pildoras-fila">
                        <span class="badge-tag-cat">
                            <i class="bi <?php echo htmlspecialchars($curso['icono']); ?>"></i>
                            <?php echo htmlspecialchars($curso['categoria_nombre']); ?>
                        </span>
                        <span class="badge-tag-nivel">
                            <i class="bi bi-reception-3"></i> Nivel <?php echo htmlspecialchars($curso['nivel']); ?>
                        </span>
                        <span class="badge-tag-modalidad">
                            <i class="bi bi-broadcast"></i> <?php echo htmlspecialchars($curso['modalidad']); ?>
                        </span>
                        <?php if (!empty($curso['resenas_cant']) && (int)$curso['resenas_cant'] > 0): ?>
                            <span class="badge-tag-rating">
                                <i class="bi bi-star-fill"></i> <?php echo htmlspecialchars($curso['rating']); ?>
                                <span class="rating-opiniones">(<?php echo htmlspecialchars($curso['resenas_cant']); ?> opiniones)</span>
                            </span>
                        <?php else: ?>
                            <span class="badge-tag-rating">
                                <i class="bi bi-star"></i> 0 <span class="rating-opiniones">(0 opiniones)</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="curso-hero-titulo"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                    <p class="curso-hero-desc"><?php echo htmlspecialchars($curso['descripcion']); ?></p>

                    <div class="curso-hero-meta-horizontal">
                        <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="curso-docente-chip" style="text-decoration: none; color: inherit; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            <div class="docente-avatar-chip" style="overflow: hidden; border-radius: 50%;">
                                <?php if (!empty($curso['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($curso['docente_avatar'])); ?>" alt="Docente" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($curso['docente'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="docente-chip-info">
                                <span>Creador del curso</span>
                                <strong style="color: var(--color-text-main);"><?php echo htmlspecialchars($curso['docente']); ?></strong>
                            </div>
                        </a>

                        <div class="meta-item-pildora">
                            <i class="bi bi-calendar3"></i>
                            <span><strong>Período:</strong> <?php echo htmlspecialchars($curso['fecha']); ?></span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-clock-history"></i>
                            <span><strong>Duración:</strong> <?php echo htmlspecialchars($curso['duracion']); ?></span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-globe"></i>
                            <span>Español</span>
                        </div>

                        <div class="meta-item-pildora">
                            <i class="bi bi-patch-check-fill" style="color: var(--color-primary);"></i>
                            <span>Certificado incluido</span>
                        </div>
                    </div>
                </article>

                <section class="caja-que-aprenderas">
                    <h3><i class="bi bi-bullseye"></i> Lo que vas a aprender en este curso</h3>
                    <div class="grid-objetivos-moderno">
                        <?php foreach ($curso['objetivos'] as $obj): ?>
                            <div class="item-objetivo-fila">
                                <span class="check-icono-circulo"><i class="bi bi-check2"></i></span>
                                <span><?php echo htmlspecialchars($obj); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- tabs -->
                <div class="curso-tabs-contenedor">
                    <nav class="curso-tabs-nav" aria-label="Navegación de secciones del curso">
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'info') ? 'activa' : ''; ?>" onclick="cambiarPestana('info', this)">
                            <i class="bi bi-journals"></i> Programa & Temario
                        </button>
                        <button type="button" class="curso-tab-btn <?php echo ($tab_activa === 'docente') ? 'activa' : ''; ?>" onclick="cambiarPestana('docente', this)">
                            <i class="bi bi-person-badge"></i> Creador del Curso
                        </button>
                    </nav>

                    <div id="tab-panel-info" class="curso-tab-panel <?php echo ($tab_activa === 'info') ? 'activo' : ''; ?>">
                        
                        <div class="temario-resumen-barra">
                            <span class="temario-resumen-texto">
                                <strong><?php echo $total_modulos; ?> módulos</strong> · 
                                <strong><?php echo $total_clases; ?> clases en total</strong> · 
                                <?php echo htmlspecialchars($curso['duracion']); ?>
                            </span>
                            <button type="button" id="btn-toggle-todos" class="btn-toggle-todos" onclick="toggleTodosModulos()">
                                Expandir todos los módulos
                            </button>
                        </div>

                        <div class="temario-acordeon">
                            <?php foreach ($curso['modulos'] as $idx => $mod): ?>
                                <article class="modulo-item">
                                    <button type="button" class="modulo-cabecera" onclick="toggleModulo(this)">
                                        <div class="modulo-titulo-lado">
                                            <i class="bi bi-chevron-down flecha-modulo" style="<?php echo ($idx === 0) ? 'transform: rotate(180deg);' : ''; ?>"></i>
                                            <span class="modulo-badge"><?php echo htmlspecialchars($mod['numero']); ?></span>
                                            <strong class="modulo-nombre"><?php echo htmlspecialchars($mod['titulo']); ?></strong>
                                        </div>
                                        <div class="modulo-meta-lado">
                                            <span><?php echo count($mod['clases']); ?> clases</span>
                                            <span style="color: #cbd5e1;">•</span>
                                            <span><?php echo htmlspecialchars($mod['duracion'] ?? '2h'); ?></span>
                                        </div>
                                    </button>
                                    <div class="modulo-cuerpo" style="<?php echo ($idx === 0) ? 'display: block;' : 'display: none;'; ?>">
                                        <ul class="modulo-lista-clases">
                                            <?php foreach ($mod['clases'] as $clase): ?>
                                                <li class="clase-fila">
                                                    <div class="clase-info-izq">
                                                        <i class="bi bi-play-circle-fill clase-icono"></i>
                                                        <span class="clase-nombre"><?php echo htmlspecialchars($clase['nombre']); ?></span>
                                                        <?php if (!empty($clase['gratis'])): ?>
                                                            <span class="badge-clase-gratis"><i class="bi bi-unlock"></i> Clase Abierta</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="clase-duracion">
                                                        <i class="bi bi-clock"></i> <?php echo htmlspecialchars($clase['duracion']); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="requisitos-caja">
                            <h4><i class="bi bi-list-check" style="color: var(--color-primary);"></i> Requisitos previos recomendados</h4>
                            <ul class="requisitos-lista">
                                <?php foreach ($curso['requisitos'] as $req): ?>
                                    <li><i class="bi bi-check-circle-fill"></i> <span><?php echo htmlspecialchars($req); ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <?php if (!empty($curso['descripcion_larga'])): ?>
                            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                                <h4 style="font-size: 1rem; font-weight: 800; color: var(--color-text-main); margin-bottom: 8px;">
                                    <i class="bi bi-card-text" style="color: var(--color-primary);"></i> Metodología y Proyecto Final
                                </h4>
                                <p style="font-size: 0.9rem; color: var(--color-text-body); line-height: 1.6; margin: 0;">
                                    <?php echo htmlspecialchars($curso['descripcion_larga']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- aviso para solo inscriptos -->
                        <div style="margin-top: 24px; background: var(--color-secondary-soft); border: 1px solid rgba(242, 165, 148, 0.4); border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--color-primary-light); display: flex; align-items: center; justify-content: center; color: var(--color-primary); font-size: 1.4rem; flex-shrink: 0;">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 0.95rem; color: var(--color-text-main); display: block;">Materiales Descargables y Foro de Consultas</strong>
                                    <span style="font-size: 0.85rem; color: var(--color-text-body);">El contenido completo, guías de estudio, descargas y el foro de debate con el profesor están disponibles en el <strong>Aula Virtual</strong> una vez confirmada tu inscripción.</span>
                                </div>
                            </div>
                            <?php if ($esta_inscripto && $estado_inscripcion === 'activa'): ?>
                                <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-primario" style="font-size: 0.85rem; padding: 8px 16px; white-space: nowrap;">
                                    <i class="bi bi-play-circle-fill"></i> Ir al Aula Virtual →
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div id="tab-panel-docente" class="curso-tab-panel <?php echo ($tab_activa === 'docente') ? 'activo' : ''; ?>">
                        <article class="docente-tarjeta-perfil">
                            <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="docente-perfil-avatar" style="overflow: hidden; border-radius: 50%; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($curso['docente_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars(obtener_url_avatar($curso['docente_avatar'])); ?>" alt="Docente" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($curso['docente'], 0, 2)); ?>
                                <?php endif; ?>
                            </a>
                            <div class="docente-perfil-cuerpo">
                                <h4>
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($curso['docente']); ?>
                                    </a>
                                </h4>
                                <span class="docente-perfil-sub"><?php echo htmlspecialchars($curso['docente_titulo']); ?></span>
                                
                                <div class="docente-perfil-stats">
                                    <span><i class="bi bi-star-fill" style="color: var(--color-secondary);"></i> <strong><?php echo isset($curso['docente_stats']['rating']) ? $curso['docente_stats']['rating'] : 0; ?></strong> Calificación del instructor</span>
                                    <span><i class="bi bi-people-fill" style="color: var(--color-primary);"></i> <strong><?php echo number_format($curso['docente_stats']['alumnos'] ?? 0, 0, ',', '.'); ?></strong> Alumnos</span>
                                    <span><i class="bi bi-collection-play-fill" style="color: var(--color-text-main);"></i> <strong><?php echo (int)($curso['docente_stats']['cursos'] ?? 0); ?></strong> Cursos</span>
                                    <span><i class="bi bi-chat-left-dots-fill" style="color: var(--color-primary);"></i> <strong><?php echo $curso['docente_stats']['respuestas'] ?? '0%'; ?></strong> Tasa de respuesta</span>
                                </div>

                                <p class="docente-perfil-bio"><?php echo htmlspecialchars($curso['docente_bio']); ?></p>

                                <div style="margin-top: 14px;">
                                    <a href="<?php echo $base_url; ?>perfil/index.php?id=<?php echo (int)$curso['docente_id']; ?>" class="btn-secundario" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; padding: 7px 14px;">
                                        <i class="bi bi-person-badge"></i> Ver Perfil Completo del Instructor
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                </div>

            </div>

            <aside class="curso-col-sidebar">
                
                <!-- sidebar -->
                <div class="card-sidebar-matricula">
                    <div class="curso-sidebar-portada">
                        <?php $imgVer = obtener_portada_curso($curso['imagen'] ?? '', $curso['categoria'] ?? '', (int)$curso['id']); ?>
                        <img src="<?php echo htmlspecialchars($imgVer); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="curso-sidebar-img" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imgVer); ?>';">
                        <div class="play-overlay-btn" title="Ver presentación del curso">
                            <i class="bi bi-play-fill"></i>
                        </div>
                        <div class="trailer-pill-inferior">
                            <i class="bi bi-play-circle-fill" style="color: var(--color-primary);"></i> Vista previa de la clase
                        </div>
                    </div>

                    <div class="sidebar-precio-caja">
                        <?php if ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0): ?>
                            <span class="precio-grande es-gratis">Gratis</span>
                            <span class="precio-sub-aclaracion">Acceso libre inmediato para toda la comunidad</span>
                        <?php else: ?>
                            <div style="display: flex; align-items: baseline; gap: 10px;">
                                <span class="precio-grande">$ <?php echo number_format($curso['precio'], 0, ',', '.'); ?></span>
                            </div>
                            <span class="precio-sub-aclaracion">Pago único · Acceso completo a todas las clases y materiales</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($es_creador): ?>
                        <div class="sidebar-docente-box">
                            <span class="sidebar-docente-badge">
                                <i class="bi bi-person-workspace"></i> Sos el creador de este curso
                            </span>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-docente-aula">
                                <i class="bi bi-mortarboard-fill"></i> Ir al Aula Virtual
                            </a>
                            <div class="sidebar-docente-acciones">
                                <a href="<?php echo $base_url; ?>cursos/alumnos.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora">
                                    <i class="bi bi-people"></i> Alumnos
                                </a>
                                <a href="<?php echo $base_url; ?>cursos/editar.php?id=<?php echo $curso['id']; ?>" class="btn-tabla-pildora">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </div>
                        </div>
                    <?php elseif ($esta_inscripto): ?>
                        <?php if ($estado_inscripcion === 'pendiente'): ?>
                            <div class="sidebar-pendiente-box">
                                <span class="sidebar-pendiente-badge">
                                    <i class="bi bi-hourglass-split"></i> Inscripción Pendiente de Confirmación
                                </span>
                                <span class="sidebar-pendiente-texto">
                                    El docente confirmará tu pago para habilitar el acceso al Aula Virtual, materiales y foros.
                                </span>
                                <div style="display: flex; gap: 8px; margin-top: 12px; flex-direction: column;">
                                    <a href="<?php echo $base_url; ?>cursos/pagar.php?id=<?php echo $curso['id']; ?>" class="btn-primario" style="font-size: 0.85rem; padding: 9px 14px; justify-content: center; text-align: center;">
                                        <i class="bi bi-credit-card"></i> Pagar / Subir Comprobante →
                                    </a>
                                    <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario" style="font-size: 0.82rem; padding: 7px 14px; justify-content: center; text-align: center;">
                                        Ver en Mis Inscripciones
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="badge-inscripto-activo" style="margin-bottom: 10px; padding: 10px 14px; border-radius: 10px; font-weight: 700; text-align: center;">
                                <i class="bi bi-patch-check-fill"></i> ¡Ya sos alumno de este curso!
                            </div>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-primario" style="text-align: center; justify-content: center; font-size: 0.95rem; padding: 12px; width: 100%; display: flex; align-items: center; gap: 8px;">
                                <i class="bi bi-mortarboard-fill" style="font-size: 1.1rem;"></i> Entrar al Aula Virtual →
                            </a>
                            <a href="<?php echo $base_url; ?>cursos/mis-inscripciones.php" class="btn-secundario" style="margin-top: 8px; text-align: center; justify-content: center; font-size: 0.82rem; width: 100%;">
                                <i class="bi bi-journal-bookmark"></i> Ver en Mis Inscripciones
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($es_admin): ?>
                            <a href="<?php echo $base_url; ?>cursos/aula.php?id=<?php echo $curso['id']; ?>" class="btn-docente-aula">
                                <i class="bi bi-shield-lock-fill"></i> Acceder al Aula (Admin)
                            </a>
                        <?php elseif (($curso['estado'] ?? 'publicado') !== 'publicado'): ?>
                            <div style="background: var(--color-surface); border: 1px dashed var(--border-color); border-radius: 12px; padding: 14px; text-align: center;">
                                <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #d97706; display: block; margin-bottom: 4px;"></i>
                                <strong style="font-size: 0.88rem; color: var(--color-text-main);">Inscripciones no disponibles</strong>
                                <span class="texto-ayuda" style="display: block; font-size: 0.78rem; margin-top: 4px;">El curso se encuentra en revisión y aún no está abierto al público.</span>
                            </div>
                        <?php elseif (!$usuario_logueado): ?>
                            <a href="<?php echo $base_url; ?>auth/registrarse.php" class="btn-inscribirme-accion" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <i class="bi bi-mortarboard-fill"></i>
                                <?php echo ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0) ? 'Inscribirme Gratis' : 'Inscribirme al Curso ($ ' . number_format($curso['precio'], 0, ',', '.') . ')'; ?>
                            </a>
                        <?php else: ?>
                            <form action="<?php echo $base_url; ?>controladores/inscripcion_controlador.php?accion=inscribir" method="POST" style="margin: 0;">
                                <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
                                <button type="submit" class="btn-inscribirme-accion">
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <?php echo ($curso['tipo_precio'] === 'gratis' || (float)$curso['precio'] <= 0) ? 'Inscribirme Gratis' : 'Inscribirme al Curso ($ ' . number_format($curso['precio'], 0, ',', '.') . ')'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="sidebar-cupo-bloque">
                        <div class="sidebar-cupo-fila">
                            <span>Inscriptos: <strong><?php echo $curso['inscriptos']; ?> / <?php echo $curso['cupo_max']; ?> alumnos</strong></span>
                            <span style="font-weight: 700; font-size: 0.78rem; color: var(--color-primary);">
                                <?php echo ($cupos_restantes > 0) ? 'Últimos lugares' : 'Agotado'; ?>
                            </span>
                        </div>
                        <div class="sidebar-cupo-barra">
                            <div class="sidebar-cupo-fill" style="width: <?php echo $porcentaje_cupo; ?>%;"></div>
                        </div>
                        <span style="font-size: 0.74rem; color: var(--color-text-muted); display: block;">
                            <?php if ($cupos_restantes > 0): ?>
                                ¡Quedan sólo <strong><?php echo $cupos_restantes; ?> vacantes disponibles</strong> para esta cohorte!
                            <?php else: ?>
                                Cupos agotados para esta cohorte.
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="garantia-bloque">
                        <i class="bi bi-shield-check"></i>
                        <span>Garantía de satisfacción de 7 días o reembolso total</span>
                    </div>
                </div>

                <div class="card-sidebar-detalles">
                    <h4><i class="bi bi-info-circle" style="color: var(--color-primary);"></i> Información de la Cursada</h4>
                    <ul class="ficha-tecnica-lista">
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-calendar3"></i> Período</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['fecha']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-clock"></i> Horarios</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['horario'] ?? 'A coordinar'); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-broadcast"></i> Modalidad</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['modalidad']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-stopwatch"></i> Carga Horaria</span>
                            <span class="ficha-valor"><?php echo htmlspecialchars($curso['duracion']); ?></span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-folder-check"></i> Recursos y Foro</span>
                            <span class="ficha-valor">Incluidos en Aula Virtual</span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-award"></i> Certificación</span>
                            <span class="ficha-valor">Oficial con validez</span>
                        </li>
                        <li class="ficha-tecnica-item">
                            <span class="ficha-label"><i class="bi bi-infinity"></i> Acceso</span>
                            <span class="ficha-valor">Ilimitado de por vida</span>
                        </li>
                    </ul>

                    <div style="padding-top: 4px;">
                        <button type="button" class="btn-sidebar-compartir" onclick="navigator.clipboard.writeText(window.location.href); alert('¡Enlace del curso copiado al portapapeles!');">
                            <i class="bi bi-share-fill" style="color: var(--color-primary);"></i> Compartir este curso
                        </button>
                    </div>
                </div>

            </aside>

        </div>

    </main>

</div>

<script>
function cambiarPestana(idTab, boton) {
    document.querySelectorAll('.curso-tab-btn').forEach(btn => btn.classList.remove('activa'));
    document.querySelectorAll('.curso-tab-panel').forEach(panel => panel.classList.remove('activo'));
    
    boton.classList.add('activa');
    const panelActivo = document.getElementById('tab-panel-' + idTab);
    if (panelActivo) {
        panelActivo.classList.add('activo');
    }
}

function toggleModulo(cabecera) {
    const cuerpo = cabecera.nextElementSibling;
    const flecha = cabecera.querySelector('.flecha-modulo');
    if (cuerpo.style.display === 'none' || cuerpo.style.display === '') {
        cuerpo.style.display = 'block';
        if (flecha) flecha.style.transform = 'rotate(180deg)';
    } else {
        cuerpo.style.display = 'none';
        if (flecha) flecha.style.transform = 'rotate(0deg)';
    }
}

let todosExpandidos = false;
function toggleTodosModulos() {
    todosExpandidos = !todosExpandidos;
    const cuerpos = document.querySelectorAll('.modulo-cuerpo');
    const flechas = document.querySelectorAll('.flecha-modulo');
    const btn = document.getElementById('btn-toggle-todos');
    
    cuerpos.forEach(c => c.style.display = todosExpandidos ? 'block' : 'none');
    flechas.forEach(f => f.style.transform = todosExpandidos ? 'rotate(180deg)' : 'rotate(0deg)');
    if (btn) {
        btn.textContent = todosExpandidos ? 'Colapsar todos los módulos' : 'Expandir todos los módulos';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
