<?php
/**
 * Shortcodes base.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

add_shortcode('webnova_whatsapp', function (array $atts): string {
    $atts = shortcode_atts([
        'label' => __('Contactar por WhatsApp', 'webnova-starter-kit'),
        'message' => __('Hola, quiero mas informacion.', 'webnova-starter-kit'),
    ], $atts, 'webnova_whatsapp');

    $phone = (string) webnova_get_option('whatsapp_phone', '');

    if ($phone === '') {
        return '';
    }

    $url = sprintf(
        'https://wa.me/%s?text=%s',
        rawurlencode(preg_replace('/\D+/', '', $phone)),
        rawurlencode((string) $atts['message'])
    );

    return sprintf(
        '<a class="wn-button" href="%s" target="_blank" rel="noopener">%s</a>',
        esc_url($url),
        esc_html((string) $atts['label'])
    );
});
