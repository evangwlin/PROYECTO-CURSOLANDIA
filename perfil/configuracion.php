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

$usuario_id = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
if ($usuario_id <= 0) {
    $_SESSION['error'] = 'Debés iniciar sesión para acceder a tu configuración.';
    header('Location: ../auth/ingresar.php');
    exit;
}
$usuarioBD = null;
if ($usuario_id > 0) {
    $uid_seguro = intval($usuario_id);
    $res = mysqli_query($conexion, "SELECT * FROM Usuario WHERE id = $uid_seguro LIMIT 1");
    $usuarioBD = $res ? mysqli_fetch_assoc($res) : null;
}

if ($usuarioBD) {
    $usuario = [
        'nombre'             => trim(($usuarioBD['nombre'] ?? '') . ' ' . ($usuarioBD['apellido'] ?? '')),
        'username'           => !empty($usuarioBD['username']) ? $usuarioBD['username'] : strtolower(str_replace(' ', '', ($usuarioBD['nombre'] ?? '') . ($usuarioBD['apellido'] ?? ''))),
        'email'              => $usuarioBD['email'] ?? '',
        'titulo_profesional' => ($usuarioBD['rol'] === 'admin') ? 'Administrador' : ($usuarioBD['titulo_profesional'] ?? ''),
        'plan'               => $usuarioBD['plan'] ?? 'gratuito',
        'intereses'          => $usuarioBD['intereses'] ?? 'PHP, MySQL, Desarrollo Web',
        'antecedentes'       => $usuarioBD['biografia'] ?? '',
        'avatar'             => $usuarioBD['avatar'] ?? null,
        'fecha_vencimiento_pro' => $usuarioBD['fecha_vencimiento_pro'] ?? null,
        'github'             => '',
        'linkedin'           => ''
    ];
} else {
    if (!isset($_SESSION['usuario'])) {
        $_SESSION['usuario'] = [
            'nombre'             => 'Alex Gómez',
            'username'           => 'alexgomez',
            'email'              => 'alex@ejemplo.com',
            'titulo_profesional' => 'Desarrollador Web',
            'plan'               => 'gratuito',
            'intereses'          => 'PHP, MySQL, Desarrollo Web, APIs',
            'antecedentes'       => 'Desarrollador web con interés en backend, modelado relacional y arquitectura de software.',
            'github'             => 'github.com/alexgomez',
            'linkedin'           => 'linkedin.com/in/alexgomez'
        ];
    }
    $usuario = $_SESSION['usuario'];
}

$es_pro = ($usuario_id > 0) ? usuario_es_pro($usuario_id) : ($usuario['plan'] === 'pro');
$es_admin = (($usuarioBD['rol'] ?? $_SESSION['usuario_rol'] ?? '') === 'admin');
$iniciales = strtoupper(substr($usuario['nombre'], 0, 2));

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
                <div class="alerta-flash exito" style="margin-bottom: 20px;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alerta-flash error" style="margin-bottom: 20px;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- tab perfil -->
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
                            <strong style="font-size: 0.95rem; color: var(--color-text-main);">Foto de perfil</strong>
                            <div class="avatar-botones-fila">
                                <label for="input-foto-perfil" class="btn-subir-avatar">
                                    <i class="bi bi-camera"></i> Subir foto
                                </label>
                                <input type="file" id="input-foto-perfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="previsualizarFoto(this)">
                                <input type="hidden" name="eliminar_avatar" id="eliminar_avatar" value="0">
                                <button type="button" class="btn-quitar-foto" onclick="quitarFoto()">
                                    <i class="bi bi-trash3"></i> Quitar
                                </button>
                                <button type="submit" class="btn-primario" id="btn-guardar-avatar-directo" style="display: none; padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                                    <i class="bi bi-check-lg"></i> Guardar foto
                                </button>
                            </div>
                            <div id="aviso-foto-seleccionada" style="display: none; margin-top: 8px; font-size: 0.82rem; padding: 6px 12px; border-radius: 8px; font-weight: 600;"></div>
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
                                    <input type="text" id="username" name="username" class="input-control input-sin-borde-izq" value="<?php echo htmlspecialchars($usuario['username'] ?? 'alexgomez'); ?>" placeholder="usuario">
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
                                <input type="text" id="titulo_profesional" name="titulo_profesional" class="input-control" value="<?php echo htmlspecialchars($usuario['titulo_profesional'] ?? ''); ?>" placeholder="Ej: Desarrollador Web Junior">
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
                                $tags = array_filter(array_map('trim', explode(',', $usuario['intereses'])));
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
                                <input type="text" id="github" name="github" class="input-control" value="<?php echo htmlspecialchars($usuario['github'] ?? ''); ?>" placeholder="github.com/usuario">
                            </div>

                            <div class="campo-grupo">
                                <label for="linkedin"><i class="bi bi-linkedin"></i> Perfil de LinkedIn</label>
                                <input type="text" id="linkedin" name="linkedin" class="input-control" value="<?php echo htmlspecialchars($usuario['linkedin'] ?? ''); ?>" placeholder="linkedin.com/in/perfil">
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

            <!-- tab seguridad -->
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

                        <div class="config-acciones-pie pie-centrado" style="border-top: none; margin-top: 10px; padding-top: 0;">
                            <button type="submit" class="btn-config-guardar" style="width: 100%; max-width: 360px;">
                                <i class="bi bi-shield-check"></i> Actualizar Contraseña
                            </button>
                        </div>

                    </div>

                </form>
            </div>

            <!-- tab membresia -->
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
                        <h4><?php echo $es_admin ? 'Cuenta con permisos de Administrador' : ($es_pro ? 'Disfrutás de todos los beneficios de Cursolandia Pro' : 'Plan Gratuito de Cursolandia'); ?></h4>
                        <p class="texto-ayuda" style="margin-top: 4px;">
                            <?php echo $es_admin ? 'Tu cuenta tiene permisos de superadministrador para gestionar cursos, usuarios y solicitudes.' : ($es_pro ? 'Tu cuenta tiene acceso sin límites para crear e inscribirte a cursos.' : 'Tenés acceso estándar para aprender y participar de la comunidad.'); ?>
                        </p>

                        <ul class="lista-beneficios-grid">
                            <li><i class="bi bi-check-circle-fill"></i> Hasta 3 cursos creados simultáneos</li>
                            <li><i class="bi bi-check-circle-fill"></i> Hasta 3 inscripciones activas a la vez</li>
                            <li><i class="bi bi-check-circle-fill"></i> 5 archivos adjuntos por curso (máx 10 MB)</li>
                            <li><i class="bi bi-check-circle-fill"></i> Certificados oficiales de aprobación</li>
                        </ul>
                    </div>

                    <?php if (!$es_pro && !$es_admin): ?>
                    <div class="solicitar-pro-caja" style="margin-top: 24px; padding: 26px; border-radius: 14px; background: #fafaf9; border: 1px solid var(--border-color);">
                        <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 4px;">
                            Pasar a Cursolandia Pro
                        </h4>
                        <p class="texto-ayuda" style="margin-bottom: 18px;">
                            Para acceder a cupos ilimitados y funciones avanzadas, realizá la transferencia y adjuntá tu comprobante acá:
                        </p>

                        <form action="../controladores/perfil_controlador.php?accion=notificar_pago_pro" method="POST" enctype="multipart/form-data">
                            <div class="campo-grupo">
                                <label for="comprobante">Comprobante de transferencia bancaria <span class="campo-obligatorio">*</span></label>
                                <input type="file" id="comprobante" name="comprobante" class="input-archivo-estilizado" accept=".pdf,image/*" required>
                                <span class="texto-ayuda">Formatos válidos: PDF, JPG, PNG.</span>
                            </div>

                            <div class="campo-grupo" style="margin-top: 14px;">
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
                    <div class="suscripcion-activa-aviso" style="margin-top: 18px;">
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
    document.querySelectorAll('.config-tab-btn').forEach(btn => {
        btn.classList.remove('activo');
    });

    document.querySelectorAll('.config-tab-panel').forEach(panel => {
        panel.classList.remove('activo');
    });

    const btnActivo = document.querySelector(`.config-tab-btn[data-tab="${tabId}"]`);
    if (btnActivo) {
        btnActivo.classList.add('activo');
    }

    const panelActivo = document.getElementById(`panel-${tabId}`);
    if (panelActivo) {
        panelActivo.classList.add('activo');
    }

    if (history.replaceState) {
        history.replaceState(null, null, `#${tabId}`);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.toLowerCase();
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
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen seleccionada supera los 2 MB permitidos. Por favor elegí una imagen más liviana.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.perfil-avatar-preview');
            preview.style.backgroundImage = `url('${e.target.result}')`;
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
            document.getElementById('avatar-iniciales').style.display = 'none';
            const eliminarInput = document.getElementById('eliminar_avatar');
            if (eliminarInput) eliminarInput.value = '0';

            const aviso = document.getElementById('aviso-foto-seleccionada');
            if (aviso) {
                aviso.style.display = 'block';
                aviso.style.color = '#15803d';
                aviso.style.background = '#dcfce7';
                aviso.innerHTML = '<i class="bi bi-check-circle-fill"></i> Foto seleccionada. Recordá hacer clic en "Guardar foto" o "Guardar Cambios" para confirmar.';
            }
            const btnDirecto = document.getElementById('btn-guardar-avatar-directo');
            if (btnDirecto) btnDirecto.style.display = 'inline-flex';
        }
        reader.readAsDataURL(file);
    }
}

function quitarFoto() {
    const preview = document.querySelector('.perfil-avatar-preview');
    preview.style.backgroundImage = 'none';
    document.getElementById('avatar-iniciales').style.display = 'block';
    document.getElementById('input-foto-perfil').value = '';
    const eliminarInput = document.getElementById('eliminar_avatar');
    if (eliminarInput) eliminarInput.value = '1';

    const aviso = document.getElementById('aviso-foto-seleccionada');
    if (aviso) {
        aviso.style.display = 'block';
        aviso.style.color = '#b45309';
        aviso.style.background = '#fef3c7';
        aviso.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Foto marcada para quitar. Hacé clic en "Guardar Cambios" para confirmar la eliminación.';
    }
    const btnDirecto = document.getElementById('btn-guardar-avatar-directo');
    if (btnDirecto) btnDirecto.style.display = 'inline-flex';
}

function agregarInteres(tag) {
    const input = document.getElementById('intereses');
    let actual = input.value.split(',').map(t => t.trim()).filter(t => t.length > 0);
    if (!actual.includes(tag)) {
        actual.push(tag);
        input.value = actual.join(', ');
        actualizarChips();
    }
}

function eliminarTag(tag) {
    const input = document.getElementById('intereses');
    let actual = input.value.split(',').map(t => t.trim()).filter(t => t.length > 0);
    actual = actual.filter(t => t.toLowerCase() !== tag.toLowerCase() && t.toLowerCase() !== ('#' + tag).toLowerCase());
    input.value = actual.join(', ');
    actualizarChips();
}

function actualizarChips() {
    const input = document.getElementById('intereses');
    const cont = document.getElementById('contenedor-chips');
    const tags = input.value.split(',').map(t => t.trim()).filter(t => t.length > 0);
    cont.innerHTML = tags.map(t => {
        const limpia = t.replace(/^#/, '');
        return `<span class="chip-item">#${limpia} <button type="button" class="btn-borrar-chip" onclick="eliminarTag('${limpia}')">×</button></span>`;
    }).join('');
}
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
