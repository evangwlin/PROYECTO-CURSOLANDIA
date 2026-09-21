/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icono` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES
(1,'Programación','programacion','bi-code-slash'),
(2,'Arte & Diseño','diseno','bi-palette'),
(3,'Comunicación','comunicacion','bi-people'),
(4,'Marketing','marketing','bi-graph-up-arrow'),
(5,'Finanzas','finanzas','bi-cash-stack'),
(6,'Ciencia & Datos','ciencia','bi-diagram-3'),
(7,'Inteligencia Artificial','ia','bi-cpu'),
(8,'Idiomas','idiomas','bi-translate');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `curso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `categoria` varchar(100) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `objetivos` text DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `modulos_contenido` text DEFAULT NULL,
  `etiquetas` varchar(255) DEFAULT NULL,
  `nivel` varchar(50) NOT NULL DEFAULT 'Intermedio',
  `duracion_horas` int(11) NOT NULL DEFAULT 10,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cupo_max` int(11) DEFAULT 30,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo_acceso` varchar(20) NOT NULL DEFAULT 'publico',
  `imagen` varchar(255) NOT NULL,
  `icono` varchar(50) NOT NULL DEFAULT 'bi-journal-bookmark',
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `curso_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `curso` WRITE;
/*!40000 ALTER TABLE `curso` DISABLE KEYS */;
INSERT INTO `curso` VALUES
(1,2,1,'Programación','Desarrollo Web Fullstack con PHP y MySQL','Dominá backend y frontend desde cero con arquitectura MVC','Aprenderás a construir aplicaciones robustas con PHP nativo, MySQL y buenas prácticas.',NULL,NULL,NULL,'PHP, MySQL, Fullstack, Backend','Intermedio',40,12000.00,30,'2026-10-15','2026-12-30','publico','https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80','bi-code-slash','publicado','2026-11-15 19:33:45'),
(2,2,6,'Ciencia & Datos','Bases de Datos Relacionales y Arquitectura 3FN','Modelado relacional, normalización y consultas optimizadas','Conceptos fundamentales de bases de datos relacionales, normalización y rendimiento.',NULL,NULL,NULL,'SQL, BaseDeDatos, Normalizacion, 3FN','Intermedio',30,9800.00,25,'2026-10-20','2026-12-30','publico','https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80','bi-database','publicado','2026-11-15 19:33:45'),
(3,3,1,'Programación','Introducción Práctica a la Web 2.0','Tus primeros pasos en el desarrollo de software para la web','Esta propuesta formativa ofrece una inmersión completa y práctica en el desarrollo web contemporáneo. A lo largo del trayecto pedagógico se abordan desde los fundamentos esenciales de la arquitectura cliente-servidor y el marcado semántico con HTML5, hasta el diseño y maquetación avanzada con CSS3 (Flexbox y Grid), la interactividad del lado del usuario con JavaScript moderno (ES6+), y la integración de estándares de accesibilidad web (WCAG), optimización para motores de búsqueda y diseño responsivo multi-dispositivo.',NULL,NULL,NULL,'HTML, CSS, PHP, Web','Inicial',20,0.00,40,'2026-11-01','2026-12-31','publico','https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80','bi-journal-code','publicado','2026-11-15 19:33:45'),
(5,3,1,'Programación','JavaScript Moderno & React: De Cero a Producción','Componentes funcionales, hooks, estado global y despliegue en la nube','Aprendé el estándar moderno de JavaScript (ES6+) y construí aplicaciones interactivas con React, integrando APIs REST y optimizando la renderización.',NULL,NULL,NULL,'JavaScript, React, Frontend, Web, SPA','Intermedio',35,14500.00,25,'2026-11-25','2027-01-20','publico','https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=600&q=80','bi-code-square','publicado','2026-11-15 21:19:19'),
(6,2,2,'Arte & Diseño','Diseño UI/UX Profesional con Figma & Design Systems','Creá prototipos interactivos, wireframes y librerías de componentes escalables','Aprendé metodología de diseño centrada en el usuario, investigación de experiencia, arquitectura de la información y construcción de sistemas de diseño atómicos en Figma.',NULL,NULL,NULL,'Figma, UIUX, DesignSystems, Prototipado, UX','Intermedio',32,15000.00,25,'2026-11-22','2027-01-15','publico','https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80','bi-palette','publicado','2026-11-15 21:19:19'),
(9,2,4,'Marketing','Growth Marketing & Campañas Publicitarias con Meta y Google Ads','Segmentación avanzada, optimización de presupuestos y métricas de conversión','Aprendé a crear embudos de venta rentables, configurar píxeles de seguimiento, tests A/B y escalar campañas de tráfico pago con ROI positivo.',NULL,NULL,NULL,'MarketingDigital, GoogleAds, MetaAds, Growth, Performance','Intermedio',30,13500.00,30,'2026-11-24','2027-01-18','publico','https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80','bi-graph-up-arrow','publicado','2026-11-15 21:19:19'),
(11,2,5,'Finanzas','Finanzas Personales e Inversiones en Mercados Financieros','Presupuesto, ahorro inteligente, CEDEARs, bonos y fondos comunes','Tomá el control de tus finanzas personales, aprendé a invertir para protegerte de la inflación y armá tu primer portafolio de inversión diversificado.',NULL,NULL,NULL,'Finanzas, Inversiones, Ahorro, MercadoDeCapitales, Bonos','Inicial',22,9500.00,35,'2026-11-26','2027-01-12','publico','https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=600&q=80','bi-cash-stack','publicado','2026-11-15 21:19:19'),
(15,2,7,'Inteligencia Artificial','Prompt Engineering y Automatización con Inteligencia Artificial','Técnicas avanzadas de prompting, agentes autónomos e integración de APIs','Optimizá flujos de trabajo diarios utilizando modelos de lenguaje de última generación. Creá prompts estructurados, chain-of-thought y automatizaciones inteligentes.',NULL,NULL,NULL,'IA, PromptEngineering, Automatizacion, ChatGPT, Productividad','Inicial',15,14000.00,45,'2026-11-20','2026-12-22','publico','https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80','bi-robot','publicado','2026-11-15 21:19:19');
/*!40000 ALTER TABLE `curso` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `inscripcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscripcion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'activa',
  `progreso_porcentaje` int(11) NOT NULL DEFAULT 0,
  `fecha_inscripcion` datetime NOT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `fecha_comprobante` datetime DEFAULT NULL,
  `mensaje_pago` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `inscripcion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `inscripcion_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `curso` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `inscripcion` WRITE;
/*!40000 ALTER TABLE `inscripcion` DISABLE KEYS */;
INSERT INTO `inscripcion` VALUES
(2,4,3,'activa',20,'2026-11-16 03:18:44',NULL,NULL,NULL),
(6,4,15,'cancelada',0,'2026-11-15 23:48:12',NULL,NULL,NULL),
(10,4,2,'cancelada',0,'2026-11-16 01:40:29','archivos/comprobantes/pago_curso_10_1794804037.jpg','2026-11-16 01:40:37','hola'),
(11,1,1,'pendiente',0,'2026-11-16 14:59:56',NULL,NULL,NULL),
(12,2,1,'pendiente',0,'2026-11-16 15:02:20',NULL,NULL,NULL),
(13,3,1,'pendiente',0,'2026-11-16 15:02:23',NULL,NULL,NULL),
(14,4,5,'pendiente',0,'2026-11-16 15:04:05',NULL,NULL,NULL),
(15,4,1,'pendiente',0,'2026-11-16 15:12:30',NULL,NULL,NULL);
/*!40000 ALTER TABLE `inscripcion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `nombre_archivo` varchar(150) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `tamano_mb` decimal(5,2) NOT NULL,
  `fecha_subida` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `material_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `curso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `material` WRITE;
/*!40000 ALTER TABLE `material` DISABLE KEYS */;
/*!40000 ALTER TABLE `material` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `leida` int(11) NOT NULL DEFAULT 0,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `notificacion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notificacion` WRITE;
/*!40000 ALTER TABLE `notificacion` DISABLE KEYS */;
INSERT INTO `notificacion` VALUES
(2,2,'Laura González se inscribió en tu curso: \"Desarrollo Web Fullstack\"','cursos/alumnos.php?id=1',1,'2026-11-15 21:30:27'),
(6,2,'Laura se ha inscripto a tu curso \"Curso de Cosechar Prestigio\".','cursos/alumnos.php?id=18',1,'2026-11-15 22:46:26'),
(7,1,'Nueva solicitud de Plan PRO de Laura con comprobante adjunto.','admin/solicitudes.php',1,'2026-11-15 22:47:33'),
(8,2,'L-aura solicitó inscribirse a tu curso pago \"Prompt Engineering y Automatización con Inteligencia Artificial\" (Pago pendiente).','cursos/alumnos.php?id=15',1,'2026-11-15 23:48:12'),
(9,2,'Laura González solicitó inscribirse a tu curso pago \"Bases de Datos Relacionales y Arquitectura 3FN\" (Pago pendiente).','cursos/alumnos.php?id=2',1,'2026-11-16 01:40:29'),
(10,2,'Laura González adjuntó el comprobante de pago para el curso \"Bases de Datos Relacionales y Arquitectura 3FN\". Podés verificarlo y confirmar el alta.','cursos/alumnos.php?id=2',1,'2026-11-16 01:40:37'),
(13,1,'¡El docente confirmó tu pago! Ya estás formalmente inscripto a \"Prompt Engineering y Automatización con Inteligencia Artificial\".','cursos/ver.php?id=15',1,'2026-11-16 01:52:56'),
(14,3,'Camila Pecorone se ha inscripto a tu curso \"Introducción Práctica a la Web 2.0\".','cursos/alumnos.php?id=3',1,'2026-11-16 03:18:44');
/*!40000 ALTER TABLE `notificacion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pregunta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pregunta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `destacada` int(11) NOT NULL DEFAULT 0,
  `cerrada` int(11) NOT NULL DEFAULT 0,
  `id_respuesta_aceptada` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `curso_id` (`curso_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pregunta_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `curso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pregunta_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pregunta` WRITE;
/*!40000 ALTER TABLE `pregunta` DISABLE KEYS */;
INSERT INTO `pregunta` VALUES
(1,1,4,'¿Cómo estructurar las carpetas del proyecto en PHP puro?','Hola profe, ¿cuál es la mejor práctica para separar controladores y vistas sin frameworks?',1,0,NULL,'2026-11-15 19:33:45');
/*!40000 ALTER TABLE `pregunta` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `respuesta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `respuesta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pregunta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `es_aceptada` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL,
  `fecha_edicion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pregunta_id` (`pregunta_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `respuesta_ibfk_1` FOREIGN KEY (`pregunta_id`) REFERENCES `pregunta` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `respuesta_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `respuesta` WRITE;
/*!40000 ALTER TABLE `respuesta` DISABLE KEYS */;
INSERT INTO `respuesta` VALUES
(1,1,2,'¡Hola Laura! Te recomiendo tener una carpeta controladores/ donde cada archivo procese las peticiones POST y redirija limpiamente.',0,'2026-11-15 19:33:45',NULL);
/*!40000 ALTER TABLE `respuesta` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `solicitudpro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudpro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `solicitudpro_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `solicitudpro` WRITE;
/*!40000 ALTER TABLE `solicitudpro` DISABLE KEYS */;
INSERT INTO `solicitudpro` VALUES
(1,4,'archivos/comprobantes/pago_pro_4_1794793653.jpg','oña','pendiente','2026-11-15 22:47:33');
/*!40000 ALTER TABLE `solicitudpro` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'usuario',
  `plan` varchar(20) NOT NULL DEFAULT 'gratuito',
  `fecha_vencimiento_pro` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `intereses` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES
(1,'Sunghoon','','sunghoon','admin@cursolandia.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','admin','pro','2030-12-31','archivos/avatares/avatar_1_1794841865.jpg','Administrador de Cursolandia','PHP, MySQL, Cursolandia','2026-11-15 19:33:45'),
(2,'Carlos Gómez','','carlosgomezcito','carlosgomecito@ejemplo.com','$2y$10$/MI1sJkooJ/Ss/xs1nO9aOTAupNjk26iNmpP3TRH4kKNRIEZc6NX.','usuario','pro','2028-12-31','archivos/avatares/avatar_2_1794804346.jpg','Docente titular y consultor en arquitecturas web modernas con PHP y SQL.','Programación, Ciencia & Datos, IA','2026-11-15 19:33:45'),
(3,'Sunoo','','sunitoo','Sunitoo@ejemplo.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','usuario','gratuito',NULL,'archivos/avatares/avatar_3_1794818065.png','Desarrollador web enfocado en la enseñanza práctica de tecnologías backend.','Programación, Arte & Diseño','2026-11-15 19:33:45'),
(4,'Camila Pecorone','','catyaoicat','camipecouwu@ejemplo.com','$2y$10$sCAmEinW/Sd1jrWoDz.98.nPjPlqZA9L8Ezuqt4mtH/TpjcYpl88u','usuario','gratuito',NULL,'archivos/avatares/avatar_4_1794805072.png','Me gustan las mantas rayas y las artesanías.','Arte, Pintura, Dibujo','2026-11-15 19:33:45');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

