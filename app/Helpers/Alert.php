<?php

class Alert
{
    private const ICONS = ['success', 'error', 'warning', 'info', 'question'];
    private const VARIANTS = ['default', 'withCopy'];
    private const SESSION_KEY = 'alerts';

    /**
     * Queues a flash alert to be rendered during the next request.
     *
     * The alert is stored in the session and removed automatically
     * after it is rendered.
     *
     * @param string $key     Unique identifier for the alert.
     * @param string $message Message text to be displayed.
     * @param string $icon    SweetAlert icon type (success, error, warning, info, question).
     * @param string $variant Alert rendering variant (default, withCopy).
     *
     * @return void
     */
    public static function flash(string $key, string $message, string $icon = 'success', string $variant = 'default'): void
    {
        if (empty($key) || empty($message)) return;

        $icon = in_array($icon, self::ICONS, true) ? $icon : 'success';
        $variant = in_array($variant, self::VARIANTS, true) ? $variant : 'default';
        $message = strip_tags($message, '<br><b><strong><i><em>');

        $_SESSION[APP_SESSION_KEY][self::SESSION_KEY] ??= [];
        $_SESSION[APP_SESSION_KEY][self::SESSION_KEY][$key] = [
            'message' => $message,
            'icon' => $icon,
            'variant' => $variant,
        ];
    }

    /**
     * Outputs and consumes a previously stored alert, rendering it as a SweetAlert popup.
     *
     * @param string $key Unique identifier for the alert to display.
     *
     * @return void
     */
    public static function render(string $key): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        if (empty($_SESSION[APP_SESSION_KEY][self::SESSION_KEY][$key])) return;

        $alert = $_SESSION[APP_SESSION_KEY][self::SESSION_KEY][$key];
        unset($_SESSION[APP_SESSION_KEY][self::SESSION_KEY][$key]);

        switch ($alert['variant']) {
            case 'withCopy':
                $variantScript = '
                    confirmButtonText: "Ok, copiar",
                    confirmButtonColor: "#00194F",
                }).then((result) => {
                    if (result.isConfirmed) navigator.clipboard.writeText(' . json_encode(strip_tags($alert['message']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ') 
                ';
                break;
            default:
                $variantScript = '
                    confirmButtonText: "OK",
                    confirmButtonColor: "#00194F"
                ';
        }

        echo '
            <script nonce="' . htmlspecialchars($GLOBALS['csp_nonce'] ?? '') . '">
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        html: ' . json_encode($alert['message'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ',
                        icon: ' . json_encode($alert['icon']) . ',
                        ' . $variantScript . '
                    });
                });
            </script>';
    }
}
