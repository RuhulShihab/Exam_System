<?php
/**
 * Minimal mail helper. Uses PHP's built-in mail() function, which only
 * actually delivers mail if the server has sendmail/postfix (or similar)
 * configured. Many small/shared hosts don't have this set up.
 *
 * Because of that, every caller of this function MUST treat a `false`
 * return as normal (not an error to show the user) and fall back to
 * displaying the link/token on screen instead, so the feature still works
 * during local development or on hosts without outbound mail.
 */
function send_app_mail(string $to, string $subject, string $bodyHtml): bool
{
    if (!function_exists('mail') || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = $GLOBALS['pdo'] instanceof PDO
        ? get_setting($GLOBALS['pdo'], 'site_name', 'Exam Center')
        : 'Exam Center';

    $fromHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $siteName . ' <no-reply@' . $fromHost . ">\r\n";

    return @mail($to, $subject, $bodyHtml, $headers);
}
