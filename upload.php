<?php
// ============================================================
//  upload.php — Gestor de archivos del panel de admin
//    POST upload.php                      -> subir archivo (image_file / audio_file)
//    GET  upload.php?action=list_files&type=image|audio
//    POST upload.php?action=delete_file   {filename, type}
// ============================================================
require_once __DIR__ . '/config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// Definición de límites de resolución en píxeles (Ancho x Alto)
define('MAX_IMAGE_WIDTH', 3840);  // 4K UHD
define('MAX_IMAGE_HEIGHT', 2160); // 4K UHD

if (!site_installed()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'El sitio no está instalado. Abre install.php primero.']);
    exit;
}

if (empty($_SESSION['admin_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Se requiere iniciar sesión.']);
    exit;
}

$action = $_GET['action'] ?? 'upload';

function dirs_for($type) {
    if ($type === 'audio') return [UPLOAD_AUD_DIR, UPLOAD_AUD_URL, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac']];
    return [UPLOAD_IMG_DIR, UPLOAD_IMG_URL, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg']];
}

// ---------- Listar archivos ----------
if ($action === 'list_files') {
    [$dir, $urlBase, ] = dirs_for($_GET['type'] ?? 'image');
    $files = [];
    if (is_dir($dir)) {
        foreach (glob($dir . '*') as $f) {
            if (is_file($f)) {
                $files[] = [
                    'filename' => basename($f),
                    'url'      => $urlBase . rawurlencode(basename($f)),
                    'size'     => filesize($f),
                    'mtime'    => filemtime($f),
                ];
            }
        }
    }
    usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    echo json_encode(['success' => true, 'files' => array_values($files)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Eliminar archivo ----------
if ($action === 'delete_file') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $type = $body['type'] ?? 'image';
    $filename = basename($body['filename'] ?? ''); // blindaje contra path traversal
    [$dir, , ] = dirs_for($type);
    $path = $dir . $filename;
    if ($filename && is_file($path)) {
        unlink($path);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Archivo no encontrado.']);
    }
    exit;
}

// ---------- Subir archivo ----------
if (!empty($_FILES['image_file']) || !empty($_FILES['audio_file'])) {
    $isAudio  = !empty($_FILES['audio_file']);
    $field    = $isAudio ? 'audio_file' : 'image_file';
    $tmp      = $_FILES[$field]['tmp_name'];
    $origName = $_FILES[$field]['name'];
    $err      = $_FILES[$field]['error'];

    // 1. Diagnóstico exacto del código de error de PHP al subir
    if ($err !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede la directiva upload_max_filesize del servidor en php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo permitido especificado en el formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo solo se subió parcialmente. Por favor, reintenta.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo para subir.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco por problemas de permisos.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida del archivo.'
        ];
        $msg = $errorMessages[$err] ?? ('Error de subida desconocido (Código ' . $err . ').');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    // 2. Comprobación de peso máximo permitido
    $fileSize = filesize($tmp);
    if ($fileSize > MAX_UPLOAD_SIZE) {
        $mbUploaded = round($fileSize / (1024 * 1024), 2);
        $mbMax = round(MAX_UPLOAD_SIZE / (1024 * 1024), 2);
        echo json_encode([
            'success' => false, 
            'error'   => "El archivo es demasiado grande ({$mbUploaded} MB). El tamaño máximo permitido es de {$mbMax} MB."
        ]);
        exit;
    }

    [$dir, $urlBase, $exts] = dirs_for($isAudio ? 'audio' : 'image');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $filename = $safeBase . '.' . $ext;

    // 3. Validación detallada de la extensión
    if (!in_array($ext, $exts, true)) {
        $allowedList = implode(', ', array_map(fn($e) => '.' . $e, $exts));
        echo json_encode([
            'success' => false, 
            'error'   => "La extensión '." . $ext . "' no está permitida. Formatos admitidos: " . $allowedList
        ]);
        exit;
    }

    // 4. Validación de resoluciones en píxeles para imágenes (excluyendo SVG)
    if (!$isAudio && $ext !== 'svg') {
        $imageInfo = @getimagesize($tmp);
        if ($imageInfo === false) {
            echo json_encode([
                'success' => false, 
                'error'   => 'El archivo subido no es una imagen válida o está dañado.'
            ]);
            exit;
        }

        $width  = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
            echo json_encode([
                'success' => false,
                'error'   => "La imagen excede las dimensiones máximas. Resolución actual: {$width}x{$height} px. Máximo permitido: " . MAX_IMAGE_WIDTH . "x" . MAX_IMAGE_HEIGHT . " px."
            ]);
            exit;
        }
    }

    // Evitar sobrescribir: agregar sufijo numérico si ya existe
    $i = 1;
    while (file_exists($dir . $filename)) {
        $filename = $safeBase . '_' . $i++ . '.' . $ext;
    }

    if (move_uploaded_file($tmp, $dir . $filename)) {
        @chmod($dir . $filename, 0644);
        echo json_encode([
            'success'  => true, 
            'url'      => $urlBase . rawurlencode($filename), 
            'filename' => $filename
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error'   => 'No se pudo mover el archivo al directorio final (uploads/). Verifica permisos de escritura en el servidor.'
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Petición no válida o no se enviaron archivos.']);