<?php
// ============================================================
//  admin/index.php — Panel de Control (estilo AdminCP)
//  Protegido por sesión. Si no hay login -> login.php
//  Almacenamiento: MySQL (PDO) + Gestor de Idiomas en Archivos PHP
// ============================================================
require_once dirname(__DIR__) . '/config.php';
session_start();
if (!site_installed()) {
    header('Location: ../install.php');
    exit;
}
if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}
$availableLangs = get_available_languages();

// Carga directa de datos desde MySQL para pintar la página de inmediato sin esperas ni ceros
$initialData     = get_site_data() ?: [];
$initialSettings = $initialData['settings'] ?? [];
$initialUiMedia  = $initialData['ui_media'] ?? [];
$initialReasons  = $initialData['reasons'] ?? [];
$initialTimeline = $initialData['timeline_chapters'] ?? [];
$initialWishes   = $initialData['wishes'] ?? [];
$initialLetter   = $initialData['letter_paragraphs'] ?? [];

$initialSiteLang  = $initialSettings['site_lang'] ?? 'es';
$initialPartner1  = $initialSettings['partner_one'] ?? '';
$initialPartner2  = $initialSettings['partner_two'] ?? '';
$initialStartDate = $initialSettings['start_date'] ?? '';

$countReasons  = count($initialReasons);
$countTimeline = count($initialTimeline);
$countWishes   = count($initialWishes);
$countLetter   = count($initialLetter);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminCP — Panel de Control</title>
    <style>
        :root {
            --p: #8b263e; --p-light: #f7ede2; --p-hover: #721e32;
            --bg: #f4f5f7; --card-bg: #ffffff; --border: #e3e8ee;
            --text-dark: #1e293b; --text-muted: #64748b;
            --danger: #ef4444; --danger-hover: #dc2626; --danger-bg: #fef2f2;
            --ok: #16a34a; --ok-bg: #f0fdf4;
            --warning: #f59e0b; --warning-bg: #fffbeb; --warning-border: #fde68a;
            --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            --sp-green: #1db954; --sp-dark: #121212;
            --sidebar-w: 260px; --topbar-h: 52px;
            --topbar-bg: #2b0a14;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg); color: var(--text-dark);
            min-height: 100vh; line-height: 1.5;
        }

        /* ===== BARRA SUPERIOR (estilo ACP) ===== */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h);
            background: var(--topbar-bg);
            background: linear-gradient(90deg, #2b0a14 0%, #4a1225 70%, #6b1d2f 100%);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; z-index: 200;
            box-shadow: 0 2px 10px rgba(0,0,0,.25);
        }
        .topbar .brand { color: #fff; font-weight: 700; font-size: .95rem; display: flex; align-items: center; gap: 8px; }
        .topbar .brand .acp { background: var(--p); padding: 2px 8px; border-radius: 5px; font-size: .72rem; letter-spacing: 1px; }
        .topbar .top-links { display: flex; align-items: center; gap: 6px; }
        .topbar .top-links a {
            color: #f3d9de; text-decoration: none; font-size: .78rem; font-weight: 600;
            padding: 6px 12px; border-radius: 6px; transition: all .2s;
        }
        .topbar .top-links a:hover { background: rgba(255,255,255,.12); color: #fff; }
        .topbar .top-links a.logout { color: #fda4af; }
        .topbar .top-links a.logout:hover { background: rgba(239,68,68,.2); color: #fecaca; }

        /* ===== SIDEBAR (menú por categorías) ===== */
        aside.sidebar {
            width: var(--sidebar-w); background: #ffffff;
            border-right: 1px solid var(--border);
            position: fixed; top: var(--topbar-h); bottom: 0; left: 0;
            overflow-y: auto; z-index: 100;
        }
        .sidebar-menu { list-style: none; padding: 12px 8px 30px 8px; display: flex; flex-direction: column; gap: 6px; }
        .nav-group { border-radius: 8px; overflow: hidden; }
        .nav-group-header {
            width: 100%; background: transparent; border: none; padding: 10px 14px;
            font-size: .82rem; font-weight: 700; color: var(--text-dark); cursor: pointer;
            border-radius: 8px; display: flex; align-items: center; justify-content: space-between;
            text-transform: uppercase; letter-spacing: .5px; transition: all .2s;
        }
        .nav-group-header:hover { background: #f1f5f9; color: var(--p); }
        .nav-group-header .arrow { font-size: .7rem; transition: transform .2s; color: var(--text-muted); }
        .nav-group-sub { list-style: none; padding: 4px 0 4px 12px; display: none; flex-direction: column; gap: 2px; }
        .nav-group.open .nav-group-sub { display: flex; }
        .nav-group.open .nav-group-header { color: var(--p); background: var(--p-light); }
        .nav-group.open .nav-group-header .arrow { transform: rotate(90deg); }
        .tab-btn {
            background: transparent; border: none; padding: 8px 12px; font-size: .87rem; font-weight: 600;
            color: var(--text-muted); cursor: pointer; border-radius: 6px; transition: all .2s;
            display: flex; align-items: center; gap: 10px; width: 100%; text-align: left;
        }
        .tab-btn:hover { color: var(--p); background: var(--p-light); }
        .tab-btn.active { color: #fff; background: var(--p); }

        /* ===== CONTENIDO ===== */
        main.content { margin-left: var(--sidebar-w); padding: calc(var(--topbar-h) + 20px) 40px 40px 40px; max-width: 1150px; }
        .breadcrumb {
            font-size: .75rem; color: var(--text-muted); margin-bottom: 14px;
            display: flex; align-items: center; gap: 6px;
        }
        .breadcrumb b { color: var(--p); }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 1.55rem; font-weight: 700; }
        .page-header p { color: var(--text-muted); font-size: .88rem; margin-top: 3px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        section {
            background: var(--card-bg); padding: 26px 28px; border-radius: 12px;
            box-shadow: var(--card-shadow); border: 1px solid var(--border); margin-bottom: 22px;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        h2 { color: var(--p); font-size: 1.15rem; font-weight: 700; margin: 0; }
        h3 { color: var(--p); font-size: 1rem; margin-bottom: 12px; }
        label { display: block; font-size: .84rem; font-weight: 600; margin: 14px 0 6px; color: #475569; }
        .field-hint { font-size: .72rem; color: var(--text-muted); margin-top: 4px; font-weight: 400; }
        input[type="text"], input[type="password"], input[type="datetime-local"], textarea, select {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px;
            font-family: inherit; font-size: .9rem; transition: all .2s; background: #f8fafc;
        }
        input:focus, textarea:focus, select:focus {
            outline: none; border-color: var(--p); background: #fff;
            box-shadow: 0 0 0 3px rgba(139,38,62,.12);
        }
		button {
            background: var(--p); color: #fff; border: none; padding: 10px 18px;
            border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .87rem;
            transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
        }
        button:hover { background: var(--p-hover); }
        .btn-secondary { background: #334155; }
        .btn-secondary:hover { background: #1e293b; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: var(--danger-hover); }
        .btn-warning { background: var(--warning); color: #000; }
        .btn-warning:hover { background: #d97706; color: #fff; }
        .row { display: flex; gap: 20px; }
        .row > div { flex: 1; }

        /* ===== TARJETAS Y PANELES DE HERRAMIENTAS ===== */
        .tool-card {
            display: flex; gap: 16px; align-items: flex-start; padding: 20px;
            background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 16px;
        }
        .tool-card .tool-icon { font-size: 2rem; line-height: 1; flex-shrink: 0; }
        .tool-card .tool-body { flex: 1; }
        .tool-card h3 { color: var(--text-dark); margin-bottom: 6px; font-size: 1.05rem; }
        .tool-card p { font-size: .85rem; color: var(--text-muted); margin-bottom: 12px; line-height: 1.5; }
        
        .alert-box {
            padding: 14px 18px; border-radius: 8px; font-size: .85rem; margin-bottom: 18px;
            display: flex; align-items: center; gap: 12px;
        }
        .alert-danger { background: var(--danger-bg); border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: var(--warning-bg); border: 1px solid var(--warning-border); color: #92400e; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* ===== ESTILOS DEL NUEVO IMPORTER (DROPZONE) ===== */
        .import-card-wrapper {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--card-shadow);
        }
        .dropzone-container {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .dropzone-container:hover, .dropzone-container.dragover {
            border-color: var(--p);
            background: #fdf2f4;
            transform: translateY(-2px);
        }
        .dropzone-icon {
            font-size: 2.8rem;
            margin-bottom: 10px;
            line-height: 1;
            transition: transform 0.2s ease;
        }
        .dropzone-container:hover .dropzone-icon {
            transform: scale(1.1);
        }
        .dropzone-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        .dropzone-hint {
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .import-preview-card {
            display: none;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 16px 20px;
            margin-top: 18px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .preview-filename {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .preview-filesize {
            font-size: 0.78rem;
            color: var(--text-muted);
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .preview-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .stat-chip {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 8px 10px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-chip-num {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--p);
            line-height: 1.2;
        }
        .stat-chip-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* ===== DASHBOARD ===== */
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .dash-card {
            background: linear-gradient(135deg, #fff 0%, var(--p-light) 100%);
            border: 1px solid var(--border); border-radius: 12px; padding: 20px;
            text-align: center; transition: all .2s;
        }
        .dash-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow); }
        .dash-card .num { font-size: 2rem; font-weight: 800; color: var(--p); }
        .dash-card .lbl { font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600; }
        .dash-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px; }

        /* ===== BOTÓN AÑADIR DESTACADO Y DISEÑO DE SECCIONES CRUD ===== */
        .btn-add-section {
            background: linear-gradient(135deg, var(--p) 0%, #a8324e 100%);
            color: #ffffff; padding: 9px 18px; border-radius: 20px; font-weight: 700;
            font-size: .85rem; border: none; box-shadow: 0 4px 12px rgba(139, 38, 62, 0.25);
            transition: all .25s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-add-section:hover {
            background: linear-gradient(135deg, #721e32 0%, var(--p) 100%);
            transform: translateY(-2px); box-shadow: 0 6px 16px rgba(139, 38, 62, 0.35);
        }

        .crud-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; font-size: .88rem; margin-top: 5px; }
        .crud-table th {
            text-align: left; padding: 12px 16px; background: transparent; color: var(--text-muted);
            font-size: .72rem; text-transform: uppercase; letter-spacing: 1px; border: none; font-weight: 700;
        }
        .crud-table td {
            padding: 11px 12px;
			border-bottom: 1px solid var(--border);
			vertical-align: middle;
        }
        .crud-table td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid #eef2f6; }
        .crud-table td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid #eef2f6; }
        .crud-table tr:hover td { background: #fffcfb; border-color: #f1dbdf; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }

        .idx-badge {
            background: linear-gradient(135deg, #fce7f3 0%, #f7ede2 100%);
            color: var(--p); font-weight: 800; font-size: .8rem;
            width: 32px; height: 32px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; border: 1px solid #f9a8d4;
        }
/* Botones de acción mejorados con SVG */
        .actions-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 13px;
            border-radius: 8px;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            line-height: 1;
        }
        .btn-action svg {
            width: 14px;
            height: 14px;
            stroke-width: 2;
            flex-shrink: 0;
        }
        .btn-action-edit {
            background: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
        }
        .btn-action-edit:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 10px rgba(37,99,235,0.25);
            transform: translateY(-1px);
        }
        .btn-action-del {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .btn-action-del:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            box-shadow: 0 4px 10px rgba(220,38,38,0.25);
            transform: translateY(-1px);
        }
        .btn-cancel { background: #64748b; padding: 8px 16px; font-size: .85rem; }
        .btn-cancel:hover { background: #475569; }
        .btn-mini { padding: 6px 12px; font-size: .76rem; font-weight: 600; }
        .badge-count { background: var(--p-light); color: var(--p); font-size: .72rem; font-weight: 700; padding: 2px 9px; border-radius: 12px; margin-left: 6px; }

        /* ===== TARJETAS DE MEDIOS ===== */
        .media-card-container {
            background: #f8fafc; border: 1px solid var(--border); border-radius: 12px;
            padding: 20px; margin-bottom: 20px; display: grid; grid-template-columns: 200px 1fr; gap: 20px; align-items: center;
        }
        .media-card-container.audio-card { grid-template-columns: 1fr; }
        .media-preview-box {
            width: 100%; height: 140px; border-radius: 10px; border: 1px solid var(--border);
            background: #e2e8f0; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;
        }
        .media-preview-box img { width: 100%; height: 100%; object-fit: cover; }
        .empty-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #94a3b8; text-align: center; }
        .empty-placeholder svg { width: 36px; height: 36px; stroke: #94a3b8; }
        .empty-placeholder span { font-size: .75rem; font-weight: 500; }
        .media-controls-wrapper { display: flex; flex-direction: column; gap: 12px; }
        .btn-media-upload { background: #334155; color: #fff; padding: 9px 16px; border-radius: 8px; cursor: pointer; font-size: .82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .btn-media-upload:hover { background: #1e293b; }
        .btn-media-select { background: #475569; color: #fff; border: none; padding: 9px 16px; border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; }
        .btn-media-select:hover { background: #334155; }
        .btn-media-clear { background: transparent; color: var(--danger); border: 1px solid rgba(239,68,68,.3); padding: 9px 16px; font-size: .82rem; font-weight: 600; border-radius: 8px; cursor: pointer; }
        .btn-media-clear:hover { background: var(--danger-bg); border-color: var(--danger); }

        /* ===== REPRODUCTOR SPOTIFY ===== */
        .spotify-player { background: var(--sp-dark); color: #fff; border-radius: 12px; padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; margin-top: 5px; border: 1px solid #282828; }
        .sp-top-bar { display: flex; align-items: center; justify-content: space-between; gap: 15px; }
        .sp-track-info { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
        .sp-disc-icon { width: 42px; height: 42px; background: #282828; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sp-green); flex-shrink: 0; }
        .sp-track-details { display: flex; flex-direction: column; overflow: hidden; }
        .sp-track-title { font-size: .88rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sp-track-subtitle { font-size: .75rem; color: #b3b3b3; }
        .sp-controls { display: flex; align-items: center; gap: 12px; }
        .sp-btn { background: transparent; border: none; color: #b3b3b3; cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all .2s; }
        .sp-btn:hover { color: #fff; transform: scale(1.08); }
        .sp-btn-play { background: var(--sp-green); color: #000; width: 38px; height: 38px; padding: 0; }
        .sp-btn-play:hover { background: #1ed760; color: #000; transform: scale(1.05); }
        .sp-progress-wrapper { display: flex; align-items: center; gap: 10px; width: 100%; }
        .sp-time { font-size: .72rem; color: #b3b3b3; font-family: monospace; min-width: 35px; text-align: center; }
        .sp-slider { -webkit-appearance: none; appearance: none; width: 100%; height: 4px; border-radius: 2px; background: #4d4d4d; outline: none; cursor: pointer; }
        .sp-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 12px; height: 12px; border-radius: 50%; background: #fff; cursor: pointer; opacity: 0; transition: opacity .15s; }
        .sp-slider:hover::-webkit-slider-thumb { opacity: 1; }
        .sp-volume-wrapper { display: flex; align-items: center; gap: 8px; width: 130px; flex-shrink: 0; }

        /* ===== SYSTEM TOAST ===== */
        #toast {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(120px);
            background: #1e293b; color: #fff; padding: 12px 22px; border-radius: 10px;
            font-size: .85rem; font-weight: 600; z-index: 4000; transition: transform .35s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,.3); display: flex; align-items: center; gap: 8px;
            pointer-events: none;
        }
        #toast.show { transform: translateX(-50%) translateY(0); }
        #toast.ok { background: var(--ok); } 
        #toast.err { background: var(--danger); }

        /* ===== MODALES BASE ===== */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center;
            z-index: 2000; padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #ffffff; border-radius: 14px; width: 100%; max-width: 680px;
            max-height: 85vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            position: relative; animation: modalIn .2s ease-out;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 14px; border-bottom: 1px solid var(--border); margin-bottom: 16px;
        }
        .modal-header h3 { margin: 0; font-size: 1.15rem; color: var(--text-dark); font-weight: 700; }
        .modal-close {
            background: #f1f5f9; border: none; font-size: 1.25rem; width: 32px; height: 32px;
            border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #64748b; transition: all .15s;
        }
        .modal-close:hover { background: #e2e8f0; color: var(--text-dark); }

        /* ===== MODAL DE ALERTAS Y CONFIRMACIÓN SEPARADO ===== */
        #dialogModal { z-index: 3000; }
        #dialogModal .modal-box { max-width: 440px; padding: 22px; text-align: center; }
        .dialog-icon { font-size: 2.5rem; margin-bottom: 10px; line-height: 1; }
        .dialog-msg { font-size: .95rem; color: var(--text-dark); margin: 12px 0 20px; line-height: 1.5; word-break: break-word; }
        .dialog-actions { display: flex; gap: 10px; justify-content: center; }

        /* Sub-pestañas dentro del selector de medios */
        .media-modal-tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .media-modal-tab { background: #f1f5f9; border: 1px solid var(--border); padding: 7px 14px; border-radius: 6px; font-size: .8rem; font-weight: 600; cursor: pointer; color: var(--text-muted); }
        .media-modal-tab.active { background: var(--p); color: #fff; border-color: var(--p); }

        /* Grid de imágenes subidas */
        .modal-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
            gap: 14px; margin-top: 10px; max-height: 52vh; overflow-y: auto; padding: 4px;
        }
        .file-select-item {
            background: #f8fafc; border: 1px solid var(--border); border-radius: 10px;
            padding: 8px; display: flex; flex-direction: column; gap: 6px; text-align: center;
            transition: all .2s;
        }
        .file-select-item:hover { border-color: var(--p); transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
        .file-thumb-container {
            width: 100%; height: 95px; border-radius: 6px; overflow: hidden; background: #e2e8f0;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .file-thumb-container img { width: 100%; height: 100%; object-fit: cover; }
        .file-select-item span {
            font-size: .72rem; color: var(--text-dark); font-weight: 600; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; display: block;
        }
        .file-actions { display: flex; gap: 4px; }
        .btn-pick-file {
            flex: 1; background: var(--p); color: #fff; border: none; padding: 4px 6px;
            font-size: .72rem; border-radius: 5px; cursor: pointer; font-weight: 600;
        }
        .btn-pick-file:hover { background: var(--p-hover); }
        .btn-delete-file {
            background: transparent; color: var(--danger); border: 1px solid rgba(239,68,68,.3);
            padding: 4px 6px; font-size: .72rem; border-radius: 5px; cursor: pointer;
        }
        .btn-delete-file:hover { background: var(--danger-bg); border-color: var(--danger); }

        /* Lista de audios subidos */
        .audio-list-item {
            background: #f8fafc; border: 1px solid var(--border); border-radius: 10px;
            padding: 12px 14px; margin-bottom: 8px; display: flex; align-items: center;
            justify-content: space-between; gap: 12px; transition: all .15s;
        }
        .audio-list-item:hover { background: #f1f5f9; border-color: var(--p); }
        .audio-info {
            display: flex; align-items: center; gap: 10px; cursor: pointer; flex: 1; min-width: 0;
        }
        .audio-info strong {
            font-size: .85rem; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .btn-delete-audio {
            background: transparent; color: var(--danger); border: 1px solid rgba(239,68,68,.3);
            padding: 5px 10px; font-size: .74rem; border-radius: 6px; cursor: pointer; flex-shrink: 0;
        }
        .btn-delete-audio:hover { background: var(--danger-bg); border-color: var(--danger); }

        @media (max-width: 850px) {
            aside.sidebar { width: 100%; position: relative; top: 0; border-right: none; border-bottom: 1px solid var(--border); }
            .topbar { position: relative; }
            main.content { margin-left: 0; padding: 20px; }
            .media-card-container { grid-template-columns: 1fr; }
            .sp-top-bar { flex-direction: column; align-items: stretch; }
            .sp-volume-wrapper { width: 100%; margin-top: 5px; }
            .crud-table { display: block; overflow-x: auto; }
            .row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand"><span>❤️</span> Propuesta Romántica <span class="acp">AdminCP</span></div>
        <div class="top-links">
            <a href="../index.php" target="_blank">👁 Ver sitio ↗</a>
            <a href="login.php?logout=1" class="logout">⏻ Cerrar sesión</a>
        </div>
    </div>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li class="nav-group¡">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>🏠 Inicio</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-dashboard" onclick="switchTab(event, 'tab-dashboard', 'Inicio', 'Panel principal')">📊 Panel principal</button></li>
                </ul>
            </li>
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>⚙️ Configuración</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-partners" onclick="switchTab(event, 'tab-partners', 'Configuración', 'Nombres Principales')">👩‍❤️‍👨 Nombres Principales</button></li>
                    <li><button class="tab-btn" data-tab="tab-date" onclick="switchTab(event, 'tab-date', 'Configuración', 'Fecha del Contador')">⏳ Fecha del Contador</button></li>
                    <li><button class="tab-btn" data-tab="tab-language-active" onclick="switchTab(event, 'tab-language-active', 'Configuración', 'Idioma Activo')">🌐 Idioma Activo</button></li>
                </ul>
            </li>
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>🌐 Idiomas</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-languages" onclick="switchTab(event, 'tab-languages', 'Idiomas', 'Gestor y editor de frases')">🌐 Editor de Idiomas</button></li>
                </ul>
            </li>
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>🖼️ Multimedia</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-images" onclick="switchTab(event, 'tab-images', 'Multimedia', 'Imágenes')">🖼️ Imágenes</button></li>
                    <li><button class="tab-btn" data-tab="tab-music" onclick="switchTab(event, 'tab-music', 'Multimedia', 'Música')">🎵 Música</button></li>
                </ul>
            </li>
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>✉️ Mensajes</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-letter" onclick="switchTab(event, 'tab-letter', 'Mensajes', 'La carta')">📜 La carta</button></li>
                </ul>
            </li>
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>💖 Secciones</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-reasons" onclick="switchTab(event, 'tab-reasons', 'Secciones', 'Razones')">💖 Razones <span class="badge-count" id="count-reasons">0</span></button></li>
                    <li><button class="tab-btn" data-tab="tab-timeline" onclick="switchTab(event, 'tab-timeline', 'Secciones', 'Línea del tiempo')">⏳ Línea del tiempo <span class="badge-count" id="count-timeline">0</span></button></li>
                    <li><button class="tab-btn" data-tab="tab-wishes" onclick="switchTab(event, 'tab-wishes', 'Secciones', 'Deseos')">✨ Deseos <span class="badge-count" id="count-wishes">0</span></button></li>
                </ul>
            </li>
            <!-- SECCIÓN HERRAMIENTAS OPTIMIZADA -->
            <li class="nav-group">
                <button class="nav-group-header" onclick="toggleGroup(this)"><span>🛠️ Herramientas</span><span class="arrow">▶</span></button>
                <ul class="nav-group-sub">
                    <li><button class="tab-btn" data-tab="tab-backups" onclick="switchTab(event, 'tab-backups', 'Herramientas', 'Copias de seguridad')">💾 Copias de seguridad</button></li>
                    <li><button class="tab-btn" data-tab="tab-security" onclick="switchTab(event, 'tab-security', 'Herramientas', 'Seguridad')">🔐 Seguridad</button></li>
                    <li><button class="tab-btn" data-tab="tab-danger-zone" onclick="switchTab(event, 'tab-danger-zone', 'Herramientas', 'Zona de peligro')">🗑️ Zona de peligro</button></li>
                    <li><button class="tab-btn" data-tab="tab-uninstall" onclick="switchTab(event, 'tab-uninstall', 'Herramientas', 'Desinstalar')">🚨 Desinstalar</button></li>
                </ul>
            </li>
        </ul>
    </aside>

    <main class="content">
        <div class="breadcrumb">🏠 Panel de Control &nbsp;»&nbsp; <b id="crumb-cat">Inicio</b> &nbsp;»&nbsp; <span id="crumb-sub">Panel principal</span></div>

        <!-- ============ DASHBOARD ============ -->
        <div id="tab-dashboard" class="tab-content">
            <div class="page-header">
                <h1>Panel principal</h1>
                <p>Bienvenido al AdminCP. Gestiona el contenido en MySQL y los idiomas en un solo lugar.</p>
            </div>
            <section>
                <h2>📊 Resumen del contenido en la Base de Datos</h2>
                <div class="dash-grid" style="margin-top:15px;">
                    <div class="dash-card"><div class="num" id="dash-reasons"><?php echo $countReasons; ?></div><div class="lbl">Razones</div></div>
                    <div class="dash-card"><div class="num" id="dash-timeline"><?php echo $countTimeline; ?></div><div class="lbl">Capítulos</div></div>
                    <div class="dash-card"><div class="num" id="dash-wishes"><?php echo $countWishes; ?></div><div class="lbl">Deseos</div></div>
                    <div class="dash-card"><div class="num" id="dash-letter"><?php echo $countLetter; ?></div><div class="lbl">Párrafos de carta</div></div>
                </div>
                <div class="dash-actions">
                    <button onclick="window.location.href='../index.php'">👁 Ver el sitio</button>
                    <button class="btn-secondary" onclick="switchTabById('tab-partners')">⚙️ Configurar nombres</button>
                    <button class="btn-secondary" onclick="switchTabById('tab-date')">⏳ Fecha del contador</button>
                    <button class="btn-secondary" onclick="switchTabById('tab-languages')">🌐 Editor de Idiomas</button>
                    <button class="btn-secondary" onclick="switchTabById('tab-letter')">📜 Editar la carta</button>
                </div>
            </section>
            <section>
                <h2>💡 Cómo funciona el sistema</h2>
                <p style="font-size:.88rem;color:var(--text-muted);line-height:1.7;">
                    • <b>Base de Datos MySQL</b>: Guarda los nombres, fecha del contador, imágenes, música, carta, razones, línea del tiempo y deseos.<br>
                    • <b>Idiomas</b>: En <b>🌐 Editor de Idiomas</b> puedes editar todas las frases y textos en un solo sitio. Se guardan directamente en los archivos <code>languages/*.php</code> y se reflejan al instante.<br>
                    • <b>Reiniciar la Propuesta</b>: Al pulsar <b>"Reiniciar Propuesta / Limpiar Fecha"</b> en <b>Configuración » Fecha del Contador</b>, la fecha se borra para volver a mostrar el botón "¡Sí, Quiero!".
                </p>
            </section>
        </div>

        <!-- ============ CONFIGURACIÓN: NOMBRES PRINCIPALES ============ -->
        <div id="tab-partners" class="tab-content">
            <div class="page-header">
                <h1>Nombres Principales</h1>
                <p>Nombres de la pareja que se mostrarán en la propuesta, la carta y las cabeceras.</p>
            </div>
            <section>
                <h2>👩‍❤️‍👨 Nombres de la Pareja</h2>
                <div class="row" style="margin-top:15px;">
                    <div>
                        <label>Pareja 1</label>
                        <input type="text" id="partner_one" value="<?php echo htmlspecialchars($initialPartner1); ?>" placeholder="Ej: María">
                    </div>
                    <div>
                        <label>Pareja 2</label>
                        <input type="text" id="partner_two" value="<?php echo htmlspecialchars($initialPartner2); ?>" placeholder="Ej: Carlos">
                    </div>
                </div>
                <button onclick="saveSettings()" style="margin-top:20px;">💾 Guardar Nombres</button>
            </section>
        </div>

        <!-- ============ CONFIGURACIÓN: FECHA DEL CONTADOR Y REINICIO ============ -->
        <div id="tab-date" class="tab-content">
            <div class="page-header">
                <h1>Fecha del Contador y Estado de la Propuesta</h1>
                <p>Gestiona la fecha de inicio de la relación para el contador en tiempo real o reinicia la propuesta.</p>
            </div>
            <section>
                <h2>⏳ Fecha de inicio de la relación</h2>
                <label>Fecha y hora de inicio</label>
                <input type="datetime-local" id="start_date" value="<?php echo htmlspecialchars($initialStartDate); ?>">
                <p class="field-hint">Si esta fecha se encuentra vacía, la web pública interpretará que la propuesta aún no ha sido aceptada y mostrará automáticamente el botón "¡Sí, Quiero!". Al ser pulsado por tu pareja, la fecha/hora exacta se guardará en MySQL automáticamente.</p>
                
                <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
                    <button onclick="saveSettings()">💾 Guardar Fecha Manualmente</button>
                </div>
            </section>

            <section>
                <h2>🔄 Estado de la Propuesta y Reinicio</h2>
                <div class="tool-card" style="margin-top:15px;margin-bottom:0;">
                    <div class="tool-icon">⏳</div>
                    <div class="tool-body">
                        <h3>Estado actual del contador</h3>
                        <p id="reset_status_text">
                            <?php if (!empty($initialStartDate)): ?>
                                🟢 La propuesta tiene la fecha registrada en MySQL: <b><?php echo htmlspecialchars($initialStartDate); ?></b>
                            <?php else: ?>
                                🟡 La propuesta está en estado inicial (esperando que tu pareja pulse "¡Sí, Quiero!").
                            <?php endif; ?>
                        </p>
                        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px;">
                            Al reiniciar la propuesta se limpiará únicamente la fecha/hora guardada para permitir repetir la experiencia. Las imágenes, canciones, frases y mensajes <b>se conservarán intactos</b>.
                        </p>
                        <button class="btn-warning" onclick="clearStartDate()">🔄 Reiniciar Propuesta / Limpiar Fecha</button>
                    </div>
                </div>
            </section>
        </div>

        <!-- ============ CONFIGURACIÓN: IDIOMA ACTIVO ============ -->
        <div id="tab-language-active" class="tab-content">
            <div class="page-header">
                <h1>Idioma Activo del Sitio</h1>
                <p>Selecciona el idioma principal con el que se mostrará la web pública.</p>
            </div>
            <section>
                <h2>🌐 Idioma principal</h2>
                <label>Selecciona el idioma con el que se mostrará la web pública</label>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <select id="site_lang" style="max-width:300px;">
                        <?php foreach ($availableLangs as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code === $initialSiteLang) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-secondary" onclick="saveSettings()">💾 Activar Idioma</button>
                    <button type="button" onclick="switchTabById('tab-languages')">✏️ Ir al Editor de Frases</button>
                </div>
            </section>
        </div>

        <!-- ============ GESTOR DE IDIOMAS ============ -->
        <div id="tab-languages" class="tab-content">
            <div class="page-header">
                <h1>Gestor y Editor de Idiomas</h1>
                <p>Edita todas las frases y textos de la web directamente en los archivos de idioma desde este único panel.</p>
            </div>
            
            <section>
                <div class="section-header">
                    <h2>📂 Seleccionar Paquete de Idioma</h2>
                </div>
                <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                    <div style="flex:1;min-width:240px;">
                        <label style="margin-top:0;">Paquete de idioma a editar</label>
                        <select id="edit_lang_select" onchange="loadLanguageToEdit(this.value)">
                            <?php foreach ($availableLangs as $code => $name): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>.php)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-end;margin-top:1.5rem">
                        <button type="button" class="btn-secondary" onclick="openNewLangModal()">➕ Crear nuevo idioma</button>
                        <button type="button" onclick="loadLanguageToEdit(document.getElementById('edit_lang_select').value)">↺ Recargar</button>
                    </div>
                </div>
            </section>

            <section>
                <div class="section-header">
                    <h2>✏️ Frases del archivo: <span id="current_editing_filename" style="color:var(--text-dark);">es.php</span></h2>
                    <div style="display:flex;gap:10px;">
                        <button type="button" onclick="saveLanguageFile()">💾 Guardar Frases en Archivo</button>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label>Nombre del Idioma</label>
                        <input type="text" id="lang_meta_name" placeholder="Ej: Español">
                    </div>
                    <div>
                        <label>Formato regional de fecha (date_locale)</label>
                        <select id="lang_meta_locale">
                            <?php foreach (available_locales() as $code => $name): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h3 style="margin-top:30px;border-bottom:1px solid var(--border);padding-bottom:8px;">Cabecera y Propuesta</h3>
                <label>Subtítulo de la cabecera (<code>sub_header_title</code>)</label>
                <input type="text" id="lang_phrase_sub_header_title">
                <label>Pregunta de la propuesta (<code>proposal_question</code>)</label>
                <input type="text" id="lang_phrase_proposal_question">
                <label>Texto del botón de aceptación (<code>btn_yes_text</code>)</label>
                <input type="text" id="lang_phrase_btn_yes_text">
                <label>Frase final bajo la segunda foto (<code>final_phrase</code>)</label>
                <input type="text" id="lang_phrase_final_phrase">

                <h3 style="margin-top:30px;border-bottom:1px solid var(--border);padding-bottom:8px;">Sobre Interactivo</h3>
                <label>Título del sobre (<code>envelope_title</code>)</label>
                <input type="text" id="lang_phrase_envelope_title">
                <label>Subtítulo del sobre (<code>envelope_subtitle</code>)</label>
                <input type="text" id="lang_phrase_envelope_subtitle">

                <h3 style="margin-top:30px;border-bottom:1px solid var(--border);padding-bottom:8px;">Contador de Tiempo</h3>
                <label>Título del contador (<code>counter_title</code>)</label>
                <input type="text" id="lang_phrase_counter_title">
                <div class="row">
                    <div><label>Etiqueta "Años" (<code>label_years</code>)</label><input type="text" id="lang_phrase_label_years"></div>
                    <div><label>Etiqueta "Meses" (<code>label_months</code>)</label><input type="text" id="lang_phrase_label_months"></div>
                </div>
                <div class="row">
                    <div><label>Etiqueta "Días" (<code>label_days</code>)</label><input type="text" id="lang_phrase_label_days"></div>
                    <div><label>Etiqueta "Horas" (<code>label_hours</code>)</label><input type="text" id="lang_phrase_label_hours"></div>
                </div>
                <div class="row">
                    <div><label>Etiqueta "Minutos" (<code>label_minutes</code>)</label><input type="text" id="lang_phrase_label_minutes"></div>
                    <div><label>Etiqueta "Segundos" (<code>label_seconds</code>)</label><input type="text" id="lang_phrase_label_seconds"></div>
                </div>
                <div class="row">
                    <div><label>Prefijo pacto sellado (<code>date_prefix_sealed</code>)</label><input type="text" id="lang_phrase_date_prefix_sealed"></div>
                    <div><label>Prefijo fecha iniciada (<code>date_prefix_started</code>)</label><input type="text" id="lang_phrase_date_prefix_started"></div>
                </div>

                <h3 style="margin-top:30px;border-bottom:1px solid var(--border);padding-bottom:8px;">Títulos de Secciones y Botones</h3>
                <label>Título de Razones (<code>reasons_title</code>)</label>
                <input type="text" id="lang_phrase_reasons_title">
                <label>Placeholder del generador de razones (<code>reason_placeholder</code>)</label>
                <input type="text" id="lang_phrase_reason_placeholder">
                <label>Texto botón otra razón (<code>btn_next_reason_text</code>)</label>
                <input type="text" id="lang_phrase_btn_next_reason_text">
                <label>Título de Línea de Tiempo (<code>timeline_section_title</code>)</label>
                <input type="text" id="lang_phrase_timeline_section_title">
                <label>Título de Deseos Futuros (<code>wishes_section_title</code>)</label>
                <input type="text" id="lang_phrase_wishes_section_title">

                <h3 style="margin-top:30px;border-bottom:1px solid var(--border);padding-bottom:8px;">Barra de Música</h3>
                <div class="row">
                    <div><label>Texto en reproducción/pausado (<code>music_play_text</code>)</label><input type="text" id="lang_phrase_music_play_text"></div>
                    <div><label>Texto en pausa (<code>music_pause_text</code>)</label><input type="text" id="lang_phrase_music_pause_text"></div>
                </div>

                <div style="display:flex;gap:12px;margin-top:30px;align-items:center;">
                    <button type="button" onclick="saveLanguageFile()">💾 Guardar Frases en Archivo</button>
                    <label style="margin:0;display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="lang_set_active" style="width:auto;"> Establecer también como idioma activo del sitio
                    </label>
                </div>
            </section>
        </div>

        <!-- ============ IMÁGENES ============ -->
        <div id="tab-images" class="tab-content">
            <div class="page-header">
                <h1>Gestión de Imágenes</h1>
                <p>Portada y segunda imagen almacenadas en la base de datos MySQL (tabla <code>ui_media</code>).</p>
            </div>
            <section>
                <h2>Imagen de Portada</h2>
                <div class="media-card-container" style="margin-top:15px;">
                    <div class="media-preview-box">
                        <img id="preview_cover" src="" alt="Vista previa" style="display:none;" onerror="handleImgError(this)">
                        <div id="no_img_cover" class="empty-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <span>Sin imagen asignada</span>
                        </div>
                    </div>
                    <div class="media-controls-wrapper">
                        <input type="text" id="ui_cover_image_url" placeholder="URL externa o uploads/images/..." oninput="updatePreview('ui_cover_image_url','preview_cover','no_img_cover')">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="btn-media-upload" onclick="document.getElementById('upload_cover_file').click()">📁 Subir foto</button>
                            <input type="file" id="upload_cover_file" accept="image/*" style="display:none;" onchange="handleFileUpload(this,'image_file','ui_cover_image_url',()=>updatePreview('ui_cover_image_url','preview_cover','no_img_cover'))">
                            <button type="button" class="btn-media-select" onclick="openMediaSelector('image','ui_cover_image_url',()=>updatePreview('ui_cover_image_url','preview_cover','no_img_cover'))">🖼️ Elegir subidas</button>
                            <button type="button" class="btn-media-select" onclick="setDefaultCoverImage()">🌟 Imagen por defecto</button>
                            <button type="button" class="btn-media-clear" onclick="clearImageField('ui_cover_image_url','preview_cover','no_img_cover')">Limpiar</button>
                        </div>
                    </div>
                </div>

                <h2 style="margin-top:30px;">Segunda Imagen (Sección final)</h2>
                <div class="media-card-container" style="margin-top:15px;">
                    <div class="media-preview-box">
                        <img id="preview_second" src="" alt="Vista previa" style="display:none;" onerror="handleImgError(this)">
                        <div id="no_img_second" class="empty-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <span>Sin imagen asignada</span>
                        </div>
                    </div>
                    <div class="media-controls-wrapper">
                        <input type="text" id="ui_second_image_url" placeholder="URL externa o uploads/images/..." oninput="updatePreview('ui_second_image_url','preview_second','no_img_second')">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <button type="button" class="btn-media-upload" onclick="document.getElementById('upload_second_file').click()">📁 Subir foto</button>
                            <input type="file" id="upload_second_file" accept="image/*" style="display:none;" onchange="handleFileUpload(this,'image_file','ui_second_image_url',()=>updatePreview('ui_second_image_url','preview_second','no_img_second'))">
                            <button type="button" class="btn-media-select" onclick="openMediaSelector('image','ui_second_image_url',()=>updatePreview('ui_second_image_url','preview_second','no_img_second'))">🖼️ Elegir subidas</button>
                            <button type="button" class="btn-media-select" onclick="setDefaultSecondImage()">🌟 Imagen por defecto</button>
                            <button type="button" class="btn-media-clear" onclick="clearImageField('ui_second_image_url','preview_second','no_img_second')">Limpiar</button>
                        </div>
                    </div>
                </div>

                <button onclick="saveMedia()" style="margin-top:10px;">💾 Guardar Imágenes en MySQL</button>
            </section>
        </div>

        <!-- ============ MÚSICA ============ -->
        <div id="tab-music" class="tab-content">
            <div class="page-header">
                <h1>Música de fondo</h1>
                <p>La canción que acompañará la propuesta en la web.</p>
            </div>
            <section>
                <h2>🎵 Reproductor y pista actual</h2>
                <div class="media-card-container audio-card" style="margin-top:15px;">
                    <div class="media-controls-wrapper">
                        <input type="text" id="ui_bg_music_url" placeholder="URL MP3 o uploads/audios/..." oninput="updateAudioPreview()">
                        <audio id="audio_preview" preload="metadata"></audio>
                        <div class="spotify-player">
                            <div class="sp-top-bar">
                                <div class="sp-track-info">
                                    <div class="sp-disc-icon">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
                                    </div>
                                    <div class="sp-track-details">
                                        <span class="sp-track-title" id="sp_title">Cargando pista...</span>
                                        <span class="sp-track-subtitle">Música de fondo del sitio</span>
                                    </div>
                                </div>
                                <div class="sp-controls">
                                    <button type="button" class="sp-btn" onclick="seekAudio(-10)" title="Retroceder 10s">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 19 2 12 11 5 11 19"></polygon><polygon points="22 19 13 12 22 5 22 19"></polygon></svg>
                                    </button>
                                    <button type="button" class="sp-btn sp-btn-play" id="sp_play_btn" onclick="togglePlayAudio()" title="Reproducir/Pausar">
                                        <svg id="sp_play_icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                    </button>
                                    <button type="button" class="sp-btn" onclick="seekAudio(10)" title="Avanzar 10s">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 19 22 12 13 5 13 19"></polygon><polygon points="2 19 11 12 2 5 2 19"></polygon></svg>
                                    </button>
                                </div>
                                <div class="sp-volume-wrapper">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b3b3b3" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                    <input type="range" id="sp_volume_slider" class="sp-slider" min="0" max="1" step="0.01" value="0.8" oninput="setAudioVolume(this.value)">
                                </div>
                            </div>
                            <div class="sp-progress-wrapper">
                                <span class="sp-time" id="sp_current_time">0:00</span>
                                <input type="range" id="sp_seek_slider" class="sp-slider" min="0" max="100" value="0" oninput="onSeekSliderChange(this.value)">
                                <span class="sp-time" id="sp_duration">0:00</span>
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:6px;">
                            <button type="button" class="btn-media-upload" onclick="document.getElementById('upload_audio_file').click()">🎵 Subir canción</button>
                            <input type="file" id="upload_audio_file" accept="audio/*" style="display:none;" onchange="handleFileUpload(this,'audio_file','ui_bg_music_url',updateAudioPreview)">
                            <button type="button" class="btn-media-select" onclick="openMediaSelector('audio','ui_bg_music_url',updateAudioPreview)">🎼 Elegir subidas</button>
                            <button type="button" class="btn-media-select" onclick="setDefaultMusic()">🌟 Música por defecto</button>
                        </div>
                    </div>
                </div>
                <button onclick="saveMedia()" style="margin-top:15px;">💾 Guardar Música en MySQL</button>
            </section>
        </div>

        <!-- ============ CARTA ============ -->
        <div id="tab-letter" class="tab-content">
            <div class="page-header">
                <h1>Carta romántica</h1>
                <p>El mensaje principal almacenado en la tabla <code>letter_paragraphs</code> de MySQL.</p>
            </div>
            <section>
                <h2>Contenido de la carta</h2>
                <label>Párrafos (separados por doble salto de línea / Enter doble)</label>
                <textarea id="letter_paragraphs_text" rows="14" placeholder="Escribe cada párrafo separado por dos Enters..."></textarea>
                <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
                    <button onclick="saveLetter()">💾 Guardar Carta en MySQL</button>
                    <button class="btn-cancel" onclick="loadAdmin();toast('Carta recargada desde MySQL','ok')">↺ Descartar cambios</button>
                </div>
            </section>
        </div>

        <!-- ============ RAZONES ============ -->
        <div id="tab-reasons" class="tab-content">
            <div class="page-header">
                <h1>Razones por las que te amo</h1>
                <p>Lista dinámica de razones guardadas en la tabla <code>reasons</code> de MySQL.</p>
            </div>
            <section>
                <div class="section-header">
                    <h2>💖 Razones <span class="badge-count" id="count-reasons-2">0</span></h2>
                    <button class="btn-add-section" onclick="openFormModal('reason')">➕ Añadir Razón</button>
                </div>
                <table class="crud-table">
                    <thead><tr><th style="width:60px;">Nº</th><th>Contenido</th><th style="width:180px; text-align:right;">Acciones</th></tr></thead>
                    <tbody id="reasons-list"></tbody>
                </table>
            </section>
        </div>

        <!-- ============ TIMELINE ============ -->
        <div id="tab-timeline" class="tab-content">
            <div class="page-header">
                <h1>Línea del tiempo</h1>
                <p>Momentos clave almacenados en la tabla <code>timeline_chapters</code> de MySQL.</p>
            </div>
            <section>
                <div class="section-header">
                    <h2>⏳ Línea del tiempo <span class="badge-count" id="count-timeline-2">0</span></h2>
                    <button class="btn-add-section" onclick="openFormModal('timeline')">➕ Añadir Capítulo</button>
                </div>
                <table class="crud-table">
                    <thead><tr><th style="width:60px;">Nº</th><th>Capítulo / Etiqueta</th><th>Título del momento</th><th>Descripción</th><th style="width:180px; text-align:right;">Acciones</th></tr></thead>
                    <tbody id="timeline-list"></tbody>
                </table>
            </section>
        </div>

        <!-- ============ DESEOS ============ -->
        <div id="tab-wishes" class="tab-content">
            <div class="page-header">
                <h1>Deseos futuros</h1>
                <p>Sueños y tarjetas secretas guardadas en la tabla <code>wishes</code> de MySQL.</p>
            </div>
            <section>
                <div class="section-header">
                    <h2>✨ Deseos <span class="badge-count" id="count-wishes-2">0</span></h2>
                    <button class="btn-add-section" onclick="openFormModal('wish')">➕ Añadir Deseo</button>
                </div>
                <table class="crud-table">
                    <thead><tr><th style="width:60px;">Nº</th><th style="width:65px;">Icono</th><th>Etiqueta / Deseo</th><th>Mensaje secreto</th><th style="width:180px; text-align:right;">Acciones</th></tr></thead>
                    <tbody id="wishes-list"></tbody>
                </table>
            </section>
        </div>

        <!-- ============ HERRAMIENTAS - 1. COPIAS DE SEGURIDAD ============ -->
        <div id="tab-backups" class="tab-content">
            <div class="page-header">
                <h1>Copias de seguridad</h1>
                <p>Exporta e importa de forma segura la estructura y contenidos enteros almacenados en la base de datos MySQL.</p>
            </div>
            <section>
                <h2>💾 Respaldo y Restauración del Sistema</h2>
                <div class="alert-box alert-info" style="margin-top:15px;">
                    <span>ℹ️</span>
                    <div>
                        Las copias de seguridad incluyen los nombres de la pareja, fecha del contador, enlaces multimedia, textos de la carta, razones, capítulos de la línea del tiempo y lista de deseos en un único archivo <b>.json</b> portable.
                    </div>
                </div>

                <div class="tool-card">
                    <div class="tool-icon">⬇️</div>
                    <div class="tool-body">
                        <h3>Exportar copia de seguridad actual</h3>
                        <p>Descarga un archivo con todo el contenido registrado en MySQL para conservarlo o migrarlo a otro servidor.</p>
                        <button onclick="exportData()">💾 Descargar Respaldo JSON</button>
                    </div>
                </div>

                <!-- IMPORTAR Y RESTAURAR MEJORADO CON DROPZONE E PREVIEW -->
                <div class="import-card-wrapper" style="margin-bottom:0;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                        <span style="font-size:1.8rem; line-height:1;">⬆️</span>
                        <div>
                            <h3 style="font-size:1.05rem; color:var(--text-dark); margin:0;">Importar y restaurar desde archivo</h3>
                            <p style="font-size:.84rem; color:var(--text-muted); margin:2px 0 0;">Restaura todo el contenido del sitio desde un archivo JSON exportado previamente.</p>
                        </div>
                    </div>

                    <div class="alert-box alert-warning" style="margin-bottom:16px; padding:10px 14px; font-size:.82rem;">
                        <span>⚠️</span>
                        <div><b>Atención:</b> Restaurar una copia reemplazarán los datos actuales almacenados en la base de datos.</div>
                    </div>

                    <!-- Dropzone -->
                    <div class="dropzone-container" id="dropzone" onclick="document.getElementById('import_file').click()">
                        <input type="file" id="import_file" accept="application/json,.json" style="display:none;" onchange="handleImportFileSelect(this)">
                        <div class="dropzone-icon">📄</div>
                        <div class="dropzone-title">Haz clic para seleccionar o arrastra tu archivo JSON aquí</div>
                        <div class="dropzone-hint">Formatos soportados: .json (Copia de respaldo oficial)</div>
                    </div>

                    <!-- Card de Vista Previa del Archivo Seleccionado -->
                    <div class="import-preview-card" id="import_preview_card">
                        <div class="preview-header">
                            <div class="preview-filename">
                                <span>📋</span>
                                <span id="preview_filename_text">respaldo.json</span>
                            </div>
                            <span class="preview-filesize" id="preview_filesize_text">0 KB</span>
                        </div>
                        
                        <div class="preview-stats-grid">
                            <div class="stat-chip">
                                <div class="stat-chip-num" id="stat_reasons">0</div>
                                <div class="stat-chip-label">Razones</div>
                            </div>
                            <div class="stat-chip">
                                <div class="stat-chip-num" id="stat_timeline">0</div>
                                <div class="stat-chip-label">Capítulos</div>
                            </div>
                            <div class="stat-chip">
                                <div class="stat-chip-num" id="stat_wishes">0</div>
                                <div class="stat-chip-label">Deseos</div>
                            </div>
                            <div class="stat-chip">
                                <div class="stat-chip-num" id="stat_letter">0</div>
                                <div class="stat-chip-label">Párrafos</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center;">
                            <button type="button" class="btn-cancel" onclick="resetImportFile()" style="padding:8px 14px; font-size:.8rem;">✕ Quitar</button>
                            <button type="button" class="btn-warning" onclick="importData()" style="padding:8px 18px; font-size:.85rem; font-weight:700;">🔄 Restaurar Copia Ahora</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ============ HERRAMIENTAS - 2. SEGURIDAD ============ -->
        <div id="tab-security" class="tab-content">
            <div class="page-header">
                <h1>Seguridad</h1>
                <p>Gestiona tus credenciales de acceso al Panel de Administración (AdminCP).</p>
            </div>
            <section>
                <h2>🔐 Credenciales de Administrador</h2>
                <div class="alert-box alert-info" style="margin-top:15px;">
                    <span>🛡️</span>
                    <div>
                        Usuario actual registrado: <b><?php echo htmlspecialchars(get_admin_username()); ?></b>.<br>
                        Las contraseñas se almacenan mediante el algoritmo seguro de hashing nativo de PHP (Bcrypt/Argon2).
                    </div>
                </div>

                <div style="max-width:550px;margin-top:10px;">
                    <label>Nuevo nombre de usuario</label>
                    <input type="text" id="sec_new_user" placeholder="Dejar vacío si no deseas cambiarlo" autocomplete="off">
                    <p class="field-hint">Si lo dejas en blanco, mantendrás el usuario actual.</p>

                    <label>Nueva contraseña</label>
                    <input type="password" id="sec_new_password" placeholder="Mínimo 5 caracteres" autocomplete="new-password" oninput="checkPassStrength(this.value)">
                    <div id="pass_strength_bar" style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:6px;transition:all .3s;"></div>

                    <label>Confirmar nueva contraseña</label>
                    <input type="password" id="sec_confirm_password" placeholder="Repite la nueva contraseña" autocomplete="new-password">

                    <hr style="border:0;border-top:1px dashed var(--border);margin:20px 0;">

                    <label style="color:var(--danger);">Contraseña ACTUAL (Requerida para autorizar cambios)</label>
                    <input type="password" id="sec_current_password" placeholder="Escribe tu contraseña actual" autocomplete="current-password" required>

                    <button onclick="changeCredentials()" style="margin-top:20px;">🔒 Actualizar Credenciales</button>
                </div>
            </section>
        </div>

        <!-- ============ HERRAMIENTAS - 3. ZONA DE PELIGRO ============ -->
        <div id="tab-danger-zone" class="tab-content">
            <div class="page-header">
                <h1>Zona de peligro</h1>
                <p>Opciones de restauración con impacto estructural directo en la base de datos.</p>
            </div>
            <section style="border-color:#fca5a5;background:#fff5f5;">
                <h2 style="color:var(--danger);">⚠️ Restaurar Valores de Fábrica</h2>
                <p style="font-size:.88rem;color:#7f1d1d;margin-top:8px;line-height:1.6;">
                    Esta acción vaciará el contenido personalizado existente en MySQL y restaurará los ejemplos y configuraciones predeterminadas con las que se instaló el sistema.
                </p>

                <div class="alert-box alert-danger" style="margin-top:15px;">
                    <span>🚫</span>
                    <div><b>Advertencia irrecuperable:</b> Se borrarán todas tus razones, frases de la carta, momentos de la línea del tiempo y deseos configurados. Se recomienda descargar una copia de seguridad previamente.</div>
                </div>

                <button class="btn-danger" style="font-weight:700;" onclick="resetData()">🗑️ Restaurar Todo a Valores por Defecto</button>
            </section>
        </div>

        <!-- ============ HERRAMIENTAS - 4. DESINSTALAR ============ -->
        <div id="tab-uninstall" class="tab-content">
            <div class="page-header">
                <h1>Desinstalar sistema</h1>
                <p>Elimina completamente la aplicación, la base de datos y libera los archivos de instalación.</p>
            </div>
            <section style="border-color:#fca5a5;background:#fff8f8;">
                <h2 style="color:var(--danger);">🚨 Eliminar Sistema y Reinstalar</h2>
                <p style="font-size:.88rem;color:#7f1d1d;margin-top:8px;line-height:1.6;">
                    Ejecutar la desinstalación eliminará permanentemente todas las tablas creadas en MySQL, eliminará los archivos de configuración en <code>data/</code> y te redirigirá al asistente de instalación inicial (<code>install.php</code>).
                </p>

                <div class="alert-box alert-danger" style="margin-top:15px;">
                    <span>🔥</span>
                    <div><b>¡Peligro extremo!</b> Esta acción borra completamente la aplicación de la base de datos. No podrás deshacer este paso.</div>
                </div>

                <div style="max-width:420px;margin-top:20px;">
                    <label style="color:var(--danger);font-weight:700;">Escribe tu contraseña de administrador para confirmar:</label>
                    <input type="password" id="uninstall_admin_pass" placeholder="Tu contraseña de admin actual" autocomplete="current-password">
                    <button class="btn-danger" style="margin-top:15px;width:100%;justify-content:center;font-weight:700;" onclick="uninstallSystem()">🚨 Confirmar y Desinstalar la Aplicación</button>
                </div>
            </section>
        </div>
    </main>

    <!-- MODAL DE DIÁLOGOS Y NOTIFICACIONES -->
    <div id="dialogModal" class="modal-overlay">
        <div class="modal-box">
            <div id="dialogIcon" class="dialog-icon">⚠️</div>
            <h3 id="dialogTitle" style="color: var(--text-dark); margin: 0; font-size: 1.15rem;">Confirmación</h3>
            <p id="dialogMsg" class="dialog-msg"></p>
            <div id="dialogActions" class="dialog-actions"></div>
        </div>
    </div>

    <!-- MODAL SELECTOR DE ARCHIVOS -->
    <div id="mediaModal" class="modal-overlay" onclick="if(event.target===this)closeMediaSelector()">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">📁 Gestor de Archivos Subidos</h3>
                <button class="modal-close" onclick="closeMediaSelector()">&times;</button>
            </div>
            <div id="modalContainer" class="modal-grid"></div>
        </div>
    </div>

    <!-- MODAL FORMULARIO CRUD -->
    <div id="formModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="formModalTitle">Añadir elemento</h3>
                <button class="modal-close" onclick="closeFormModal()">&times;</button>
            </div>
            <div id="formModalBody"></div>
        </div>
    </div>

    <!-- MODAL CREAR NUEVO IDIOMA -->
    <div id="newLangModal" class="modal-overlay">
        <div class="modal-box" style="max-width:440px;">
            <div class="modal-header">
                <h3>➕ Crear nuevo idioma</h3>
                <button class="modal-close" onclick="closeNewLangModal()">&times;</button>
            </div>
            <label>Código de idioma (ej: fr, it, pt, de)</label>
            <input type="text" id="new_lang_code" placeholder="fr" maxlength="10">
            <label>Nombre legible (ej: Français)</label>
            <input type="text" id="new_lang_name" placeholder="Français">
            <label>Basar frases iniciales en</label>
            <select id="new_lang_source">
                <?php foreach ($availableLangs as $code => $name): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($code); ?>)</option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                <button class="btn-cancel" onclick="closeNewLangModal()">Cancelar</button>
                <button onclick="createNewLanguageSubmit()">Crear y Editar</button>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        // ============================================================
        //  AdminCP — Lógica del panel (MySQL + Gestor de Idiomas)
        // ============================================================
        const API_BASE = '../';
        let globalData = {};
        const DEFAULT_COVER_URL = '<?php echo DEFAULT_COVER_IMAGE; ?>';
        const DEFAULT_SECOND_URL = '<?php echo DEFAULT_SECOND_IMAGE; ?>';
        const DEFAULT_MUSIC_URL = '<?php echo DEFAULT_MUSIC_URL; ?>';

        let currentTargetInputId = null, currentSelectCallback = null, currentMediaType = 'image';
        let currentEditingLangCode = 'es';

        // ---------- SISTEMA DE DIÁLOGOS/MODALES ----------
        let dialogResolver = null;

        function closeDialogModal() {
            const modal = document.getElementById('dialogModal');
            if (modal) modal.classList.remove('active');
        }

        function customAlert(msg, title = 'Notificación', icon = 'ℹ️') {
            closeDialogModal();
            return new Promise((resolve) => {
                dialogResolver = resolve;
                document.getElementById('dialogIcon').textContent = icon;
                document.getElementById('dialogTitle').textContent = title;
                document.getElementById('dialogMsg').textContent = msg;
                const actions = document.getElementById('dialogActions');
                actions.innerHTML = '<button id="btnDialogOk" style="min-width:100px; justify-content:center;">Aceptar</button>';
                
                document.getElementById('btnDialogOk').onclick = () => {
                    closeDialogModal();
                    if (dialogResolver) dialogResolver(true);
                };
                
                document.getElementById('dialogModal').classList.add('active');
            });
        }

        function customConfirm(msg, title = 'Confirmar acción', icon = '⚠️') {
            closeDialogModal();
            return new Promise((resolve) => {
                dialogResolver = resolve;
                document.getElementById('dialogIcon').textContent = icon;
                document.getElementById('dialogTitle').textContent = title;
                document.getElementById('dialogMsg').textContent = msg;
                const actions = document.getElementById('dialogActions');
                actions.innerHTML = `
                    <button id="btnDialogCancel" class="btn-cancel" style="min-width:90px; justify-content:center;">Cancelar</button>
                    <button id="btnDialogOk" style="background:var(--danger); min-width:90px; justify-content:center;">Aceptar</button>
                `;
                
                document.getElementById('btnDialogCancel').onclick = () => {
                    closeDialogModal();
                    if (dialogResolver) dialogResolver(false);
                };
                document.getElementById('btnDialogOk').onclick = () => {
                    closeDialogModal();
                    if (dialogResolver) dialogResolver(true);
                };
                
                document.getElementById('dialogModal').classList.add('active');
            });
        }

        // ---------- Toast Flotante ----------
        let toastTimer = null;
        function toast(msg, type = 'ok') {
            closeDialogModal();
            const t = document.getElementById('toast');
            if (!t) return;
            t.textContent = (type === 'ok' ? '✅ ' : '⚠️ ') + msg;
            t.className = 'show ' + type;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
        }

        async function apiFetch(endpoint, options = {}) {
            const cleanEndpoint = endpoint.replace(/^(\.\/|\/)/, '');
            try {
                const res = await fetch(API_BASE + cleanEndpoint, options);
                if (res.status === 401) { window.location.href = 'login.php'; return null; }
                return res;
            } catch(e) {
                toast('Error de conexión con el servidor', 'err');
                return null;
            }
        }

        function resolveMediaUrl(url) {
			if (!url) return '';
			
			// Si es una URL absoluta de internet o protocolo de datos (data:image/...)
			if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
				return encodeURI(url);
			}
			
			// Limpia barras iniciales redundantes si existen
			let cleanUrl = url.replace(/^\//, '');

			// Si la ruta no tiene el prefijo de la raíz/subdominio, lo añade
			if (!cleanUrl.startsWith('http')) {
				cleanUrl = API_BASE + cleanUrl;
			}

			// Aplica encodeURI para asegurar que archivos con espacios o acentos carguen correctamente
			return encodeURI(cleanUrl);
		}

        const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

        // ---------- Navegación ----------
        function toggleGroup(btn) {
            if (!btn) return;
            const group = btn.closest('.nav-group');
            if (!group) return;
            group.classList.toggle('open');
        }

        function switchTab(evt, tabId, cat, sub) {
			document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
			document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
			
			const targetContent = document.getElementById(tabId);
			if (targetContent) targetContent.classList.add('active');
			
			const targetBtn = (evt && evt.currentTarget) ? evt.currentTarget : document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
			if (targetBtn) {
				targetBtn.classList.add('active');
				const group = targetBtn.closest('.nav-group');
				if (group) group.classList.open = true;
				if (group) group.classList.add('open');
			}
			
			const crumbCat = document.getElementById('crumb-cat');
			if (crumbCat && cat) crumbCat.textContent = cat;
			const crumbSub = document.getElementById('crumb-sub');
			if (crumbSub && sub) crumbSub.textContent = sub;
			
			// Evita actualizar la URL con el hash si se trata del dashboard
			try {
				if (tabId === 'tab-dashboard') {
					// Limpia el hash de la barra de dirección manteniendo la URL limpia
					history.replaceState(null, '', window.location.pathname);
				} else {
					history.replaceState(null, '', '#' + tabId);
				}
			} catch (e) {}
			
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}

        function switchTabById(tabId) {
            const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            if (btn) btn.click();
        }

        // Remplaza o actualiza restoreActiveTab
		function restoreActiveTab() {
			// Lee el hash sin el '#'
			const tabId = (location.hash || '').replace('#', '');
			
			if (tabId && document.getElementById(tabId)) {
				// Si el usuario navegó a un hash específico (ej. #tab-settings), abre ese
				switchTabById(tabId);
			} else {
				// Si entra limpia a index.php, abre el dashboard sin cambiar la URL
				switchTabById('tab-dashboard');
			}
		}

        // ---------- Carga general de datos ----------
        async function loadAdmin() {
            try {
                const res = await apiFetch('api.php?action=get_data');
                if (!res) return;
                const d = await res.json();
                if (d && d.success) {
                    globalData = d;
                } else {
                    globalData = d || {};
                }
            } catch (e) {
                toast('No se pudo cargar la información', 'err');
                return;
            }

            const s = globalData.settings || {};
            const m = globalData.ui_media || {};

            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = (val !== undefined && val !== null) ? val : '';
            };

            setVal('partner_one', s.partner_one || '');
            setVal('partner_two', s.partner_two || '');
            setVal('start_date', s.start_date || '');
            setVal('site_lang', s.site_lang || 'es');

			setVal('ui_cover_image_url', m.cover_image_url ?? DEFAULT_COVER_URL);
			updatePreview('ui_cover_image_url', 'preview_cover', 'no_img_cover');

			setVal('ui_second_image_url', m.second_image_url ?? DEFAULT_SECOND_URL);
			updatePreview('ui_second_image_url', 'preview_second', 'no_img_second');

			setVal('ui_bg_music_url', m.bg_music_url || DEFAULT_MUSIC_URL);
            initAudioPlayer();
            updateAudioPreview();

            const letterEl = document.getElementById('letter_paragraphs_text');
            if (letterEl) {
                letterEl.value = (globalData.letter_paragraphs || []).map(p => p.content).join('\n\n');
            }

            // Actualizar texto de estado del reinicio
            const resetText = document.getElementById('reset_status_text');
            if (resetText) {
                if (s.start_date) {
                    resetText.innerHTML = `🟢 La propuesta tiene la fecha registrada en MySQL: <b>${esc(s.start_date)}</b>`;
                } else {
                    resetText.innerHTML = `🟡 La propuesta está en estado inicial (esperando que tu pareja pulse "¡Sí, Quiero!").`;
                }
            }

            renderReasons();
            renderTimeline();
            renderWishes();
            renderDashboard();

            // Cargar el idioma seleccionado en el editor
            const initialLang = s.site_lang || 'es';
            const selectEl = document.getElementById('edit_lang_select');
            if (selectEl) selectEl.value = initialLang;
            loadLanguageToEdit(initialLang);
        }

        function renderDashboard() {
            const r = (globalData.reasons || []).length,
                  tl = (globalData.timeline_chapters || []).length,
                  w = (globalData.wishes || []).length,
                  lp = (globalData.letter_paragraphs || []).length;
            const setText = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            setText('dash-reasons', r);
            setText('dash-timeline', tl);
            setText('dash-wishes', w);
            setText('dash-letter', lp);
            setText('count-reasons', r);
            setText('count-timeline', tl);
            setText('count-wishes', w);
            setText('count-reasons-2', r);
            setText('count-timeline-2', tl);
            setText('count-wishes-2', w);
        }

        // ---------- Guardar Pareja y Fecha en MySQL ----------
        async function saveSettings() {
            const getVal = id => {
                const el = document.getElementById(id);
                return el ? el.value : '';
            };
            const payload = {
                partner_one: getVal('partner_one'),
                partner_two: getVal('partner_two'),
                start_date: getVal('start_date'),
                site_lang: getVal('site_lang')
            };
            const res = await apiFetch('api.php?action=update_settings', { method: 'POST', body: JSON.stringify(payload) });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                toast('Configuración guardada');
                loadAdmin();
            } else {
                toast(d.error || 'Error al guardar', 'err');
            }
        }

        async function clearStartDate() {
            const confirmReset = await customConfirm('¿Borrar la fecha/hora actual y volver a mostrar el botón "¡Sí, Quiero!" en la web pública?', 'Reiniciar Propuesta', '🔄');
            if (!confirmReset) return;

            const res = await apiFetch('api.php?action=reset_ui_state', { method: 'POST', body: '{}' });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                const dateInput = document.getElementById('start_date');
                if (dateInput) dateInput.value = '';
                toast('Propuesta borrada/reiniciada. La web volverá a mostrar "¡Sí, Quiero!".');
                loadAdmin();
            } else {
                toast(d.error || 'Error al reiniciar la propuesta', 'err');
            }
        }

        // ---------- GESTOR DE IDIOMAS ----------
        async function loadLanguageToEdit(code) {
            currentEditingLangCode = code || 'es';
            const fileLabel = document.getElementById('current_editing_filename');
            if (fileLabel) fileLabel.textContent = currentEditingLangCode + '.php';

            const res = await apiFetch(`api.php?action=get_language&code=${encodeURIComponent(currentEditingLangCode)}`);
            if (!res) return;
            const d = await res.json();
            if (!d || !d.success) {
                toast(d.error || 'No se pudo cargar el archivo de idioma', 'err');
                return;
            }

            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val !== undefined ? val : '';
            };

            setVal('lang_meta_name', d.lang_name || '');
            setVal('lang_meta_locale', d.date_locale || 'es-ES');

            const t = d.ui_texts || {};
            setVal('lang_phrase_sub_header_title', t.sub_header_title || '');
            setVal('lang_phrase_envelope_title', t.envelope_title || '');
            setVal('lang_phrase_envelope_subtitle', t.envelope_subtitle || '');
            setVal('lang_phrase_proposal_question', t.proposal_question || '');
            setVal('lang_phrase_btn_yes_text', t.btn_yes_text || '');
            setVal('lang_phrase_timeline_section_title', t.timeline_section_title || '');
            setVal('lang_phrase_counter_title', t.counter_title || '');
            setVal('lang_phrase_label_years', t.label_years || '');
            setVal('lang_phrase_label_months', t.label_months || '');
            setVal('lang_phrase_label_days', t.label_days || '');
            setVal('lang_phrase_label_hours', t.label_hours || '');
            setVal('lang_phrase_label_minutes', t.label_minutes || '');
            setVal('lang_phrase_label_seconds', t.label_seconds || '');
            setVal('lang_phrase_date_prefix_sealed', t.date_prefix_sealed || '');
            setVal('lang_phrase_date_prefix_started', t.date_prefix_started || '');
            setVal('lang_phrase_reasons_title', t.reasons_title || '');
            setVal('lang_phrase_reason_placeholder', t.reason_placeholder || '');
            setVal('lang_phrase_btn_next_reason_text', t.btn_next_reason_text || '');
            setVal('lang_phrase_wishes_section_title', t.wishes_section_title || '');
            setVal('lang_phrase_music_play_text', t.music_play_text || '');
            setVal('lang_phrase_music_pause_text', t.music_pause_text || '');
            setVal('lang_phrase_final_phrase', t.final_phrase || '');
        }

        async function saveLanguageFile() {
            const getVal = id => {
                const el = document.getElementById(id);
                return el ? el.value : '';
            };

            const payload = {
                lang_code: currentEditingLangCode,
                lang_name: getVal('lang_meta_name'),
                date_locale: getVal('lang_meta_locale'),
                set_active: document.getElementById('lang_set_active') ? document.getElementById('lang_set_active').checked : false,
                ui_texts: {
                    sub_header_title: getVal('lang_phrase_sub_header_title'),
                    envelope_title: getVal('lang_phrase_envelope_title'),
                    envelope_subtitle: getVal('lang_phrase_envelope_subtitle'),
                    proposal_question: getVal('lang_phrase_proposal_question'),
                    btn_yes_text: getVal('lang_phrase_btn_yes_text'),
                    timeline_section_title: getVal('lang_phrase_timeline_section_title'),
                    counter_title: getVal('lang_phrase_counter_title'),
                    label_years: getVal('lang_phrase_label_years'),
                    label_months: getVal('lang_phrase_label_months'),
                    label_days: getVal('lang_phrase_label_days'),
                    label_hours: getVal('lang_phrase_label_hours'),
                    label_minutes: getVal('lang_phrase_label_minutes'),
                    label_seconds: getVal('lang_phrase_label_seconds'),
                    date_prefix_sealed: getVal('lang_phrase_date_prefix_sealed'),
                    date_prefix_started: getVal('lang_phrase_date_prefix_started'),
                    reasons_title: getVal('lang_phrase_reasons_title'),
                    reason_placeholder: getVal('lang_phrase_reason_placeholder'),
                    btn_next_reason_text: getVal('lang_phrase_btn_next_reason_text'),
                    wishes_section_title: getVal('lang_phrase_wishes_section_title'),
                    music_play_text: getVal('lang_phrase_music_play_text'),
                    music_pause_text: getVal('lang_phrase_music_pause_text'),
                    final_phrase: getVal('lang_phrase_final_phrase')
                }
            };

            const res = await apiFetch('api.php?action=save_language', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                toast(`Archivo languages/${currentEditingLangCode}.php guardado correctamente`);
                loadAdmin();
            } else {
                toast(d.error || 'Error al guardar archivo de idioma', 'err');
            }
        }

        function openNewLangModal() {
            document.getElementById('new_lang_code').value = '';
            document.getElementById('new_lang_name').value = '';
            document.getElementById('newLangModal').classList.add('active');
        }

        function closeNewLangModal() {
            document.getElementById('newLangModal').classList.remove('active');
        }

        async function createNewLanguageSubmit() {
            const code = document.getElementById('new_lang_code').value.trim().toLowerCase();
            const name = document.getElementById('new_lang_name').value.trim();
            const sourceCode = document.getElementById('new_lang_source').value;

            if (!code || !name) {
                toast('Debes indicar código y nombre del nuevo idioma', 'err');
                return;
            }

            const resSrc = await apiFetch(`api.php?action=get_language&code=${encodeURIComponent(sourceCode)}`);
            if (!resSrc) return;
            const dSrc = await resSrc.json();
            const baseTexts = (dSrc && dSrc.ui_texts) ? dSrc.ui_texts : {};

            const payload = {
                lang_code: code,
                lang_name: name,
                date_locale: 'es-ES',
                ui_texts: baseTexts
            };

            const res = await apiFetch('api.php?action=save_language', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                closeNewLangModal();
                toast(`Idioma ${name} (${code}.php) creado`);
                const opt1 = new Option(`${name} (${code}.php)`, code);
                const opt2 = new Option(`${name} (${code})`, code);
                document.getElementById('edit_lang_select').add(opt1);
                document.getElementById('site_lang').add(opt2);
                document.getElementById('edit_lang_select').value = code;
                loadLanguageToEdit(code);
            } else {
                toast(d.error || 'Error al crear idioma', 'err');
            }
        }

		// ---------- MULTIMEDIA (PRESETS Y GUARDADO EN MYSQL) ----------
		function setDefaultCoverImage() {
			document.getElementById('ui_cover_image_url').value = DEFAULT_COVER_URL;
			updatePreview('ui_cover_image_url', 'preview_cover', 'no_img_cover');
			toast('Imagen de portada por defecto cargada');
		}

		function setDefaultSecondImage() {
			document.getElementById('ui_second_image_url').value = DEFAULT_SECOND_URL;
			updatePreview('ui_second_image_url', 'preview_second', 'no_img_second');
			toast('Segunda imagen por defecto cargada');
		}

		function setDefaultMusic() {
			document.getElementById('ui_bg_music_url').value = DEFAULT_MUSIC_URL;
			updateAudioPreview();
			toast('Música por defecto cargada');
		}

		async function saveMedia() {
			const getVal = id => (document.getElementById(id) ? document.getElementById(id).value.trim() : '');
			const payload = {
				cover_image_url: getVal('ui_cover_image_url'),
				second_image_url: getVal('ui_second_image_url'),
				bg_music_url: getVal('ui_bg_music_url')
			};
			const res = await apiFetch('api.php?action=update_media', { method: 'POST', body: JSON.stringify(payload) });
			if (!res) return;
			const d = await res.json();
			d.success ? toast('Multimedia guardada en MySQL') : toast(d.error || 'Error al guardar', 'err');
		}

        function updatePreview(inputId, imgId, placeholderId) {
            const val = document.getElementById(inputId)?.value.trim();
            const img = document.getElementById(imgId);
            const ph = document.getElementById(placeholderId);
            if (val) {
                img.src = resolveMediaUrl(val);
                img.style.display = 'block';
                if (ph) ph.style.display = 'none';
            } else {
                img.style.display = 'none';
                if (ph) ph.style.display = 'flex';
            }
        }

        function handleImgError(img) {
            img.style.display = 'none';
            const parent = img.parentElement;
            if (parent) {
                const ph = parent.querySelector('.empty-placeholder');
                if (ph) ph.style.display = 'flex';
            }
        }

        function clearImageField(inputId, imgId, placeholderId) {
            const input = document.getElementById(inputId);
            if (input) input.value = '';
            updatePreview(inputId, imgId, placeholderId);
        }

        // ---------- PREVIEW Y REPRODUCTOR MÚSICA ----------
        let audioPlayer = null;
        function initAudioPlayer() {
            audioPlayer = document.getElementById('audio_preview');
            if (!audioPlayer) return;

            audioPlayer.addEventListener('timeupdate', () => {
                const cur = audioPlayer.currentTime || 0;
                const dur = audioPlayer.duration || 0;
                document.getElementById('sp_current_time').textContent = formatTime(cur);
                document.getElementById('sp_duration').textContent = formatTime(dur);
                if (dur > 0) {
                    document.getElementById('sp_seek_slider').value = (cur / dur) * 100;
                }
            });

            audioPlayer.addEventListener('ended', () => {
                updatePlayIcon(false);
            });
        }

        function updateAudioPreview() {
            const url = document.getElementById('ui_bg_music_url')?.value.trim();
            const titleEl = document.getElementById('sp_title');
            if (audioPlayer) {
                audioPlayer.pause();
                updatePlayIcon(false);
                if (url) {
                    audioPlayer.src = resolveMediaUrl(url);
                    if (titleEl) titleEl.textContent = url.split('/').pop() || 'Canción seleccionada';
                } else {
                    audioPlayer.removeAttribute('src');
                    if (titleEl) titleEl.textContent = 'Sin música de fondo';
                }
            }
        }

        function togglePlayAudio() {
            if (!audioPlayer || !audioPlayer.src) return;
            if (audioPlayer.paused) {
                audioPlayer.play().then(() => updatePlayIcon(true)).catch(() => toast('No se pudo reproducir el audio', 'err'));
            } else {
                audioPlayer.pause();
                updatePlayIcon(false);
            }
        }

        function updatePlayIcon(isPlaying) {
            const icon = document.getElementById('sp_play_icon');
            if (!icon) return;
            icon.innerHTML = isPlaying 
                ? '<rect x="5" y="4" width="4" height="16"></rect><rect x="15" y="4" width="4" height="16"></rect>'
                : '<polygon points="5 3 19 12 5 21 5 3"></polygon>';
        }

        function seekAudio(secs) {
            if (!audioPlayer || !audioPlayer.src) return;
            audioPlayer.currentTime = Math.max(0, Math.min(audioPlayer.duration || 0, audioPlayer.currentTime + secs));
        }

        function onSeekSliderChange(val) {
            if (!audioPlayer || !audioPlayer.duration) return;
            audioPlayer.currentTime = (val / 100) * audioPlayer.duration;
        }

        function setAudioVolume(val) {
            if (audioPlayer) audioPlayer.volume = val;
        }

        function formatTime(sec) {
            if (isNaN(sec) || sec === 0) return '0:00';
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60);
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }

        // ---------- SELECTOR MODAL DE MEDIOS ----------
        function switchMediaModalType(type) {
            currentMediaType = type || 'image';
            const tabImg = document.getElementById('modalTabImg');
            const tabAud = document.getElementById('modalTabAud');
            if (tabImg) tabImg.classList.toggle('active', currentMediaType === 'image');
            if (tabAud) tabAud.classList.toggle('active', currentMediaType === 'audio');
            const quickUpload = document.getElementById('modal_quick_upload');
            if (quickUpload) quickUpload.accept = currentMediaType === 'audio' ? 'audio/*' : 'image/*';
            refreshMediaList();
        }

        async function openMediaSelector(type, targetInputId, onSelectCallback) {
            currentTargetInputId = targetInputId;
            currentSelectCallback = onSelectCallback;
            currentMediaType = type || 'image';
            document.getElementById('mediaModal').classList.add('active');
            switchMediaModalType(currentMediaType);
        }

        async function refreshMediaList() {
            const container = document.getElementById('modalContainer');
            const title = document.getElementById('modalTitle');
            const type = currentMediaType;
            title.innerText = type === 'audio' ? '🎵 Audios subidos (uploads/audios/)' : '🖼️ Imágenes subidas (uploads/images/)';
            container.className = type === 'audio' ? '' : 'modal-grid';
            container.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#888;padding:24px;">Cargando archivos...</p>';

            try {
                const res = await apiFetch(`upload.php?action=list_files&type=${type}`);
                if (!res) return;
                const data = await res.json();
                if (!data.success || !data.files || !data.files.length) {
                    container.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#888;padding:24px;">No hay ${type === 'audio' ? 'audios' : 'imágenes'} guardados aún.</p>`;
                    return;
                }
                if (type === 'audio') {
                    container.innerHTML = data.files.map(f => `
                        <div class="audio-list-item">
                            <div class="audio-info" onclick="selectUploadedFile('${f.url}')">
                                <span style="font-size:1.3rem;">🎵</span>
                                <div>
                                    <strong>${esc(f.filename)}</strong>
                                    <div style="font-size:.72rem;color:#888;">${f.url}</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <button class="btn-pick-file" style="padding:6px 12px;" onclick="selectUploadedFile('${f.url}')">✓ Seleccionar</button>
                                <button class="btn-delete-audio" onclick="deleteUploadedFile('${f.filename}','audio',event)">🗑️</button>
                            </div>
                        </div>`).join('');
                } else {
                    container.innerHTML = data.files.map(f => `
                        <div class="file-select-item">
                            <div class="file-thumb-container" onclick="selectUploadedFile('${f.url}')" title="Clic para seleccionar">
                                <img src="${resolveMediaUrl(f.url)}" alt="${esc(f.filename)}" loading="lazy" onerror="handleImgError(this)">
                            </div>
                            <span title="${esc(f.filename)}">${esc(f.filename)}</span>
                            <div class="file-actions">
                                <button class="btn-pick-file" onclick="selectUploadedFile('${f.url}')">Elegir</button>
                                <button class="btn-delete-file" onclick="deleteUploadedFile('${f.filename}','image',event)">🗑️</button>
                            </div>
                        </div>`).join('');
                }
            } catch (err) {
                container.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--danger);padding:24px;">Error al obtener los archivos.</p>';
            }
        }

        async function deleteUploadedFile(filename, type, event) {
            if (event) event.stopPropagation();
            const confirmDelFile = await customConfirm(`¿Eliminar permanentemente "${filename}" del servidor?`, 'Eliminar Archivo');
            if (!confirmDelFile) return;

            try {
                const res = await apiFetch('upload.php?action=delete_file', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filename, type })
                });
                if (!res) return;
                const data = await res.json();
                data.success ? await refreshMediaList() : toast('Error: ' + data.error, 'err');
            } catch (err) { toast('No se pudo eliminar el archivo.', 'err'); }
        }

        function selectUploadedFile(url) {
            if (currentTargetInputId) {
                const input = document.getElementById(currentTargetInputId);
                if (input) input.value = url;
                if (currentSelectCallback) currentSelectCallback();
            }
            closeMediaSelector();
            toast('Archivo seleccionado: ' + url.split('/').pop());
        }

        function closeMediaSelector() { document.getElementById('mediaModal').classList.remove('active'); }

        // ---------- Previsualización e inputs ----------
		function updatePreview(inputId, imgId, noImgId) {
			const input = document.getElementById(inputId);
			const rawUrl = input ? input.value.trim() : '';
			const imgEl = document.getElementById(imgId);
			const noImgEl = document.getElementById(noImgId);
			if (!imgEl || !noImgEl) return;
			if (rawUrl) {
				imgEl.src = resolveMediaUrl(rawUrl);
				imgEl.style.display = 'block';
				noImgEl.style.display = 'none';
			} else {
				imgEl.src = ''; imgEl.style.display = 'none'; noImgEl.style.display = 'flex';
			}
		}

		function handleImgError(imgEl) {
			imgEl.style.display = 'none';
			const noImgEl = imgEl.parentElement ? imgEl.parentElement.querySelector('.empty-placeholder') : null;
			if (noImgEl) noImgEl.style.display = 'flex';
		}

		async function clearImageField(inputId, imgId, noImgId) {
			const input = document.getElementById(inputId);
			if (input) input.value = '';
			
			updatePreview(inputId, imgId, noImgId);
			
			await saveMedia();
		}

		async function handleFileUpload(inputElement, fileFieldName, targetInputId, callback) {
			const file = inputElement.files[0];
			if (!file) return;
			const formData = new FormData();
			formData.append(fileFieldName, file);
			try {
				const res = await apiFetch('upload.php', { method: 'POST', body: formData });
				if (!res) return;
				const result = await res.json();
				if (result.success) {
					const input = document.getElementById(targetInputId);
					if (input) input.value = result.url;
					if (callback) callback();
					toast('Archivo subido: ' + result.filename);
				} else {
					toast('Error: ' + result.error, 'err');
				}
			} catch (err) {
				toast('Error al subir el archivo.', 'err');
			} finally {
				inputElement.value = '';
			}
		}

        // ---------- CARTA ----------
        async function saveLetter() {
            const letterEl = document.getElementById('letter_paragraphs_text');
            const paragraphs = (letterEl ? letterEl.value : '')
                .split('\n\n').map(p => p.trim()).filter(p => p !== '');
            const res = await apiFetch('api.php?action=save_letter', { method: 'POST', body: JSON.stringify({ paragraphs }) });
            if (!res) return;
            const d = await res.json();
            if (d.success) { toast(`Carta guardada (${d.count} párrafos)`); loadAdmin(); }
            else toast(d.error || 'Error al guardar la carta', 'err');
        }

        // ---------- Renderizado CRUD ----------
        function renderReasons() {
            const list = document.getElementById('reasons-list');
            if (!list) return;
            const rows = (globalData.reasons || []).map((r, index) => `
                <tr>
                    <td><div class="idx-badge">${index + 1}</div></td>
                    <td><div style="font-weight:600; color:var(--text-dark); line-height:1.5;">${esc(r.content)}</div></td>
					<td>
                        <div class="actions-cell">
							<button type="button" class="btn-action btn-action-edit" onclick="openFormModal('reason', ${r.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Editar</span>
                            </button>
							<button type="button" class="btn-action btn-action-del" onclick="deleteItem('reason', ${r.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Borrar</span>
                            </button>
                    </td>
                </tr>`).join('');
            list.innerHTML = rows || '<tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:24px;">No hay razones. Pulsa "➕ Añadir Razón".</td></tr>';
        }

        function renderTimeline() {
            const list = document.getElementById('timeline-list');
            if (!list) return;
            const rows = (globalData.timeline_chapters || []).map((ch, index) => `
                <tr>
                    <td><div class="idx-badge">${index + 1}</div></td>
                    <td><span style="background:var(--p-light); color:var(--p); font-weight:700; font-size:.75rem; padding:3px 10px; border-radius:12px; display:inline-block;">${esc(ch.chapter_label)}</span></td>
                    <td><strong style="color:var(--text-dark); font-size:.9rem;">${esc(ch.title)}</strong></td>
                    <td><div style="color:var(--text-muted); font-size:.82rem; line-height:1.4; max-width:350px;">${esc(ch.description)}</div></td>
                    <td>
                        <div class="actions-cell">
							<button type="button" class="btn-action btn-action-edit" onclick="openFormModal('timeline', ${ch.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Editar</span>
                            </button>
							<button type="button" class="btn-action btn-action-del" onclick="deleteItem('timeline', ${ch.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Borrar</span>
                            </button>
                        </div>
                    </td>
                </tr>`).join('');
            list.innerHTML = rows || '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">No hay capítulos. Pulsa "➕ Añadir Capítulo".</td></tr>';
        }

        function renderWishes() {
            const list = document.getElementById('wishes-list');
            if (!list) return;
            const rows = (globalData.wishes || []).map((w, index) => `
                <tr>
                    <td><div class="idx-badge">${index + 1}</div></td>
                    <td><div style="font-size:1.4rem; background:#f1f5f9; width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center;">${esc(w.icon)}</div></td>
                    <td><strong style="color:var(--text-dark); font-size:.9rem;">${esc(w.label)}</strong></td>
                    <td><div style="font-size:.8rem; padding:4px; display:inline-block;">${esc(w.secret_text)}</div></td>
                    <td>
                        <div class="actions-cell">
							<button type="button" class="btn-action btn-action-edit" onclick="openFormModal('wish', ${w.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Editar</span>
                            </button>
							<button type="button" class="btn-action btn-action-del" onclick="deleteItem('wish', ${w.id})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Borrar</span>
                            </button>
                        </div>
                    </td>
                </tr>`).join('');
            list.innerHTML = rows || '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">No hay deseos. Pulsa "➕ Añadir Deseo".</td></tr>';
        }

        // ---------- Modal formularios CRUD ----------
        function openFormModal(type, id = null) {
            const modal = document.getElementById('formModal');
            const title = document.getElementById('formModalTitle');
            const body = document.getElementById('formModalBody');

            if (type === 'reason') {
                const item = id ? globalData.reasons.find(r => r.id == id) : null;
                title.innerText = id ? 'Editar razón' : 'Añadir razón';
                body.innerHTML = `
                    <label>Razón:</label>
                    <input type="text" id="modal_reason_content" value="${item ? esc(item.content) : ''}" placeholder="Escribe la razón aquí...">
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                        <button class="btn-cancel" onclick="closeFormModal()">Cancelar</button>
                        <button onclick="saveReasonSubmit(${id})">💾 Guardar</button>
                    </div>`;
            } else if (type === 'timeline') {
                const item = id ? globalData.timeline_chapters.find(ch => ch.id == id) : null;
                title.innerText = id ? 'Editar capítulo' : 'Añadir capítulo';
                body.innerHTML = `
                    <label>Etiqueta:</label>
                    <input type="text" id="modal_chapter_label" value="${item ? esc(item.chapter_label) : ''}" placeholder="Ej. CAPÍTULO 1">
                    <label>Título:</label>
                    <input type="text" id="modal_chapter_title" value="${item ? esc(item.title) : ''}" placeholder="Ej. Nuestro primer día">
                    <label>Descripción:</label>
                    <textarea id="modal_chapter_desc" rows="3" placeholder="Descripción detallada...">${item ? esc(item.description) : ''}</textarea>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                        <button class="btn-cancel" onclick="closeFormModal()">Cancelar</button>
                        <button onclick="saveTimelineSubmit(${id})">💾 Guardar</button>
                    </div>`;
            } else if (type === 'wish') {
                const item = id ? globalData.wishes.find(w => w.id == id) : null;
                title.innerText = id ? 'Editar deseo' : 'Añadir deseo';
                body.innerHTML = `
                    <label>Icono / Emoji:</label>
                    <input type="text" id="modal_wish_icon" value="${item ? esc(item.icon) : ''}" placeholder="Ej. ✈️">
                    <label>Etiqueta:</label>
                    <input type="text" id="modal_wish_label" value="${item ? esc(item.label) : ''}" placeholder="Ej. Viajar juntos">
                    <label>Mensaje secreto:</label>
                    <input type="text" id="modal_wish_secret" value="${item ? esc(item.secret_text) : ''}" placeholder="Mensaje que aparece al pulsar...">
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                        <button class="btn-cancel" onclick="closeFormModal()">Cancelar</button>
                        <button onclick="saveWishSubmit(${id})">💾 Guardar</button>
                    </div>`;
            }
            modal.classList.add('active');
        }

        function closeFormModal() { document.getElementById('formModal').classList.remove('active'); }

        async function saveReasonSubmit(id) {
            const content = document.getElementById('modal_reason_content').value.trim();
            if (!content) { toast('La razón no puede estar vacía', 'err'); return; }
            const payload = { type: 'reasons', content };
            if (id) payload.id = id;
            const res = await apiFetch('api.php?action=save_item', { method: 'POST', body: JSON.stringify(payload) });
            if (!res) return;
            const d = await res.json();
            if (d.success) { closeFormModal(); loadAdmin(); toast('Razón guardada'); }
            else toast(d.error || 'Error', 'err');
        }

        async function saveTimelineSubmit(id) {
            const payload = {
                type: 'timeline_chapters',
                chapter_label: document.getElementById('modal_chapter_label').value,
                title: document.getElementById('modal_chapter_title').value,
                description: document.getElementById('modal_chapter_desc').value
            };
            if (!payload.title.trim()) { toast('El título es obligatorio', 'err'); return; }
            if (id) payload.id = id;
            const res = await apiFetch('api.php?action=save_item', { method: 'POST', body: JSON.stringify(payload) });
            if (!res) return;
            const d = await res.json();
            if (d.success) { closeFormModal(); loadAdmin(); toast('Capítulo guardado'); }
            else toast(d.error || 'Error', 'err');
        }

        async function saveWishSubmit(id) {
            const payload = {
                type: 'wishes',
                icon: document.getElementById('modal_wish_icon').value,
                label: document.getElementById('modal_wish_label').value,
                secret_text: document.getElementById('modal_wish_secret').value
            };
            if (!payload.label.trim()) { toast('La etiqueta es obligatoria', 'err'); return; }
            if (id) payload.id = id;
            const res = await apiFetch('api.php?action=save_item', { method: 'POST', body: JSON.stringify(payload) });
            if (!res) return;
            const d = await res.json();
            if (d.success) { closeFormModal(); loadAdmin(); toast('Deseo guardado'); }
            else toast(d.error || 'Error', 'err');
        }

        async function deleteItem(table, id) {
            const confirmDel = await customConfirm('¿Deseas eliminar este elemento de la base de datos?', 'Eliminar Registro');
            if (!confirmDel) return;

            const res = await apiFetch('api.php?action=delete_item', { method: 'POST', body: JSON.stringify({ table, id }) });
            if (!res) return;
            const d = await res.json();
            if (d.success) { loadAdmin(); toast('Elemento eliminado correctamente'); }
            else toast(d.error || 'Error al eliminar', 'err');
        }

        // ---------- Herramientas: Funciones JS ----------
        function checkPassStrength(val) {
            const bar = document.getElementById('pass_strength_bar');
            if (!bar) return;
            if (!val) { bar.style.width = '0%'; bar.style.background = '#e2e8f0'; return; }
            if (val.length < 5) { bar.style.width = '30%'; bar.style.background = 'var(--danger)'; }
            else if (val.length < 8) { bar.style.width = '60%'; bar.style.background = 'var(--warning)'; }
            else { bar.style.width = '100%'; bar.style.background = 'var(--ok)'; }
        }

        async function changeCredentials() {
            const newUser = document.getElementById('sec_new_user').value.trim();
            const newPass = document.getElementById('sec_new_password').value;
            const confirmPass = document.getElementById('sec_confirm_password').value;
            const currentPass = document.getElementById('sec_current_password').value;

            if (!currentPass) { toast('Debes escribir tu contraseña actual para autorizar', 'err'); return; }
            if (!newUser && !newPass) { toast('Indica un nuevo usuario o una nueva contraseña', 'err'); return; }

            if (newPass) {
                if (newPass.length < 5) { toast('La nueva contraseña debe tener al menos 5 caracteres', 'err'); return; }
                if (newPass !== confirmPass) { toast('Las nuevas contraseñas no coinciden', 'err'); return; }
            }

            const payload = {
                new_user: newUser,
                new_password: newPass,
                current_password: currentPass
            };

            const res = await apiFetch('api.php?action=change_credentials', { method: 'POST', body: JSON.stringify(payload) });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                toast('Credenciales actualizadas en MySQL');
                document.getElementById('sec_new_user').value = '';
                document.getElementById('sec_new_password').value = '';
                document.getElementById('sec_confirm_password').value = '';
                document.getElementById('sec_current_password').value = '';
                checkPassStrength('');
            } else toast(d.error || 'Error al cambiar credenciales', 'err');
        }

        async function exportData() {
            try {
                window.location.href = API_BASE + 'api.php?action=export_backup';
                toast('Descargando copia de seguridad...');
            } catch(e) {
                toast('Error al descargar copia de seguridad', 'err');
            }
        }

        // ---------- NUEVAS FUNCIONES PARA DROPDOWN & PREVIEW DE IMPORTACIÓN ----------
		/**
		 * Procesa la selección o arrastre del archivo JSON y calcula
		 * en tiempo real las cantidades de cada sección para la vista previa.
		 */
		function handleImportFileSelect(input) {
			const file = input.files ? input.files[0] : null;
			if (!file) return;

			// 1. Mostrar nombre y tamaño formateado del archivo
			document.getElementById('preview_filename_text').textContent = file.name;
			document.getElementById('preview_filesize_text').textContent = formatBytes(file.size);

			// 2. Leer e inspeccionar el contenido del archivo JSON
			const reader = new FileReader();
			reader.onload = function(e) {
				try {
					const data = JSON.parse(e.target.result);

					// Extraer las colecciones o arreglos según la estructura exportada
					// (Acepta estructuras anidadas o de primer nivel)
					const reasons = data.reasons || (data.data && data.data.reasons) || [];
					const timeline = data.timeline_chapters || (data.data && data.data.timeline_chapters) || [];
					const wishes = data.wishes || (data.data && data.data.wishes) || [];
					const letter = data.letter_paragraphs || (data.data && data.data.letter_paragraphs) || [];

					// 3. Asignar los valores calculados a los elementos HTML
					document.getElementById('stat_reasons').textContent = Array.isArray(reasons) ? reasons.length : 0;
					document.getElementById('stat_timeline').textContent = Array.isArray(timeline) ? timeline.length : 0;
					document.getElementById('stat_wishes').textContent = Array.isArray(wishes) ? wishes.length : 0;
					document.getElementById('stat_letter').textContent = Array.isArray(letter) ? letter.length : 0;

					// 4. Mostrar el contenedor de la tarjeta de vista previa
					document.getElementById('import_preview_card').style.display = 'block';

				} catch (err) {
					toast('El archivo seleccionado no es un JSON válido.', 'err');
					resetImportFile();
				}
			};

			reader.readAsText(file);
		}

		/**
		 * Formatea bytes a una unidad legible (Bytes, KB, MB, GB, etc.)
		 * @param {number} bytes - Cantidad de bytes.
		 * @param {number} [decimals=2] - Número de decimales a mostrar.
		 * @returns {string} Tamaño formateado con su unidad.
		 */
		function formatBytes(bytes, decimals = 2) {
			if (bytes === 0) return '0 Bytes';

			const k = 1024;
			const dm = decimals < 0 ? 0 : decimals;
			const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

			const i = Math.floor(Math.log(bytes) / Math.log(k));
			const index = Math.min(i, sizes.length - 1);

			return `${parseFloat((bytes / Math.pow(k, index)).toFixed(dm))} ${sizes[index]}`;
		}

		/**
		 * Oculta la tarjeta de vista previa y reinicia el campo input
		 */
		function resetImportFile() {
			const input = document.getElementById('import_file');
			if (input) input.value = '';
			
			document.getElementById('stat_reasons').textContent = '0';
			document.getElementById('stat_timeline').textContent = '0';
			document.getElementById('stat_wishes').textContent = '0';
			document.getElementById('stat_letter').textContent = '0';
			
			document.getElementById('import_preview_card').style.display = 'none';
		}

        async function importData() {
            const fileInput = document.getElementById('import_file');
            if (!fileInput.files || !fileInput.files[0]) {
                toast('Por favor, selecciona un archivo JSON de respaldo', 'err');
                return;
            }

            const confirmImp = await customConfirm('¿Restaurar esta copia de seguridad? Se reemplazará el contenido actual en la base de datos.', 'Restaurar Copia');
            if (!confirmImp) return;

            const file = fileInput.files[0];
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const content = JSON.parse(e.target.result);
                    const res = await apiFetch('api.php?action=import_backup', {
                        method: 'POST',
                        body: JSON.stringify(content)
                    });
                    if (!res) return;
                    const d = await res.json();
                    if (d.success) {
                        toast('Copia de seguridad restaurada con éxito');
                        resetImportFile();
                        loadAdmin();
                    } else toast(d.error || 'Error al restaurar copia', 'err');
                } catch (err) {
                    toast('El archivo seleccionado no es un JSON válido', 'err');
                }
            };
            reader.readAsText(file);
        }

        async function resetData() {
            const confirmReset = await customConfirm('¿Estás seguro de restablecer todos los datos a sus valores iniciales por defecto?', 'Restaurar Valores de Fábrica', '🔥');
            if (!confirmReset) return;

            const res = await apiFetch('api.php?action=reset_defaults', { method: 'POST', body: '{}' });
            if (!res) return;
            const d = await res.json();
            if (d.success) {
                toast('Datos restaurados por defecto');
                loadAdmin();
            } else toast(d.error || 'Error al restaurar datos', 'err');
        }

		async function uninstallSystem() {
			const passInput = document.getElementById('uninstall_admin_pass');
			const pass = passInput ? passInput.value : '';

			if (!pass) {
				toast('Escribe tu contraseña de administrador para continuar', 'err');
				return;
			}

			const confirm1 = await customConfirm('¿Estás COMPLETAMENTE SEGURO de desinstalar todo el sistema y borrar la base de datos?', 'Desinstalar Aplicación', '🔥');
			if (!confirm1) return;

			const res = await apiFetch('api.php?action=uninstall_system', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ password: pass }) // <--- Cambiado a 'password'
			});
			if (!res) return;
			const d = await res.json();
			if (d.success) {
				alert('El sistema ha sido desinstalado. Redirigiendo...');
				window.location.href = '../install.php';
			} else {
				toast(d.error || 'Contraseña incorrecta o error al desinstalar', 'err');
			}
		}

        // ---------- INICIALIZACIÓN ----------
        document.addEventListener('DOMContentLoaded', () => {
            loadAdmin();
            restoreActiveTab();

            // Configurar Drag and Drop para la zona de importación
            const dropzone = document.getElementById('dropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('dragover');
                    }, false);
                });

                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    const input = document.getElementById('import_file');
                    
                    if (files && files.length > 0) {
                        input.files = files;
                        handleImportFileSelect(input);
                    }
                }, false);
            }
        });
    </script>
</body>
</html>