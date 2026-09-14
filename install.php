<?php
// ============================================================
//  install.php — Asistente de Instalación y Desinstalación estilo MyBB
//  - Estructura con Sidebar y Pasos interactivos estilo MyBB
//  - Bloqueo de pasos hasta proceder con el botón siguiente (sin alerts)
//  - Iconos en todos los pasos, campos y resúmenes
//  - Separación de Base de Datos en data/db_schema.php y data/default_data.php
//  - Bloqueo mediante archivos data/lock y data/installer con contenido "1"
//  - Contraseña de admin de 5 caracteres mínimo
//  - Asistente de Desinstalación paso a paso
// ============================================================
require_once __DIR__ . '/config.php';
session_start();

$alreadyInstalled = site_installed();
$isAdminLogged    = !empty($_SESSION['admin_logged']);

// ---------- Endpoint AJAX: Probar conexión MySQL en el paso 3 ----------
if (isset($_GET['action']) && $_GET['action'] === 'test_db') {
    header('Content-Type: application/json; charset=utf-8');
    $host = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $name = trim((string)($_POST['db_name'] ?? 'romantic_app'));
    $user = trim((string)($_POST['db_user'] ?? 'root'));
    $pass = (string)($_POST['db_pass'] ?? '');

    if ($host === '' || $name === '' || $user === '') {
        echo json_encode(['success' => false, 'error' => 'Debes completar el host, base de datos y usuario.']);
        exit;
    }

    try {
        $pdoTest = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo json_encode(['success' => true, 'message' => '¡Conexión con el servidor MySQL exitosa!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    }
    exit;
}

// ---------- Endpoint AJAX: Desinstalador paso a paso ----------
if (isset($_GET['action']) && $_GET['action'] === 'process_uninstall') {
    header('Content-Type: application/json; charset=utf-8');
    $pass         = trim((string)($_POST['admin_pass'] ?? ''));
    $dropTables   = !empty($_POST['drop_tables']);
    $removeConfig = !empty($_POST['remove_config']);
    $removeUploads= !empty($_POST['remove_uploads']);

    $db = get_db();
    $authenticated = false;

    if ($isAdminLogged) {
        $authenticated = true;
    } elseif ($db) {
        try {
            $stmt = $db->query("SELECT password_hash FROM admin_users ORDER BY id ASC LIMIT 1");
            $hash = $stmt ? $stmt->fetchColumn() : null;
            if ($hash && password_verify($pass, $hash)) {
                $authenticated = true;
            }
        } catch (Exception $e) {}
    } else {
        // Si no hay conexión a base de datos, permitir limpiar archivos de bloqueo
        $authenticated = true;
    }

    if (!$authenticated) {
        echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
        exit;
    }

    try {
        // 1. Eliminar tablas
        if ($dropTables && $db) {
            $tables = ['settings', 'ui_media', 'letter_paragraphs', 'reasons', 'timeline_chapters', 'wishes', 'admin_users'];
            foreach ($tables as $t) {
                try { $db->exec("DROP TABLE IF EXISTS `{$t}`"); } catch (Exception $e) {}
            }
        }

        // 2. Eliminar uploads si se solicitó
        if ($removeUploads) {
            foreach ([UPLOAD_IMG_DIR, UPLOAD_AUD_DIR] as $dir) {
                if (is_dir($dir)) {
                    foreach (glob($dir . '*') as $file) {
                        if (is_file($file)) @unlink($file);
                    }
                }
            }
        }

        // 3. Eliminar archivos de bloqueo (data/lock, data/installer) y db_config.php
        if ($removeConfig) {
            unlock_installer();
            if (file_exists(DB_CONFIG_FILE)) @unlink(DB_CONFIG_FILE);
        }

        $_SESSION = [];
        if (session_id()) session_destroy();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error durante la desinstalación: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// VISTA: ASISTENTE DE DESINSTALACIÓN (SI YA ESTÁ INSTALADO)
// ============================================================
if ($alreadyInstalled && empty($_GET['force'])) {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desinstalador — MyBB Installer</title>
    <style>
        :root {
            --p: #8b263e; --p-light: #f7ede2; --p-hover: #721e32;
            --bg: #f4f5f7; --card-bg: #ffffff; --border: #e3e8ee;
            --text-dark: #1e293b; --text-muted: #64748b;
            --danger: #ef4444; --danger-bg: #fef2f2;
            --ok: #16a34a; --ok-bg: #f0fdf4;
            --sidebar-w: 280px; --topbar-h: 56px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
               background: var(--bg); color: var(--text-dark); min-height: 100vh; line-height: 1.5; }
        .topbar { position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h);
                  background: linear-gradient(90deg, #2b0a14 0%, #4a1225 70%, #6b1d2f 100%);
                  display: flex; align-items: center; justify-content: space-between; padding: 0 24px; z-index: 200; box-shadow: 0 2px 12px rgba(0,0,0,.25); }
        .topbar .brand { color: #fff; font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 9px; }
        .topbar .brand .badge { background: #b91c1c; padding: 3px 9px; border-radius: 6px; font-size: .72rem; letter-spacing: 1px; font-weight: 800; }
        .topbar .version { color: #f3d9de; font-size: .82rem; font-weight: 500; }
        
        aside.sidebar { width: var(--sidebar-w); background: #ffffff; border-right: 1px solid var(--border);
                        position: fixed; top: var(--topbar-h); bottom: 0; left: 0; overflow-y: auto; z-index: 100; }
        .wizard-steps { list-style: none; padding: 22px 14px; display: flex; flex-direction: column; gap: 8px; }
        .step-item { width: 100%; border: 1px solid transparent; background: transparent; padding: 12px 14px; border-radius: 9px;
                     display: flex; align-items: center; gap: 12px; text-align: left; font-size: .88rem; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all .2s; }
        .step-item.active { background: #b91c1c; color: #fff; box-shadow: 0 4px 12px rgba(185,28,28,.25); }
        .step-item.done { color: var(--ok); }
        .step-item.done .step-icon { background: var(--ok-bg); color: var(--ok); border-color: var(--ok); }
        .step-item.locked { opacity: 0.5; cursor: not-allowed; }
        .step-icon { width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: 1px solid var(--border);
                     display: flex; align-items: center; justify-content: center; font-size: .88rem; font-weight: 700; flex-shrink: 0; }
        .step-item.active .step-icon { background: #fff; color: #b91c1c; border-color: #fff; }

        main.content { margin-left: var(--sidebar-w); padding: calc(var(--topbar-h) + 26px) 44px 60px 44px; max-width: 900px; }
        .panel { background: var(--card-bg); padding: 36px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,.04); margin-bottom: 24px; }
        .panel h1 { font-size: 1.5rem; color: #b91c1c; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .panel p.desc { color: var(--text-muted); font-size: .92rem; margin-bottom: 24px; line-height: 1.6; }
        
        .tab-step { display: none; }
        .tab-step.active { display: block; animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .step-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); }
        button.btn-next { background: #b91c1c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: .92rem; display: inline-flex; align-items: center; gap: 8px; }
        button.btn-next:hover { background: #991b1b; }
        button.btn-prev { background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border); padding: 12px 22px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .88rem; }
        button.btn-prev:hover { background: #e2e8f0; }

        .errors-box { background: var(--danger-bg); border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 10px; font-size: .88rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        label { display: block; font-size: .86rem; font-weight: 600; margin: 16px 0 6px; color: #334155; }
        input[type="password"] { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: .92rem; background: #f8fafc; }
        .checkbox-group { display: flex; flex-direction: column; gap: 12px; margin: 20px 0; }
        .checkbox-item { display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px; font-size: .9rem; cursor: pointer; }
        .checkbox-item input { width: 18px; height: 18px; cursor: pointer; }

        @media (max-width: 820px) {
            aside.sidebar { width: 100%; position: relative; top: 0; border-right: none; }
            main.content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand"><span>❤️</span> Propuesta Romántica <span class="badge">DESINSTALADOR</span></div>
        <div class="version">🔒 Sitio Instalado y Bloqueado</div>
    </div>

    <aside class="sidebar">
        <ul class="wizard-steps">
            <li>
                <button type="button" class="step-item active" data-step="1" onclick="uninstStepClick(1)">
                    <span class="step-icon" id="uninst-icon-1">🔒</span>
                    <span>1. Estado y Acceso</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="2" onclick="uninstStepClick(2)">
                    <span class="step-icon" id="uninst-icon-2">🔒</span>
                    <span>2. Opciones</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="3" onclick="uninstStepClick(3)">
                    <span class="step-icon" id="uninst-icon-3">🔒</span>
                    <span>3. Confirmar</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="4" onclick="uninstStepClick(4)">
                    <span class="step-icon" id="uninst-icon-4">🔒</span>
                    <span>4. Finalizado</span>
                </button>
            </li>
        </ul>
    </aside>

    <main class="content">
        <div id="uninst_inline_error" class="errors-box" style="display:none;"></div>

        <!-- PASO 1: ESTADO Y AUTENTICACIÓN -->
        <div class="tab-step active" id="uninst-step-1">
            <div class="panel">
                <h1>🔒 El sitio ya está instalado</h1>
                <p class="desc">
                    El asistente se encuentra protegido por los archivos de bloqueo (<code>data/lock</code> y <code>data/installer</code>) para evitar sobrescribir la base de datos existente.
                </p>

                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;line-height:1.7;font-size:.88rem;">
                    <h3 style="color:#b91c1c;margin-bottom:8px;">¿Qué deseas hacer?</h3>
                    • <b>Administrar tu web</b>: Accede directamente al <a href="admin/index.php" style="color:var(--p);font-weight:700;">Panel de Administración ⚙️</a>.<br>
                    • <b>Reinstalar desde cero</b>: Completa los pasos de este asistente de desinstalación para reiniciar la base de datos y desbloquear el instalador.<br>
                    • <b>Desbloqueo manual</b>: También puedes borrar manualmente los archivos <code>data/lock</code> y <code>data/installer</code> en tu servidor.
                </div>

                <?php if (!$isAdminLogged): ?>
                    <label for="uninst_admin_pass">🔑 Introduce la contraseña de administrador para continuar:</label>
                    <input type="password" id="uninst_admin_pass" placeholder="Contraseña de admin actual" required>
                <?php else: ?>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:8px;font-size:.88rem;font-weight:600;margin-bottom:15px;">
                        ✓ Has iniciado sesión como administrador. No necesitas ingresar la contraseña.
                    </div>
                <?php endif; ?>

                <div class="step-actions">
                    <a href="admin/index.php" style="background:#334155;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;font-size:.88rem;">⚙️ Ir al Panel Admin</a>
                    <button type="button" class="btn-next" onclick="uninstAdvance(1)">Continuar a Opciones ➔</button>
                </div>
            </div>
        </div>

        <!-- PASO 2: OPCIONES -->
        <div class="tab-step" id="uninst-step-2">
            <div class="panel">
                <h1>⚙️ Opciones de Desinstalación</h1>
                <p class="desc">Selecciona los componentes que deseas eliminar y limpiar de tu servidor:</p>

                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="uninst_opt_tables" checked>
                        <div>
                            <b>🗄️ Eliminar tablas de MySQL</b>
                            <div style="font-size:.78rem;color:#64748b;">Elimina las tablas <code>settings</code>, <code>ui_media</code>, <code>letter_paragraphs</code>, <code>reasons</code>, <code>timeline_chapters</code>, <code>wishes</code>, <code>admin_users</code>.</div>
                        </div>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" id="uninst_opt_config" checked>
                        <div>
                            <b>🗑️ Eliminar configuración y archivos de bloqueo</b>
                            <div style="font-size:.78rem;color:#64748b;">Borra <code>data/db_config.php</code>, <code>data/lock</code> y <code>data/installer</code>.</div>
                        </div>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" id="uninst_opt_uploads">
                        <div>
                            <b>📁 Vaciar carpeta de archivos subidos (uploads/)</b>
                            <div style="font-size:.78rem;color:#64748b;">Elimina las imágenes y audios guardados en <code>uploads/images/</code> y <code>uploads/audios/</code>.</div>
                        </div>
                    </label>
                </div>

                <div class="step-actions">
                    <button type="button" class="btn-prev" onclick="uninstGoTo(1)">⬅ Anterior</button>
                    <button type="button" class="btn-next" onclick="uninstAdvance(2)">Revisar y Confirmar ➔</button>
                </div>
            </div>
        </div>

        <!-- PASO 3: CONFIRMACIÓN -->
        <div class="tab-step" id="uninst-step-3">
            <div class="panel">
                <h1>⚠️ Confirmación Final de Desinstalación</h1>
                <p class="desc">Revisa el resumen de las acciones que se ejecutarán en el servidor:</p>

                <div style="background:#fff8f8;border:1px solid #fecaca;border-radius:12px;padding:22px;margin-bottom:20px;line-height:1.8;font-size:.9rem;">
                    <h3 style="color:#b91c1c;margin-bottom:10px;">📋 Acciones a ejecutar:</h3>
                    • <span id="summary_uninst_tables"></span><br>
                    • <span id="summary_uninst_config"></span><br>
                    • <span id="summary_uninst_uploads"></span><br>
                    • Cerrar todas las sesiones activas del administrador.
                </div>

                <div class="step-actions">
                    <button type="button" class="btn-prev" onclick="uninstGoTo(2)">⬅ Anterior</button>
                    <button type="button" class="btn-next" onclick="executeUninstall()">🗑️ Ejecutar Desinstalación</button>
                </div>
            </div>
        </div>

        <!-- PASO 4: ÉXITO -->
        <div class="tab-step" id="uninst-step-4">
            <div class="panel" style="text-align:center;padding:44px 20px;">
                <div style="font-size:3.5rem;margin-bottom:12px;">🎉</div>
                <h1 style="color:var(--ok);justify-content:center;">¡Desinstalación Completada!</h1>
                <p class="desc" style="max-width:500px;margin:0 auto 26px auto;">
                    Los datos han sido eliminados correctamente y el instalador se encuentra ahora completamente desbloqueado y listo para una instalación limpia.
                </p>
                <a href="install.php" style="background:var(--p);color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
                    🚀 Iniciar Nueva Instalación
                </a>
            </div>
        </div>
    </main>

    <script>
        let uninstCurrentStep = 1;
        let uninstMaxUnlocked = 1;
        const uninstIcons = { 1: '🔒', 2: '⚙️', 3: '⚠️', 4: '🎉' };

        function showUninstError(msg) {
            const el = document.getElementById('uninst_inline_error');
            if (!el) return;
            if (msg) {
                el.innerHTML = '⚠️ ' + msg;
                el.style.display = 'flex';
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                el.style.display = 'none';
            }
        }

        function uninstStepClick(step) {
            if (step > uninstMaxUnlocked) {
                showUninstError('Debes completar el paso actual para continuar.');
                return;
            }
            uninstGoTo(step);
        }

        function uninstAdvance(fromStep) {
            showUninstError(null);
            if (fromStep === 1) {
                <?php if (!$isAdminLogged): ?>
                const p = document.getElementById('uninst_admin_pass').value.trim();
                if (!p) {
                    showUninstError('Debes ingresar tu contraseña de administrador para continuar.');
                    return;
                }
                <?php endif; ?>
                uninstUnlockAndGo(2);
            } else if (fromStep === 2) {
                const dropT = document.getElementById('uninst_opt_tables').checked;
                const remC = document.getElementById('uninst_opt_config').checked;
                const remU = document.getElementById('uninst_opt_uploads').checked;
                if (!dropT && !remC && !remU) {
                    showUninstError('Debes seleccionar al menos una opción para desinstalar.');
                    return;
                }
                document.getElementById('summary_uninst_tables').textContent = dropT ? 'Se eliminarán todas las tablas en MySQL' : 'Se conservarán las tablas en MySQL';
                document.getElementById('summary_uninst_config').textContent = remC ? 'Se borrarán data/lock, data/installer y data/db_config.php' : 'Se conservarán los archivos de configuración';
                document.getElementById('summary_uninst_uploads').textContent = remU ? 'Se vaciarán las imágenes y audios de uploads/' : 'Se conservarán los archivos subidos';
                uninstUnlockAndGo(3);
            }
        }

        function uninstUnlockAndGo(step) {
            if (step > uninstMaxUnlocked) uninstMaxUnlocked = step;
            uninstGoTo(step);
        }

        function uninstGoTo(step) {
            uninstCurrentStep = step;
            document.querySelectorAll('.tab-step').forEach(t => t.classList.remove('active'));
            const target = document.getElementById('uninst-step-' + step);
            if (target) target.classList.add('active');

            document.querySelectorAll('.step-item').forEach(item => {
                const s = parseInt(item.getAttribute('data-step'), 10);
                const icon = document.getElementById('uninst-icon-' + s);
                item.classList.remove('active', 'done', 'locked');
                if (s === uninstCurrentStep) {
                    item.classList.add('active');
                    if (icon) icon.textContent = uninstIcons[s];
                } else if (s < uninstCurrentStep) {
                    item.classList.add('done');
                    if (icon) icon.textContent = '✓';
                } else if (s <= uninstMaxUnlocked) {
                    if (icon) icon.textContent = uninstIcons[s];
                } else {
                    item.classList.add('locked');
                    if (icon) icon.textContent = '🔒';
                }
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function executeUninstall() {
            showUninstError(null);
            const passEl = document.getElementById('uninst_admin_pass');
            const pass = passEl ? passEl.value : '';

            const formData = new FormData();
            formData.append('admin_pass', pass);
            formData.append('drop_tables', document.getElementById('uninst_opt_tables').checked ? '1' : '');
            formData.append('remove_config', document.getElementById('uninst_opt_config').checked ? '1' : '');
            formData.append('remove_uploads', document.getElementById('uninst_opt_uploads').checked ? '1' : '');

            try {
                const res = await fetch('install.php?action=process_uninstall', { method: 'POST', body: formData });
                const d = await res.json();
                if (d.success) {
                    uninstUnlockAndGo(4);
                } else {
                    showUninstError(d.error || 'Error al ejecutar la desinstalación.');
                }
            } catch (e) {
                showUninstError('Error de conexión al ejecutar la desinstalación.');
            }
        }
    </script>
</body>
</html>
    <?php
    exit;
}

// ============================================================
// VISTA: ASISTENTE DE INSTALACIÓN (ESTILO MYBB)
// ============================================================

// ---------- Comprobaciones del entorno ----------
$checks = [
    ['label' => 'Versión de PHP 7.4 o superior (Actual: ' . PHP_VERSION . ')', 'ok' => version_compare(PHP_VERSION, '7.4.0', '>=')],
    ['label' => 'Extensión PDO MySQL instalada',                              'ok' => extension_loaded('pdo_mysql')],
    ['label' => 'Función password_hash() disponible',                         'ok' => function_exists('password_hash')],
    ['label' => 'Carpeta data/ con permiso de escritura',                     'ok' => (is_dir(__DIR__ . '/data') ? is_writable(__DIR__ . '/data') : @mkdir(__DIR__ . '/data', 0775, true))],
    ['label' => 'Carpeta uploads/images/ con permiso de escritura',           'ok' => (is_dir(UPLOAD_IMG_DIR) ? is_writable(UPLOAD_IMG_DIR) : @mkdir(UPLOAD_IMG_DIR, 0775, true))],
    ['label' => 'Carpeta uploads/audios/ con permiso de escritura',           'ok' => (is_dir(UPLOAD_AUD_DIR) ? is_writable(UPLOAD_AUD_DIR) : @mkdir(UPLOAD_AUD_DIR, 0775, true))],
    ['label' => 'Carpeta languages/ disponible y editable',                   'ok' => is_dir(LANG_DIR) && is_writable(LANG_DIR)],
];
$allOk = !in_array(false, array_column($checks, 'ok'), true);

$availableLangs = get_available_languages();

$prev = [
    'db_host'     => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
    'db_name'     => defined('DB_NAME') ? DB_NAME : 'romantic_app',
    'db_user'     => defined('DB_USER') ? DB_USER : 'root',
    'db_pass'     => defined('DB_PASS') ? DB_PASS : '',
    'user'        => 'admin',
    'site_lang'   => 'es',
    'partner_one' => 'Ella',
    'partner_two' => 'Él',
    'start_date'  => '',
    'date_locale' => 'es-ES',
];

$errors = [];
$done   = false;

// ---------- Procesar instalación ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allOk && empty($_POST['action'])) {
    $dbHost      = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $dbName      = trim((string)($_POST['db_name'] ?? 'romantic_app'));
    $dbUser      = trim((string)($_POST['db_user'] ?? 'root'));
    $dbPass      = (string)($_POST['db_pass'] ?? '');

    $user        = trim((string)($_POST['admin_user'] ?? 'admin'));
    $pass        = (string)($_POST['admin_pass'] ?? '');
    $pass2       = (string)($_POST['admin_pass2'] ?? '');
    
    $siteLang    = trim((string)($_POST['site_lang'] ?? 'es'));
    if (!array_key_exists($siteLang, $availableLangs)) $siteLang = 'es';
    $langData    = load_language($siteLang);

    $partnerOne  = trim((string)($_POST['partner_one'] ?? 'Ella'));
    $partnerTwo  = trim((string)($_POST['partner_two'] ?? 'Él'));
    $startDate   = trim((string)($_POST['start_date'] ?? ''));
    $locale      = valid_locale($_POST['date_locale'] ?? ($langData['date_locale'] ?? 'es-ES'));

    if ($dbHost === '') $errors[] = 'El host de la base de datos es obligatorio.';
    if ($dbName === '') $errors[] = 'El nombre de la base de datos es obligatorio.';
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $dbName)) $errors[] = 'El nombre de la base de datos contiene caracteres no válidos.';
    if ($dbUser === '') $errors[] = 'El usuario de la base de datos es obligatorio.';

    if (mb_strlen($user) < 3) $errors[] = 'El usuario de administrador debe tener al menos 3 caracteres.';
    if (!preg_match('/^[\w.\-]+$/u', $user)) $errors[] = 'El usuario solo puede contener letras, números, guiones, puntos y guiones bajos.';
    
    // Contraseña mínimo 5 caracteres (estilo MyBB)
    if (strlen($pass) < 5) $errors[] = 'La contraseña debe tener al menos 5 caracteres.';
    if ($pass !== $pass2)  $errors[] = 'Las contraseñas no coinciden.';

    if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $startDate)) {
        $errors[] = 'La fecha del contador no tiene un formato válido.';
    }

    if (!$errors) {
        try {
            // 1. Conectar a MySQL Server para crear la BD si no existe
            $pdoServer = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            unset($pdoServer);

            // 2. Conectar a la BD seleccionada
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // 3. Crear tablas desde data/db_schema.php (Separación modular estilo MyBB)
            $schemaFile = __DIR__ . '/data/db_schema.php';
            if (file_exists($schemaFile)) {
                $tableQueries = include $schemaFile;
                if (is_array($tableQueries)) {
                    foreach ($tableQueries as $query) {
                        $pdo->exec($query);
                    }
                }
            }

            // 4. Guardar credenciales de administrador (mínimo 5 caracteres)
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO `admin_users` (`username`, `password_hash`) VALUES (?, ?)");
            $stmtUser->execute([$user, $hash]);

            // 5. Poblar datos por defecto desde data/default_data.php
            $defaultDataFile = __DIR__ . '/data/default_data.php';
            $defaultData = file_exists($defaultDataFile) ? include $defaultDataFile : [];

            // Settings
            $stmtSet = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
            $initSettings = [
                'partner_one'       => $partnerOne,
                'partner_two'       => $partnerTwo,
                'start_date'        => $startDate,
                'proposal_accepted' => '0',
                'site_lang'         => $siteLang,
                'date_locale'       => $locale,
                'state_version'     => (string)time()
            ];
            foreach ($initSettings as $k => $v) {
                $stmtSet->execute([$k, (string)$v]);
            }

            // UI Media
            $stmtMedia = $pdo->prepare("INSERT INTO `ui_media` (`media_key`, `media_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `media_value` = VALUES(`media_value`)");
            $defaultMedia = is_array($defaultData['ui_media'] ?? null) ? $defaultData['ui_media'] : [
                'cover_image_url'  => DEFAULT_COVER_IMAGE,
                'second_image_url' => DEFAULT_SECOND_IMAGE,
                'bg_music_url'     => DEFAULT_MUSIC_URL
            ];
            foreach ($defaultMedia as $k => $v) {
                $stmtMedia->execute([$k, (string)$v]);
            }

            // Carta
            if (!empty($defaultData['letter_paragraphs'])) {
                $stmtLetter = $pdo->prepare("INSERT INTO `letter_paragraphs` (`paragraph_order`, `content`) VALUES (?, ?)");
                $order = 1;
                foreach ($defaultData['letter_paragraphs'] as $p) {
                    $stmtLetter->execute([$order++, $p]);
                }
            }

            // Razones
            if (!empty($defaultData['reasons'])) {
                $stmtReason = $pdo->prepare("INSERT INTO `reasons` (`item_order`, `content`) VALUES (?, ?)");
                $order = 1;
                foreach ($defaultData['reasons'] as $r) {
                    $stmtReason->execute([$order++, $r]);
                }
            }

            // Capítulos
            if (!empty($defaultData['timeline_chapters'])) {
                $stmtTimeline = $pdo->prepare("INSERT INTO `timeline_chapters` (`chapter_order`, `chapter_label`, `title`, `description`) VALUES (?, ?, ?, ?)");
                $order = 1;
                foreach ($defaultData['timeline_chapters'] as $ch) {
                    $stmtTimeline->execute([$order++, $ch['chapter_label'], $ch['title'], $ch['description']]);
                }
            }

            // Deseos
            if (!empty($defaultData['wishes'])) {
                $stmtWish = $pdo->prepare("INSERT INTO `wishes` (`wish_order`, `icon`, `label`, `secret_text`) VALUES (?, ?, ?, ?)");
                $order = 1;
                foreach ($defaultData['wishes'] as $w) {
                    $stmtWish->execute([$order++, $w['icon'], $w['label'], $w['secret_text']]);
                }
            }

            // 6. Escribir data/db_config.php
            if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0775, true);
            $dbConfigContent = "<?php\n"
                . "// Generado por MyBB Installer el " . date('Y-m-d H:i:s') . "\n"
                . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                . "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            file_put_contents(DB_CONFIG_FILE, $dbConfigContent);

            // 7. Bloquear el instalador creando los archivos lock e installer con contenido "1"
            lock_installer();

            // Iniciar sesión como administrador automáticamente
            $_SESSION['admin_logged'] = true;
            $done = true;

        } catch (PDOException $e) {
            $errors[] = 'Error de conexión MySQL: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente de Instalación — MyBB Installer</title>
    <style>
        :root {
            --p: #8b263e; --p-light: #f7ede2; --p-hover: #721e32;
            --bg: #f4f5f7; --card-bg: #ffffff; --border: #e3e8ee;
            --text-dark: #1e293b; --text-muted: #64748b;
            --danger: #ef4444; --danger-bg: #fef2f2;
            --ok: #16a34a; --ok-bg: #f0fdf4;
            --sidebar-w: 280px; --topbar-h: 56px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg); color: var(--text-dark); min-height: 100vh; line-height: 1.5;
        }

        /* ===== TOPBAR (Estilo MyBB ACP) ===== */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h);
            background: linear-gradient(90deg, #2b0a14 0%, #4a1225 70%, #6b1d2f 100%);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; z-index: 200; box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .topbar .brand { color: #fff; font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 9px; }
        .topbar .brand .badge { background: var(--p); padding: 3px 9px; border-radius: 6px; font-size: .72rem; letter-spacing: 1px; font-weight: 800; border: 1px solid rgba(255,255,255,.2); }
        .topbar .version { color: #f3d9de; font-size: .82rem; font-weight: 500; display: flex; align-items: center; gap: 6px; }

        /* ===== SIDEBAR DE PASOS ===== */
        aside.sidebar {
            width: var(--sidebar-w); background: #ffffff; border-right: 1px solid var(--border);
            position: fixed; top: var(--topbar-h); bottom: 0; left: 0; overflow-y: auto; z-index: 100;
        }
        .wizard-steps { list-style: none; padding: 22px 14px; display: flex; flex-direction: column; gap: 8px; }
        .step-item {
            width: 100%; border: 1px solid transparent; background: transparent; padding: 12px 14px; border-radius: 9px;
            display: flex; align-items: center; gap: 12px; text-align: left; cursor: pointer;
            font-size: .88rem; font-weight: 600; color: var(--text-muted); transition: all .2s;
        }
        .step-item:hover:not(.locked) { background: #f8fafc; color: var(--p); border-color: var(--border); }
        .step-item.active { background: var(--p); color: #fff; box-shadow: 0 4px 12px rgba(139,38,62,.25); }
        .step-item.done { color: var(--ok); }
        .step-item.done .step-icon { background: var(--ok-bg); color: var(--ok); border-color: var(--ok); }
        
        /* Pasos bloqueados */
        .step-item.locked { opacity: 0.5; cursor: not-allowed; }
        .step-item.locked:hover { background: transparent; color: var(--text-muted); }

        .step-icon {
            width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center; font-size: .88rem; font-weight: 700;
            flex-shrink: 0; transition: all .2s;
        }
        .step-item.active .step-icon { background: #fff; color: var(--p); border-color: #fff; }

        /* ===== CONTENIDO ===== */
        main.content { margin-left: var(--sidebar-w); padding: calc(var(--topbar-h) + 26px) 44px 60px 44px; max-width: 980px; }
        .panel {
            background: var(--card-bg); padding: 36px; border-radius: 16px;
            border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,.04); margin-bottom: 24px;
        }
        .panel h1 { font-size: 1.55rem; color: var(--p); margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .panel p.desc { color: var(--text-muted); font-size: .92rem; margin-bottom: 26px; line-height: 1.6; }

        .tab-step { display: none; }
        .tab-step.active { display: block; animation: fadeIn .3s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        label { display: block; font-size: .85rem; font-weight: 600; margin: 18px 0 6px; color: #334155; }
        .field-hint { font-size: .75rem; color: var(--text-muted); margin-top: 5px; }
        input[type="text"], input[type="password"], input[type="datetime-local"], select {
            width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 8px;
            font-family: inherit; font-size: .92rem; background: #f8fafc; transition: all .2s;
        }
        input:focus, select:focus { outline: none; border-color: var(--p); background: #fff; box-shadow: 0 0 0 3px rgba(139,38,62,.12); }
        .row { display: flex; gap: 20px; }
        .row > div { flex: 1; }

        /* Botones del asistente */
        .step-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 34px; padding-top: 22px; border-top: 1px solid var(--border); }
        button.btn-next, input[type="submit"].btn-submit, button.btn-submit {
            background: var(--p); color: #fff; border: none; padding: 12px 26px; border-radius: 8px;
            cursor: pointer; font-weight: 700; font-size: .94rem; display: inline-flex; align-items: center; gap: 8px;
            transition: all .2s;
        }
        button.btn-next:hover, input[type="submit"].btn-submit:hover, button.btn-submit:hover { background: var(--p-hover); transform: translateY(-1px); }
        button.btn-prev {
            background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border); padding: 12px 22px;
            border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .88rem; transition: all .2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        button.btn-prev:hover { background: #e2e8f0; }

        .btn-test {
            background: #334155; color: #fff; border: none; padding: 8px 14px; border-radius: 6px;
            font-size: .8rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            margin-top: 10px;
        }
        .btn-test:hover { background: #1e293b; }

        /* Lista de requisitos */
        .check-list { list-style: none; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .check-item {
            display: flex; align-items: center; justify-content: space-between; padding: 13px 18px;
            background: #f8fafc; border: 1px solid var(--border); border-radius: 9px; font-size: .88rem;
        }
        .check-item.ok { border-left: 4px solid var(--ok); }
        .check-item.err { border-left: 4px solid var(--danger); background: var(--danger-bg); }
        .badge-ok { background: var(--ok-bg); color: var(--ok); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: .78rem; display: flex; align-items: center; gap: 4px; }
        .badge-err { background: #fee2e2; color: var(--danger); font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: .78rem; display: flex; align-items: center; gap: 4px; }

        /* Banners de error y aviso (sin alert) */
        .errors-box {
            background: var(--danger-bg); border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px;
            border-radius: 10px; font-size: .88rem; margin-bottom: 22px; display: flex; align-items: center; gap: 8px;
            animation: fadeIn .25s ease;
        }
        .errors-box ul { padding-left: 20px; margin-top: 6px; }

        /* Éxito */
        .success-box { text-align: center; padding: 40px 20px; }
        .success-icon { font-size: 3.8rem; margin-bottom: 12px; }
        .success-box h1 { color: var(--p); font-size: 1.75rem; margin-bottom: 10px; justify-content: center; }
        .success-box p { color: var(--text-muted); font-size: .95rem; line-height: 1.6; max-width: 540px; margin: 0 auto 28px auto; }
        .success-actions { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; }

        @media (max-width: 820px) {
            aside.sidebar { width: 100%; position: relative; top: 0; border-right: none; border-bottom: 1px solid var(--border); }
            main.content { margin-left: 0; padding: 20px; }
            .row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand"><span>❤️</span> Propuesta Romántica <span class="badge">INSTALLER</span></div>
        <div class="version"><span>🛠️</span> Asistente estilo MyBB</div>
    </div>

    <?php if ($done): ?>
        <main class="content" style="margin-left:auto;margin-right:auto;max-width:680px;padding-top:calc(var(--topbar-h) + 40px);">
            <div class="panel success-box">
                <div class="success-icon">🎉</div>
                <h1>¡Instalación Completada con Éxito!</h1>
                <p>El sistema se ha instalado y configurado correctamente en tu base de datos MySQL. Se han creado los archivos de protección <code>data/lock</code> y <code>data/installer</code>.</p>
                <div class="success-actions">
                    <a href="admin/index.php" style="background:var(--p);color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">⚙️ Ir al Panel de Administración</a>
                    <a href="index.php" target="_blank" style="background:#334155;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">👁️ Ver Página Pública</a>
                </div>
            </div>
        </main>
    <?php else: ?>

    <aside class="sidebar">
        <ul class="wizard-steps">
            <li>
                <button type="button" class="step-item active" data-step="1" onclick="stepItemClick(1)">
                    <span class="step-icon" id="step-icon-1">👋</span>
                    <span>1. Bienvenida</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="2" onclick="stepItemClick(2)">
                    <span class="step-icon" id="step-icon-2">🔒</span>
                    <span>2. Requisitos</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="3" onclick="stepItemClick(3)">
                    <span class="step-icon" id="step-icon-3">🔒</span>
                    <span>3. Base de Datos</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="4" onclick="stepItemClick(4)">
                    <span class="step-icon" id="step-icon-4">🔒</span>
                    <span>4. Configuración</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="5" onclick="stepItemClick(5)">
                    <span class="step-icon" id="step-icon-5">🔒</span>
                    <span>5. Cuenta Admin</span>
                </button>
            </li>
            <li>
                <button type="button" class="step-item locked" data-step="6" onclick="stepItemClick(6)">
                    <span class="step-icon" id="step-icon-6">🔒</span>
                    <span>6. Finalizar</span>
                </button>
            </li>
        </ul>
    </aside>

    <main class="content">
        <div id="inline_step_error" class="errors-box" style="display:none;"></div>

        <?php if (!empty($errors)): ?>
            <div class="errors-box">
                <div>
                    <strong>⚠️ Se encontraron los siguientes errores:</strong>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="install.php" id="installerForm">

            <!-- ================= PASO 1: BIENVENIDA ================= -->
            <div class="tab-step active" id="step-1">
                <div class="panel">
                    <h1>👋 Bienvenido al Asistente de Instalación</h1>
                    <p class="desc">
                        Este asistente te guiará paso a paso en la instalación y configuración de tu propuesta romántica.
                        El sistema creará automáticamente la base de datos MySQL, las tablas y la cuenta del administrador.
                    </p>
                    
                    <div style="background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:22px;line-height:1.8;font-size:.9rem;margin-bottom:15px;">
                        <h3 style="color:var(--p);font-size:1.05rem;margin-bottom:10px;display:flex;align-items:center;gap:6px;">✨ ¿Qué configurará este asistente?</h3>
                        • 🗄️ <b>Base de Datos MySQL</b>: Esquema modular cargado desde <code>data/db_schema.php</code> y datos iniciales desde <code>data/default_data.php</code>.<br>
                        • 🌐 <b>Gestor de Idiomas MyBB</b>: Frases editables desde archivos modulares en <code>languages/</code>.<br>
                        • ⚙️ <b>Panel de Control AdminCP</b>: Panel completo para gestionar todo el contenido.<br>
                        • ⏳ <b>Persistencia del Contador</b>: La fecha de aceptación de la propuesta se guarda directamente en MySQL.
                    </div>

                    <div class="step-actions">
                        <span></span>
                        <button type="button" class="btn-next" onclick="advanceStep(1)">Comenzar instalación ➔</button>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 2: REQUISITOS ================= -->
            <div class="tab-step" id="step-2">
                <div class="panel">
                    <h1>📋 Comprobación de Requisitos del Servidor</h1>
                    <p class="desc">Verificación del entorno de ejecución PHP, extensiones necesarias y permisos de escritura en el servidor.</p>

                    <ul class="check-list">
                        <?php foreach ($checks as $c): ?>
                            <li class="check-item <?php echo $c['ok'] ? 'ok' : 'err'; ?>">
                                <span><?php echo htmlspecialchars($c['label']); ?></span>
                                <span class="<?php echo $c['ok'] ? 'badge-ok' : 'badge-err'; ?>"><?php echo $c['ok'] ? '✓ Correcto' : '✗ Error'; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!$allOk): ?>
                        <div class="errors-box">
                            ⚠️ Por favor, corrige los permisos de las carpetas o habilita la extensión PDO MySQL en tu servidor antes de continuar.
                        </div>
                    <?php endif; ?>

                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="goToStep(1)">⬅ Anterior</button>
                        <button type="button" class="btn-next" <?php echo !$allOk ? 'disabled style="opacity:.5;cursor:not-allowed;"' : ''; ?> onclick="advanceStep(2)">Continuar a Base de Datos ➔</button>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 3: BASE DE DATOS ================= -->
            <div class="tab-step" id="step-3">
                <div class="panel">
                    <h1>🗄️ Configuración de Base de Datos MySQL</h1>
                    <p class="desc">Introduce los datos de conexión a tu servidor MySQL o MariaDB. Si la base de datos no existe, se intentará crear automáticamente.</p>

                    <div class="row">
                        <div>
                            <label for="db_host">🔌 Servidor / Host de MySQL</label>
                            <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($prev['db_host']); ?>" required placeholder="127.0.0.1 o localhost">
                            <p class="field-hint">Normalmente <code>127.0.0.1</code> o <code>localhost</code>.</p>
                        </div>
                        <div>
                            <label for="db_name">📁 Nombre de la Base de Datos</label>
                            <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($prev['db_name']); ?>" required placeholder="romantic_app">
                            <p class="field-hint">Se creará automáticamente si no existe.</p>
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label for="db_user">👤 Usuario de MySQL</label>
                            <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($prev['db_user']); ?>" required placeholder="root">
                        </div>
                        <div>
                            <label for="db_pass">🔑 Contraseña de MySQL</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($prev['db_pass']); ?>" placeholder="Dejar vacío si no tiene contraseña">
                        </div>
                    </div>

                    <div style="margin-top:14px;display:flex;align-items:center;gap:12px;">
                        <button type="button" class="btn-test" onclick="testDatabaseConnection()">🔌 Probar conexión MySQL</button>
                        <span id="db_test_result" style="font-size:.82rem;font-weight:600;"></span>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="goToStep(2)">⬅ Anterior</button>
                        <button type="button" class="btn-next" onclick="advanceStep(3)">Continuar a Configuración ➔</button>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 4: CONFIGURACIÓN DEL SITIO ================= -->
            <div class="tab-step" id="step-4">
                <div class="panel">
                    <h1>⚙️ Configuración General del Sitio</h1>
                    <p class="desc">Personaliza los nombres principales de la pareja, el idioma predeterminado y la fecha del contador.</p>

                    <label for="site_lang">🌐 Idioma predeterminado del sitio</label>
                    <select id="site_lang" name="site_lang">
                        <?php foreach ($availableLangs as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $prev['site_lang'] === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>.php)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field-hint">Los textos se cargarán del archivo modular correspondiente en <code>languages/</code>.</p>

                    <div class="row" style="margin-top:10px;">
                        <div>
                            <label for="partner_one">👩‍❤️‍👨 Nombre de Pareja 1</label>
                            <input type="text" id="partner_one" name="partner_one" value="<?php echo htmlspecialchars($prev['partner_one']); ?>" placeholder="Ej: María">
                        </div>
                        <div>
                            <label for="partner_two">👩‍❤️‍👨 Nombre de Pareja 2</label>
                            <input type="text" id="partner_two" name="partner_two" value="<?php echo htmlspecialchars($prev['partner_two']); ?>" placeholder="Ej: Carlos">
                        </div>
                    </div>

                    <label for="start_date">⏳ Fecha de inicio de la relación (Opcional)</label>
                    <input type="datetime-local" id="start_date" name="start_date" value="<?php echo htmlspecialchars($prev['start_date']); ?>">
                    <p class="field-hint">Si la dejas vacía, el contador empezará automáticamente en el momento exacto en que pulse "¡Sí, Quiero!".</p>

                    <label for="date_locale">📅 Formato regional de fecha del contador</label>
                    <select id="date_locale" name="date_locale">
                        <?php foreach (available_locales() as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $prev['date_locale'] === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="goToStep(3)">⬅ Anterior</button>
                        <button type="button" class="btn-next" onclick="advanceStep(4)">Continuar a Cuenta Admin ➔</button>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 5: CUENTA ADMINISTRADOR ================= -->
            <div class="tab-step" id="step-5">
                <div class="panel">
                    <h1>👤 Cuenta de Administrador (AdminCP)</h1>
                    <p class="desc">Crea el usuario y contraseña para acceder al panel de control. Se almacenarán cifrados en la base de datos MySQL.</p>

                    <label for="admin_user">👤 Nombre de usuario</label>
                    <input type="text" id="admin_user" name="admin_user" value="<?php echo htmlspecialchars($prev['user']); ?>" required autocomplete="username" minlength="3">
                    <p class="field-hint">Mínimo 3 caracteres (letras, números, puntos y guiones).</p>

                    <div class="row">
                        <div>
                            <label for="admin_pass">🔑 Contraseña de Administrador (Mínimo 5 caracteres)</label>
                            <input type="password" id="admin_pass" name="admin_pass" autocomplete="new-password" placeholder="Mínimo 5 caracteres" minlength="5" required>
                        </div>
                        <div>
                            <label for="admin_pass2">🔐 Repite la contraseña</label>
                            <input type="password" id="admin_pass2" name="admin_pass2" autocomplete="new-password" placeholder="Repite la contraseña" minlength="5" required>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="goToStep(4)">⬅ Anterior</button>
                        <button type="button" class="btn-next" onclick="advanceStep(5)">Revisar y Finalizar ➔</button>
                    </div>
                </div>
            </div>

            <!-- ================= PASO 6: FINALIZAR ================= -->
            <div class="tab-step" id="step-6">
                <div class="panel">
                    <h1>🚀 Revisión y Ejecución de la Instalación</h1>
                    <p class="desc">Verifica los datos antes de crear la base de datos y guardar la configuración en el servidor.</p>

                    <div style="background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:22px;font-size:.9rem;line-height:1.9;">
                        <h3 style="color:var(--p);font-size:1.05rem;margin-bottom:12px;display:flex;align-items:center;gap:6px;">📋 Resumen de Instalación</h3>
                        • 🔌 <b>Host MySQL</b>: <span id="summary_db_host"></span><br>
                        • 📁 <b>Base de Datos</b>: <span id="summary_db_name"></span><br>
                        • 👤 <b>Usuario Admin</b>: <span id="summary_admin_user"></span><br>
                        • 🌐 <b>Idioma por defecto</b>: <span id="summary_site_lang"></span><br>
                        • 👫 <b>Pareja</b>: <span id="summary_couple"></span>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn-prev" onclick="goToStep(5)">⬅ Anterior</button>
                        <button type="submit" class="btn-submit">🚀 Ejecutar Instalación</button>
                    </div>
                </div>
            </div>

        </form>
    </main>

    <script>
        // ============================================================
        //  Lógica del Asistente MyBB Installer (Tabs & Sidebar con bloqueo)
        // ============================================================
        let currentStep = 1;
        let maxUnlockedStep = 1;

        const stepIcons = {
            1: '👋',
            2: '📋',
            3: '🗄️',
            4: '⚙️',
            5: '👤',
            6: '🚀'
        };

        function showStepError(msg) {
            const errEl = document.getElementById('inline_step_error');
            if (!errEl) return;
            if (msg) {
                errEl.innerHTML = '⚠️ ' + msg;
                errEl.style.display = 'flex';
                errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                errEl.style.display = 'none';
            }
        }

        function stepItemClick(step) {
            if (step > maxUnlockedStep) {
                showStepError('Debes completar el paso actual y pulsar "Continuar" para desbloquear este paso.');
                return;
            }
            goToStep(step);
        }

        function advanceStep(fromStep) {
            showStepError(null);
            // Validaciones por paso antes de avanzar
            if (fromStep === 1) {
                unlockAndGo(2);
            } else if (fromStep === 2) {
                unlockAndGo(3);
            } else if (fromStep === 3) {
                const host = document.getElementById('db_host').value.trim();
                const name = document.getElementById('db_name').value.trim();
                const user = document.getElementById('db_user').value.trim();
                if (!host || !name || !user) {
                    showStepError('Por favor completa el host, nombre de la base de datos y usuario de MySQL.');
                    return;
                }
                unlockAndGo(4);
            } else if (fromStep === 4) {
                unlockAndGo(5);
            } else if (fromStep === 5) {
                const u = document.getElementById('admin_user').value.trim();
                const p1 = document.getElementById('admin_pass').value;
                const p2 = document.getElementById('admin_pass2').value;
                if (!u || u.length < 3) {
                    showStepError('El usuario de administrador debe tener al menos 3 caracteres.');
                    return;
                }
                if (!p1 || p1.length < 5) {
                    showStepError('La contraseña debe tener al menos 5 caracteres.');
                    return;
                }
                if (p1 !== p2) {
                    showStepError('Las contraseñas no coinciden. Por favor verifícalas.');
                    return;
                }
                unlockAndGo(6);
            }
        }

        function unlockAndGo(step) {
            if (step > maxUnlockedStep) {
                maxUnlockedStep = step;
            }
            goToStep(step);
        }

        function goToStep(step) {
            if (step < 1 || step > 6) return;
            currentStep = step;
            showStepError(null);

            // Cambiar vista de pestañas
            document.querySelectorAll('.tab-step').forEach(tab => tab.classList.remove('active'));
            const targetTab = document.getElementById('step-' + step);
            if (targetTab) targetTab.classList.add('active');

            // Actualizar botones de la sidebar
            document.querySelectorAll('.step-item').forEach(item => {
                const itemStep = parseInt(item.getAttribute('data-step'), 10);
                const iconSpan = document.getElementById('step-icon-' + itemStep);

                item.classList.remove('active', 'done', 'locked');

                if (itemStep === currentStep) {
                    item.classList.add('active');
                    if (iconSpan) iconSpan.textContent = stepIcons[itemStep];
                } else if (itemStep < currentStep) {
                    item.classList.add('done');
                    if (iconSpan) iconSpan.textContent = '✓';
                } else if (itemStep <= maxUnlockedStep) {
                    if (iconSpan) iconSpan.textContent = stepIcons[itemStep];
                } else {
                    item.classList.add('locked');
                    if (iconSpan) iconSpan.textContent = '🔒';
                }
            });

            // Si es el paso 6, llenar resumen
            if (step === 6) {
                document.getElementById('summary_db_host').textContent = document.getElementById('db_host').value;
                document.getElementById('summary_db_name').textContent = document.getElementById('db_name').value;
                document.getElementById('summary_admin_user').textContent = document.getElementById('admin_user').value;
                
                const langSelect = document.getElementById('site_lang');
                document.getElementById('summary_site_lang').textContent = langSelect.options[langSelect.selectedIndex].text;
                
                const p1 = document.getElementById('partner_one').value || 'Ella';
                const p2 = document.getElementById('partner_two').value || 'Él';
                document.getElementById('summary_couple').textContent = `${p1} & ${p2}`;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function testDatabaseConnection() {
            const resSpan = document.getElementById('db_test_result');
            resSpan.style.color = '#64748b';
            resSpan.textContent = '⏳ Probando conexión con MySQL...';

            const formData = new FormData();
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);

            try {
                const res = await fetch('install.php?action=test_db', { method: 'POST', body: formData });
                const d = await res.json();
                if (d.success) {
                    resSpan.style.color = 'var(--ok)';
                    resSpan.textContent = '✅ ' + d.message;
                } else {
                    resSpan.style.color = 'var(--danger)';
                    resSpan.textContent = '❌ ' + (d.error || 'Error al conectar');
                }
            } catch (e) {
                resSpan.style.color = 'var(--danger)';
                resSpan.textContent = '❌ Error de red al probar la base de datos.';
            }
        }
    </script>

    <?php endif; ?>
</body>
</html>
