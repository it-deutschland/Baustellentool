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
-- Sonka Bau & Sonnenimmobilien - Multi Administration – Dokumentvorlagen (Default-Templates)
-- Diese Vorlagen werden bei der Installation eingefügt.

INSERT IGNORE INTO `templates` (`type`, `name`, `description`, `content`, `is_default`) VALUES

-- Verkehrsrechtliche Anordnung
('vra', 'Antrag verkehrsrechtliche Anordnung', 'Standardvorlage für eine Verkehrsrechtliche Anordnung',
'{"sections":[
  {"id":"header","label":"Antragsteller","fields":[
    {"name":"applicant_name","label":"Name / Firma","type":"text","value":"{{company_name}}"},
    {"name":"applicant_address","label":"Anschrift","type":"textarea","value":"{{company_address}}"},
    {"name":"applicant_phone","label":"Telefon","type":"text","value":"{{company_phone}}"},
    {"name":"applicant_email","label":"E-Mail","type":"text","value":"{{company_email}}"}
  ]},
  {"id":"project","label":"Baustelle / Veranstaltung","fields":[
    {"name":"location","label":"Ort / Straße","type":"text","value":"{{location}}"},
    {"name":"start_date","label":"Beginn","type":"date","value":"{{start_date}}"},
    {"name":"end_date","label":"Ende","type":"date","value":"{{end_date}}"},
    {"name":"description","label":"Beschreibung der Maßnahme","type":"textarea","value":"{{description}}"}
  ]},
  {"id":"measures","label":"Beantragte Maßnahmen","fields":[
    {"name":"measures","label":"Angeordnete Verkehrszeichen / Verkehrseinrichtungen","type":"textarea","value":""},
    {"name":"speed_limit","label":"Geschwindigkeitsbeschränkung","type":"text","value":""},
    {"name":"road_closure","label":"Sperrungen","type":"textarea","value":""}
  ]},
  {"id":"legal","label":"Erklärung","fields":[
    {"name":"disclaimer","label":"Rechtlicher Hinweis","type":"static","value":"Die eingereichten Unterlagen wurden mit Sonka Bau & Sonnenimmobilien - Multi Administration erstellt. Sie ersetzen keine fachkundige Prüfung durch einen qualifizierten Fachmann. Alle Angaben sind nach bestem Wissen und Gewissen gemacht. Die Genehmigungsfähigkeit kann durch diese Software nicht zugesichert werden."},
    {"name":"date_place","label":"Ort, Datum","type":"text","value":"{{city}}, {{date}}"},
    {"name":"signature","label":"Unterschrift Antragsteller","type":"signature","value":""}
  ]}
]}', 1),

-- Verkehrszeichenliste
('signlist', 'Verkehrszeichenliste', 'Liste aller verwendeten Verkehrszeichen',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"location","label":"Baustellenort","type":"text","value":"{{location}}"},
    {"name":"period","label":"Maßnahmenzeitraum","type":"text","value":"{{start_date}} – {{end_date}}"}
  ]},
  {"id":"signs","label":"Verkehrszeichen","type":"table",
   "columns":["Pos.","Zeichen-Nr.","Bezeichnung","Anzahl","Größe","Aufstellort","Bemerkung"],
   "rows":[]}
]}', 1),

-- Materialliste
('materiallist', 'Materialliste', 'Auflistung aller benötigten Materialien',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"date","label":"Stand","type":"text","value":"{{date}}"}
  ]},
  {"id":"materials","label":"Materialien","type":"table",
   "columns":["Pos.","Artikel-Nr.","Bezeichnung","Menge","Einheit","Lieferant","Preis/E","Gesamt"],
   "rows":[]}
]}', 1),

-- Tagesbericht
('dailyreport', 'Tagesbericht', 'Täglich auszufüllender Bericht',
'{"sections":[
  {"id":"header","label":"Kopfdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"date","label":"Datum","type":"date","value":"{{today}}"},
    {"name":"weather","label":"Wetter","type":"text","value":""},
    {"name":"temp","label":"Temperatur","type":"text","value":""},
    {"name":"reporter","label":"Berichterstatter","type":"text","value":""}
  ]},
  {"id":"personnel","label":"Personal","type":"table",
   "columns":["Name","Funktion","Beginn","Ende","Std.","Bemerkung"],
   "rows":[]},
  {"id":"activities","label":"Tätigkeiten","fields":[
    {"name":"activities","label":"Durchgeführte Arbeiten","type":"textarea","value":""},
    {"name":"incidents","label":"Besondere Vorkommnisse","type":"textarea","value":""},
    {"name":"notes","label":"Sonstige Bemerkungen","type":"textarea","value":""}
  ]},
  {"id":"signature","label":"Unterschriften","fields":[
    {"name":"sig_bauleiter","label":"Bauleiter","type":"signature","value":""},
    {"name":"sig_auftraggeber","label":"Auftraggeber","type":"signature","value":""}
  ]}
]}', 1),

-- Baustellenkontrolle
('sitecheck', 'Baustellenkontrolle', 'Protokoll zur Baustellenüberprüfung nach RSA21',
'{"sections":[
  {"id":"header","label":"Kopfdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"},
    {"name":"check_date","label":"Kontrollzeitpunkt","type":"datetime","value":""},
    {"name":"checker","label":"Kontrolleur","type":"text","value":""}
  ]},
  {"id":"checklist","label":"Prüfliste","type":"checklist","items":[
    {"id":"c1","label":"Verkehrszeichen vollständig und lesbar aufgestellt"},
    {"id":"c2","label":"Absperrungen intakt und vollständig"},
    {"id":"c3","label":"Beleuchtung und Warneinrichtungen funktionsfähig"},
    {"id":"c4","label":"Zufahrten für Rettungsfahrzeuge freigehalten"},
    {"id":"c5","label":"Fahrbahnmarkierungen vorhanden"},
    {"id":"c6","label":"Schutzeinrichtungen für Fußgänger vorhanden"},
    {"id":"c7","label":"Sicherheitsabstände eingehalten"},
    {"id":"c8","label":"Beleuchtung der Arbeitsstelle ausreichend (Nacht)"}
  ]},
  {"id":"defects","label":"Mängel","fields":[
    {"name":"defects","label":"Festgestellte Mängel","type":"textarea","value":""},
    {"name":"measures","label":"Sofortmaßnahmen","type":"textarea","value":""},
    {"name":"followup","label":"Nachzuverfolgende Punkte","type":"textarea","value":""}
  ]},
  {"id":"signature","label":"Unterschrift","fields":[
    {"name":"signature","label":"Kontrolleur","type":"signature","value":""}
  ]}
]}', 1),

-- Abnahmeprotokoll
('acceptance', 'Abnahmeprotokoll', 'Protokoll zur Abnahme der Baustellensicherung',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"},
    {"name":"acceptance_date","label":"Abnahmedatum","type":"date","value":""},
    {"name":"contractor","label":"Auftragnehmer","type":"text","value":"{{company_name}}"}
  ]},
  {"id":"result","label":"Abnahmeergebnis","fields":[
    {"name":"result","label":"Ergebnis","type":"select","options":["Abgenommen","Abgenommen mit Auflagen","Nicht abgenommen"],"value":""},
    {"name":"conditions","label":"Auflagen / Mängel","type":"textarea","value":""},
    {"name":"deadline","label":"Behebungsfrist","type":"date","value":""}
  ]},
  {"id":"signatures","label":"Unterschriften","fields":[
    {"name":"sig_ag","label":"Auftraggeber","type":"signature","value":""},
    {"name":"sig_an","label":"Auftragnehmer","type":"signature","value":""}
  ]}
]}', 1),

-- Projektbericht
('report', 'Projektabschlussbericht', 'Zusammenfassender Bericht nach Abschluss der Maßnahme',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekttitel","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"customer","label":"Auftraggeber","type":"text","value":"{{customer}}"},
    {"name":"period","label":"Maßnahmenzeitraum","type":"text","value":"{{start_date}} – {{end_date}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"}
  ]},
  {"id":"summary","label":"Zusammenfassung","fields":[
    {"name":"summary","label":"Projektzusammenfassung","type":"textarea","value":""},
    {"name":"objectives","label":"Ziele der Maßnahme","type":"textarea","value":""},
    {"name":"results","label":"Erzielte Ergebnisse","type":"textarea","value":""},
    {"name":"problems","label":"Aufgetretene Probleme / Lösungen","type":"textarea","value":""}
  ]},
  {"id":"disclaimer","label":"Rechtlicher Hinweis","fields":[
    {"name":"disclaimer","label":"Hinweis","type":"static","value":"Diese Dokumentation wurde mit Sonka Bau & Sonnenimmobilien - Multi Administration erstellt. Die Genehmigungsfähigkeit der erstellten Unterlagen kann durch diese Software nicht zugesichert werden. Alle Planunterlagen und Dokumente sind vor der Einreichung bei der zuständigen Behörde durch einen qualifizierten Fachmann zu prüfen."}
  ]}
]}', 1);
