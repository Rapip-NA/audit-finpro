-- ============================================================
-- SQL Schema untuk Audit Finpro (cPanel / MySQL / phpMyAdmin)
-- Mendukung Folders, Notes, dan File HTML
-- ============================================================

CREATE TABLE IF NOT EXISTS `items` (
  `id` varchar(64) NOT NULL,
  `parent_id` varchar(64) DEFAULT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'folder',
  `name` varchar(255) NOT NULL,
  `color` varchar(32) DEFAULT 'black',
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
