<?php
// ============================================================
//  db_schema.php — Definición de Tablas MySQL (Estilo MyBB)
// ============================================================

return [
    'admin_users' => "CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'ui_media' => "CREATE TABLE IF NOT EXISTS `ui_media` (
        `media_key` VARCHAR(100) PRIMARY KEY,
        `media_value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'letter_paragraphs' => "CREATE TABLE IF NOT EXISTS `letter_paragraphs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `paragraph_order` INT NOT NULL DEFAULT 0,
        `content` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'reasons' => "CREATE TABLE IF NOT EXISTS `reasons` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `item_order` INT NOT NULL DEFAULT 0,
        `content` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'timeline_chapters' => "CREATE TABLE IF NOT EXISTS `timeline_chapters` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `chapter_order` INT NOT NULL DEFAULT 0,
        `chapter_label` VARCHAR(100) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'wishes' => "CREATE TABLE IF NOT EXISTS `wishes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `wish_order` INT NOT NULL DEFAULT 0,
        `icon` VARCHAR(50) NOT NULL,
        `label` VARCHAR(255) NOT NULL,
        `secret_text` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

