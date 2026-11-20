<?php

namespace App\Templates;

class TemplatesContactConfirmation
{
    /**
     * Generates a static HTML success page with a manual close button.
     *
     * @return string Full HTML page content.
     */
    public static function successConfirmationPage(array $data): string
    {
        $language = $data["language"];
        return <<<HTML
<!DOCTYPE html>
<html lang='$language' style='height: 100%;'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Confirmed | GreatApart</title>
</head>
<body style='margin: 0; padding: 20px; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; box-sizing: border-box;'>
    <div style='width: 100%; max-width: 400px; margin: 0 auto; padding: 40px 30px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; color: #0f172a; text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02), 0 8px 10px -6px rgba(0,0,0,0.02); box-sizing: border-box;'>
        <div style='font-size: 54px; color: #16a34a; margin-bottom: 16px; line-height: 1;'>✓</div>
        <h2 style='color: #0f172a; text-align: center; margin: 0 0 8px 0; font-size: 24px; font-weight: 700; letter-spacing: -0.025em;' data-translate='templates.templatesContactConfirmation.successConfirmationPage.heading'></h2>
        <p style='font-size: 13.5px; line-height: 1.5; color: #64748b; margin: 0 0 28px 0; padding: 0 10px;' data-translate='templates.templatesContactConfirmation.successConfirmationPage.description'></p>
        <div style='text-align: center;'>
            <a href='#' onclick='window.close(); return false;' style='display: inline-block; width: 100%; padding: 12px 0; font-size: 14px; font-weight: 600; color: #2563eb; background-color: #eff6ff; text-decoration: none; border: 1px solid #bfdbfe; border-radius: 10px; box-sizing: border-box;'>OK</a>
        </div>
    </div>
</body>
</html>
HTML;
    }



    /**
     * Generates the HTML body for the verification email.
     *
     * @param string $url The unique verification link.
     * @return string Full HTML template for PHPMailer.
     */
    public static function emailConfirmationRequest(array $data): string
    {
        $url = $data["url"];
        $domain = $data["domain"];
        return <<<HTML
<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' height='100%' bgcolor='#f8fafc' style='width: 100%; height: 100%; border-collapse: collapse;'>
    <tr>
        <td align='center' valign='middle' style='padding: 30px 10px; vertical-align: middle;'>
            <div style='width: 100%; max-width: 440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; padding: 35px 25px; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); color: #0f172a; background-color: #ffffff; text-align: left; box-sizing: border-box;'>
                <h2 style='color: #0f172a; text-align: center; margin: 0 0 15px 0; font-size: 22px; font-weight: 700; letter-spacing: -0.025em;' data-translate='templates.templatesContactConfirmation.emailConfirmationRequest.title'></h2>                        
                <p style='font-size: 14px; line-height: 1.5; color: #64748b; margin: 0 0 25px 0; text-align: center; padding: 0 10px;' data-translate='["templates.templatesContactConfirmation.emailConfirmationRequest.description","{$domain}"]'></p>            
                <div style='text-align: center; margin-bottom: 25px;'>
                    <a href='{$url}' style='display: inline-block; width: 100%; background-color: #eff6ff; color: #2563eb; padding: 12px 0; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #bfdbfe; border-radius: 10px; box-sizing: border-box;' data-translate='templates.templatesContactConfirmation.emailConfirmationRequest.button_text'></a>
                </div>                        
                <p style='font-size: 12px; color: #94a3b8; line-height: 1.45; margin: 0 0 20px 0; text-align: center;' data-translate='["templates.templatesContactConfirmation.emailConfirmationRequest.warning","1"]'></p>                    
                <div style='margin: 20px 0; text-align: center; border-bottom: 1px solid #f1f5f9;'></div>                        
                <p style='font-size: 11px; color: #94a3b8; line-height: 1.4; text-align: center; margin: 0;'>
                    <span data-translate='templates.templatesContactConfirmation.emailConfirmationRequest.alternative_link'></span><br style='margin-bottom: 4px;'>
                    <a href='{$url}' style='color: #2563eb; text-decoration: none; word-break: break-all; font-weight: 500;'>{$url}</a>
                </p>
            </div>
        </td>
    </tr>
</table>
HTML;
    }


    public static function formaVerification(array $data): string
    {
        return <<<HTML
<table id='verification-table' role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' height='100%' bgcolor='#f8fafc' style='min-height: 100vh; width: 100%; border-collapse: collapse;'>
    <tr>
        <td align='center' valign='middle' style='padding: 30px 10px; vertical-align: middle;'>
            <div style='width: 380px; max-width: 380px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; padding: 35px 25px; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02), 0 8px 10px -6px rgba(0,0,0,0.02); color: #0f172a; background-color: #ffffff; text-align: center; box-sizing: border-box;'>
                <h2 style='color: #0f172a; margin: 0 0 4px 0; font-size: 24px; font-weight: 700; letter-spacing: -0.025em;'>GreatApart</h2>
                <p style='color: #64748b; font-size: 13px; line-height: 1.45; margin: 0 0 20px 0; padding: 0 8px;' data-translate='templates.TemplatesContactConfirmation.formaVerification.emailInstruction'></p>
                <div style='margin-bottom: 22px; text-align: left;'>
                    <div style='margin-bottom: 10px;'>
                        <input type='email' id='email' name='email' required placeholder='example@mail.com' style='width: 100%; padding: 12px 14px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; outline: none; font-family: inherit;'>
                    </div>
                    <div style='display: flex; align-items: center; justify-content: space-between;'>
                        <button id='emailButton' style='display: inline-block; flex-grow: 1; background-color: #eff6ff; color: #2563eb; padding: 12px 0; font-size: 14px; font-weight: 600; border: 1px solid #bfdbfe; border-radius: 10px; cursor: pointer; font-family: inherit; box-sizing: border-box;' data-translate='templates.TemplatesContactConfirmation.formaVerification.buttonEmailLink'></button>
                        <span id='emailChecked' style='display: none; font-size: 18px; color: #2563eb; font-weight: bold; margin-left: 12px; user-select: none;'>✓</span>
                    </div>
                </div>
                <div style='margin: 22px 0; text-align: center; border-bottom: 1px solid #f1f5f9; line-height: 0.1em;'><span style='background: #ffffff; padding: 0 10px; font-size: 11px; color: #94a3b8; font-weight: 600; letter-spacing: 0.05em;' data-translate='templates.TemplatesContactConfirmation.formaVerification.dividerText'></span></div>
                <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;'>
                    <a id='telegram' href='' target='_blank' style='display: inline-block; flex-grow: 1; background-color: #f0fdf4; color: #16a34a; padding: 12px 0; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 10px; border: 1px solid #bbf7d0; box-sizing: border-box;'>
                        🟢 <span data-translate='templates.TemplatesContactConfirmation.formaVerification.buttonTelegram'></span>
                    </a>
                    <span id='telegramChecked' style='display: none; font-size: 18px; color: #16a34a; font-weight: bold; margin-left: 12px; user-select: none;'>✓</span>
                </div>
                <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px;'>
                    <a id='viber' href='' target='_blank' style='display: inline-block; flex-grow: 1; background-color: #f5f3ff; color: #7c3aed; padding: 12px 0; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 10px; border: 1px solid #ddd6fe; box-sizing: border-box;'>
                        🔮 <span data-translate='templates.TemplatesContactConfirmation.formaVerification.buttonViber'></span>
                    </a>
                    <span id='viberChecked' style='display: none; font-size: 18px; color: #7c3aed; font-weight: bold; margin-left: 12px; user-select: none;'>✓</span>
                </div>
                <div style='margin-top: 32px; padding-top: 18px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8; line-height: 1.4;'>
                    <p style='margin: 0 0 2px 0; font-weight: 600; color: #475569;'>GreatApart Management</p>
                </div>
            </div>
        </td>
    </tr>
</table>
<script src="/js/common/UserRegistration.js" type="module" async></script>
HTML;
    }
}
