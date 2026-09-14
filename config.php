<?php
// Added safeguard to prevent accidental database reset via reset_data endpoint
if (!defined('ALLOW_RESET_DATA')) define('ALLOW_RESET_DATA', false);
// ============================================================
//  CONFIGURACION GENERAL DEL SITIO (MySQL PDO Storage)
// ============================================================
define('DB_CONFIG_FILE', __DIR__ . '/data/db_config.php');
define('LOCK_FILE',      __DIR__ . '/data/lock');
define('INSTALLER_FILE', __DIR__ . '/data/installer');
define('INSTALL_LOCK',   __DIR__ . '/data/installed.lock'); // Retrocompatibilidad
define('LANG_DIR',       __DIR__ . '/languages/');

define('UPLOAD_IMG_DIR', __DIR__ . '/uploads/images/');
define('UPLOAD_AUD_DIR', __DIR__ . '/uploads/audios/');
define('UPLOAD_IMG_URL', 'uploads/images/');
define('UPLOAD_AUD_URL', 'uploads/audios/');

define('MAX_UPLOAD_SIZE', 25 * 1024 * 1024); // 25 MB

// Medios por defecto
define('DEFAULT_COVER_IMAGE', 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?auto=format&fit=crop&w=800&q=80');
define('DEFAULT_SECOND_IMAGE', 'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?auto=format&fit=crop&w=800&q=80');
define('DEFAULT_MUSIC_URL', 'https://cdn.pixabay.com/audio/2022/05/27/audio_1808fbf07a.mp3');

// Cargar credenciales de conexion MySQL si existen
if (file_exists(DB_CONFIG_FILE)) {
    require_once DB_CONFIG_FILE;
}

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_NAME')) define('DB_NAME', 'romantic_app');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// --- Funciones de Bloqueo del Instalador (Estilo MyBB) ---
function lock_installer() {
    if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0775, true);
    @file_put_contents(LOCK_FILE, "1");
    @file_put_contents(INSTALLER_FILE, "1");
    @file_put_contents(INSTALL_LOCK, "1");
}

function unlock_installer() {
    if (file_exists(LOCK_FILE)) @unlink(LOCK_FILE);
    if (file_exists(INSTALLER_FILE)) @unlink(INSTALLER_FILE);
    if (file_exists(INSTALL_LOCK)) @unlink(INSTALL_LOCK);
}

// --- Gestion de Idiomas (Estilo MyBB) ---
function get_available_languages() {
    $langs = [];
    if (!is_dir(LANG_DIR)) @mkdir(LANG_DIR, 0775, true);
    $files = glob(LANG_DIR . '*.php');
    if ($files) {
        foreach ($files as $file) {
            $code = basename($file, '.php');
            $data = @include $file;
            if (is_array($data) && !empty($data['lang_name'])) {
                $langs[$code] = $data['lang_name'];
            } else {
                $langs[$code] = strtoupper($code);
            }
        }
    }
    if (empty($langs)) {
        $langs['es'] = 'Español';
        $langs['en'] = 'English';
    }
    return $langs;
}

function load_language($code = 'es') {
    $code = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$code);
    if ($code === '') $code = 'es';
    $file = LANG_DIR . $code . '.php';
    if (!file_exists($file)) {
        $file = LANG_DIR . 'es.php';
    }
    if (file_exists($file)) {
        $data = @include $file;
        if (is_array($data)) return $data;
    }
    return [
        'lang_code'   => $code,
        'lang_name'   => strtoupper($code),
        'date_locale' => 'es-ES',
        'ui_texts'    => []
    ];
}

function save_language_file($code, $data) {
    $code = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$code);
    if ($code === '') return false;
    if (!is_dir(LANG_DIR)) @mkdir(LANG_DIR, 0775, true);
    $file = LANG_DIR . $code . '.php';
    
    $langName   = addcslashes($data['lang_name'] ?? strtoupper($code), "'\\");
    $dateLocale = addcslashes($data['date_locale'] ?? 'es-ES', "'\\");
    
    $out  = "<?php\n";
    $out .= "// ============================================================\n";
    $out .= "//  Archivo de Idioma: {$code} ({$langName})\n";
    $out .= "// ============================================================\n";
    $out .= "return [\n";
    $out .= "    'lang_code'   => '{$code}',\n";
    $out .= "    'lang_name'   => '{$langName}',\n";
    $out .= "    'date_locale' => '{$dateLocale}',\n";
    $out .= "    'ui_texts'    => [\n";
    
    $uiTexts = is_array($data['ui_texts'] ?? null) ? $data['ui_texts'] : [];
    foreach ($uiTexts as $k => $v) {
        $safeK = addcslashes($k, "'\\");
        $safeV = addcslashes((string)$v, "'\\");
        $out .= "        '{$safeK}' => '{$safeV}',\n";
    }
    
    $out .= "    ]\n";
    $out .= "];\n";
    
    return (bool)@file_put_contents($file, $out, LOCK_EX);
}

// --- Conexion PDO Singleton a MySQL ---
function get_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    
    $host   = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $port   = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $dbname = defined('DB_NAME') ? DB_NAME : 'romantic_app';
    $user   = defined('DB_USER') ? DB_USER : 'root';
    $pass   = defined('DB_PASS') ? DB_PASS : '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// --- Asegurar existencia de tablas MySQL cargando esquema y datos de los ficheros separados ---
function ensure_database_schema($db) {
    if (!$db) return;
    try {
        // 1. Cargar y ejecutar esquema desde data/db_schema.php
        $schemaFile = __DIR__ . '/data/db_schema.php';
        if (file_exists($schemaFile)) {
            $tableQueries = include $schemaFile;
            if (is_array($tableQueries)) {
                foreach ($tableQueries as $query) {
                    $db->exec($query);
                }
            }
        }

        // 2. Si la tabla settings está vacía, sembrar con los datos por defecto desde data/default_data.php
        $settingsCount = (int)$db->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
        if ($settingsCount === 0) {
            $defaultDataFile = __DIR__ . '/data/default_data.php';
            $defaultData = file_exists($defaultDataFile) ? include $defaultDataFile : [];

            if (is_array($defaultData)) {
                // Settings
                if (!empty($defaultData['settings'])) {
                    $stmtSet = $db->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
                    foreach ($defaultData['settings'] as $k => $v) {
                        $stmtSet->execute([$k, (string)$v]);
                    }
                    $stmtSet->execute(['state_version', (string)time()]);
                }

                // UI Media
                if (!empty($defaultData['ui_media'])) {
                    $stmtMedia = $db->prepare("INSERT IGNORE INTO `ui_media` (`media_key`, `media_value`) VALUES (?, ?)");
                    foreach ($defaultData['ui_media'] as $k => $v) {
                        $stmtMedia->execute([$k, (string)$v]);
                    }
                }

                // Carta
                if (!empty($defaultData['letter_paragraphs'])) {
                    $stmtLetter = $db->prepare("INSERT INTO `letter_paragraphs` (`paragraph_order`, `content`) VALUES (?, ?)");
                    $order = 1;
                    foreach ($defaultData['letter_paragraphs'] as $p) {
                        $stmtLetter->execute([$order++, $p]);
                    }
                }

                // Razones
                if (!empty($defaultData['reasons'])) {
                    $stmtReason = $db->prepare("INSERT INTO `reasons` (`item_order`, `content`) VALUES (?, ?)");
                    $order = 1;
                    foreach ($defaultData['reasons'] as $r) {
                        $stmtReason->execute([$order++, $r]);
                    }
                }

                // Capítulos
                if (!empty($defaultData['timeline_chapters'])) {
                    $stmtTimeline = $db->prepare("INSERT INTO `timeline_chapters` (`chapter_order`, `chapter_label`, `title`, `description`) VALUES (?, ?, ?, ?)");
                    $order = 1;
                    foreach ($defaultData['timeline_chapters'] as $ch) {
                        $stmtTimeline->execute([$order++, $ch['chapter_label'], $ch['title'], $ch['description']]);
                    }
                }

                // Deseos
                if (!empty($defaultData['wishes'])) {
                    $stmtWish = $db->prepare("INSERT INTO `wishes` (`wish_order`, `icon`, `label`, `secret_text`) VALUES (?, ?, ?, ?)");
                    $order = 1;
                    foreach ($defaultData['wishes'] as $w) {
                        $stmtWish->execute([$order++, $w['icon'], $w['label'], $w['secret_text']]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ignorar si no se puede ejecutar
    }
}

// --- Comprobacion de credenciales (usada por login.php y api.php) ---
function admin_credentials_ok($user, $pass) {
    $db = get_db();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $row = $stmt->fetch();
        if ($row && password_verify((string)$pass, $row['password_hash'])) {
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

// --- Obtener nombre de usuario actual de admin ---
function get_admin_username() {
    $db = get_db();
    if (!$db) return 'admin';
    try {
        $stmt = $db->query("SELECT username FROM admin_users ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row && !empty($row['username'])) return $row['username'];
    } catch (Exception $e) {}
    return 'admin';
}

// --- Guardar nuevas credenciales en el servidor (MySQL) ---
function save_admin_credentials($user, $pass) {
    $db = get_db();
    if (!$db) return false;
    $hash = password_hash((string)$pass, PASSWORD_DEFAULT);
    try {
        $count = (int)$db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($count === 0) {
            $stmt = $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            return $stmt->execute([$user, $hash]);
        } else {
            $stmt = $db->prepare("UPDATE admin_users SET username = ?, password_hash = ? ORDER BY id ASC LIMIT 1");
            return $stmt->execute([$user, $hash]);
        }
    } catch (Exception $e) {
        return false;
    }
}

// --- ¿El sitio ya paso por install.php? (Estilo MyBB) ---
function site_installed() {
    $hasLock = (file_exists(LOCK_FILE) || file_exists(INSTALLER_FILE) || file_exists(INSTALL_LOCK));
    return $hasLock && file_exists(DB_CONFIG_FILE);
}

// --- Obtener todos los datos combinando MySQL y los textos del archivo de Idioma ---
function get_site_data() {
    $db = get_db();
    if (!$db) return null;
    ensure_database_schema($db);
    try {
        $data = [
            'settings'          => [],
            'ui_texts'          => [],
            'ui_media'          => [],
            'letter_paragraphs' => [],
            'reasons'           => [],
            'timeline_chapters' => [],
            'wishes'            => [],
        ];

        // 1. Settings desde MySQL
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $data['settings'][$row['setting_key']] = $row['setting_value'];
        }

        // Idioma configurado
        $siteLang = !empty($data['settings']['site_lang']) ? $data['settings']['site_lang'] : 'es';

        // 2. UI Texts cargados DIRECTAMENTE desde el archivo de idioma
        $langData = load_language($siteLang);
        $data['ui_texts'] = !empty($langData['ui_texts']) ? $langData['ui_texts'] : [];
        if (!isset($data['settings']['date_locale']) && !empty($langData['date_locale'])) {
            $data['settings']['date_locale'] = $langData['date_locale'];
        }

        // 3. UI Media desde la tabla ui_media en MySQL
        $stmt = $db->query("SELECT media_key, media_value FROM ui_media");
        while ($row = $stmt->fetch()) {
            $data['ui_media'][$row['media_key']] = $row['media_value'];
            // Para conveniencia de acceso en vistas
            $data['ui_texts'][$row['media_key']] = $row['media_value'];
        }

        // 4. Listas dinámicas desde MySQL
        // Letter paragraphs
        $stmt = $db->query("SELECT id, paragraph_order, content FROM letter_paragraphs ORDER BY paragraph_order ASC, id ASC");
        $data['letter_paragraphs'] = $stmt->fetchAll() ?: [];

        // Reasons
        $stmt = $db->query("SELECT id, item_order, content FROM reasons ORDER BY item_order ASC, id ASC");
        $data['reasons'] = $stmt->fetchAll() ?: [];

        // Timeline chapters
        $stmt = $db->query("SELECT id, chapter_order, chapter_label, title, description FROM timeline_chapters ORDER BY chapter_order ASC, id ASC");
        $data['timeline_chapters'] = $stmt->fetchAll() ?: [];

        // Wishes
        $stmt = $db->query("SELECT id, wish_order, icon, label, secret_text FROM wishes ORDER BY wish_order ASC, id ASC");
        $data['wishes'] = $stmt->fetchAll() ?: [];

        return $data;
    } catch (Exception $e) {
        return null;
    }
}

// --- Idiomas disponibles para las fechas del contador ---
function available_locales() {
    return [
        'es-ES' => 'Español (España)',
        'es-MX' => 'Español (México)',
        'es-AR' => 'Español (Argentina)',
        'es-CL' => 'Español (Chile)',
        'es-CO' => 'Español (Colombia)',
        'es-PE' => 'Español (Perú)',
        'en-US' => 'English (US)',
        'en-GB' => 'English (UK)',
        'pt-BR' => 'Português (Brasil)',
        'fr-FR' => 'Français',
        'de-DE' => 'Deutsch',
        'it-IT' => 'Italiano',
    ];
}

function valid_locale($loc) {
    $loc = trim((string)$loc);
    return array_key_exists($loc, available_locales()) ? $loc : 'es-ES';
}
