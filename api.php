<?php
// ============================================================
//  api.php — Backend CRUD del sistema (MySQL + Gestor de Idiomas MyBB)
//  Endpoints:
//    get_data            -> devuelve todo el contenido (público)
//    accept_proposal     -> guarda la fecha y hora exacta del "¡Sí, Quiero!" en MySQL (público)
//    update_settings     -> nombres de pareja, fecha del contador, idioma activo
//    get_language        -> obtiene las frases de un archivo de idioma
//    save_language       -> edita/guarda frases directamente en el archivo de idioma
//    update_media        -> guarda URLs de cover_image_url, second_image_url, bg_music_url en MySQL
//    save_letter         -> guarda los párrafos de la carta en MySQL
//    save_item           -> crear/editar razones, capítulos, deseos en MySQL
//    delete_item         -> eliminar elemento de una tabla en MySQL
//    change_credentials  -> cambiar usuario/contraseña del admin
//    export_data         -> descargar copia de seguridad (JSON)
//    import_data         -> restaurar desde copia (JSON)
//    reset_ui_state      -> reiniciar propuesta / volver a mostrar botón
//    reset_data          -> volver a los valores por defecto en MySQL
// ============================================================
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Si el sitio no está instalado, responder error
if (!site_installed()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'El sitio no está instalado. Abre install.php primero.']);
    exit;
}

$db = get_db();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se pudo conectar a la base de datos MySQL. Revisa la configuración.']);
    exit;
}

// Asegurar que las tablas existan
ensure_database_schema($db);

// ---------- Helpers ----------
function respond($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean($v) {
    return trim(strip_tags((string)$v));
}

function require_auth() {
    if (empty($_SESSION['admin_logged'])) {
        respond(['success' => false, 'error' => 'No autorizado. Inicia sesión en el panel de administración.'], 401);
    }
}

// ---------- Router ----------
$action  = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true);
if (!is_array($body)) $body = [];

// ============================================================
// ENDPOINTS PÚBLICOS
// ============================================================

// 1. Obtener todos los datos combinando MySQL y los textos del archivo de Idioma
if ($action === 'get_data') {
    $d = get_site_data();
    if ($d === null) respond(['success' => false, 'error' => 'Error al leer datos del servidor.'], 500);
    $d['success'] = true;
    respond($d);
}

// 2. Guardar la fecha y hora exacta en la que se pulsa "¡Sí, Quiero!" directamente en MySQL (sin localStorage)
if ($action === 'accept_proposal') {
    $acceptedAt = !empty($body['accepted_at']) ? clean($body['accepted_at']) : date('Y-m-d\TH:i:s');
    
    // Obtener start_date actual en settings
    $stmtCur = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'start_date' LIMIT 1");
    $curStart = $stmtCur ? $stmtCur->fetchColumn() : '';
    
    // Si no había una fecha fija establecida por el admin, registrar la fecha y hora del clic
    $finalStartDate = !empty($curStart) ? $curStart : $acceptedAt;
    
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$finalStartDate]);
    
    $stmtAcc = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('proposal_accepted', '1') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmtAcc->execute();
    
    respond(['success' => true, 'start_date' => $finalStartDate]);
}

// ============================================================
// ENDPOINTS PROTEGIDOS (Requieren sesión de Admin)
// ============================================================
require_auth();

switch ($action) {

    case 'update_settings': {
        $partnerOne = clean($body['partner_one'] ?? '');
        $partnerTwo = clean($body['partner_two'] ?? '');
        $startDate  = clean($body['start_date'] ?? '');
        $siteLang   = clean($body['site_lang'] ?? 'es');
        $dateLocale = valid_locale($body['date_locale'] ?? 'es-ES');

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        if ($partnerOne !== '') $stmt->execute(['partner_one', $partnerOne]);
        if ($partnerTwo !== '') $stmt->execute(['partner_two', $partnerTwo]);
        
        $stmt->execute(['start_date', $startDate]);
        
        // Si se limpia la fecha, marcar que la propuesta no está aceptada para que vuelva a salir el botón
        if ($startDate === '') {
            $stmt->execute(['proposal_accepted', '0']);
            $stmt->execute(['state_version', (string)time()]);
        }
        
        $stmt->execute(['site_lang', $siteLang]);
        $stmt->execute(['date_locale', $dateLocale]);

        respond(['success' => true]);
    }

    // Obtener información y frases de un archivo de idioma específico (Estilo MyBB)
    case 'get_language': {
        $code = clean($_GET['code'] ?? 'es');
        $data = load_language($code);
        $languages = get_available_languages();
        respond([
            'success'   => true,
            'lang_code' => $code,
            'lang_name' => $data['lang_name'] ?? ($languages[$code] ?? strtoupper($code)),
            'date_locale' => $data['date_locale'] ?? 'es-ES',
            'ui_texts'  => $data['ui_texts'] ?? [],
            'languages' => $languages
        ]);
    }

    // Guardar / editar frases en el archivo de idioma en disco (Estilo MyBB)
    case 'save_language': {
        $code = clean($body['lang_code'] ?? 'es');
        $langName = clean($body['lang_name'] ?? strtoupper($code));
        $dateLocale = valid_locale($body['date_locale'] ?? 'es-ES');
        $uiTexts = is_array($body['ui_texts'] ?? null) ? $body['ui_texts'] : [];

        // Claves estándar permitidas
        $allowedKeys = [
            'sub_header_title', 'envelope_title', 'envelope_subtitle',
            'proposal_question', 'btn_yes_text', 'timeline_section_title',
            'counter_title', 'label_years', 'label_months', 'label_days',
            'label_hours', 'label_minutes', 'label_seconds',
            'date_prefix_sealed', 'date_prefix_started',
            'reasons_title', 'reason_placeholder', 'btn_next_reason_text',
            'wishes_section_title', 'music_play_text', 'music_pause_text',
            'final_phrase'
        ];

        $cleanedTexts = [];
        foreach ($allowedKeys as $k) {
            if (array_key_exists($k, $uiTexts)) {
                $cleanedTexts[$k] = clean($uiTexts[$k]);
            }
        }

        $saveData = [
            'lang_code'   => $code,
            'lang_name'   => $langName,
            'date_locale' => $dateLocale,
            'ui_texts'    => $cleanedTexts
        ];

        if (save_language_file($code, $saveData)) {
            // Si además se pide activar este idioma como el del sitio
            if (!empty($body['set_active'])) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_lang', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$code]);
                $stmtLoc = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('date_locale', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmtLoc->execute([$dateLocale]);
            }
            respond(['success' => true]);
        } else {
            respond(['success' => false, 'error' => 'No se pudo escribir en el archivo languages/' . $code . '.php. Comprueba permisos de escritura.'], 500);
        }
    }

    // Actualizar Multimedia en la base de datos MySQL (ui_media)
    case 'update_media': {
        $mediaKeys = ['cover_image_url', 'second_image_url', 'bg_music_url'];
        $stmt = $db->prepare("INSERT INTO ui_media (media_key, media_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE media_value = VALUES(media_value)");
        
        foreach ($mediaKeys as $k) {
            if (array_key_exists($k, $body)) {
                $stmt->execute([$k, clean($body[$k])]);
            }
        }
        respond(['success' => true]);
    }

    case 'save_letter': {
        $paragraphs = $body['paragraphs'] ?? [];
        if (!is_array($paragraphs)) $paragraphs = [];

        try {
            $db->exec("DELETE FROM letter_paragraphs");
            try { $db->exec("ALTER TABLE letter_paragraphs AUTO_INCREMENT = 1"); } catch (Exception $e) {}

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO letter_paragraphs (paragraph_order, content) VALUES (?, ?)");
            $order = 1;
            foreach ($paragraphs as $p) {
                $p = trim((string)$p);
                if ($p !== '') {
                    $stmt->execute([$order++, $p]);
                }
            }
            $db->commit();
            respond(['success' => true, 'count' => $order - 1]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            respond(['success' => false, 'error' => 'Error al guardar la carta: ' . $e->getMessage()], 500);
        }
    }

    case 'save_item': {
        $type = $body['type'] ?? '';
        $id   = !empty($body['id']) ? (int)$body['id'] : null;

        if ($type === 'reasons') {
            $content = clean($body['content'] ?? '');
            if ($content === '') respond(['success' => false, 'error' => 'El contenido de la razón no puede estar vacío.'], 400);

            if ($id) {
                $stmt = $db->prepare("UPDATE reasons SET content = ? WHERE id = ?");
                $stmt->execute([$content, $id]);
            } else {
                $maxOrder = (int)$db->query("SELECT IFNULL(MAX(item_order), 0) FROM reasons")->fetchColumn();
                $stmt = $db->prepare("INSERT INTO reasons (item_order, content) VALUES (?, ?)");
                $stmt->execute([$maxOrder + 1, $content]);
            }
            respond(['success' => true]);
        }

        if ($type === 'timeline_chapters') {
            $chapterLabel = clean($body['chapter_label'] ?? '');
            $title        = clean($body['title'] ?? '');
            $desc         = clean($body['description'] ?? '');
            if ($title === '') respond(['success' => false, 'error' => 'El título del capítulo es obligatorio.'], 400);

            if ($id) {
                $stmt = $db->prepare("UPDATE timeline_chapters SET chapter_label = ?, title = ?, description = ? WHERE id = ?");
                $stmt->execute([$chapterLabel, $title, $desc, $id]);
            } else {
                $maxOrder = (int)$db->query("SELECT IFNULL(MAX(chapter_order), 0) FROM timeline_chapters")->fetchColumn();
                $stmt = $db->prepare("INSERT INTO timeline_chapters (chapter_order, chapter_label, title, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$maxOrder + 1, $chapterLabel, $title, $desc]);
            }
            respond(['success' => true]);
        }

        if ($type === 'wishes') {
            $icon       = clean($body['icon'] ?? '✨');
            $label      = clean($body['label'] ?? '');
            $secretText = clean($body['secret_text'] ?? '');
            if ($label === '') respond(['success' => false, 'error' => 'La etiqueta del deseo es obligatoria.'], 400);

            if ($id) {
                $stmt = $db->prepare("UPDATE wishes SET icon = ?, label = ?, secret_text = ? WHERE id = ?");
                $stmt->execute([$icon, $label, $secretText, $id]);
            } else {
                $maxOrder = (int)$db->query("SELECT IFNULL(MAX(wish_order), 0) FROM wishes")->fetchColumn();
                $stmt = $db->prepare("INSERT INTO wishes (wish_order, icon, label, secret_text) VALUES (?, ?, ?, ?)");
                $stmt->execute([$maxOrder + 1, $icon, $label, $secretText]);
            }
            respond(['success' => true]);
        }

        respond(['success' => false, 'error' => 'Tipo de elemento no válido.'], 400);
    }

    case 'delete_item': {
        $table = $body['table'] ?? '';
        $id    = (int)($body['id'] ?? 0);
        $allowedTables = ['reasons', 'timeline_chapters', 'wishes'];

        if (!in_array($table, $allowedTables, true) || $id <= 0) {
            respond(['success' => false, 'error' => 'Petición no permitida.'], 400);
        }

        $orderCols = [
            'reasons'           => 'item_order',
            'timeline_chapters' => 'chapter_order',
            'wishes'            => 'wish_order'
        ];
        $orderCol = $orderCols[$table] ?? null;

        $stmt = $db->prepare("DELETE FROM `{$table}` WHERE id = ?");
        $stmt->execute([$id]);

        // Renumerar el orden de los elementos restantes para que sea siempre consecutivo (1, 2, 3...)
        if ($orderCol) {
            $rows = $db->query("SELECT id FROM `{$table}` ORDER BY `{$orderCol}` ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $upd = $db->prepare("UPDATE `{$table}` SET `{$orderCol}` = ? WHERE id = ?");
            foreach ($rows as $index => $rowId) {
                $upd->execute([$index + 1, $rowId]);
            }
        }

        respond(['success' => true]);
    }

    case 'change_credentials': {
        $current = (string)($body['current_password'] ?? '');
        $adminUser = get_admin_username();
        if (!admin_credentials_ok($adminUser, $current)) {
            respond(['success' => false, 'error' => 'La contraseña actual no es correcta.'], 403);
        }
        $newUser = clean($body['new_user'] ?? '');
        $newPass = (string)($body['new_password'] ?? '');
        if ($newUser === '') $newUser = $adminUser;
        if (mb_strlen($newUser) < 3) respond(['success' => false, 'error' => 'El usuario debe tener al menos 3 caracteres.'], 400);
        if ($newPass !== '' && strlen($newPass) < 5) respond(['success' => false, 'error' => 'La nueva contraseña debe tener al menos 5 caracteres.'], 400);

        if (!save_admin_credentials($newUser, $newPass !== '' ? $newPass : $current)) {
            respond(['success' => false, 'error' => 'No se pudieron actualizar las credenciales en la base de datos.'], 500);
        }
        respond(['success' => true]);
    }

    case 'export_backup': {
        $data = get_site_data();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="backup_propuesta_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode(['success' => true, 'exported_at' => date('c'), 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    case 'import_backup': {
        $incoming = $body['data'] ?? null;
        if (!is_array($incoming) || !isset($incoming['settings'])) {
            respond(['success' => false, 'error' => 'Archivo de respaldo no válido.'], 400);
        }

        $db->beginTransaction();
        try {
            // Settings
            $stmtSet = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach (($incoming['settings'] ?? []) as $k => $v) {
                $stmtSet->execute([$k, (string)$v]);
            }

            // UI Media
            if (isset($incoming['ui_media']) && is_array($incoming['ui_media'])) {
                $stmtMedia = $db->prepare("INSERT INTO ui_media (media_key, media_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE media_value = VALUES(media_value)");
                foreach ($incoming['ui_media'] as $k => $v) {
                    $stmtMedia->execute([$k, (string)$v]);
                }
            }

            // Letter
            if (isset($incoming['letter_paragraphs']) && is_array($incoming['letter_paragraphs'])) {
                $db->exec("DELETE FROM letter_paragraphs");
                $stmtLetter = $db->prepare("INSERT INTO letter_paragraphs (paragraph_order, content) VALUES (?, ?)");
                $order = 1;
                foreach ($incoming['letter_paragraphs'] as $p) {
                    $content = is_array($p) ? ($p['content'] ?? '') : (string)$p;
                    if (trim($content) !== '') $stmtLetter->execute([$order++, trim($content)]);
                }
            }

            // Reasons
            if (isset($incoming['reasons']) && is_array($incoming['reasons'])) {
                $db->exec("DELETE FROM reasons");
                $stmtReason = $db->prepare("INSERT INTO reasons (item_order, content) VALUES (?, ?)");
                $order = 1;
                foreach ($incoming['reasons'] as $r) {
                    $content = is_array($r) ? ($r['content'] ?? '') : (string)$r;
                    if (trim($content) !== '') $stmtReason->execute([$order++, trim($content)]);
                }
            }

            // Timeline
            if (isset($incoming['timeline_chapters']) && is_array($incoming['timeline_chapters'])) {
                $db->exec("DELETE FROM timeline_chapters");
                $stmtTimeline = $db->prepare("INSERT INTO timeline_chapters (chapter_order, chapter_label, title, description) VALUES (?, ?, ?, ?)");
                $order = 1;
                foreach ($incoming['timeline_chapters'] as $ch) {
                    $stmtTimeline->execute([
                        $order++,
                        $ch['chapter_label'] ?? "Capítulo {$order}",
                        $ch['title'] ?? '',
                        $ch['description'] ?? ''
                    ]);
                }
            }

            // Wishes
            if (isset($incoming['wishes']) && is_array($incoming['wishes'])) {
                $db->exec("DELETE FROM wishes");
                $stmtWish = $db->prepare("INSERT INTO wishes (wish_order, icon, label, secret_text) VALUES (?, ?, ?, ?)");
                $order = 1;
                foreach ($incoming['wishes'] as $w) {
                    $stmtWish->execute([
                        $order++,
                        $w['icon'] ?? '✨',
                        $w['label'] ?? '',
                        $w['secret_text'] ?? ''
                    ]);
                }
            }

            $db->commit();
            respond(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            respond(['success' => false, 'error' => 'Error al importar datos: ' . $e->getMessage()], 500);
        }
    }

    case 'reset_ui_state': {
        // Reinicia el estado de la propuesta en MySQL
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['start_date', '']);
        $stmt->execute(['proposal_accepted', '0']);
        $stmt->execute(['state_version', (string)time()]);
        respond(['success' => true]);
    }

    case 'reset_defaults': {
        try {
            $db->exec("DELETE FROM settings");
            $db->exec("DELETE FROM ui_media");
            $db->exec("DELETE FROM letter_paragraphs");
            $db->exec("DELETE FROM reasons");
            $db->exec("DELETE FROM timeline_chapters");
            $db->exec("DELETE FROM wishes");

            foreach (['letter_paragraphs', 'reasons', 'timeline_chapters', 'wishes'] as $tbl) {
                try { $db->exec("ALTER TABLE `{$tbl}` AUTO_INCREMENT = 1"); } catch (Exception $e) {}
            }

            $db->beginTransaction();

            // Settings
            $stmtSet = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $initSettings = [
                'partner_one'       => 'Ella',
                'partner_two'       => 'Él',
                'start_date'        => '',
                'proposal_accepted' => '0',
                'site_lang'         => 'es',
                'date_locale'       => 'es-ES',
                'state_version'     => (string)time()
            ];
            foreach ($initSettings as $k => $v) {
                $stmtSet->execute([$k, (string)$v]);
            }

            // UI Media
            $stmtMedia = $db->prepare("INSERT INTO ui_media (media_key, media_value) VALUES (?, ?)");
            $defaultMedia = [
                'cover_image_url'  => DEFAULT_COVER_IMAGE,
                'second_image_url' => DEFAULT_SECOND_IMAGE,
                'bg_music_url'     => DEFAULT_MUSIC_URL
            ];
            foreach ($defaultMedia as $k => $v) {
                $stmtMedia->execute([$k, (string)$v]);
            }

            // Letter
            $stmtLetter = $db->prepare("INSERT INTO letter_paragraphs (paragraph_order, content) VALUES (?, ?)");
            $defaultLetter = [
                "Mi vida,",
                "Si me hubieran dicho tiempo atrás que me enamoraría de la forma en que lo he hecho de ti, jamás lo habría creído. Contigo descubrí que el amor no se trata solo de coincidir, sino de encontrar a esa persona que hace que cualquier día común se sienta como el mejor regalo.",
                "Amo la paz que me da tu abrazo, la magia con la que llenas cada espacio y la forma tan única en la que me miras. Contigo aprendí que mi lugar favorito en el mundo no es un sitio geográfico... es estar a tu lado.",
                "Hay algo que todavía no te he confesado. Algo que mis ojos llevan tiempo intentando decirte cada vez que te miran, aunque mis palabras nunca hayan sabido cómo hacerlo.",
                "Porque contigo pasó algo diferente. Sin pedir permiso, te fuiste colando en mis pensamientos y en mis mejores días. Y entonces entendí que, desde el mismo día en que te vi, había una pregunta que no dejaba de rondarme la cabeza.",
                "Y hoy, por fin, quiero hacértela..."
            ];
            $order = 1;
            foreach ($defaultLetter as $p) {
                $stmtLetter->execute([$order++, $p]);
            }

            // Reasons
            $stmtReason = $db->prepare("INSERT INTO reasons (item_order, content) VALUES (?, ?)");
            $defaultReasons = [
                "Amo cómo se iluminan tus ojos cuando sonríes.",
                "La calma absoluta que siento cuando me abrazas.",
                "Cómo logras transformar un día ordinario en algo especial.",
                "Tu forma única de escucharme y entender mi mundo.",
                "Tu bondad y la ternura con la que tratas a los demás.",
                "Que a tu lado puedo ser 100% yo mismo.",
                "La forma en que me miras incluso cuando no me doy cuenta."
            ];
            $order = 1;
            foreach ($defaultReasons as $r) {
                $stmtReason->execute([$order++, $r]);
            }

            // Timeline
            $stmtTimeline = $db->prepare("INSERT INTO timeline_chapters (chapter_order, chapter_label, title, description) VALUES (?, ?, ?, ?)");
            $defaultTimeline = [
                ['chapter_label' => 'Capítulo 1', 'title' => 'El Primer Encuentro', 'description' => 'El día en que cruzamos miradas por primera vez y el mundo pareció detenerse un instante.'],
                ['chapter_label' => 'Capítulo 2', 'title' => 'Nuestra Primera Risa Juntos', 'description' => 'Ese momento en el que me di cuenta de que tu risa se convertiría en mi sonido favorito.'],
                ['chapter_label' => 'Capítulo 3', 'title' => 'Un Viaje Inolvidable', 'description' => 'Cada paseo y aventura donde entendí que no importa el lugar, sino la compañía.']
            ];
            $order = 1;
            foreach ($defaultTimeline as $ch) {
                $stmtTimeline->execute([
                    $order++,
                    $ch['chapter_label'] ?? "Capítulo {$order}",
                    $ch['title'] ?? '',
                    $ch['description'] ?? ''
                ]);
            }

            // Wishes
            $stmtWish = $db->prepare("INSERT INTO wishes (wish_order, icon, label, secret_text) VALUES (?, ?, ?, ?)");
            $defaultWishes = [
                ['icon' => '✈️', 'label' => 'Un Viaje Juntos', 'secret_text' => 'Descubrir un nuevo país agarrados de la mano.'],
                ['icon' => '☕', 'label' => 'Mañanas Pacíficas', 'secret_text' => 'Prepararte el café cada mañana con una sonrisa.'],
                ['icon' => '🏡', 'label' => 'Nuestro Espacio', 'secret_text' => 'Construir un lugar donde siempre reine la paz.'],
                ['icon' => '🌟', 'label' => 'Siempre Apoyarte', 'secret_text' => 'Estar a tu lado en cada sueño que decidas emprender.']
            ];
            $order = 1;
            foreach ($defaultWishes as $w) {
                $stmtWish->execute([
                    $order++,
                    $w['icon'] ?? '✨',
                    $w['label'] ?? '',
                    $w['secret_text'] ?? ''
                ]);
            }

            $db->commit();
            respond(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            respond(['success' => false, 'error' => 'Error al restaurar valores por defecto: ' . $e->getMessage()], 500);
        }
    }

case 'uninstall_system': {
    require_auth();

    // Parsear el body si viene en formato JSON
    if (empty($body)) {
        $rawInput = file_get_contents('php://input');
        $body = json_decode($rawInput, true) ?? $_POST;
    }

    $password = $body['password'] ?? '';
    if (empty($password)) {
        respond(['success' => false, 'error' => 'Debes ingresar tu contraseña de administrador para confirmar la desinstalación.'], 400);
    }

    // Validar contraseña del admin
	$stmtUser = $db->query("SELECT username, password_hash FROM admin_users ORDER BY id ASC LIMIT 1");
	$admin = $stmtUser ? $stmtUser->fetch(PDO::FETCH_ASSOC) : null;

	// Verificar que existan datos y la columna 'password_hash' no esté vacía
	if (!$admin || empty($admin['password_hash']) || !password_verify($password, $admin['password_hash'])) {
		respond(['success' => false, 'error' => 'Contraseña de administrador incorrecta.'], 403);
	}

    try {
        // Eliminar tablas
        $tables = ['settings', 'ui_media', 'letter_paragraphs', 'reasons', 'timeline_chapters', 'wishes', 'admin_users'];
        foreach ($tables as $t) {
            $db->exec("DROP TABLE IF EXISTS `{$t}`");
        }

        // Eliminar archivos de bloqueo y configuración de BD
        unlock_installer();
        if (file_exists(DB_CONFIG_FILE)) {
            @unlink(DB_CONFIG_FILE);
        }

        // Destruir sesión de administrador
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }

        respond(['success' => true, 'redirect' => '../install.php']);
    } catch (Exception $e) {
        respond(['success' => false, 'error' => 'Error durante la desinstalación: ' . $e->getMessage()], 500);
    }
}

    default:
        respond(['success' => false, 'error' => 'Acción no reconocida: ' . $action], 404);
}

