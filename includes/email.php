<?php
// includes/email.php

/**
 * Send HTML Email with standard MIME headers from support@reyonglobal.com
 */
function sendEmpressHtmlEmail($toEmail, $subject, $htmlContent) {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromEmail = 'support@reyonglobal.com';
    $fromName = 'Reyon Global Impact';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "X-Mailer: ReyonGlobal/PHP" . phpversion() . "\r\n";

    // Standard HTML email wrapper template with Obsidian & Gold theme
    $wrappedHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #07080c; color: #f3e5ab; margin: 0; padding: 20px; }
            .email-container { max-width: 600px; margin: 0 auto; background-color: #0f1117; border: 1px solid #c5a059; border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            .header { text-align: center; border-bottom: 1px solid rgba(197, 160, 89, 0.3); padding-bottom: 20px; margin-bottom: 20px; }
            .logo-text { font-family: "Cinzel", Georgia, serif; font-size: 22px; font-weight: bold; color: #c5a059; text-transform: uppercase; letter-spacing: 2px; }
            .tagline { font-size: 12px; color: #a0a5b5; margin-top: 5px; }
            .content { font-size: 14px; line-height: 1.6; color: #e2e8f0; }
            .highlight { color: #c5a059; font-weight: bold; }
            .btn { display: inline-block; background: linear-gradient(135deg, #d4b062, #a38140); color: #07080c; text-decoration: none; font-weight: bold; padding: 12px 24px; border-radius: 8px; margin-top: 20px; }
            .footer { border-top: 1px solid rgba(197, 160, 89, 0.2); margin-top: 30px; padding-top: 20px; text-align: center; font-size: 11px; color: #718096; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <div class="logo-text">REYON GLOBAL IMPACT</div>
                <div class="tagline">Double Your Path, Empower Your Future</div>
            </div>
            <div class="content">
                ' . $htmlContent . '
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Reyon Global Impact. All Rights Reserved.<br>
                Official Support: <a href="mailto:support@reyonglobal.com" style="color: #c5a059;">support@reyonglobal.com</a>
            </div>
        </div>
    </body>
    </html>';

    // Suppress system sendmail output if MTA binary is unconfigured
    return @mail($toEmail, $subject, $wrappedHtml, $headers);
}

/**
 * Replace {{field}} dynamic variables in template with member record array
 */
function renderEmailTemplate($templateContent, array $memberData) {
    foreach ($memberData as $key => $val) {
        if (is_array($val) || is_object($val)) continue;
        $tag = '{{' . strtolower(trim($key)) . '}}';
        $templateContent = str_replace($tag, htmlspecialchars((string)$val), $templateContent);
        // Also support uppercase tag variations e.g. {{MEMBER_ID}}
        $tagUpper = '{{' . strtoupper(trim($key)) . '}}';
        $templateContent = str_replace($tagUpper, htmlspecialchars((string)$val), $templateContent);
    }

    // Default system replacements
    $templateContent = str_replace('{{year}}', date('Y'), $templateContent);
    $templateContent = str_replace('{{site_url}}', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'reyonglobal.com'), $templateContent);

    return $templateContent;
}
