<?php
/**
 * Mailer
 * Sends emails using PHPMailer (SMTP) with fallback to PHP mail()
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('mailer_send')) {
    function mailer_send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
    {
        $config = $GLOBALS['config']['mail'] ?? [];

        // Try PHPMailer first if available and configured
        $phpMailerPath = __DIR__ . '/../vendor/autoload.php';
        if (($config['use_smtp'] ?? false) && file_exists($phpMailerPath)) {
            require_once $phpMailerPath;

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $config['host'] ?? '';
                $mail->Port = $config['port'] ?? 587;
                $mail->SMTPAuth = !empty($config['username']);
                $mail->Username = $config['username'] ?? '';
                $mail->Password = $config['password'] ?? '';
                $encryption = $config['encryption'] ?? '';
                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                }
                $mail->CharSet = 'UTF-8';

                $mail->setFrom($config['from_email'] ?? 'noreply@localhost', $config['from_name'] ?? 'Lectura');
                $mail->addAddress($toEmail, $toName);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody ?: strip_tags($htmlBody);
                $mail->isHTML(true);

                $mail->send();
                return ['success' => true, 'method' => 'smtp'];
            } catch (Exception $e) {
                error_log('PHPMailer error: ' . $e->getMessage());
                // Fall through to mail() fallback
            }
        }

        // Fallback to PHP mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($config['from_name'] ?? 'Lectura') . ' <' . ($config['from_email'] ?? 'noreply@localhost') . '>',
            'Reply-To: ' . ($config['from_email'] ?? 'noreply@localhost'),
        ];

        $result = mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));

        if ($result) {
            return ['success' => true, 'method' => 'mail'];
        }

        return ['success' => false, 'error' => 'mail() returned false'];
    }
}

// Send notification email for new item
if (!function_exists('mailer_send_item_notification')) {
    function mailer_send_item_notification(array $user, array $item, array $course, string $itemUrl): array
    {
        $lang = $user['lang'] ?? 'ar';
        $typeLabel = item_type_label($item['type'], $lang);
        $safeTitle = e($item['title']);
        $safeCourse = e($course['name']);
        $safeDescription = !empty($item['description']) ? nl2br(e($item['description'])) : '';
        $dueText = !empty($item['due_at']) ? format_date(utc_datetime($item['due_at']), 'medium', $lang) : '';
        $settingsUrl = base_url('settings.php');
        $subjectTitle = str_replace(["\r", "\n"], ' ', (string)$item['title']);

        if ($lang === 'ar') {
            $subject = "📚 {$typeLabel} جديد: {$subjectTitle}";
            $body = '';
            if ($safeDescription !== '') {
                $body .= "<p style='color: #1B1B2F;'>{$safeDescription}</p>";
            }
            if ($dueText !== '') {
                $body .= "<p style='color: #D97706;'><strong>موعد التسليم:</strong> {$dueText}</p>";
            }
            $html = <<<HTML
                <div style=\"font-family: 'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif; direction: rtl; max-width: 600px; margin: 0 auto;\">
                    <div style="background: #12355B; color: #ffffff; padding: 24px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px;">Lectura</h1>
                    </div>
                    <div style="padding: 24px; background: #fafafa;">
                        <h2 style="color: #1B1B2F; margin-top: 0;">{$safeTitle}</h2>
                        <p style="color: #6B7085;">تمت إضافة محتوى جديد (<strong>{$typeLabel}</strong>) في مادة <strong>{$safeCourse}</strong>.</p>
                        {$body}
                        <p style="margin-top: 32px; text-align: center;">
                            <a href="{$itemUrl}" style="background: #12355B; color: #ffffff; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;">
                                عرض على Lectura
                            </a>
                        </p>
                    </div>
                    <div style="background: #f0f0f0; padding: 16px; text-align: center; color: #6B7085; font-size: 12px;">
                        تلقيت هذه الرسالة لأنك مفعّل إشعارات البريد الإلكتروني.
                        <br><a href="{$settingsUrl}" style="color: #12355B;">إدارة الإشعارات</a>
                    </div>
                </div>
            HTML;
        } else {
            $subject = "📚 New {$typeLabel}: {$subjectTitle}";
            $body = '';
            if ($safeDescription !== '') {
                $body .= "<p style='color: #1B1B2F;'>{$safeDescription}</p>";
            }
            if ($dueText !== '') {
                $body .= "<p style='color: #D97706;'><strong>Due:</strong> {$dueText}</p>";
            }
            $html = <<<HTML
                <div style=\"font-family: Inter, 'Segoe UI', Arial, sans-serif; direction: ltr; max-width: 600px; margin: 0 auto;\">
                    <div style="background: #12355B; color: #ffffff; padding: 24px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px;">Lectura</h1>
                    </div>
                    <div style="padding: 24px; background: #fafafa;">
                        <h2 style="color: #1B1B2F; margin-top: 0;">{$safeTitle}</h2>
                        <p style="color: #6B7085;">A new <strong>{$typeLabel}</strong> has been uploaded in <strong>{$safeCourse}</strong>.</p>
                        {$body}
                        <p style="margin-top: 32px; text-align: center;">
                            <a href="{$itemUrl}" style="background: #12355B; color: #ffffff; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;">
                                View on Lectura
                            </a>
                        </p>
                    </div>
                    <div style="background: #f0f0f0; padding: 16px; text-align: center; color: #6B7085; font-size: 12px;">
                        You received this because you have email notifications enabled.
                        <br><a href="{$settingsUrl}" style="color: #12355B;">Manage notifications</a>
                    </div>
                </div>
            HTML;
        }

        return mailer_send($user['email'], $user['name'], $subject, $html);
    }
}

// Send notification email for announcement
if (!function_exists('mailer_send_announcement_notification')) {
    function mailer_send_announcement_notification(array $user, array $announcement, string $siteUrl): array
    {
        $lang = $user['lang'] ?? 'ar';
        $safeTitle = e($announcement['title']);
        $safeBody = e($announcement['body']);
        $settingsUrl = base_url('settings.php');
        $subjectTitle = str_replace(["\r", "\n"], ' ', (string)$announcement['title']);

        if ($lang === 'ar') {
            $subject = "📢 إعلان جديد: {$subjectTitle}";
            $html = "
                <div style='font-family: Tahoma, Arial, sans-serif; direction: rtl; max-width: 600px; margin: 0 auto;'>
                    <div style='background: #F59E0B; color: white; padding: 24px; text-align: center;'>
                        <h1 style='margin: 0; font-size: 24px;'>Lectura</h1>
                    </div>
                    <div style='padding: 24px; background: #fafafa;'>
                        <h2 style='color: #1B1B2F; margin-top: 0;'>{$safeTitle}</h2>
                        <div style='color: #1B1B2F; line-height: 1.8; white-space: pre-wrap;'>{$safeBody}</div>
                        <p style='margin-top: 32px; text-align: center;'>
                            <a href='{$siteUrl}' style='background: #F59E0B; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;'>
                                عرض على Lectura
                            </a>
                        </p>
                    </div>
                    <div style='background: #f0f0f0; padding: 16px; text-align: center; color: #6B7085; font-size: 12px;'>
                        تلقيت هذه الرسالة لأنك مفعّل إشعارات البريد الإلكتروني.
                        <br><a href='{$settingsUrl}' style='color: #12355B;'>إدارة الإشعارات</a>
                    </div>
                </div>
            ";
        } else {
            $subject = "📢 New Announcement: {$subjectTitle}";
            $html = "
                <div style='font-family: Inter, Arial, sans-serif; direction: ltr; max-width: 600px; margin: 0 auto;'>
                    <div style='background: #F59E0B; color: white; padding: 24px; text-align: center;'>
                        <h1 style='margin: 0; font-size: 24px;'>Lectura</h1>
                    </div>
                    <div style='padding: 24px; background: #fafafa;'>
                        <h2 style='color: #1B1B2F; margin-top: 0;'>{$safeTitle}</h2>
                        <div style='color: #1B1B2F; line-height: 1.8; white-space: pre-wrap;'>{$safeBody}</div>
                        <p style='margin-top: 32px; text-align: center;'>
                            <a href='{$siteUrl}' style='background: #F59E0B; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;'>
                                View on Lectura
                            </a>
                        </p>
                    </div>
                    <div style='background: #f0f0f0; padding: 16px; text-align: center; color: #6B7085; font-size: 12px;'>
                        You received this because you have email notifications enabled.
                        <br><a href='{$settingsUrl}' style='color: #12355B;'>Manage notifications</a>
                    </div>
                </div>
            ";
        }

        return mailer_send($user['email'], $user['name'], $subject, $html);
    }
}
