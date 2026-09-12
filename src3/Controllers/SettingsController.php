<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;

/**
 * Manages application settings.
 */
class SettingsController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');

        $this->render('settings/index', ['settings' => $this->groupedSettings()]);
    }

    public function update(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');
        $this->ensureCsrf();

        $settings = $this->db()->fetchAll('SELECT * FROM `settings`');
        $map = [];
        foreach ($settings as $setting) {
            $map[(string) $setting['key']] = $setting;
        }

        foreach (Request::all() as $key => $value) {
            if (in_array($key, ['_token'], true) || !isset($map[$key])) {
                continue;
            }

            $this->db()->table('settings')->where('key', $key)->update([
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $logo = Request::file('company_logo_file');
        if ($logo !== null && (int) ($logo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $storedLogo = $this->storeUploadedFile($logo, 'settings', ['png', 'jpg', 'jpeg', 'svg', 'webp']);
            $this->upsertSettingValue('company_logo', (string) $storedLogo['file_path']);
        }

        $pdfLogo = Request::file('company_logo_pdf_file');
        if ($pdfLogo !== null && (int) ($pdfLogo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $storedPdfLogo = $this->storeUploadedFile($pdfLogo, 'settings', ['png', 'jpg', 'jpeg', 'svg', 'pdf']);
            $this->upsertSettingValue('company_logo_pdf', (string) $storedPdfLogo['file_path']);
        }

        $this->recordLog('settings_updated', 'settings');
        Session::flash('success', 'Settings updated successfully.');
        $this->redirect('/settings');
    }

    public function testSmtp(): void
    {
        $this->requireAuth();
        $this->enforceRole('admin');

        $email = trim((string) Request::get('email', ''));
        if ($email === '') {
            $email = (string) ($this->loadUserById((int) Auth::id())['email'] ?? '');
        }

        if ($email === '') {
            $this->json(['message' => 'No test recipient available.'], 422);
        }

        $success = Mailer::send($email, 'Sonka Bau & Sonnenimmobilien - Multi Administration SMTP test', '<p>This is a test email from Sonka Bau & Sonnenimmobilien - Multi Administration.</p>', true);
        if ($success) {
            $this->recordLog('smtp_test_sent', 'settings', null, null, [], ['recipient' => $email]);
            $this->json(['message' => 'Test email sent successfully.']);
        }

        $this->json(['message' => 'Unable to send the test email.'], 500);
    }

    private function upsertSettingValue(string $key, string $value): void
    {
        $existing = $this->db()->table('settings')->where('key', $key)->first();
        if ($existing !== null) {
            $this->db()->table('settings')->where('key', $key)->update([
                'value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
