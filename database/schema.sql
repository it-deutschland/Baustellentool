-- ============================================================
-- Sonka Bau & Sonnenimmobilien - Multi Administration – Datenbankschema
-- MariaDB / MySQL 10+  |  UTF-8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Roles ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(50)  NOT NULL UNIQUE,
  `display_name`VARCHAR(100) NOT NULL,
  `description` TEXT         NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`) VALUES
  ('admin',    'Administrator', 'Vollzugriff auf alle Funktionen'),
  ('editor',   'Bearbeiter',    'Erstellen und Bearbeiten von Projekten'),
  ('reviewer', 'Prüfer',        'Nur-Lesen und Prüfen von Projekten'),
  ('guest',    'Gast',          'Eingeschränkter Lesezugriff');

-- ── Permissions ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `display_name`VARCHAR(150) NOT NULL,
  `module`      VARCHAR(50)  NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `permissions` (`name`, `display_name`, `module`) VALUES
  ('projects.view',    'Projekte anzeigen',    'projects'),
  ('projects.create',  'Projekte erstellen',   'projects'),
  ('projects.edit',    'Projekte bearbeiten',  'projects'),
  ('projects.delete',  'Projekte löschen',     'projects'),
  ('plans.view',       'Pläne anzeigen',       'plans'),
  ('plans.create',     'Pläne erstellen',      'plans'),
  ('plans.edit',       'Pläne bearbeiten',     'plans'),
  ('plans.delete',     'Pläne löschen',        'plans'),
  ('documents.view',   'Dokumente anzeigen',   'documents'),
  ('documents.create', 'Dokumente erstellen',  'documents'),
  ('documents.edit',   'Dokumente bearbeiten', 'documents'),
  ('documents.delete', 'Dokumente löschen',    'documents'),
  ('materials.view',   'Material anzeigen',    'materials'),
  ('materials.manage', 'Material verwalten',   'materials'),
  ('symbols.view',     'Symbole anzeigen',     'symbols'),
  ('symbols.manage',   'Symbole verwalten',    'symbols'),
  ('users.view',       'Benutzer anzeigen',    'users'),
  ('users.manage',     'Benutzer verwalten',   'users'),
  ('settings.view',    'Einstellungen anzeigen','settings'),
  ('settings.manage',  'Einstellungen verwalten','settings'),
  ('backup.manage',    'Backup verwalten',     'backup'),
  ('api.access',       'API-Zugriff',          'api');

-- ── Role-Permission pivot ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- admin gets all permissions (inserted after users table)
-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id`            INT UNSIGNED NOT NULL DEFAULT 2,
  `name`               VARCHAR(100) NOT NULL,
  `email`              VARCHAR(191) NOT NULL UNIQUE,
  `password`           VARCHAR(255) NOT NULL,
  `avatar`             VARCHAR(255) NULL,
  `phone`              VARCHAR(30)  NULL,
  `two_factor_secret`  VARCHAR(100) NULL,
  `two_factor_enabled` TINYINT(1)   NOT NULL DEFAULT 0,
  `email_verified_at`  DATETIME     NULL,
  `token`              VARCHAR(64)  NULL,
  `token_expires_at`   DATETIME     NULL,
  `login_attempts`     TINYINT      NOT NULL DEFAULT 0,
  `locked_until`       DATETIME     NULL,
  `remember_token`     VARCHAR(100) NULL,
  `last_login_at`      DATETIME     NULL,
  `last_login_ip`      VARCHAR(45)  NULL,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `dark_mode`          TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Settings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`         VARCHAR(100) NOT NULL UNIQUE,
  `value`       TEXT         NULL,
  `group`       VARCHAR(50)  NOT NULL DEFAULT 'general',
  `type`        ENUM('text','number','boolean','json','textarea') NOT NULL DEFAULT 'text',
  `label`       VARCHAR(150) NOT NULL,
  `description` TEXT         NULL,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`key`, `value`, `group`, `type`, `label`) VALUES
  ('company_name',      'Meine Firma GmbH',            'company',  'text',     'Firmenname'),
  ('company_address',   '',                            'company',  'textarea', 'Firmenanschrift'),
  ('company_phone',     '',                            'company',  'text',     'Telefon'),
  ('company_email',     '',                            'company',  'text',     'E-Mail'),
  ('company_website',   '',                            'company',  'text',     'Website'),
  ('company_logo',      '',                            'company',  'text',     'Logo (Pfad)'),
  ('company_logo_pdf',  '',                            'company',  'text',     'PDF-Logo (Pfad)'),
  ('pdf_footer_text',   'Erstellt mit Sonka Bau & Sonnenimmobilien - Multi Administration',     'pdf',      'text',     'PDF-Fußzeile'),
  ('pdf_page_size',     'A4',                          'pdf',      'text',     'Seitengröße'),
  ('pdf_orientation',   'P',                           'pdf',      'text',     'Ausrichtung (P/L)'),
  ('registration_enabled','1',                         'security', 'boolean',  'Registrierung erlaubt'),
  ('2fa_enabled',       '0',                           'security', 'boolean',  '2FA verfügbar'),
  ('smtp_host',         '',                            'mail',     'text',     'SMTP-Host'),
  ('smtp_port',         '587',                         'mail',     'number',   'SMTP-Port'),
  ('smtp_user',         '',                            'mail',     'text',     'SMTP-Benutzername'),
  ('smtp_pass',         '',                            'mail',     'text',     'SMTP-Passwort'),
  ('smtp_from',         '',                            'mail',     'text',     'Absender-E-Mail'),
  ('smtp_from_name',    '',                            'mail',     'text',     'Absendername'),
  ('smtp_encryption',   'tls',                         'mail',     'text',     'Verschlüsselung'),
  ('legal_disclaimer',  'Diese Unterlagen wurden mit Sonka Bau & Sonnenimmobilien - Multi Administration erstellt. Sie ersetzen keine fachkundige Prüfung. Vor der Einreichung ist eine Überprüfung durch einen qualifizierten Fachmann erforderlich.', 'legal', 'textarea', 'Rechtlicher Hinweis');

-- ── Customers ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customers` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company`      VARCHAR(200) NOT NULL,
  `contact_name` VARCHAR(100) NULL,
  `email`        VARCHAR(191) NULL,
  `phone`        VARCHAR(30)  NULL,
  `address`      TEXT         NULL,
  `city`         VARCHAR(100) NULL,
  `zip`          VARCHAR(10)  NULL,
  `country`      VARCHAR(50)  NOT NULL DEFAULT 'Deutschland',
  `notes`        TEXT         NULL,
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Projects ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `projects` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id`     INT UNSIGNED NULL,
  `created_by`      INT UNSIGNED NULL,
  `assigned_to`     INT UNSIGNED NULL,
  `project_number`  VARCHAR(50)  NOT NULL UNIQUE,
  `title`           VARCHAR(200) NOT NULL,
  `description`     TEXT         NULL,
  `location`        VARCHAR(255) NULL,
  `address`         TEXT         NULL,
  `gps_lat`         DECIMAL(10,8)NULL,
  `gps_lng`         DECIMAL(11,8)NULL,
  `contact_name`    VARCHAR(100) NULL,
  `contact_phone`   VARCHAR(30)  NULL,
  `contact_email`   VARCHAR(191) NULL,
  `start_date`      DATE         NULL,
  `end_date`        DATE         NULL,
  `status`          ENUM('draft','active','review','completed','archived') NOT NULL DEFAULT 'draft',
  `priority`        ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)     ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)     ON DELETE SET NULL,
  INDEX `idx_status`  (`status`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Plans ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `plans` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT UNSIGNED NOT NULL,
  `created_by`  INT UNSIGNED NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT         NULL,
  `scale`       VARCHAR(20)  NOT NULL DEFAULT '1:500',
  `canvas_data` LONGTEXT     NULL,      -- JSON (Fabric.js state)
  `thumbnail`   VARCHAR(255) NULL,
  `version`     SMALLINT     NOT NULL DEFAULT 1,
  `status`      ENUM('draft','review','approved','archived') NOT NULL DEFAULT 'draft',
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME     NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`)REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Documents ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `documents` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id`   INT UNSIGNED NOT NULL,
  `created_by`   INT UNSIGNED NULL,
  `template_id`  INT UNSIGNED NULL,
  `type`         ENUM('vra','signlist','materiallist','dailyreport','sitecheck','acceptance','report','other') NOT NULL DEFAULT 'other',
  `title`        VARCHAR(200) NOT NULL,
  `content`      LONGTEXT     NULL,      -- JSON or HTML
  `file_path`    VARCHAR(255) NULL,      -- generated PDF
  `version`      SMALLINT     NOT NULL DEFAULT 1,
  `status`       ENUM('draft','review','approved','archived') NOT NULL DEFAULT 'draft',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)      ON DELETE SET NULL,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Templates ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `templates` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`        VARCHAR(50)  NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT         NULL,
  `content`     LONGTEXT     NOT NULL,
  `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Materials ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `materials` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`     VARCHAR(100) NOT NULL,
  `name`         VARCHAR(200) NOT NULL,
  `article_no`   VARCHAR(50)  NULL,
  `unit`         VARCHAR(20)  NOT NULL DEFAULT 'Stk',
  `description`  TEXT         NULL,
  `stock`        DECIMAL(10,2)NOT NULL DEFAULT 0,
  `min_stock`    DECIMAL(10,2)NOT NULL DEFAULT 0,
  `price`        DECIMAL(10,2)NULL,
  `supplier`     VARCHAR(200) NULL,
  `location`     VARCHAR(100) NULL,
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Project-Material pivot ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `project_materials` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id`   INT UNSIGNED NOT NULL,
  `material_id`  INT UNSIGNED NOT NULL,
  `quantity`     DECIMAL(10,2)NOT NULL DEFAULT 1,
  `notes`        TEXT         NULL,
  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Symbols ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `symbols` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`    VARCHAR(100) NOT NULL,
  `subcategory` VARCHAR(100) NULL,
  `name`        VARCHAR(200) NOT NULL,
  `sign_number` VARCHAR(30)  NULL,      -- StVO sign number e.g. "123"
  `description` TEXT         NULL,
  `file_path`   VARCHAR(255) NOT NULL,
  `file_type`   ENUM('svg','png','jpg') NOT NULL DEFAULT 'svg',
  `width_mm`    SMALLINT     NULL,      -- standard width in mm
  `height_mm`   SMALLINT     NULL,      -- standard height in mm
  `tags`        VARCHAR(500) NULL,      -- comma-separated
  `is_favourite`TINYINT(1)   NOT NULL DEFAULT 0,
  `license`     VARCHAR(100) NULL,
  `source`      VARCHAR(255) NULL,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_category`  (`category`),
  INDEX `idx_sign_no`   (`sign_number`),
  FULLTEXT INDEX `ft_search` (`name`, `description`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Uploads ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `uploads` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NULL,
  `project_id`   INT UNSIGNED NULL,
  `plan_id`      INT UNSIGNED NULL,
  `document_id`  INT UNSIGNED NULL,
  `original_name`VARCHAR(255) NOT NULL,
  `stored_name`  VARCHAR(255) NOT NULL,
  `file_path`    VARCHAR(500) NOT NULL,
  `file_type`    VARCHAR(10)  NOT NULL,
  `mime_type`    VARCHAR(100) NOT NULL,
  `file_size`    INT UNSIGNED NOT NULL,
  `purpose`      ENUM('photo','attachment','drawing','plan_bg','symbol','template','backup','other') NOT NULL DEFAULT 'other',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE SET NULL,
  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`)     REFERENCES `plans`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Vehicles (Fahrzeuge) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT UNSIGNED NULL,
  `license_plate`VARCHAR(20) NULL,
  `type`        VARCHAR(100) NOT NULL,
  `description` TEXT         NULL,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Activity Log ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `logs` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED  NULL,
  `action`     VARCHAR(100)  NOT NULL,
  `module`     VARCHAR(50)   NOT NULL,
  `subject_id` INT UNSIGNED  NULL,
  `subject_type`VARCHAR(50)  NULL,
  `old_values` TEXT          NULL,     -- JSON
  `new_values` TEXT          NULL,     -- JSON
  `ip_address` VARCHAR(45)   NULL,
  `user_agent` VARCHAR(500)  NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_user`    (`user_id`),
  INDEX `idx_module`  (`module`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(50)  NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `link`       VARCHAR(255) NULL,
  `read_at`    DATETIME     NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_unread` (`user_id`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── API Tokens ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `token_hash` VARCHAR(64)  NOT NULL UNIQUE,
  `last_used`  DATETIME     NULL,
  `expires_at` DATETIME     NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
