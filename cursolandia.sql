SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;


DROP TABLE IF EXISTS usuario;

CREATE TABLE usuario (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  apellido varchar(50) NOT NULL,
  username varchar(50) DEFAULT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  rol varchar(20) NOT NULL DEFAULT 'usuario',
  plan varchar(20) NOT NULL DEFAULT 'gratuito',
  fecha_vencimiento_pro date DEFAULT NULL,
  avatar varchar(255) DEFAULT NULL,
  biografia text DEFAULT NULL,
  intereses varchar(255) DEFAULT NULL,
  fecha_registro datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO usuario VALUES
(1,'Sunghoon','','sunghoon','admin@cursolandia.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','admin','pro','2030-12-31','archivos/avatares/avatar_1_1794841865.jpg','Administrador de Cursolandia','PHP, MySQL, Cursolandia','2026-11-15 19:33:45'),
(2,'Carlos Gómez','','carlosgomezcito','carlosgomecito@ejemplo.com','$2y$10$/MI1sJkooJ/Ss/xs1nO9aOTAupNjk26iNmpP3TRH4kKNRIEZc6NX.','usuario','pro','2028-12-31','archivos/avatares/avatar_2_1794804346.jpg','Docente titular y consultor en arquitecturas web modernas con PHP y SQL.','Programación, Ciencia & Datos, IA','2026-11-15 19:33:45'),
(3,'Sunoo','','sunitoo','Sunitoo@ejemplo.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','usuario','gratuito',NULL,'archivos/avatares/avatar_3_1794818065.png','Desarrollador web enfocado en la enseñanza práctica de tecnologías backend.','Programación, Arte & Diseño','2026-11-15 19:33:45'),
(4,'Camila Pecorone','','catyaoicat','camipecouwu@ejemplo.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','usuario','gratuito',NULL,'archivos/avatares/avatar_4_1794805072.png','Me gustan las mantas rayas y las artesanías.','Arte, Pintura, Dibujo','2026-11-15 19:33:45');



DROP TABLE IF EXISTS categoria;

CREATE TABLE categoria (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  slug varchar(100) NOT NULL,
  icono varchar(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO categoria VALUES
(1,'Programación','programacion','bi-code-slash'),
(2,'Arte & Diseño','diseno','bi-palette'),
(3,'Comunicación','comunicacion','bi-people'),
(4,'Marketing','marketing','bi-graph-up-arrow'),
(5,'Finanzas','finanzas','bi-cash-stack'),
(6,'Ciencia & Datos','ciencia','bi-diagram-3'),
(7,'Inteligencia Artificial','ia','bi-cpu'),
(8,'Idiomas','idiomas','bi-translate');


DROP TABLE IF EXISTS curso;

CREATE TABLE curso (
  id int(11) NOT NULL AUTO_INCREMENT,
  docente_id int(11) NOT NULL,
  id_categoria int(11) DEFAULT NULL,
  categoria varchar(100) NOT NULL,
  titulo varchar(150) NOT NULL,
  subtitulo varchar(255) DEFAULT NULL,
  descripcion text NOT NULL,
  objetivos text DEFAULT NULL,
  requisitos text DEFAULT NULL,
  modulos_contenido text DEFAULT NULL,
  etiquetas varchar(255) DEFAULT NULL,
  nivel varchar(50) NOT NULL DEFAULT 'Intermedio',
  duracion_horas int(11) NOT NULL DEFAULT 10,
  precio decimal(10,2) NOT NULL DEFAULT 0.00,
  cupo_max int(11) DEFAULT 30,
  fecha_inicio date NOT NULL,
  fecha_fin date NOT NULL,
  tipo_acceso varchar(20) NOT NULL DEFAULT 'publico',
  imagen varchar(255) NOT NULL,
  icono varchar(50) NOT NULL DEFAULT 'bi-journal-bookmark',
  estado varchar(20) NOT NULL DEFAULT 'publicado',
  fecha_creacion datetime NOT NULL,
  PRIMARY KEY (id),
  KEY docente_id (docente_id),
  KEY id_categoria (id_categoria),
  CONSTRAINT curso_ibfk_1 FOREIGN KEY (docente_id) REFERENCES usuario (id) ON UPDATE CASCADE,
  CONSTRAINT curso_ibfk_2 FOREIGN KEY (id_categoria) REFERENCES categoria (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO curso VALUES
(1,2,1,'Programación','Desarrollo Web Fullstack con PHP y MySQL','Dominá backend y frontend desde cero con arquitectura MVC','Aprenderás a construir aplicaciones robustas con PHP nativo, MySQL y buenas prácticas.','Aprender los fundamentos de PHP 8 moderno y buenas prácticas de desarrollo web.\nDiseñar bases de datos relacionales en MySQL y conectar mediante MySQLi.\nImplementar autenticación segura de usuarios mediante sesiones y hashing de contraseñas.\nConstruir un CRUD completo con subida de archivos y validación de formularios.','Conocimientos básicos de HTML y CSS.\nComputadora con XAMPP o similar instalado (Apache + MySQL + PHP 8).\nEditor de código recomendado (como VS Code).','[{\"numero\":\"Módulo 1\",\"titulo\":\"Fundamentos de PHP 8 y Entorno de Desarrollo\",\"duracion\":\"2h 15m\",\"clases\":[{\"nombre\":\"Introducción al desarrollo web dinámico y arquitectura cliente-servidor\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"Sintaxis de PHP 8: variables, estructuras de control y funciones\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Procesamiento de formularios: peticiones GET y POST con sanitización\",\"duracion\":\"40 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Bases de Datos MySQL y Conexión con MySQLi\",\"duracion\":\"3h 00m\",\"clases\":[{\"nombre\":\"Modelado de datos relacionales y diseño de tablas en phpMyAdmin\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Conexión a base de datos y consultas SELECT, INSERT, UPDATE, DELETE\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Operaciones CRUD completas y prevención básica de errores\",\"duracion\":\"60 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Sesiones, Autenticación y Proyecto Final\",\"duracion\":\"2h 45m\",\"clases\":[{\"nombre\":\"Manejo de sesiones, login, registro y password_hash()\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Subida y validación de archivos al servidor\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Integración del proyecto final y puesta en marcha\",\"duracion\":\"60 min\",\"gratis\":false}]}]','PHP, MySQL, Fullstack, Backend','Intermedio',40,12000.00,30,'2026-10-15','2026-12-30','publico','https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80','bi-code-slash','publicado','2026-11-15 19:33:45'),
(2,2,6,'Ciencia & Datos','Bases de Datos Relacionales y Arquitectura 3FN','Modelado relacional, normalización y consultas optimizadas','Conceptos fundamentales de bases de datos relacionales, normalización y rendimiento.','Comprender la teoría relacional y los principios de integridad de datos.\nAplicar las tres primeras Formas Normales (1FN, 2FN y 3FN) para evitar redundancias.\nEscribir consultas SQL avanzadas con múltiples JOINs y agregaciones.\nOptimizar índices y estructurar bases de datos escalables.','Conocimientos básicos de computación y manejo de datos en tablas.\nServidor MySQL o MariaDB instalado localmente.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Introducción al Modelo Relacional\",\"duracion\":\"1h 45m\",\"clases\":[{\"nombre\":\"Conceptos clave: entidades, atributos, claves primarias y foráneas\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"Diagramas de Entidad-Relación y cardinalidad\",\"duracion\":\"60 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Normalización de Datos: 1FN, 2FN y 3FN\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Primera Forma Normal: atomicidad y eliminación de grupos repetitivos\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Segunda y Tercera Forma Normal: dependencias funcionales y transitivas\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Casos prácticos de normalización y rendimiento\",\"duracion\":\"50 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Consultas SQL y Optimización\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"INNER JOIN, LEFT JOIN y subconsultas complejas\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Creación de índices y análisis de planes de ejecución\",\"duracion\":\"60 min\",\"gratis\":false}]}]','SQL, BaseDeDatos, Normalizacion, 3FN','Intermedio',30,9800.00,25,'2026-10-20','2026-12-30','publico','https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80','bi-database','publicado','2026-11-15 19:33:45'),
(3,3,1,'Programación','Introducción Práctica a la Web 2.0','Tus primeros pasos en el desarrollo de software para la web','Esta propuesta formativa ofrece una inmersión completa y práctica en el desarrollo web contemporáneo. A lo largo del trayecto pedagógico se abordan desde los fundamentos esenciales de la arquitectura cliente-servidor y el marcado semántico con HTML5, hasta el diseño y maquetación avanzada con CSS3 (Flexbox y Grid), la interactividad del lado del usuario con JavaScript moderno (ES6+), y la integración de estándares de accesibilidad web (WCAG), optimización para motores de búsqueda y diseño responsivo multi-dispositivo.','Aprender la estructura y semántica de documentos HTML5 modernos.\nAplicar estilos atractivos y adaptables con CSS3 y Flexbox.\nComprender el diseño responsivo para móviles, tablets y escritorio.\nPublicar tu primer sitio web en internet.','No se requieren conocimientos previos de programación.\nNavegador web moderno (Chrome, Firefox o Edge).\nEditor de texto recomendado: VS Code.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Estructura Semántica con HTML5\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Cómo funciona la web: navegadores, servidores y protocolos\",\"duracion\":\"40 min\",\"gratis\":true},{\"nombre\":\"Etiquetas esenciales: encabezados, párrafos, listas y enlaces\",\"duracion\":\"40 min\",\"gratis\":false},{\"nombre\":\"Estructura semántica moderna: header, nav, main, section y footer\",\"duracion\":\"40 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Estilos y Maquetación con CSS3\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Selectores, cascada, especificidad y modelo de caja\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Diseño flexible con Flexbox: alineación y distribución\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Media queries y diseño responsivo adaptado a dispositivos móviles\",\"duracion\":\"50 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Proyecto Web y Publicación\",\"duracion\":\"1h 30m\",\"clases\":[{\"nombre\":\"Maquetación paso a paso de una página de aterrizaje completa\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Subida y publicación del sitio en hosting gratuito\",\"duracion\":\"40 min\",\"gratis\":false}]}]','HTML, CSS, PHP, Web','Inicial',20,0.00,40,'2026-11-01','2026-12-31','publico','https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80','bi-journal-code','publicado','2026-11-15 19:33:45'),
(5,3,1,'Programación','JavaScript Moderno & React: De Cero a Producción','Componentes funcionales, hooks, estado global y despliegue en la nube','Aprendé el estándar moderno de JavaScript (ES6+) y construí aplicaciones interactivas con React, integrando APIs REST y optimizando la renderización.','Dominar JavaScript moderno (ES6+): destructuring, async/await, promesas y módulos.\nConstruir interfaces dinámicas y reutilizables con React y componentes funcionales.\nManejar estado y efectos con React Hooks (useState, useEffect, useContext).\nConsumir APIs RESTful y desplegar aplicaciones web a producción.','Conocimientos básicos de HTML y CSS.\nComputadora con Node.js instalado.','[{\"numero\":\"Módulo 1\",\"titulo\":\"JavaScript Moderno (ES6+)\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Arrow functions, template literals, destructuring y spread operator\",\"duracion\":\"50 min\",\"gratis\":true},{\"nombre\":\"Métodos de arrays: map, filter, reduce y find\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Asincronía en JS: Promesas, fetch API y async\\/await\",\"duracion\":\"50 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Fundamentos de React y Componentes\",\"duracion\":\"3h 00m\",\"clases\":[{\"nombre\":\"Instalación del entorno con Vite y JSX desde cero\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Componentes, props y renderizado condicional\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Estado con useState y manejo de eventos\",\"duracion\":\"75 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Hooks Avanzados y Conexión con APIs\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Ciclo de vida y efectos secundarios con useEffect\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Consumo de API REST y renderizado de datos dinámicos\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Compilación y despliegue del proyecto en producción\",\"duracion\":\"40 min\",\"gratis\":false}]}]','JavaScript, React, Frontend, Web, SPA','Intermedio',35,14500.00,25,'2026-11-25','2027-01-20','publico','https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=600&q=80','bi-code-square','publicado','2026-11-15 21:19:19'),
(6,2,2,'Arte & Diseño','Diseño UI/UX Profesional con Figma & Design Systems','Creá prototipos interactivos, wireframes y librerías de componentes escalables','Aprendé metodología de diseño centrada en el usuario, investigación de experiencia, arquitectura de la información y construcción de sistemas de diseño atómicos en Figma.','Aprender la metodología de diseño centrado en el usuario (UX).\nDiseñar interfaces limpias, modernas y consistentes en Figma.\nCrear sistemas de diseño profesionales con componentes, variantes y Auto Layout.\nPrototipar flujos interactivos para pruebas con usuarios reales.','Cuenta gratuita en Figma.\nNo se requiere experiencia previa en diseño.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Fundamentos de UI\\/UX y Herramientas en Figma\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Principios de UX: investigación de usuarios, wireframes y jerarquía visual\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"Interfaz de Figma: frames, capas, formas y tipografía\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Sistemas de color, espaciado y grillas tipográficas\",\"duracion\":\"30 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Componentes y Auto Layout Avanzado\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Dominando Auto Layout: alineaciones, padding y resizing\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Creación de componentes y sets de variantes con propiedades\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Diseño de un Design System escalable para web y app\",\"duracion\":\"45 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Prototipado Interactivo y Presentación\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Animaciones Smart Animate y microinteracciones\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Documentación para desarrolladores y exportación de assets\",\"duracion\":\"60 min\",\"gratis\":false}]}]','Figma, UIUX, DesignSystems, Prototipado, UX','Intermedio',32,15000.00,25,'2026-11-22','2027-01-15','publico','https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80','bi-palette','publicado','2026-11-15 21:19:19'),
(9,2,4,'Marketing','Growth Marketing & Campañas Publicitarias con Meta y Google Ads','Segmentación avanzada, optimización de presupuestos y métricas de conversión','Aprendé a crear embudos de venta rentables, configurar píxeles de seguimiento, tests A/B y escalar campañas de tráfico pago con ROI positivo.','Diseñar estrategias de adquisición y retención orientadas a crecimiento (Growth).\nConfigurar el administrador comercial de Meta Ads y Google Ads.\nSegmentar audiencias personalizadas, lookalikes e intención de búsqueda.\nMedir métricas de rendimiento (CPA, CTR, ROAS) y optimizar el presupuesto.','Nociones básicas sobre redes sociales y marketing.\nCuenta en plataformas digitales.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Estrategia de Crecimiento y Embudos de Conversión\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Introducción al Growth Marketing: métricas AARRR y embudos de venta\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"Definición del cliente ideal (Buyer Persona) y propuesta de valor\",\"duracion\":\"40 min\",\"gratis\":false},{\"nombre\":\"Estructura de una oferta irresistible y páginas de captura\",\"duracion\":\"35 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Campañas en Meta Ads (Facebook e Instagram)\",\"duracion\":\"2h 45m\",\"clases\":[{\"nombre\":\"Configuración del Business Manager, cuenta publicitaria y Píxel\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Estructura de campaña: reconocimiento, tráfico y conversiones\",\"duracion\":\"55 min\",\"gratis\":false},{\"nombre\":\"Creatividades ganadoras: formatos de imagen, video y copywriting\",\"duracion\":\"55 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Google Ads y Medición de Rendimiento\",\"duracion\":\"2h 15m\",\"clases\":[{\"nombre\":\"Campañas de búsqueda en Google: palabras clave e intención de compra\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Lectura de métricas clave: CTR, CPC, CPA, ROAS y tasa de conversión\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Optimización continua y escalado de presupuestos publicitarios\",\"duracion\":\"40 min\",\"gratis\":false}]}]','MarketingDigital, GoogleAds, MetaAds, Growth, Performance','Intermedio',30,13500.00,30,'2026-11-24','2027-01-18','publico','https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80','bi-graph-up-arrow','publicado','2026-11-15 21:19:19'),
(11,2,5,'Finanzas','Finanzas Personales e Inversiones en Mercados Financieros','Presupuesto, ahorro inteligente, CEDEARs, bonos y fondos comunes','Tomá el control de tus finanzas personales, aprendé a invertir para protegerte de la inflación y armá tu primer portafolio de inversión diversificado.','Diagnosticar tu situación financiera y armar un presupuesto personal inteligente.\nEstablecer un fondo de emergencia seguro contra imprevistos.\nConocer los diferentes instrumentos de inversión: bonos, CEDEARs, acciones y fondos comunes.\nDiseñar un portafolio de inversión diversificado acorde a tu perfil de riesgo.','No se requieren conocimientos contables o matemáticos previos.\nComputadora o smartphone para acceder a aplicaciones financieras.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Diagnóstico Financiero y Presupuesto Realista\",\"duracion\":\"1h 45m\",\"clases\":[{\"nombre\":\"Identificación de gastos fijos, variables y de ocio\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"La regla 50\\/30\\/20 y cómo adaptarla a tus ingresos\",\"duracion\":\"60 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Ahorro y Fondo de Emergencia\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Estrategias prácticas de ahorro y eliminación de deudas\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Cálculo y constitución de un fondo de emergencia blindado\",\"duracion\":\"40 min\",\"gratis\":false},{\"nombre\":\"Cuentas remuneradas e instrumentos de liquidez inmediata\",\"duracion\":\"30 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Inversión en Mercados Financieros\",\"duracion\":\"2h 30m\",\"clases\":[{\"nombre\":\"Conceptos de inflación, interés compuesto y perfil de inversor\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Inversión en bonos, Fondos Comunes de Inversión y CEDEARs\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Armado y rebalanceo de tu primer portafolio diversificado\",\"duracion\":\"45 min\",\"gratis\":false}]}]','Finanzas, Inversiones, Ahorro, MercadoDeCapitales, Bonos','Inicial',22,9500.00,35,'2026-11-26','2027-01-12','publico','https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=600&q=80','bi-cash-stack','publicado','2026-11-15 21:19:19'),
(15,2,7,'Inteligencia Artificial','Prompt Engineering y Automatización con Inteligencia Artificial','Técnicas avanzadas de prompting, agentes autónomos e integración de APIs','Optimizá flujos de trabajo diarios utilizando modelos de lenguaje de última generación. Creá prompts estructurados, chain-of-thought y automatizaciones inteligentes.','Dominar las técnicas de Prompt Engineering para modelos de lenguaje avanzados.\nAutomatizar tareas repetitivas de redacción, análisis de datos y atención al cliente.\nIntegrar APIs de inteligencia artificial en flujos de trabajo cotidianos.\nConstruir agentes y asistentes personalizados para resolver problemas reales.','Navegador web y curiosidad por las herramientas de IA.\nNo se requieren conocimientos avanzados de programación.','[{\"numero\":\"Módulo 1\",\"titulo\":\"Fundamentos de Inteligencia Artificial Generativa\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Cómo funcionan los Grandes Modelos de Lenguaje (LLMs)\",\"duracion\":\"45 min\",\"gratis\":true},{\"nombre\":\"Anatomía de un buen prompt: rol, contexto, tarea y formato\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Técnicas avanzadas: Few-shot prompting y Chain-of-Thought\",\"duracion\":\"30 min\",\"gratis\":false}]},{\"numero\":\"Módulo 2\",\"titulo\":\"Productividad y Creación de Contenido\",\"duracion\":\"2h 15m\",\"clases\":[{\"nombre\":\"Redacción asistida, síntesis de documentos y análisis de texto\",\"duracion\":\"50 min\",\"gratis\":false},{\"nombre\":\"Generación de código y asistencia para depuración de programas\",\"duracion\":\"45 min\",\"gratis\":false},{\"nombre\":\"Creación y optimización de asistentes personalizados\",\"duracion\":\"40 min\",\"gratis\":false}]},{\"numero\":\"Módulo 3\",\"titulo\":\"Automatización de Tareas y Flujos de Trabajo\",\"duracion\":\"2h 00m\",\"clases\":[{\"nombre\":\"Integración de IA con herramientas de automatización de tareas\",\"duracion\":\"60 min\",\"gratis\":false},{\"nombre\":\"Construcción de un pipeline automatizado de principio a fin\",\"duracion\":\"60 min\",\"gratis\":false}]}]','IA, PromptEngineering, Automatizacion, ChatGPT, Productividad','Inicial',15,14000.00,45,'2026-11-20','2026-12-22','publico','https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80','bi-robot','publicado','2026-11-15 21:19:19');

DROP TABLE IF EXISTS inscripcion;

CREATE TABLE inscripcion (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) NOT NULL,
  curso_id int(11) NOT NULL,
  estado varchar(20) NOT NULL DEFAULT 'activa',
  progreso_porcentaje int(11) NOT NULL DEFAULT 0,
  fecha_inscripcion datetime NOT NULL,
  comprobante varchar(255) DEFAULT NULL,
  fecha_comprobante datetime DEFAULT NULL,
  mensaje_pago text DEFAULT NULL,
  PRIMARY KEY (id),
  KEY usuario_id (usuario_id),
  KEY curso_id (curso_id),
  CONSTRAINT inscripcion_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON UPDATE CASCADE,
  CONSTRAINT inscripcion_ibfk_2 FOREIGN KEY (curso_id) REFERENCES curso (id) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO inscripcion VALUES
(2,4,3,'activa',20,'2026-11-16 03:18:44',NULL,NULL,NULL),
(6,4,15,'cancelada',0,'2026-11-15 23:48:12',NULL,NULL,NULL),
(10,4,2,'cancelada',0,'2026-11-16 01:40:29','archivos/comprobantes/pago_curso_10_1794804037.jpg','2026-11-16 01:40:37','hola'),
(11,1,1,'pendiente',0,'2026-11-16 14:59:56',NULL,NULL,NULL),
(12,2,1,'pendiente',0,'2026-11-16 15:02:20',NULL,NULL,NULL),
(13,3,1,'pendiente',0,'2026-11-16 15:02:23',NULL,NULL,NULL),
(14,4,5,'pendiente',0,'2026-11-16 15:04:05',NULL,NULL,NULL),
(15,4,1,'pendiente',0,'2026-11-16 15:12:30',NULL,NULL,NULL);


DROP TABLE IF EXISTS material;

CREATE TABLE material (
  id int(11) NOT NULL AUTO_INCREMENT,
  curso_id int(11) NOT NULL,
  nombre_archivo varchar(150) NOT NULL,
  ruta_archivo varchar(255) NOT NULL,
  tamano_mb decimal(5,2) NOT NULL,
  fecha_subida datetime NOT NULL,
  PRIMARY KEY (id),
  KEY curso_id (curso_id),
  CONSTRAINT material_ibfk_1 FOREIGN KEY (curso_id) REFERENCES curso (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO material VALUES
(1,1,'Guia_PHP8_MySQL_Moderno.pdf','archivos/cursos/guia_php_mysql.pdf',3.20,'2026-11-21 00:14:06'),
(2,1,'Codigo_Fuente_Proyecto_CRUD.zip','archivos/cursos/codigo_crud.zip',1.80,'2026-11-21 00:14:06'),
(3,2,'Ejercicios_Normalizacion_3FN.pdf','archivos/cursos/ejercicios_3fn.pdf',2.10,'2026-11-21 00:14:06'),
(4,3,'Manual_HTML5_CSS3_Flexbox.pdf','archivos/cursos/manual_html_css.pdf',4.00,'2026-11-21 00:14:06'),
(5,5,'CheatSheet_React_Hooks.pdf','archivos/cursos/cheatsheet_react.pdf',1.50,'2026-11-21 00:14:06'),
(6,6,'Plantilla_Design_System_Figma.zip','archivos/cursos/design_system_figma.zip',5.40,'2026-11-21 00:14:06'),
(7,11,'Planilla_Presupuesto_Personal.xlsx','archivos/cursos/planilla_presupuesto.xlsx',1.20,'2026-11-21 00:14:06'),
(8,15,'Guia_Prompt_Engineering_Avanzado.pdf','archivos/cursos/guia_prompt_engineering.pdf',2.80,'2026-11-21 00:14:06');

DROP TABLE IF EXISTS notificacion;

CREATE TABLE notificacion (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) NOT NULL,
  mensaje text NOT NULL,
  enlace varchar(255) DEFAULT NULL,
  leida int(11) NOT NULL DEFAULT 0,
  fecha datetime NOT NULL,
  PRIMARY KEY (id),
  KEY usuario_id (usuario_id),
  CONSTRAINT notificacion_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO notificacion VALUES
(2,2,'Laura González se inscribió en tu curso: \"Desarrollo Web Fullstack\"','cursos/alumnos.php?id=1',1,'2026-11-15 21:30:27'),
(6,2,'Laura se ha inscripto a tu curso \"Curso de Cosechar Prestigio\".','cursos/alumnos.php?id=18',1,'2026-11-15 22:46:26'),
(7,1,'Nueva solicitud de Plan PRO de Laura con comprobante adjunto.','admin/solicitudes.php',1,'2026-11-15 22:47:33'),
(8,2,'L-aura solicitó inscribirse a tu curso pago \"Prompt Engineering y Automatización con Inteligencia Artificial\" (Pago pendiente).','cursos/alumnos.php?id=15',1,'2026-11-15 23:48:12'),
(9,2,'Laura González solicitó inscribirse a tu curso pago \"Bases de Datos Relacionales y Arquitectura 3FN\" (Pago pendiente).','cursos/alumnos.php?id=2',1,'2026-11-16 01:40:29'),
(10,2,'Laura González adjuntó el comprobante de pago para el curso \"Bases de Datos Relacionales y Arquitectura 3FN\". Podés verificarlo y confirmar el alta.','cursos/alumnos.php?id=2',1,'2026-11-16 01:40:37'),
(13,1,'¡El docente confirmó tu pago! Ya estás formalmente inscripto a \"Prompt Engineering y Automatización con Inteligencia Artificial\".','cursos/ver.php?id=15',1,'2026-11-16 01:52:56'),
(14,3,'Camila Pecorone se ha inscripto a tu curso \"Introducción Práctica a la Web 2.0\".','cursos/alumnos.php?id=3',1,'2026-11-16 03:18:44'),
(15,2,'¡Excelente noticia! El administrador aprobó tu curso \"Prompt Engineering y Automatización con Inteligencia Artificial\" y ya se encuentra publicado en el catálogo.','cursos/ver.php?id=15',0,'2026-11-16 15:51:45');


DROP TABLE IF EXISTS pregunta;

CREATE TABLE pregunta (
  id int(11) NOT NULL AUTO_INCREMENT,
  curso_id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  titulo varchar(200) NOT NULL,
  contenido text NOT NULL,
  destacada int(11) NOT NULL DEFAULT 0,
  cerrada int(11) NOT NULL DEFAULT 0,
  id_respuesta_aceptada int(11) DEFAULT NULL,
  fecha_creacion datetime NOT NULL,
  PRIMARY KEY (id),
  KEY curso_id (curso_id),
  KEY usuario_id (usuario_id),
  CONSTRAINT pregunta_ibfk_1 FOREIGN KEY (curso_id) REFERENCES curso (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT pregunta_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO pregunta VALUES
(1,1,4,'¿Cómo estructurar las carpetas del proyecto en PHP puro?','Hola profe, ¿cuál es la mejor práctica para separar controladores y vistas sin frameworks?',1,0,NULL,'2026-11-15 19:33:45'),
(4,1,4,'¿Cómo configurar correctamente el entorno local en XAMPP?','Hola profesor, tengo una duda sobre la configuración del VirtualHost en Apache para proyectos PHP.',1,0,NULL,'2026-11-21 00:14:06'),
(5,1,3,'¿Es conveniente usar MySQLi o PDO en este proyecto?','Quisiera saber qué diferencias prácticas encontraremos al trabajar con consultas preparadas.',0,0,NULL,'2026-11-21 00:14:26');


DROP TABLE IF EXISTS respuesta;

CREATE TABLE respuesta (
  id int(11) NOT NULL AUTO_INCREMENT,
  pregunta_id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  contenido text NOT NULL,
  es_aceptada int(11) NOT NULL DEFAULT 0,
  fecha_creacion datetime NOT NULL,
  fecha_edicion datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY pregunta_id (pregunta_id),
  KEY usuario_id (usuario_id),
  CONSTRAINT respuesta_ibfk_1 FOREIGN KEY (pregunta_id) REFERENCES pregunta (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT respuesta_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO respuesta VALUES
(1,1,2,'¡Hola Laura! Te recomiendo tener una carpeta controladores/ donde cada archivo procese las peticiones POST y redirija limpiamente.',0,'2026-11-15 19:33:45',NULL),
(2,5,2,'¡Hola! Ambas librerías son excelentes y seguras. En este curso usamos MySQLi de forma modular para entender la conexión directa.',0,'2026-11-21 00:14:26',NULL);


DROP TABLE IF EXISTS solicitudpro;

CREATE TABLE solicitudpro (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) NOT NULL,
  comprobante varchar(255) NOT NULL,
  mensaje text DEFAULT NULL,
  estado varchar(20) NOT NULL DEFAULT 'pendiente',
  fecha_solicitud datetime NOT NULL,
  PRIMARY KEY (id),
  KEY usuario_id (usuario_id),
  CONSTRAINT solicitudpro_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO solicitudpro VALUES
(1,4,'archivos/comprobantes/pago_pro_4_1794793653.jpg','oña','pendiente','2026-11-15 22:47:33');


COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
