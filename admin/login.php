<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

// Si el sitio aún no se ha instalado, ir primero al instalador
if (!site_installed()) {
    header('Location: install.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!empty($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    if (admin_credentials_ok($u, $p)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso al Panel — AdminCP</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: linear-gradient(135deg, #2b0a14 0%, #4a1225 60%, #6b1d2f 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .login-card { background: #fff; border-radius: 16px; padding: 40px 36px; width: 100%; max-width: 380px; box-shadow: 0 25px 60px rgba(0,0,0,.4); text-align: center; }
    .login-card .logo { font-size: 2.6rem; margin-bottom: 6px; }
    .login-card h1 { font-size: 1.25rem; color: #8b263e; margin-bottom: 2px; }
    .login-card .sub { font-size: .78rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 26px; }
    label { display: block; text-align: left; font-size: .8rem; font-weight: 600; color: #475569; margin: 14px 0 6px; }
    input[type=text], input[type=password] { width: 100%; padding: 11px 14px; border: 1px solid #e3e8ee; border-radius: 8px; font-size: .92rem; background: #f8fafc; transition: all .2s; }
    input:focus { outline: none; border-color: #8b263e; background: #fff; box-shadow: 0 0 0 3px rgba(139,38,62,.12); }
    button { width: 100%; margin-top: 24px; padding: 12px; border: none; border-radius: 8px; cursor: pointer; background: #8b263e; color: #fff; font-size: .9rem; font-weight: 700; letter-spacing: 1px; transition: background .2s; }
    button:hover { background: #721e32; }
    .error { margin-top: 16px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: .8rem; padding: 10px; border-radius: 8px; }
    .hint { margin-top: 20px; font-size: .72rem; color: #94a3b8; }
</style>
</head>
<body>
    <form class="login-card" method="POST" action="">
        <div class="logo">❤️</div>
        <h1>AdminCP</h1>
        <div class="sub">Panel de Control</div>
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" autocomplete="username" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
        <button type="submit">Entrar al Panel</button>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    </form>
</body>
</html>

