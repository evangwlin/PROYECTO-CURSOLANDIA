<?php
$titulo_pagina = 'Configuración';
$menu_activo = 'configuracion';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/funciones.php';

$base_url = obtener_base_url();
$conexion = obtener_conexion();

$usuario_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = (int)$_SESSION['usuario_id'];
} elseif (isset($_SESSION['usuario']['id'])) {
    $usuario_id = (int)$_SESSION['usuario']['id'];
}
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para acceder a tu configuración.';
    header('Location: ../auth/ingresar.php');
    exit;
}
$uid_seguro = intval($usuario_id);
$res = mysqli_query($conexion, "SELECT * FROM usuario WHERE id = $uid_seguro LIMIT 1");
$usuarioBD = $res ? mysqli_fetch_assoc($res) : null;

if (!$usuarioBD) {
    header('Location: ../auth/ingresar.php');
    exit;
}

$uNom = isset($usuarioBD['nombre']) ? $usuarioBD['nombre'] : '';
$uApe = isset($usuarioBD['apellido']) ? $usuarioBD['apellido'] : '';

$titulo_profesional = '';
if (isset($usuarioBD['rol']) && $usuarioBD['rol'] === 'admin') {
    $titulo_profesional = 'Administrador';
} elseif (isset($usuarioBD['titulo_profesional'])) {
    $titulo_profesional = $usuarioBD['titulo_profesional'];
}

$plan_usuario = isset($usuarioBD['plan']) ? $usuarioBD['plan'] : 'gratuito';

$usuario = [
    'nombre'             => trim($uNom . ' ' . $uApe),
    'username'           => !empty($usuarioBD['username']) ? $usuarioBD['username'] : strtolower(str_replace(' ', '', $uNom . $uApe)),
    'email'              => isset($usuarioBD['email']) ? $usuarioBD['email'] : '',
    'titulo_profesional' => $titulo_profesional,
    'plan'               => $plan_usuario,
    'intereses'          => isset($usuarioBD['intereses']) ? $usuarioBD['intereses'] : 'PHP, MySQL, Desarrollo Web',
    'antecedentes'       => isset($usuarioBD['biografia']) ? $usuarioBD['biografia'] : '',
    'avatar'             => isset($usuarioBD['avatar']) ? $usuarioBD['avatar'] : null,
    'fecha_vencimiento_pro' => isset($usuarioBD['fecha_vencimiento_pro']) ? $usuarioBD['fecha_vencimiento_pro'] : null,
    'github'             => '',
    'linkedin'           => ''
];

$es_pro = false;
if ($usuario_id > 0) {
    $es_pro = usuario_es_pro($usuario_id);
} elseif ($plan_usuario === 'pro') {
    $es_pro = true;
}

$uRolCheck = '';
if (isset($usuarioBD['rol'])) {
    $uRolCheck = $usuarioBD['rol'];
} elseif (isset($_SESSION['usuario_rol'])) {
    $uRolCheck = $_SESSION['usuario_rol'];
}

$es_admin = ($uRolCheck === 'admin');
$iniciales = strtoupper(substr($usuario['nombre'], 0, 2));

$titulo_plan = 'Plan Gratuito de Cursolandia';
$desc_plan = 'Tenés acceso estándar para aprender y participar de la comunidad.';
if ($es_admin) {
    $titulo_plan = 'Cuenta con permisos de Administrador';
    $desc_plan = 'Tu cuenta tiene permisos de superadministrador para gestionar cursos, usuarios y solicitudes.';
} elseif ($es_pro) {
    $titulo_plan = 'Disfrutás de todos los beneficios de Cursolandia Pro';
    $desc_plan = 'Tu cuenta tiene acceso sin límites para crear e inscribirte a cursos.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-contenedor">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-contenido">

        <div class="config-vista-wrapper">

            <header class="config-cabecera-principal">
                <h1>Configuración de la Cuenta</h1>
                <p>Gestioná tus datos personales, credenciales de acceso y tu suscripción a Cursolandia.</p>
            </header>

            <nav class="config-tabs-nav" aria-label="Secciones de configuración">
                <button type="button" class="config-tab-btn activo" data-tab="perfil" onclick="cambiarTab('perfil')">
                    <i class="bi bi-person-gear"></i> Perfil y Datos
                </button>
                <button type="button" class="config-tab-btn" data-tab="seguridad" onclick="cambiarTab('seguridad')">
                    <i class="bi bi-shield-lock"></i> Seguridad
                </button>
                <button type="button" class="config-tab-btn" data-tab="membresia" id="tab-btn-membresia" onclick="cambiarTab('membresia')">
                    <i class="bi bi-award"></i> Membresía y Plan
                </button>
            </nav>

            <?php if (!empty($_SESSION['exito'])): ?>
                <div class="alerta-flash exito">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alerta-flash error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

     
            <div class="config-tab-panel activo" id="panel-perfil">
                <form action="../controladores/perfil_controlador.php?accion=actualizar_perfil" method="POST" enctype="multipart/form-data" class="config-card">

                    <div class="config-card-cabecera">
                        <h3>Perfil y Datos Públicos</h3>
                        <p>Actualizá la información visible para la comunidad académica de Cursolandia.</p>
                    </div>

                    <div class="perfil-avatar-seccion">
                        <?php $avatar_actual_url = !empty($usuario['avatar']) ? (obtener_url_avatar($usuario['avatar']) . '?v=' . time()) : ''; ?>
                        <div class="perfil-avatar-preview" style="<?php if (!empty($avatar_actual_url)): ?>background-image: url('<?php echo htmlspecialchars($avatar_actual_url); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
                            <span id="avatar-iniciales" style="<?php if (!empty($avatar_actual_url)): ?>display: none;<?php endif; ?>"><?php echo $iniciales; ?></span>
                        </div>
                        <div class="perfil-avatar-info">
                            <strong class="config-label-titulo">Foto de perfil</strong>
                            <div class="avatar-botones-fila">
                                <label for="input-foto-perfil" class="btn-subir-avatar">
                                    <i class="bi bi-camera"></i> Subir foto
                                </label>
                                <input type="file" id="input-foto-perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="previsualizarFoto(this)">
                                <input type="hidden" name="eliminar_avatar" id="eliminar_avatar" value="0">
                                <button type="button" class="btn-quitar-foto" onclick="quitarFoto()">
                                    <i class="bi bi-trash3"></i> Quitar
                                </button>
                                <button type="submit" class="btn-primario btn-guardar-avatar-directo" id="btn-guardar-avatar-directo" style="display: none;">
                                    <i class="bi bi-check-lg"></i> Guardar foto
                                </button>
                            </div>
                            <div id="aviso-foto-seleccionada" class="aviso-foto-estado" style="display: none;"></div>
                            <span class="texto-ayuda">Formatos admitidos: JPG, PNG o WebP. Máx. 2 MB.</span>
                        </div>
                    </div>

                    <div class="config-seccion-bloque">
                        <span class="config-bloque-titulo"><i class="bi bi-person-badge"></i> Información Básica</span>
                        
                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="nombre">Nombre completo <span class="campo-obligatorio">*</span></label>
                                <input type="text" id="nombre" name="nombre" class="input-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>

                            <div class="campo-grupo">
                                <label for="username">Nombre de usuario</label>
                                <div class="input-con-prefijo">
                                    <span class="input-prefijo">@</span>
                                    <input type="text" id="username" name="username" class="input-control input-sin-borde-izq" value="<?php echo isset($usuario['username']) ? htmlspecialchars($usuario['username']) : 'alexgomez'; ?>" placeholder="usuario">
                                </div>
                            </div>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="email">Correo electrónico <span class="campo-obligatorio">*</span></label>
                                <input type="email" id="email" name="email" class="input-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>

                            <div class="campo-grupo">
                                <label for="titulo_profesional">Título u Ocupación</label>
                                <input type="text" id="titulo_profesional" name="titulo_profesional" class="input-control" value="<?php echo isset($usuario['titulo_profesional']) ? htmlspecialchars($usuario['titulo_profesional']) : ''; ?>" placeholder="Ej: Desarrollador Web Junior">
                            </div>
                        </div>
                    </div>

                    <div class="config-seccion-bloque">
                        <span class="config-bloque-titulo"><i class="bi bi-card-text"></i> Biografía y Habilidades</span>

                        <div class="campo-grupo">
                            <label for="antecedentes">Biografía / Acerca de ti</label>
                            <textarea id="antecedentes" name="antecedentes" class="input-control textarea-control" rows="3" placeholder="Contá tu formación, experiencia o temas de interés..."><?php echo htmlspecialchars($usuario['antecedentes']); ?></textarea>
                        </div>

                        <div class="campo-grupo">
                            <label for="intereses">Intereses y Tecnologías</label>
                            <input type="text" id="intereses" name="intereses" class="input-control" value="<?php echo htmlspecialchars($usuario['intereses']); ?>" placeholder="Ej: PHP, MySQL, Frontend, APIs" oninput="actualizarChips()">
                            <div class="chips-intereses" id="contenedor-chips">
                                <?php 
                                $tags = [];
                                if (!empty($usuario['intereses'])) {
                                    foreach (explode(',', $usuario['intereses']) as $pt) {
                                        $pt = trim($pt);
                                        if ($pt !== '') $tags[] = $pt;
                                    }
                                }
                                foreach ($tags as $tag): 
                                ?>
                                    <span class="chip-item">
                                        #<?php echo htmlspecialchars(ltrim($tag, '#')); ?>
                                        <button type="button" class="btn-borrar-chip" onclick="eliminarTag('<?php echo htmlspecialchars($tag); ?>')">×</button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="formulario-fila">
                            <div class="campo-grupo">
                                <label for="github"><i class="bi bi-github"></i> Perfil de GitHub</label>
                                <input type="text" id="github" name="github" class="input-control" value="<?php echo isset($usuario['github']) ? htmlspecialchars($usuario['github']) : ''; ?>" placeholder="github.com/usuario">
                            </div>

                            <div class="campo-grupo">
                                <label for="linkedin"><i class="bi bi-linkedin"></i> Perfil de LinkedIn</label>
                                <input type="text" id="linkedin" name="linkedin" class="input-control" value="<?php echo isset($usuario['linkedin']) ? htmlspecialchars($usuario['linkedin']) : ''; ?>" placeholder="linkedin.com/in/perfil">
                            </div>
                        </div>
                    </div>

                    <div class="config-acciones-pie">
                        <button type="submit" class="btn-config-guardar">
                            <i class="bi bi-check2"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>

    
            <div class="config-tab-panel" id="panel-seguridad">
                <form action="../controladores/perfil_controlador.php?accion=cambiar_password" method="POST" class="config-card">

                    <div class="config-card-cabecera">
                        <h3>Seguridad y Contraseña</h3>
                        <p>Mantené tu cuenta protegida renovando tu clave de acceso con regularidad.</p>
                    </div>

                    <div class="config-seguridad-wrap">
                        
                        <div class="campo-grupo">
                            <label for="pass_actual">Contraseña actual <span class="campo-obligatorio">*</span></label>
                            <input type="password" id="pass_actual" name="pass_actual" class="input-control" placeholder="••••••••" required>
                        </div>

                        <div class="campo-grupo">
                            <label for="pass_nueva">Nueva contraseña <span class="campo-obligatorio">*</span></label>
                            <input type="password" id="pass_nueva" name="pass_nueva" class="input-control" placeholder="••••••••" required>
                        </div>

                        <div class="campo-grupo">
                            <label for="pass_confirmar">Confirmar nueva contraseña <span class="campo-obligatorio">*</span></label>
                            <input type="password" id="pass_confirmar" name="pass_confirmar" class="input-control" placeholder="••••••••" required>
                        </div>

                        <div class="seguridad-tip-caja">
                            <i class="bi bi-shield-check"></i>
                            <div>
                                <strong>Recomendación para una clave segura</strong>
                                <p>Usá al menos 8 caracteres combinando letras mayúsculas, minúsculas, números y símbolos para evitar accesos no autorizados.</p>
                            </div>
                        </div>

                        <div class="config-acciones-pie pie-centrado config-pie-sin-borde">
                            <button type="submit" class="btn-config-guardar btn-guardar-full">
                                <i class="bi bi-shield-check"></i> Actualizar Contraseña
                            </button>
                        </div>

                    </div>

                </form>
            </div>

       
            <div class="config-tab-panel" id="panel-membresia">
                <div class="config-card" id="seccion-plan-pro">

                    <div class="config-card-cabecera">
                        <h3>Tu Suscripción a Cursolandia</h3>
                        <p>Consultá el estado de tu membresía y desbloqueá herramientas avanzadas para tu formación.</p>
                    </div>

                    <div class="estado-plan-tarjeta">
                        <div class="estado-plan-tarjeta-cabecera">
                            <?php if ($es_admin): ?>
                                <span class="sidebar-plan-badge plan-admin">
                                    Administrador
                                </span>
                            <?php else: ?>
                                <span class="sidebar-plan-badge <?php echo $es_pro ? 'plan-pro' : 'plan-gratuito'; ?>">
                                    <?php echo $es_pro ? 'Plan Pro Activo' : 'Plan Gratuito'; ?>
                                </span>
                            <?php endif; ?>
                            <span class="texto-ayuda">Membresía activa</span>
                        </div>
                        <h4><?php echo $titulo_plan; ?></h4>
                        <p class="texto-ayuda">
                            <?php echo $desc_plan; ?>
                        </p>

                        <ul class="lista-beneficios-grid">
                            <li><i class="bi bi-check-circle-fill"></i> Hasta 3 cursos creados simultáneos</li>
                            <li><i class="bi bi-check-circle-fill"></i> Hasta 3 inscripciones activas a la vez</li>
                            <li><i class="bi bi-check-circle-fill"></i> 5 archivos adjuntos por curso (máx 10 MB)</li>
                            <li><i class="bi bi-check-circle-fill"></i> Certificados oficiales de aprobación</li>
                        </ul>
                    </div>

                    <?php if (!$es_pro && !$es_admin): ?>
                    <div class="solicitar-pro-caja pro-caja-wrap">
                        <h4 class="pro-caja-titulo">
                            Pasar a Cursolandia Pro
                        </h4>
                        <p class="texto-ayuda">
                            Para acceder a cupos ilimitados y funciones avanzadas, realizá la transferencia y adjuntá tu comprobante acá:
                        </p>

                        <form action="../controladores/perfil_controlador.php?accion=notificar_pago_pro" method="POST" enctype="multipart/form-data">
                            <div class="campo-grupo">
                                <label for="comprobante">Comprobante de transferencia bancaria <span class="campo-obligatorio">*</span></label>
                                <input type="file" id="comprobante" name="comprobante" class="input-archivo-estilizado" accept=".pdf,image/*" required>
                                <span class="texto-ayuda">Formatos válidos: PDF, JPG, PNG.</span>
                            </div>

                            <div class="campo-grupo">
                                <label for="mensaje_pro">Nota o mensaje adicional (opcional)</label>
                                <input type="text" id="mensaje_pro" name="mensaje_pro" class="input-control" placeholder="Ej: Hola, adjunto comprobante de pago de mi suscripción Pro...">
                            </div>

                            <div class="config-acciones-pie">
                                <button type="submit" class="btn-config-guardar">
                                    <i class="bi bi-upload"></i> Enviar Comprobante Pro
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="suscripcion-activa-aviso">
                        <i class="bi bi-patch-check-fill"></i> Tu membresía Pro se encuentra activa y al día
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>

    </main>

</div>

<script>
function cambiarTab(tabId) {
    var botonesTabs = document.querySelectorAll('.config-tab-btn');
    for (var i = 0; i < botonesTabs.length; i++) {
        botonesTabs[i].classList.remove('activo');
    }

    var panelesTabs = document.querySelectorAll('.config-tab-panel');
    for (var j = 0; j < panelesTabs.length; j++) {
        panelesTabs[j].classList.remove('activo');
    }

    var btnActivo = document.querySelector('.config-tab-btn[data-tab="' + tabId + '"]');
    if (btnActivo) {
        btnActivo.classList.add('activo');
    }

    var panelActivo = document.getElementById('panel-' + tabId);
    if (panelActivo) {
        panelActivo.classList.add('activo');
    }

    if (history.replaceState) {
        history.replaceState(null, null, '#' + tabId);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash.toLowerCase();
    if (hash === '#seccion-plan-pro' || hash === '#membresia' || hash === '#plan') {
        cambiarTab('membresia');
    } else if (hash === '#seguridad') {
        cambiarTab('seguridad');
    } else if (hash === '#perfil') {
        cambiarTab('perfil');
    }
});

function previsualizarFoto(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen seleccionada supera los 2 MB permitidos. Por favor elegí una imagen más liviana.');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.querySelector('.perfil-avatar-preview');
            preview.style.backgroundImage = 'url(\'' + e.target.result + '\')';
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
            document.getElementById('avatar-iniciales').style.display = 'none';
            var eliminarInput = document.getElementById('eliminar_avatar');
            if (eliminarInput) eliminarInput.value = '0';

            var aviso = document.getElementById('aviso-foto-seleccionada');
            if (aviso) {
                aviso.className = 'aviso-foto-estado aviso-foto-exito';
                aviso.style.display = 'block';
                aviso.innerHTML = '<i class="bi bi-check-circle-fill"></i> Foto seleccionada. Recordá hacer clic en "Guardar foto" o "Guardar Cambios" para confirmar.';
            }
            var btnDirecto = document.getElementById('btn-guardar-avatar-directo');
            if (btnDirecto) btnDirecto.style.display = 'inline-flex';
        }
        reader.readAsDataURL(file);
    }
}

function quitarFoto() {
    var preview = document.querySelector('.perfil-avatar-preview');
    preview.style.backgroundImage = 'none';
    document.getElementById('avatar-iniciales').style.display = 'block';
    document.getElementById('input-foto-perfil').value = '';
    var eliminarInput = document.getElementById('eliminar_avatar');
    if (eliminarInput) eliminarInput.value = '1';

    var aviso = document.getElementById('aviso-foto-seleccionada');
    if (aviso) {
        aviso.className = 'aviso-foto-estado aviso-foto-alerta';
        aviso.style.display = 'block';
        aviso.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Foto marcada para quitar. Hacé clic en "Guardar Cambios" para confirmar la eliminación.';
    }
    var btnDirecto = document.getElementById('btn-guardar-avatar-directo');
    if (btnDirecto) btnDirecto.style.display = 'inline-flex';
}

function agregarInteres(tag) {
    var input = document.getElementById('intereses');
    var partes = input.value.split(',');
    var actual = [];
    var existe = false;
    for (var i = 0; i < partes.length; i++) {
        var t = partes[i].trim();
        if (t.length > 0) {
            actual.push(t);
            if (t.toLowerCase() === tag.toLowerCase()) {
                existe = true;
            }
        }
    }
    if (!existe) {
        actual.push(tag);
        input.value = actual.join(', ');
        actualizarChips();
    }
}

function eliminarTag(tag) {
    var input = document.getElementById('intereses');
    var partes = input.value.split(',');
    var actual = [];
    for (var i = 0; i < partes.length; i++) {
        var t = partes[i].trim();
        if (t.length > 0) {
            if (t.toLowerCase() !== tag.toLowerCase() && t.toLowerCase() !== ('#' + tag).toLowerCase()) {
                actual.push(t);
            }
        }
    }
    input.value = actual.join(', ');
    actualizarChips();
}

function actualizarChips() {
    var input = document.getElementById('intereses');
    var cont = document.getElementById('contenedor-chips');
    var partes = input.value.split(',');
    var html = '';
    for (var i = 0; i < partes.length; i++) {
        var t = partes[i].trim();
        if (t.length > 0) {
            var limpia = t.replace(/^#/, '');
            html += '<span class="chip-item">#' + limpia + ' <button type="button" class="btn-borrar-chip" onclick="eliminarTag(\'' + limpia + '\')">×</button></span>';
        }
    }
    cont.innerHTML = html;
}
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
