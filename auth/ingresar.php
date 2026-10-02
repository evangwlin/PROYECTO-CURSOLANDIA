<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id']) || isset($_SESSION['usuario']['id'])) {
    header('Location: ../panel/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursolandia · Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/estilo.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">
        

        <div class="auth-banner">
            <div class="auth-banner-aura"></div>
            <div class="auth-banner-content">
                <div class="auth-banner-top">
                    <svg class="auth-asterisk" viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                        <path d="M12 2a1.5 1.5 0 0 1 1.5 1.5v17a1.5 1.5 0 0 1-3 0v-17A1.5 1.5 0 0 1 12 2z"/>
                        <path d="M2 12a1.5 1.5 0 0 1 1.5-1.5h17a1.5 1.5 0 0 1 0 3h-17A1.5 1.5 0 0 1 2 12z"/>
                        <path d="M4.93 4.93a1.5 1.5 0 0 1 2.12 0l12.02 12.02a1.5 1.5 0 0 1-2.12 2.12L4.93 7.05a1.5 1.5 0 0 1 0-2.12z"/>
                        <path d="M19.07 4.93a1.5 1.5 0 0 1 0 2.12L7.05 19.07a1.5 1.5 0 0 1-2.12-2.12L16.95 4.93a1.5 1.5 0 0 1 2.12 0z"/>
                    </svg>
                </div>
                <div class="auth-banner-bottom">
                    <span class="auth-eyebrow">Aprendé y enseñá</span>
                    <h2>Accedé a tu portal de cursos y aprendizaje continuo</h2>
                </div>
            </div>
        </div>


        <div class="auth-form-side">
            <div class="auth-header">
                <h1 class="auth-title">Iniciar sesión</h1>
                <p class="auth-subtitle">Accedé a tus cursos, notas y proyectos en un solo lugar.</p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alerta-flash error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['exito'])): ?>
                <div class="alerta-flash exito">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="../controladores/auth_controlador.php?accion=login" method="POST">
                <input type="hidden" name="accion" value="login">
                <div class="auth-field">
                    <label for="email">Tu correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                </div>

                <div class="auth-field">
                    <label for="password">Contraseña</label>
                    <div class="auth-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••••••" required>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword('password', this)" aria-label="Mostrar contraseña">
                            <svg class="icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-btn-submit">Ingresar</button>

                <div class="auth-divider">
                    <span>o continuar con</span>
                </div>

                <div class="auth-socials">
                    <button type="button" class="auth-btn-social" aria-label="Continuar con Google">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                            <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3 0-.8.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15.2c0 2.8.7 5.5 1.9 7.8l3.7-2.9z"/>
                            <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16.5C3.7 20.3 7.5 23.5 12 23.5z"/>
                        </svg>
                    </button>
                    <button type="button" class="auth-btn-social" aria-label="Continuar con Facebook">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </button>
                </div>

                <div class="auth-switch">
                    ¿No tenés una cuenta? <a href="registrarse.php">Registrate acá</a>
                </div>

                <div class="auth-back-link">
                    <a href="../index.php">← Volver al inicio</a>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('active');
    } else {
        input.type = 'password';
        btn.classList.remove('active');
    }
}
</script>
</body>
</html>
