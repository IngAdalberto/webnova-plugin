<?php
/**
 * Aplicador de configuraciones basicas de plantilla.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Settings_Applier
{
    public function apply(array $settings, array $page_ids): array
    {
        $applied = [];

        if (! empty($settings['site_title'])) {
            update_option('blogname', sanitize_text_field((string) $settings['site_title']));
            $applied[] = __('Titulo del sitio', 'webnova-starter-kit');
        }

        if (! empty($settings['front_page'])) {
            $slug = sanitize_title((string) $settings['front_page']);

            if (! empty($page_ids[$slug])) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', (int) $page_ids[$slug]);
                $applied[] = __('Pagina de inicio', 'webnova-starter-kit');
            }
        }

        foreach ((array) ($settings['colors'] ?? []) as $key => $value) {
            $color_key = sanitize_key((string) $key);
            $option = 'webnova_' . $color_key . '_color';
            $color = sanitize_hex_color((string) $value) ?: '';

            update_option($option, $color);
            $applied[] = $option;

            if ($color_key === 'accent' || $color_key === 'secondary') {
                update_option('webnova_accent_color', $color);
                $applied[] = 'webnova_accent_color';
            }
        }

        foreach ((array) ($settings['typography'] ?? []) as $key => $value) {
            $option = 'webnova_' . sanitize_key((string) $key) . '_font';
            update_option($option, sanitize_text_field((string) $value));
            $applied[] = $option;
        }

        if (! empty($settings['whatsapp_phone']) || ! empty($settings['email'])) {
            $options = get_option('webnova_core_options', []);
            $options = is_array($options) ? $options : [];
            $options['whatsapp_phone'] = sanitize_text_field((string) ($settings['whatsapp_phone'] ?? ''));
            $options['email'] = sanitize_email((string) ($settings['email'] ?? ''));

            if (! empty($settings['layout_width'])) {
                set_theme_mod('webnova_layout_width', webnova_sanitize_layout_width((string) $settings['layout_width']));
                $applied[] = __('Ancho del sitio', 'webnova-starter-kit');
            }

            update_option('webnova_core_options', $options);
            $applied[] = __('Datos de contacto demo', 'webnova-starter-kit');
        } elseif (! empty($settings['layout_width'])) {
            set_theme_mod('webnova_layout_width', webnova_sanitize_layout_width((string) $settings['layout_width']));
            $applied[] = __('Ancho del sitio', 'webnova-starter-kit');
        }

        return $applied;
    }
}
