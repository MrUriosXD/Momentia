<?php
// ============================================================
//  index.php — Página pública de la propuesta romántica
//  Lee configuración desde MySQL y textos desde el archivo de idioma activo.
// ============================================================
require_once __DIR__ . '/config.php';

// Si el sitio aún no se ha instalado, ir primero al asistente
if (!site_installed()) {
    header('Location: install.php');
    exit;
}

$siteData  = get_site_data();
if (!$siteData) $siteData = [];
$settings  = $siteData['settings'] ?? [];
$uiTexts   = $siteData['ui_texts'] ?? [];
$uiMedia   = $siteData['ui_media'] ?? [];

$partner1  = !empty($settings['partner_one']) ? $settings['partner_one'] : 'Ella';
$partner2  = !empty($settings['partner_two']) ? $settings['partner_two'] : 'Él';
$startDate = !empty($settings['start_date']) ? $settings['start_date'] : '';
$isAccepted = (!empty($startDate) || (!empty($settings['proposal_accepted']) && $settings['proposal_accepted'] === '1'));

$coverImg  = !empty($uiMedia['cover_image_url']) ? $uiMedia['cover_image_url'] : '';
$secondImg = !empty($uiMedia['second_image_url']) ? $uiMedia['second_image_url'] : '';
$bgMusic   = !empty($uiMedia['bg_music_url']) ? $uiMedia['bg_music_url'] : DEFAULT_MUSIC_URL;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($settings['site_lang'] ?? 'es'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($partner1 . ' & ' . $partner2); ?> - <?php echo htmlspecialchars($uiTexts['sub_header_title'] ?? 'Nuestra Historia'); ?></title>

    <?php
    // Construcción de URLs y textos para compartir / Open Graph / Twitter Cards
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $currentUrl = $protocol . $host . ($_SERVER['REQUEST_URI'] ?? '/');

    $metaTitle = htmlspecialchars($partner1 . ' & ' . $partner2 . ' — ' . ($uiTexts['sub_header_title'] ?? 'Nuestra Historia'));
    $metaDescription = htmlspecialchars($uiTexts['final_phrase'] ?? 'Una historia de amor inolvidable.');
    $siteName = htmlspecialchars($partner1 . ' & ' . $partner2);

    $metaImage = !empty($coverImg) ? $coverImg : DEFAULT_COVER_IMAGE;
    if (!preg_match('~^https?://~i', $metaImage)) {
        $metaImage = rtrim($protocol . $host, '/') . '/' . ltrim($metaImage, '/');
    }
    ?>
    <!-- Metadatos Open Graph (Facebook, WhatsApp, Telegram, Discord, LinkedIn, etc.) -->
    <meta property="og:title" content="<?php echo $metaTitle; ?>">
    <meta property="og:description" content="<?php echo $metaDescription; ?>">
    <meta property="og:site_name" content="<?php echo $siteName; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($metaImage); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($metaImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Cards (X) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $metaTitle; ?>">
    <meta name="twitter:description" content="<?php echo $metaDescription; ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($metaImage); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f7f3ee;
            --primary-color: #8b263e;
            --secondary-color: #c89666;
            --text-dark: #2b2d42;
            --text-light: #6c757d;
            --accent-soft: #e8b4b8;
            --card-bg: #ffffff;
            --shadow-soft: 0 10px 30px rgba(139, 38, 62, 0.06);
            --shadow-strong: 0 20px 40px rgba(0, 0, 0, 0.08);

            --envelope-bg: #efe6da;
            --envelope-flap: #e3d5c3;
            --parchment-bg: #fbf7f0;
            --parchment-border: #e2d1bc;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cormorant Garamond', serif;
            background: linear-gradient(135deg, #fdfbf7 0%, #f5ebd9 50%, #f2dcd5 100%);
            background-attachment: fixed;
            color: var(--text-dark);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 15px 80px 15px;
        }

        #canvas-hearts { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 100; }
        .container { width: 100%; max-width: 540px; margin: 0 auto; text-align: center; }

        .hero-section {
            margin-bottom: 25px;
            opacity: 0; transform: translateY(15px);
            animation: fadeIn 1s forwards;
        }

        .names-title { font-family: 'Dancing Script', cursive; font-size: 3.4rem; font-weight: 700; color: var(--primary-color); margin-bottom: 2px; line-height: 1.1; }
        .subtitle { font-family: 'Montserrat', sans-serif; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; color: var(--secondary-color); margin-bottom: 20px; font-weight: 600; }

        /* FOTOS Y PLACEHOLDERS */
        .cover-frame, .second-photo-frame {
            position: relative; width: 100%; height: 270px;
            margin: 0 auto; border-radius: 20px; padding: 8px;
            background: #ffffff; box-shadow: var(--shadow-soft);
            transition: transform 0.3s ease;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .cover-frame:hover { transform: translateY(-3px); }

        .cover-img, .second-photo {
            width: 100%; height: 100%; object-fit: cover; border-radius: 14px;
            transition: opacity 0.4s ease;
        }

        .empty-image-placeholder {
            width: 100%; height: 100%; border-radius: 14px;
            background: radial-gradient(circle, #fffaf5 0%, #f5e9dc 100%);
            border: 1px dashed var(--secondary-color);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 12px; color: var(--primary-color); padding: 15px; box-sizing: border-box;
        }
        .empty-image-placeholder svg {
            width: 44px; height: 44px; fill: none; stroke: var(--primary-color); stroke-width: 2;
            stroke-linecap: round; stroke-linejoin: round;
            filter: drop-shadow(0 2px 5px rgba(139, 38, 62, 0.15));
        }
        .empty-image-placeholder span {
            font-family: 'Montserrat', sans-serif; font-size: 0.75rem;
            letter-spacing: 2px; text-transform: uppercase; color: var(--primary-color);
            opacity: 0.85; font-weight: 600;
        }

        /* ===== ESTILOS DEL SOBRE Y CARTA (INDEX (2).HTML) ===== */
        .letter-wrapper {
            margin: 35px 0;
            display: flex;
            justify-content: center;
            perspective: 1200px;
        }

        .mystic-envelope {
            width: 100%;
            background: var(--envelope-bg);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(60, 40, 30, 0.12), inset 0 0 20px rgba(255, 255, 255, 0.5);
            cursor: pointer;
            position: relative;
            transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            text-align: center;
            border: 1px solid #e5d7c5;
            overflow: hidden;
        }

        .envelope-flap {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 90px;
            background: linear-gradient(180deg, #e3d3be 0%, #ecdcc8 100%);
            clip-path: polygon(0 0, 100% 0, 50% 100%);
            transform-origin: top center;
            transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 3;
            border-top: 1px solid #e5d5c0;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));
        }

        .wax-seal {
            position: absolute;
            top: 55px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: radial-gradient(circle at 30% 30%, #a82e4b 0%, #6b1a2b 100%);
            border-radius: 50%;
            box-shadow: 0 6px 15px rgba(107, 26, 43, 0.35), inset 0 2px 4px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fceade;
            font-size: 1.3rem;
            z-index: 4;
            transition: transform 0.5s ease, opacity 0.4s ease;
        }

        .envelope-header-content {
            padding: 120px 20px 30px 20px;
            transition: opacity 0.3s ease;
        }

        .envelope-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--primary-color);
            font-weight: 700;
        }

        .envelope-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 6px;
            font-style: italic;
        }

        .parchment-letter {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            background: 
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.8) 0%, rgba(243, 233, 218, 0.9) 100%),
                repeating-linear-gradient(0deg, transparent, transparent 27px, rgba(200, 150, 102, 0.08) 28px);
            border-radius: 16px;
            margin: 0 12px;
            box-shadow: 
                inset 0 0 30px rgba(180, 130, 90, 0.18),
                0 4px 15px rgba(0, 0, 0, 0.03);
            transition: max-height 0.8s ease-in-out, opacity 0.6s ease, padding 0.5s ease, margin 0.5s ease;
            text-align: left;
            border: 1px solid var(--parchment-border);
            position: relative;
        }

        .parchment-letter::before {
            content: '✦'; position: absolute; top: 12px; left: 15px;
            color: var(--secondary-color); opacity: 0.5; font-size: 0.8rem;
        }
        .parchment-letter::after {
            content: '✦'; position: absolute; bottom: 12px; right: 15px;
            color: var(--secondary-color); opacity: 0.5; font-size: 0.8rem;
        }

        .mystic-envelope.open .envelope-flap {
            transform: rotateX(180deg);
            z-index: 1;
        }

        .mystic-envelope.open .wax-seal {
            opacity: 0;
            transform: translateX(-50%) scale(0.4);
            pointer-events: none;
        }

        .mystic-envelope.open .envelope-header-content { display: none; }

        .mystic-envelope.open .parchment-letter {
            max-height: 2200px; opacity: 1;
            padding: 30px 24px; margin: 20px 12px;
        }

        .handwritten-letter {
            font-family: 'Dancing Script', cursive;
            font-size: 1.35rem; font-weight: 600; line-height: 1.6;
            color: #382225; border-left: 2px solid var(--accent-soft); padding-left: 18px;
        }

        /* PROPUESTA Y REVELACIÓN */
        .proposal-section { display: none; text-align: center; margin: 25px 0; padding: 30px 20px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: var(--shadow-soft); opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease, transform 0.8s ease; }
        .proposal-section.visible { display: block; opacity: 1; transform: translateY(0); animation: slideUp 0.8s forwards; }
        .question-text { font-size: 1.8rem; font-style: italic; color: var(--primary-color); margin-bottom: 20px; font-weight: 600; }
        
        .btn-yes {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), #6b1d2f);
            color: white; border: none; padding: 14px 38px; font-size: 0.85rem;
            font-weight: 600; letter-spacing: 2px; text-transform: uppercase;
            border-radius: 40px; cursor: pointer; box-shadow: 0 8px 20px rgba(139, 38, 62, 0.25);
            transition: all 0.3s ease;
        }
        .btn-yes:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 12px 25px rgba(139, 38, 62, 0.35); }

        .reveal-section { display: none; opacity: 0; transition: opacity 1s ease; }
        .reveal-section.active { display: block; opacity: 1; animation: slideUp 0.8s forwards; }

        .counter-card { background: #ffffff; border-radius: 20px; padding: 25px 15px; text-align: center; box-shadow: var(--shadow-strong); margin-bottom: 25px; border: 1px solid #f1e4d8; }
        .counter-title { font-family: 'Montserrat', sans-serif; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--text-light); margin-bottom: 15px; font-weight: 600; }
        .timer-grid { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
        .timer-box { background: #faf6f0; padding: 12px 6px; border-radius: 12px; flex: 1; min-width: 65px; max-width: 80px; border: 1px solid #eee2d5; display: none; }
        .timer-box.active-box { display: block; }
        .timer-value { font-family: 'Montserrat', sans-serif; font-size: 1.3rem; font-weight: 700; color: var(--primary-color); line-height: 1; }
        .timer-label { font-family: 'Montserrat', sans-serif; font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-light); margin-top: 5px; font-weight: 500; }
        .date-badge { display: inline-block; margin-top: 15px; padding: 6px 16px; background: #f7ede2; border-radius: 20px; font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: var(--primary-color); font-weight: 600; }

        .reasons-card { background: #ffffff; border-radius: 20px; padding: 25px 20px; margin: 25px 0; box-shadow: var(--shadow-soft); border: 1px solid #f1e4d8; text-align: center; }
        .reasons-title { font-family: 'Montserrat', sans-serif; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: var(--secondary-color); margin-bottom: 12px; font-weight: 600; }
        .reason-text { font-family: 'Dancing Script', cursive; font-size: 1.6rem; color: var(--primary-color); min-height: 70px; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease, transform 0.4s ease; line-height: 1.3; }
        .btn-reason { font-family: 'Montserrat', sans-serif; background: #f7ede2; color: var(--primary-color); border: 1px solid var(--accent-soft); padding: 10px 22px; font-size: 0.75rem; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; border-radius: 30px; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; }
        .btn-reason:hover { background: var(--primary-color); color: #ffffff; box-shadow: 0 4px 12px rgba(139, 38, 62, 0.2); }

        .timeline-section { margin: 30px 0; text-align: left; }
        .timeline-title { font-family: 'Montserrat', sans-serif; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; color: var(--primary-color); text-align: center; margin-bottom: 20px; font-weight: 600; }
        .timeline-container { position: relative; padding-left: 20px; border-left: 2px dashed var(--secondary-color); margin-left: 10px; }
        .timeline-item { position: relative; margin-bottom: 20px; cursor: pointer; }
        .timeline-item::before { content: '♥'; position: absolute; left: -28px; top: 0; width: 16px; height: 16px; background: #fdfbf7; color: var(--primary-color); font-size: 0.8rem; display: flex; align-items: center; justify-content: center; }
        .timeline-date { font-family: 'Montserrat', sans-serif; font-size: 0.7rem; color: var(--secondary-color); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .timeline-header { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; font-weight: 600; color: var(--text-dark); }
        .timeline-body { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, opacity 0.4s ease; opacity: 0; font-size: 0.95rem; color: var(--text-light); margin-top: 4px; }
        .timeline-item.active .timeline-body { max-height: 200px; opacity: 1; padding-top: 5px; }

        .capsule-section { margin: 30px 0; background: rgba(255, 255, 255, 0.7); border-radius: 20px; padding: 20px 15px; border: 1px solid #f1e4d8; }
        .capsule-title { font-family: 'Montserrat', sans-serif; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: var(--primary-color); margin-bottom: 15px; font-weight: 600; }
        .promises-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .promise-card { background: #ffffff; border-radius: 14px; padding: 15px 10px; border: 1px solid #eee2d5; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; text-align: center; }
        .promise-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-soft); }
        .promise-icon { font-size: 1.4rem; margin-bottom: 5px; }
        .promise-label { font-family: 'Montserrat', sans-serif; font-size: 0.7rem; font-weight: 600; color: var(--text-dark); }
        .promise-secret { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; padding: 8px; font-family: 'Dancing Script', cursive; font-size: 1.15rem; opacity: 0; pointer-events: none; transition: opacity 0.4s ease; text-align: center; }
        .promise-card.revealed .promise-secret { opacity: 1; pointer-events: auto; }

        .romantic-moment { text-align: center; margin-top: 25px; }
        .final-phrase { font-size: 2rem; font-style: italic; color: var(--primary-color); line-height: 1.25; margin-top: 15px; }

        /* SECCIÓN COMPARTIR */
        .share-section {
            margin-top: 35px;
            padding: 20px 15px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid #ebd9cb;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
        }
        .share-title {
            font-family: 'Dancing Script', cursive;
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        .share-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .share-buttons-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        .share-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 50px;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            border: none;
            outline: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.12);
            opacity: 0.92;
        }
        .share-btn svg { width: 15px; height: 15px; fill: currentColor; flex-shrink: 0; }
        
        .share-btn-whatsapp  { background-color: #25D366; }
        .share-btn-telegram  { background-color: #229ED9; }
        .share-btn-facebook  { background-color: #1877F2; }
        .share-btn-twitter   { background-color: #0f1419; }
        .share-btn-native    { background: linear-gradient(135deg, var(--primary-color), #b2415d); }
        .share-btn-copy      { background-color: #5c677d; }

        /* Toast flotante de copiado */
        .share-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #2b2d42;
            color: #ffffff;
            padding: 10px 22px;
            border-radius: 30px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .share-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulseGlow { 0% { box-shadow: 0 4px 15px rgba(139, 38, 62, 0.15); } 50% { box-shadow: 0 6px 22px rgba(139, 38, 62, 0.3); } 100% { box-shadow: 0 4px 15px rgba(139, 38, 62, 0.15); } }

        .footer-music-bar { position: fixed; bottom: 25px; right: 25px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(212, 163, 115, 0.4); padding: 8px 16px; border-radius: 30px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); display: flex; align-items: center; gap: 10px; cursor: pointer; z-index: 99; font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: var(--primary-color); font-weight: 600; letter-spacing: 0.5px; animation: pulseGlow 3s infinite ease-in-out; transition: all 0.3s ease; }
        .footer-music-bar:hover { transform: translateY(-2px); background: rgba(255, 255, 255, 1); }
        .music-icon-wrapper { width: 24px; height: 24px; background: #f7ede2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; }

        @media (max-width: 480px) {
            .names-title { font-size: 2.8rem; }
            .cover-frame, .second-photo-frame { height: 210px; }
            .question-text { font-size: 1.5rem; }
            .handwritten-letter { font-size: 1.25rem; }
            .timer-value { font-size: 1.1rem; }
            .final-phrase { font-size: 1.7rem; }
            .footer-music-bar { bottom: 15px; right: 15px; padding: 6px 12px; font-size: 0.7rem; }
            .promises-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <canvas id="canvas-hearts"></canvas>

    <div class="container">
        <header class="hero-section">
            <h1 class="names-title" id="names-title"><?php echo htmlspecialchars($partner1 . ' & ' . $partner2); ?></h1>
            <p class="subtitle" id="sub-header-title"><?php echo htmlspecialchars($uiTexts['sub_header_title'] ?? 'Nuestra Historia de Amor'); ?></p>
            
            <div class="cover-frame">
                <img id="cover-img-display" src="<?php echo htmlspecialchars($coverImg); ?>" alt="Foto de portada" class="cover-img" style="<?php echo !empty($coverImg) ? '' : 'display:none;'; ?>" onerror="renderImageFallback('cover-img-display', 'no-cover-placeholder')">
                <div id="no-cover-placeholder" class="empty-image-placeholder" style="<?php echo !empty($coverImg) ? 'display:none;' : 'display:flex;'; ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                    </svg>
                    <span>Sin imagen de portada</span>
                </div>
            </div>
        </header>

        <!-- CARTA CON EL DISEÑO DE INDEX (2).HTML -->
        <section class="letter-wrapper">
            <div class="mystic-envelope" id="envelope" onclick="toggleLetter()">
                <div class="envelope-flap"></div>
                <div class="wax-seal">♥</div>

                <div class="envelope-header-content">
                    <div class="envelope-title" id="envelope-title"><?php echo htmlspecialchars($uiTexts['envelope_title'] ?? 'Carta para ti'); ?></div>
                    <div class="envelope-hint" id="envelope-subtitle"><?php echo htmlspecialchars($uiTexts['envelope_subtitle'] ?? '(Toca para romper el sello y leer)'); ?></div>
                </div>

                <div class="parchment-letter">
                    <div class="handwritten-letter" id="letter-body">
                        <?php 
                        $paragraphs = $siteData['letter_paragraphs'] ?? [];
                        if (!empty($paragraphs)) {
                            foreach ($paragraphs as $p) {
                                echo '<p style="margin-bottom:14px;">' . nl2br(htmlspecialchars($p['content'])) . '</p>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- PROPUESTA -->
        <section class="proposal-section" id="proposal-box" style="<?php echo $isAccepted ? 'display:none;' : ''; ?>">
            <h2 class="question-text" id="proposal-question"><?php echo htmlspecialchars($uiTexts['proposal_question'] ?? '¿Quieres ser mi novia?'); ?></h2>
            <button class="btn-yes" id="btn-yes" onclick="acceptProposal()"><?php echo htmlspecialchars($uiTexts['btn_yes_text'] ?? '¡Sí, Quiero!'); ?></button>
        </section>

        <!-- SECCIÓN REVELADA -->
        <section class="reveal-section <?php echo $isAccepted ? 'active' : ''; ?>" id="reveal-content">
            <div class="counter-card">
                <div class="counter-title" id="counter-title"><?php echo htmlspecialchars($uiTexts['counter_title'] ?? 'Tiempo caminando juntos'); ?></div>
                <div class="timer-grid">
                    <div class="timer-box" id="box-years"><div class="timer-value" id="years">00</div><div class="timer-label" id="label-years"><?php echo htmlspecialchars($uiTexts['label_years'] ?? 'Años'); ?></div></div>
                    <div class="timer-box" id="box-months"><div class="timer-value" id="months">00</div><div class="timer-label" id="label-months"><?php echo htmlspecialchars($uiTexts['label_months'] ?? 'Meses'); ?></div></div>
                    <div class="timer-box" id="box-days"><div class="timer-value" id="days">00</div><div class="timer-label" id="label-days"><?php echo htmlspecialchars($uiTexts['label_days'] ?? 'Días'); ?></div></div>
                    <div class="timer-box active-box"><div class="timer-value" id="hours">00</div><div class="timer-label" id="label-hours"><?php echo htmlspecialchars($uiTexts['label_hours'] ?? 'Horas'); ?></div></div>
                    <div class="timer-box active-box"><div class="timer-value" id="minutes">00</div><div class="timer-label" id="label-minutes"><?php echo htmlspecialchars($uiTexts['label_minutes'] ?? 'Minutos'); ?></div></div>
                    <div class="timer-box active-box"><div class="timer-value" id="seconds">00</div><div class="timer-label" id="label-seconds"><?php echo htmlspecialchars($uiTexts['label_seconds'] ?? 'Segundos'); ?></div></div>
                </div>
                <div class="date-badge" id="start-date-display"></div>
            </div>

            <section class="reasons-card">
                <div class="reasons-title" id="reasons-title"><?php echo htmlspecialchars($uiTexts['reasons_title'] ?? 'Razones por las que te amo'); ?></div>
                <div class="reason-text" id="reason-display"><?php echo htmlspecialchars($uiTexts['reason_placeholder'] ?? 'Haz clic abajo para descubrir una razón especial...'); ?></div>
                <button class="btn-reason" id="btn-reason" onclick="nextReason()"><?php echo htmlspecialchars($uiTexts['btn_next_reason_text'] ?? 'Ver otra razón ✨'); ?></button>
            </section>

            <section class="timeline-section">
                <div class="timeline-title" id="timeline-section-title"><?php echo htmlspecialchars($uiTexts['timeline_section_title'] ?? 'Nuestra Historia en Momentos'); ?></div>
                <div class="timeline-container" id="timeline-container">
                    <?php 
                    $timeline = $siteData['timeline_chapters'] ?? [];
                    foreach ($timeline as $ch): ?>
                        <div class="timeline-item" onclick="toggleTimeline(this)">
                            <div class="timeline-date"><?php echo htmlspecialchars($ch['chapter_label']); ?></div>
                            <div class="timeline-header"><?php echo htmlspecialchars($ch['title']); ?></div>
                            <div class="timeline-body"><?php echo nl2br(htmlspecialchars($ch['description'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="capsule-section">
                <div class="capsule-title" id="wishes-section-title"><?php echo htmlspecialchars($uiTexts['wishes_section_title'] ?? 'Nuestros Deseos para el Futuro'); ?></div>
                <div class="promises-grid" id="promises-grid">
                    <?php 
                    $wishes = $siteData['wishes'] ?? [];
                    foreach ($wishes as $w): ?>
                        <div class="promise-card" onclick="revealPromise(this)">
                            <div class="promise-icon"><?php echo htmlspecialchars($w['icon']); ?></div>
                            <div class="promise-label"><?php echo htmlspecialchars($w['label']); ?></div>
                            <div class="promise-secret"><?php echo htmlspecialchars($w['secret_text']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="romantic-moment">
                <div class="second-photo-frame">
                    <img id="second-photo-display" src="<?php echo htmlspecialchars($secondImg); ?>" alt="Nuestro momento" class="second-photo" style="<?php echo !empty($secondImg) ? '' : 'display:none;'; ?>" onerror="renderImageFallback('second-photo-display', 'no-second-placeholder')">
                    <div id="no-second-placeholder" class="empty-image-placeholder" style="<?php echo !empty($secondImg) ? 'display:none;' : 'display:flex;'; ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                        </svg>
                        <span>Sin segunda imagen</span>
                    </div>
                </div>
                <h2 class="final-phrase" id="final-phrase"><?php echo htmlspecialchars($uiTexts['final_phrase'] ?? 'Hoy es el primer día de nuestra vida'); ?></h2>
            </div>

            <!-- BOTONERA PARA COMPARTIR EN REDES SOCIALES Y MENSAJERÍA -->
            <section class="share-section">
                <div class="share-title">Comparte Nuestro Momento</div>
                <p class="share-subtitle">Envía nuestra historia a tus seres queridos o publícala en tus redes</p>
                <div class="share-buttons-grid">
                    <!-- WhatsApp -->
                    <button type="button" class="share-btn share-btn-whatsapp" onclick="shareTo('whatsapp')" title="Compartir en WhatsApp">
                        <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.044c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.393-10.416c-5.514 0-10 4.486-10 10 0 1.942.557 3.755 1.52 5.292l-1.558 5.698 5.845-1.533c1.48.835 3.19 1.309 5.011 1.309 5.514 0 10-4.486 10-10s-4.486-10-10-10z"/></svg>
                        WhatsApp
                    </button>

                    <!-- Telegram -->
                    <button type="button" class="share-btn share-btn-telegram" onclick="shareTo('telegram')" title="Compartir en Telegram">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .36z"/></svg>
                        Telegram
                    </button>

                    <!-- Facebook -->
                    <button type="button" class="share-btn share-btn-facebook" onclick="shareTo('facebook')" title="Compartir en Facebook">
                        <svg viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.325v21.351C0 23.404.597 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.728 0 1.325-.596 1.325-1.324V1.325C24 .597 23.404 0 22.675 0z"/></svg>
                        Facebook
                    </button>

                    <!-- Twitter / X -->
                    <button type="button" class="share-btn share-btn-twitter" onclick="shareTo('twitter')" title="Compartir en X">
                        <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X
                    </button>

                    <!-- Compartir nativo (Web Share API para móviles y apps de mensajería) -->
                    <button type="button" class="share-btn share-btn-native" id="btn-native-share" onclick="shareNative()" title="Más opciones de compartir" style="display:none;">
                        <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92z"/></svg>
                        Compartir
                    </button>

                    <!-- Copiar Enlace -->
                    <button type="button" class="share-btn share-btn-copy" onclick="copyShareLink()" title="Copiar enlace al portapapeles">
                        <svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                        Copiar Enlace
                    </button>
                </div>
            </section>
        </section>
    </div>

    <!-- Notificación emergente toast -->
    <div id="share-toast" class="share-toast">¡Enlace copiado al portapapeles! 💕</div>

    <div class="footer-music-bar" onclick="toggleMusic()">
        <div class="music-icon-wrapper"><span id="music-icon">🎵</span></div>
        <span id="music-text"><?php echo htmlspecialchars($uiTexts['music_play_text'] ?? 'Música'); ?></span>
    </div>

    <audio id="bg-music" loop preload="auto" src="<?php echo htmlspecialchars($bgMusic); ?>"></audio>

    <script>
        const SITE_DATA = <?php echo json_encode($siteData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;

        const s = SITE_DATA.settings || {};
        const t = SITE_DATA.ui_texts || {};
        const reasonsList = (SITE_DATA.reasons || []).map(r => r.content);

        let configuredStartDate = s.start_date || '';
        let isProposalAccepted = <?php echo $isAccepted ? 'true' : 'false'; ?>;
        let siteLocale = s.date_locale || 'es-ES';
        let musicPlayText = t.music_play_text || 'Música';
        let musicPauseText = t.music_pause_text || 'Pausar';
        let prefixSealed = t.date_prefix_sealed || 'Pacto sellado el';
        let prefixStarted = t.date_prefix_started || 'Iniciado el';

        let timerInterval = null;
        let startDate = null;

        function renderImageFallback(imgId, placeholderId) {
            const img = document.getElementById(imgId);
            const placeholder = document.getElementById(placeholderId);
            if (img) img.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
        }

        // ---------- Abrir / Cerrar Sobre (SIEMPRE PERMITIDO) ----------
        function toggleLetter() {
            const envelope = document.getElementById('envelope');
            const proposalBox = document.getElementById('proposal-box');
            
            // Alterna la clase open independientemente del estado de la propuesta
            envelope.classList.toggle('open');

            // Si se abre por primera vez y la propuesta aún no fue aceptada, se despliega el botón de Sí
            if (envelope.classList.contains('open') && !isProposalAccepted) {
                setTimeout(() => {
                    proposalBox.classList.add('visible');
                    proposalBox.style.display = 'block';
                    envelope.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 400);
            }
        }

        // ---------- Pulsar "¡Sí, Quiero!" ----------
        async function acceptProposal() {
            document.getElementById('proposal-box').style.display = 'none';
            const revealContent = document.getElementById('reveal-content');
            revealContent.classList.add('active');

            isProposalAccepted = true;
            const clientDate = new Date();
            const year = clientDate.getFullYear();
            const month = String(clientDate.getMonth() + 1).padStart(2, '0');
            const day = String(clientDate.getDate()).padStart(2, '0');
            const hours = String(clientDate.getHours()).padStart(2, '0');
            const minutes = String(clientDate.getMinutes()).padStart(2, '0');
            const seconds = String(clientDate.getSeconds()).padStart(2, '0');
            const exactTimestamp = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;

            try {
                const res = await fetch('api.php?action=accept_proposal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accepted_at: exactTimestamp })
                });
                const data = await res.json();
                if (data.success && data.start_date) {
                    configuredStartDate = data.start_date;
                    startDate = new Date(data.start_date);
                } else {
                    startDate = clientDate;
                }
            } catch (err) {
                startDate = clientDate;
            }

            renderStartDateBadge();
            startCounter();
            triggerCelebrationBurst();

            const audio = document.getElementById('bg-music');
            audio.play().then(() => {
                document.getElementById('music-text').innerText = musicPauseText;
            }).catch(() => {});

            setTimeout(() => {
                revealContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 600);
        }

        function renderStartDateBadge() {
            if (!startDate) return;
            const prefix = configuredStartDate ? prefixSealed : prefixStarted;
            let formatted;
            try {
                formatted = startDate.toLocaleString(siteLocale, { dateStyle: 'long', timeStyle: 'short' });
            } catch (e) {
                formatted = startDate.toLocaleString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
            const el = document.getElementById('start-date-display');
            if (el) el.innerText = `${prefix}: ${formatted}`;
        }

        function startCounter() {
            if (!startDate) return;
            if (timerInterval) clearInterval(timerInterval);
            const tick = () => {
                const now = new Date();
                let years = now.getFullYear() - startDate.getFullYear();
                let months = now.getMonth() - startDate.getMonth();
                let days = now.getDate() - startDate.getDate();
                let hours = now.getHours() - startDate.getHours();
                let minutes = now.getMinutes() - startDate.getMinutes();
                let seconds = now.getSeconds() - startDate.getSeconds();
                if (seconds < 0) { seconds += 60; minutes--; }
                if (minutes < 0) { minutes += 60; hours--; }
                if (hours < 0) { hours += 24; days--; }
                if (days < 0) { months--; days += new Date(now.getFullYear(), now.getMonth(), 0).getDate(); }
                if (months < 0) { months += 12; years--; }

                const boxYears = document.getElementById('box-years');
                if (years > 0) { boxYears.classList.add('active-box'); document.getElementById('years').innerText = String(years).padStart(2, '0'); }
                else boxYears.classList.remove('active-box');

                const boxMonths = document.getElementById('box-months');
                if (months > 0 || years > 0) { boxMonths.classList.add('active-box'); document.getElementById('months').innerText = String(months).padStart(2, '0'); }
                else boxMonths.classList.remove('active-box');

                const boxDays = document.getElementById('box-days');
                if (days > 0 || months > 0 || years > 0) { boxDays.classList.add('active-box'); document.getElementById('days').innerText = String(days).padStart(2, '0'); }
                else boxDays.classList.remove('active-box');

                document.getElementById('hours').innerText = String(Math.max(0, hours)).padStart(2, '0');
                document.getElementById('minutes').innerText = String(Math.max(0, minutes)).padStart(2, '0');
                document.getElementById('seconds').innerText = String(Math.max(0, seconds)).padStart(2, '0');
            };
            tick();
            timerInterval = setInterval(tick, 1000);
        }

        let reasonIndex = 0;
        function nextReason() {
            if (!reasonsList.length) return;
            const display = document.getElementById('reason-display');
            display.style.opacity = '0';
            display.style.transform = 'translateY(10px)';
            setTimeout(() => {
                display.innerText = '"' + reasonsList[reasonIndex] + '"';
                display.style.opacity = '1';
                display.style.transform = 'translateY(0)';
                reasonIndex = (reasonIndex + 1) % reasonsList.length;
            }, 300);
        }

        function toggleTimeline(el) { el.classList.toggle('active'); }
        function revealPromise(el) { el.classList.toggle('revealed'); }

        function toggleMusic() {
            const audio = document.getElementById('bg-music');
            const musicText = document.getElementById('music-text');
            if (audio.paused) {
                audio.play().then(() => { musicText.innerText = musicPauseText; }).catch(() => {});
            } else {
                audio.pause();
                musicText.innerText = musicPlayText;
            }
        }

        /* ANIMACIÓN DE CORAZONES */
        const canvas = document.getElementById('canvas-hearts');
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;
        window.addEventListener('resize', () => { width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; });

        class Particle {
            constructor(x, y, isBurst = false, isConfetti = false) {
                this.x = x || Math.random() * width;
                this.y = y || height + 20;
                this.size = isConfetti ? Math.random() * 8 + 4 : Math.random() * 12 + 6;
                this.speedY = isBurst ? (Math.random() - 0.7) * 9 : Math.random() * 1.2 + 0.6;
                this.speedX = isBurst ? (Math.random() - 0.5) * 8 : Math.random() * 0.8 - 0.4;
                this.opacity = 1;
                this.isBurst = isBurst;
                this.isConfetti = isConfetti;
                this.color = isConfetti ? `hsl(${Math.random() * 360}, 90%, 65%)` : `hsl(${Math.random() * 20 + 340}, 80%, 65%)`;
                this.rotation = Math.random() * Math.PI * 2;
                this.rotationSpeed = (Math.random() - 0.5) * 0.2;
            }
            update() {
                if (this.isBurst) {
                    this.x += this.speedX; this.y += this.speedY;
                    this.opacity -= 0.015; this.rotation += this.rotationSpeed;
                } else {
                    this.y -= this.speedY;
                    this.x += Math.sin(this.y * 0.02) * 0.4;
                    if (this.y < -20) { this.y = height + 20; this.x = Math.random() * width; }
                }
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = Math.max(0, this.opacity);
                ctx.fillStyle = this.color;
                ctx.translate(this.x, this.y);
                ctx.rotate(this.rotation);
                if (this.isConfetti) {
                    ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size / 2);
                } else {
                    ctx.beginPath();
                    const t = this.size * 0.3;
                    ctx.moveTo(0, t);
                    ctx.bezierCurveTo(0, 0, -this.size / 2, 0, -this.size / 2, t);
                    ctx.bezierCurveTo(-this.size / 2, (this.size + t) / 2, 0, this.size, 0, this.size);
                    ctx.bezierCurveTo(0, (this.size + t) / 2, this.size / 2, t, this.size / 2, t);
                    ctx.bezierCurveTo(this.size / 2, 0, 0, 0, 0, t);
                    ctx.closePath(); ctx.fill();
                }
                ctx.restore();
            }
        }

        const particles = Array.from({ length: 20 }, () => new Particle());

        function triggerCelebrationBurst() {
            const btn = document.getElementById('btn-yes');
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const cx = rect.left + rect.width / 2, cy = rect.top + rect.height / 2;
            for (let i = 0; i < 40; i++) particles.push(new Particle(cx, cy, true, false));
            for (let i = 0; i < 40; i++) particles.push(new Particle(cx, cy, true, true));
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);
            for (let i = particles.length - 1; i >= 0; i--) {
                particles[i].update(); particles[i].draw();
                if (particles[i].isBurst && particles[i].opacity <= 0) particles.splice(i, 1);
            }
            requestAnimationFrame(animate);
        }
        animate();

        window.addEventListener('mousemove', e => {
            if (Math.random() < 0.08) particles.push(new Particle(e.clientX, e.clientY, true, false));
        });

        // ---------- Compartir en Redes Sociales y Mensajería ----------
        function showShareToast(message) {
            const toast = document.getElementById('share-toast');
            if (!toast) return;
            if (message) toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function getShareData() {
            const title = (s.partner_one || 'Ella') + ' & ' + (s.partner_two || 'Él') + ' — ' + (t.sub_header_title || 'Nuestra Historia');
            const text = t.final_phrase || 'Hoy es el primer día de nuestra vida juntos 💕';
            const url = window.location.href;
            return { title, text, url };
        }

        function shareTo(platform) {
            const { title, text, url } = getShareData();
            let shareUrl = '';

            switch (platform) {
                case 'whatsapp':
                    shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(title + '\n' + text + '\n' + url)}`;
                    break;
                case 'telegram':
                    shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title + ' - ' + text)}`;
                    break;
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title + ' ' + text)}&url=${encodeURIComponent(url)}`;
                    break;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'noopener,noreferrer,width=600,height=500');
            }
        }

        async function shareNative() {
            const { title, text, url } = getShareData();
            if (navigator.share) {
                try {
                    await navigator.share({ title, text, url });
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        copyShareLink();
                    }
                }
            } else {
                copyShareLink();
            }
        }

        function copyShareLink() {
            const url = window.location.href;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(() => {
                    showShareToast('¡Enlace copiado al portapapeles! 💕');
                }).catch(() => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(text) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand('copy');
                showShareToast('¡Enlace copiado al portapapeles! 💕');
            } catch (e) {
                showShareToast('Por favor copia la URL de la barra de direcciones.');
            }
            document.body.removeChild(tempInput);
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (configuredStartDate) {
                startDate = new Date(configuredStartDate);
                renderStartDateBadge();
                startCounter();
            }

            // Si el navegador soporta Web Share API (smartphones, tablets o navegadores compatibles), habilitar el botón
            if (navigator.share) {
                const nativeBtn = document.getElementById('btn-native-share');
                if (nativeBtn) nativeBtn.style.display = 'inline-flex';
            }
        });
    </script>
</body>
</html>