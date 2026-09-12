<?php

/**
 * Sonka Bau & Sonnenimmobilien - Multi Administration – Installationsassistent
 * Läuft ohne config.php – prüft Servervoraussetzungen,
 * legt die Datenbank an und schreibt die Konfiguration.
 *
 * @license MIT
 */

declare(strict_types=1);

define('INSTALLER_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('MIN_PHP', '8.1.0');

// Redirect away if already installed.
// Exception: allow step=5 (the "Fertig" confirmation page) to render even after config.php exists,
// so the final step is visible without triggering a redirect loop.
// After the user leaves step 5, any access to /install will redirect to app root.
$currentStep = (int) ($_GET['step'] ?? 1);
if (file_exists(BASE_PATH . '/config.php') && $currentStep !== 5) {
    header('Location: /');
    exit;
}

session_start();

// ── Step logic ───────────────────────────────────────────────
$step    = $currentStep;
$errors  = [];
$success = [];

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process system check – just advance
    header('Location: install/?step=3');
    exit;
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save DB credentials to session and test
    $dbHost    = trim($_POST['db_host']    ?? 'localhost');
    $dbPort    = trim($_POST['db_port']    ?? '3306');
    $dbName    = trim($_POST['db_name']    ?? '');
    $dbUser    = trim($_POST['db_user']    ?? '');
    $dbPass    = $_POST['db_pass']         ?? '';
    $appUrl    = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $appKey    = trim($_POST['app_key']    ?? bin2hex(random_bytes(16)));

    if (empty($dbName) || empty($dbUser)) {
        $errors[] = 'Datenbankname und Benutzername sind erforderlich.';
    } else {
        // Test connection
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            // Try to create the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            $_SESSION['install_db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass', 'appUrl', 'appKey');
            header('Location: install/?step=4');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Datenbankverbindung fehlgeschlagen: ' . htmlspecialchars($e->getMessage());
        }
    }
}

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create tables + admin user
    $db = $_SESSION['install_db'] ?? null;
    if (!$db) {
        header('Location: install/?step=3');
        exit;
    }

    $adminName  = trim($_POST['admin_name']  ?? 'Administrator');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = $_POST['admin_pass']       ?? '';
    $adminPass2 = $_POST['admin_pass2']      ?? '';

    if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    }
    if ($adminPass !== $adminPass2) {
        $errors[] = 'Die Passwörter stimmen nicht überein.';
    }

    if (empty($errors)) {
        try {
            $dsn = "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['dbUser'], $db['dbPass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Execute schema
            $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
            $statements = parseSqlStatements($schema === false ? '' : $schema);
            foreach ($statements as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    // Ignore "already exists" errors during re-install
                    if (!str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            }

            // Create admin user
            $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare(
                "INSERT INTO users (role_id, name, email, password, is_active, email_verified_at)
                 VALUES (1, ?, ?, ?, 1, NOW())"
            );
            $stmt->execute([$adminName, $adminEmail, $hash]);

            // Grant all permissions to admin role
            $permStmt = $pdo->query("SELECT id FROM permissions");
            $rolePermStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
            foreach ($permStmt->fetchAll(PDO::FETCH_COLUMN) as $permId) {
                $rolePermStmt->execute([$permId]);
            }
            // Editor permissions
            $editorPerms = ['projects.view','projects.create','projects.edit','plans.view','plans.create','plans.edit','documents.view','documents.create','documents.edit','materials.view','symbols.view','api.access'];
            $epStmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
            $rpStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, ?)");
            foreach ($editorPerms as $perm) {
                $epStmt->execute([$perm]);
                $pid = $epStmt->fetchColumn();
                if ($pid) $rpStmt->execute([$pid]);
            }
            // Reviewer permissions
            $reviewPerms = ['projects.view','plans.view','documents.view','materials.view','symbols.view'];
            $rpStmt2 = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, ?)");
            foreach ($reviewPerms as $perm) {
                $epStmt->execute([$perm]);
                $pid = $epStmt->fetchColumn();
                if ($pid) $rpStmt2->execute([$pid]);
            }
            // Guest permissions
            $guestPerms = ['projects.view'];
            $rpStmt3 = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (4, ?)");
            foreach ($guestPerms as $perm) {
                $epStmt->execute([$perm]);
                $pid = $epStmt->fetchColumn();
                if ($pid) $rpStmt3->execute([$pid]);
            }

            $_SESSION['install_admin'] = compact('adminName', 'adminEmail');

            // Write config file
            $appKey = $db['appKey'];
            $configContent = <<<PHP
<?php
// Sonka Bau & Sonnenimmobilien - Multi Administration – Auto-generated configuration
// Generated: {$_SERVER['REQUEST_TIME_FLOAT']}

define('DB_HOST',     '{$db['dbHost']}');
define('DB_PORT',     '{$db['dbPort']}');
define('DB_NAME',     '{$db['dbName']}');
define('DB_USER',     '{$db['dbUser']}');
define('DB_PASS',     '{$db['dbPass']}');
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',     'Sonka Bau & Sonnenimmobilien - Multi Administration');
define('APP_URL',      '{$db['appUrl']}');
define('APP_KEY',      '{$appKey}');
define('APP_DEBUG',    false);
define('APP_TIMEZONE', 'Europe/Berlin');
define('APP_LOCALE',   'de_DE');
define('APP_VERSION',  '1.0.0');

define('SESSION_LIFETIME', 7200);
define('SESSION_NAME',     'RSA21_SESSION');

define('SMTP_HOST',      '');
define('SMTP_PORT',      587);
define('SMTP_USER',      '');
define('SMTP_PASS',      '');
define('SMTP_FROM',      '');
define('SMTP_FROM_NAME', 'Sonka Bau & Sonnenimmobilien - Multi Administration');
define('SMTP_ENCRYPTION','tls');

define('MAX_LOGIN_ATTEMPTS',  5);
define('LOGIN_LOCKOUT_TIME',  900);
define('PASSWORD_MIN_LENGTH', 8);
define('TWO_FACTOR_ENABLED',  false);
define('REGISTRATION_ENABLED', true);

define('UPLOAD_MAX_SIZE',    52428800);
define('UPLOAD_PATH',        BASE_PATH . '/dateien');
define('ALLOWED_EXTENSIONS', ['svg','png','jpg','jpeg','pdf','zip','dxf']);

define('LOG_LEVEL',    'info');
define('LOG_PATH',     BASE_PATH . '/infos');
define('BACKUP_PATH',  BASE_PATH . '/sicherungen');
define('STORAGE_PATH', BASE_PATH . '/speicher');

define('PDF_AUTHOR',   APP_NAME);
define('PDF_CREATOR',  APP_NAME . ' v' . APP_VERSION);
PHP;
            file_put_contents(BASE_PATH . '/config.php', $configContent);
            header('Location: install/?step=5');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Fehler beim Installieren: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Helpers ─────────────────────────────────────────────────

function checkPHP(): array
{
    $required = [
        'pdo'       => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json'      => extension_loaded('json'),
        'mbstring'  => extension_loaded('mbstring'),
        'openssl'   => extension_loaded('openssl'),
        'gd'        => extension_loaded('gd'),
        'zip'       => extension_loaded('zip'),
        'fileinfo'  => extension_loaded('fileinfo'),
    ];
    return $required;
}

function checkDirs(): array
{
    $paths = [
        'dateien/'    => BASE_PATH . '/dateien',
        'infos/'      => BASE_PATH . '/infos',
        'sicherungen/'    => BASE_PATH . '/sicherungen',
        'speicher/'    => BASE_PATH . '/speicher',
        './ (config)' => BASE_PATH,
    ];

    $dirs = [];
    foreach ($paths as $label => $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $dirs[$label] = is_writable($path);
    }
    return $dirs;
}

function stepClass(int $current, int $n): string
{
    if ($n < $current) return 'completed';
    if ($n === $current) return 'active';
    return '';
}

function parseSqlStatements(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;

    return array_values(array_filter(
        array_map('trim', explode(';', $sql)),
        static fn(string $statement): bool => $statement !== ''
    ));
}

$db       = $_SESSION['install_db']    ?? [];
$phpChecks = checkPHP();
$dirChecks = checkDirs();
$phpOk     = !in_array(false, $phpChecks, true);
$dirsOk    = !in_array(false, $dirChecks, true);
$phpVersion = version_compare(PHP_VERSION, MIN_PHP, '>=');

?><!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sonka Bau & Sonnenimmobilien - Multi Administration Installationsassistent</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --rsa-primary: #0d6efd; --rsa-glass: rgba(255,255,255,.06); }
  body { background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; }
  .installer-card { background: var(--rsa-glass); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,.12); border-radius: 1.5rem; }
  .step-indicator { display: flex; justify-content: center; gap: 0; }
  .step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
  .step:not(:last-child)::after { content: ''; position: absolute; top: 18px; left: 50%; width: 100%; height: 2px; background: rgba(255,255,255,.15); z-index: 0; }
  .step.completed:not(:last-child)::after { background: var(--rsa-primary); }
  .step-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: .85rem; z-index: 1; background: rgba(255,255,255,.1); border: 2px solid rgba(255,255,255,.2); }
  .step.active .step-icon { background: var(--rsa-primary); border-color: var(--rsa-primary); }
  .step.completed .step-icon { background: #198754; border-color: #198754; }
  .step-label { font-size: .7rem; margin-top: 4px; color: rgba(255,255,255,.6); }
  .step.active .step-label { color: #fff; }
  .step.completed .step-label { color: #198754; }
  .check-item { display: flex; justify-content: space-between; align-items: center; padding: .4rem 0; border-bottom: 1px solid rgba(255,255,255,.05); }
  .check-item:last-child { border-bottom: none; }
</style>
</head>
<body class="py-5">
<div class="container" style="max-width: 700px">

  <!-- Header -->
  <div class="text-center mb-4">
    <h1 class="h3 fw-bold text-white">
      <i class="bi bi-sign-turn-right-fill text-primary me-2"></i>Sonka Bau & Sonnenimmobilien - Multi Administration
    </h1>
    <p class="text-white-50">Installationsassistent v<?= INSTALLER_VERSION ?></p>
  </div>

  <!-- Step indicator -->
  <div class="step-indicator mb-4 px-3">
    <?php foreach ([1=>'Start',2=>'System',3=>'Datenbank',4=>'Admin',5=>'Fertig'] as $n=>$label): ?>
      <div class="step <?= stepClass($step, $n) ?>">
        <div class="step-icon">
          <?php if ($n < $step): ?><i class="bi bi-check text-white"></i>
          <?php else: ?><?= $n ?><?php endif; ?>
        </div>
        <span class="step-label"><?= $label ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Card -->
  <div class="installer-card p-4 text-white">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php /* ── STEP 1: Willkommen ────────────────────────────── */ if ($step === 1): ?>
      <h4 class="mb-3"><i class="bi bi-house-heart me-2"></i>Willkommen bei Sonka Bau & Sonnenimmobilien - Multi Administration</h4>
      <p>Dieser Assistent führt Sie durch die Installation der Anwendung. Bitte stellen Sie sicher, dass:</p>
      <ul>
        <li>Sie die Dateien per FTP hochgeladen haben</li>
        <li>Eine MySQL/MariaDB-Datenbank bereitsteht</li>
        <li>PHP <?= MIN_PHP ?>+ verfügbar ist</li>
      </ul>
      <div class="alert alert-warning">
        <i class="bi bi-shield-check me-1"></i>
        <strong>Hinweis:</strong> Diese Anwendung dient als Werkzeug zur Erstellung von Verkehrszeichenplänen.
        Erstellte Unterlagen ersetzen <strong>keine</strong> fachkundige Prüfung und sind vor der behördlichen
        Einreichung durch einen qualifizierten Fachmann zu prüfen.
      </div>
      <a href="install/?step=2" class="btn btn-primary">
        <i class="bi bi-arrow-right me-1"></i>Installation starten
      </a>

    <?php /* ── STEP 2: Systemprüfung ───────────────────────── */ elseif ($step === 2): ?>
      <h4 class="mb-3"><i class="bi bi-cpu me-2"></i>Systemprüfung</h4>
      <p class="text-white-50 small">PHP <?= PHP_VERSION ?> – Benötigt <?= MIN_PHP ?>+</p>

      <div class="mb-3">
        <div class="check-item">
          <span>PHP-Version (<?= MIN_PHP ?>+)</span>
          <?php if ($phpVersion): ?>
            <span class="badge bg-success"><i class="bi bi-check"></i> OK</span>
          <?php else: ?>
            <span class="badge bg-danger"><i class="bi bi-x"></i> Fehler: <?= PHP_VERSION ?></span>
          <?php endif; ?>
        </div>
        <?php foreach ($phpChecks as $ext => $ok): ?>
          <div class="check-item">
            <span>PHP-Extension: <?= $ext ?></span>
            <?php if ($ok): ?>
              <span class="badge bg-success"><i class="bi bi-check"></i> OK</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark"><i class="bi bi-exclamation"></i> Fehlt (optional)</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <h6>Schreibrechte</h6>
      <div class="mb-3">
        <?php foreach ($dirChecks as $dir => $ok): ?>
          <div class="check-item">
            <span><code><?= $dir ?></code></span>
            <?php if ($ok): ?>
              <span class="badge bg-success"><i class="bi bi-check"></i> Schreibbar</span>
            <?php else: ?>
              <span class="badge bg-danger"><i class="bi bi-x"></i> Nicht schreibbar</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!$phpVersion || !$phpOk): ?>
        <div class="alert alert-danger">Bitte beheben Sie die kritischen Fehler bevor Sie fortfahren.</div>
      <?php endif; ?>

      <form method="post">
        <button class="btn btn-primary" <?= (!$phpVersion) ? 'disabled' : '' ?>>
          <i class="bi bi-arrow-right me-1"></i>Weiter
        </button>
      </form>

    <?php /* ── STEP 3: Datenbank ─────────────────────────── */ elseif ($step === 3): ?>
      <h4 class="mb-3"><i class="bi bi-database me-2"></i>Datenbankverbindung</h4>
      <form method="post">
        <div class="row g-3">
          <div class="col-8">
            <label class="form-label">Datenbankhost</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" name="db_host" value="<?= htmlspecialchars($db['dbHost'] ?? 'localhost') ?>">
          </div>
          <div class="col-4">
            <label class="form-label">Port</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" name="db_port" value="<?= htmlspecialchars($db['dbPort'] ?? '3306') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Datenbankname</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" name="db_name" value="<?= htmlspecialchars($db['dbName'] ?? 'rsa21') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label">Datenbankbenutzer</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" name="db_user" value="<?= htmlspecialchars($db['dbUser'] ?? '') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label">Datenbankpasswort</label>
            <input type="password" class="form-control bg-dark text-white border-secondary" name="db_pass">
          </div>
          <div class="col-12">
            <label class="form-label">Anwendungs-URL</label>
            <input type="url" class="form-control bg-dark text-white border-secondary" name="app_url"
                   value="<?= htmlspecialchars($db['appUrl'] ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com')) ?>" required>
            <div class="form-text text-white-50">z.B. https://ihre-domain.de (ohne abschließenden Schrägstrich)</div>
          </div>
          <div class="col-12">
            <label class="form-label">Sicherheitsschlüssel (APP_KEY)</label>
            <div class="input-group">
              <input type="text" class="form-control bg-dark text-white border-secondary" name="app_key"
                     id="appKey" value="<?= htmlspecialchars($db['appKey'] ?? bin2hex(random_bytes(16))) ?>" required>
              <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('appKey').value = Array.from(crypto.getRandomValues(new Uint8Array(16))).map(b=>b.toString(16).padStart(2,'0')).join('')">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div class="form-text text-white-50">Mindestens 32 Zeichen – wird automatisch generiert</div>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>Verbinden & Weiter</button>
        </div>
      </form>

    <?php /* ── STEP 4: Admin-Konto ───────────────────────── */ elseif ($step === 4): ?>
      <h4 class="mb-3"><i class="bi bi-person-badge me-2"></i>Administrator-Konto erstellen</h4>
      <form method="post">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Name</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" name="admin_name" value="Administrator" required>
          </div>
          <div class="col-12">
            <label class="form-label">E-Mail-Adresse</label>
            <input type="email" class="form-control bg-dark text-white border-secondary" name="admin_email" required>
          </div>
          <div class="col-6">
            <label class="form-label">Passwort</label>
            <input type="password" class="form-control bg-dark text-white border-secondary" name="admin_pass" minlength="8" required>
          </div>
          <div class="col-6">
            <label class="form-label">Passwort wiederholen</label>
            <input type="password" class="form-control bg-dark text-white border-secondary" name="admin_pass2" minlength="8" required>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Installation abschließen</button>
        </div>
      </form>

    <?php /* ── STEP 5: Fertig ─────────────────────────────── */ elseif ($step === 5): ?>
      <div class="text-center py-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
        <h4 class="mt-3">Installation erfolgreich!</h4>
        <p class="text-white-50">
          Sonka Bau & Sonnenimmobilien - Multi Administration wurde erfolgreich installiert.<br>
          Sie können sich nun mit Ihrem Administrator-Konto anmelden.
        </p>
        <div class="alert alert-warning text-start">
          <i class="bi bi-shield-exclamation me-1"></i>
          <strong>Sicherheitshinweis:</strong> Bitte löschen oder benennen Sie den
          <code>install/</code> Ordner um, um unbefugten Zugriff zu verhindern.
        </div>
        <?php
            // Auto-delete installer? Better to warn user.
        ?>
        <a href="/" class="btn btn-primary btn-lg mt-2">
          <i class="bi bi-box-arrow-in-right me-1"></i>Zur Anwendung
        </a>
      </div>
    <?php endif; ?>

  </div><!-- /.installer-card -->

  <p class="text-center text-white-50 small mt-3">
    Sonka Bau & Sonnenimmobilien - Multi Administration ist Open Source und wird unter der MIT-Lizenz veröffentlicht.
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
