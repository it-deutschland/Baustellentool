<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Mail sending helper.
 */
class Mailer
{
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $smtpHost = defined('SMTP_HOST') ? (string) SMTP_HOST : '';

        if ($smtpHost !== '' && class_exists(PHPMailer::class)) {
            return self::sendViaSmtp($to, $subject, $body, $isHtml);
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        ];

        $from = defined('SMTP_FROM') ? (string) SMTP_FROM : '';
        if ($from !== '') {
            $fromName = defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : $from;
            $headers[] = sprintf('From: %s <%s>', $fromName, $from);
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private static function sendViaSmtp(string $to, string $subject, string $body, bool $isHtml): bool
    {
        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = (string) SMTP_HOST;
            $mailer->Port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
            $mailer->SMTPAuth = (defined('SMTP_USER') && (string) SMTP_USER !== '');
            $mailer->Username = defined('SMTP_USER') ? (string) SMTP_USER : '';
            $mailer->Password = defined('SMTP_PASS') ? (string) SMTP_PASS : '';
            $mailer->SMTPSecure = defined('SMTP_ENCRYPTION') ? (string) SMTP_ENCRYPTION : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(
                defined('SMTP_FROM') ? (string) SMTP_FROM : (string) SMTP_USER,
                defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : 'Sonka Bau & Sonnenimmobilien - Multi Administration'
            );
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->isHTML($isHtml);
            $mailer->Body = $body;

            return $mailer->send();
        } catch (PHPMailerException|Throwable $exception) {
            Logger::error('Mail delivery failed.', ['exception' => $exception->getMessage(), 'recipient' => $to]);

            return false;
        }
    }
}
