<?php
/**
 * Ajustes basicos del plugin.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function () {
    register_setting('webnova_core_settings', 'webnova_core_options', [
        'type' => 'array',
        'sanitize_callback' => function ($value): array {
            $value = is_array($value) ? $value : [];

            return [
                'whatsapp_phone' => sanitize_text_field($value['whatsapp_phone'] ?? ''),
                'email' => sanitize_email($value['email'] ?? ''),
            ];
        },
        'default' => [],
    ]);
});
