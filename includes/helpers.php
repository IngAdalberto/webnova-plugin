<?php
/**
 * Helpers compartidos del plugin WebNova Starter Kit.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

function webnova_get_option(string $key, mixed $default = ''): mixed
{
    $options = get_option('webnova_core_options', []);

    if (! is_array($options)) {
        return $default;
    }

    return $options[$key] ?? $default;
}

function webnova_get_layout_width_presets(): array
{
    if (function_exists('webnova_theme_get_layout_width_presets')) {
        return webnova_theme_get_layout_width_presets();
    }

    return [
        'full' => [
            'label' => __('Ancho completo', 'webnova-starter-kit'),
            'description' => __('Las secciones ocupan todo el ancho disponible.', 'webnova-starter-kit'),
            'site_width' => '100%',
            'site_gutter' => '0px',
        ],
        'wide' => [
            'label' => __('Amplio con margenes', 'webnova-starter-kit'),
            'description' => __('Mantiene una presencia amplia, con aire lateral en pantallas grandes.', 'webnova-starter-kit'),
            'site_width' => '1440px',
            'site_gutter' => '32px',
        ],
        'boxed' => [
            'label' => __('Contenido centrado', 'webnova-starter-kit'),
            'description' => __('Usa un ancho mas contenido para sitios sobrios o corporativos.', 'webnova-starter-kit'),
            'site_width' => '1180px',
            'site_gutter' => '32px',
        ],
    ];
}

function webnova_sanitize_layout_width(string $value): string
{
    if (function_exists('webnova_theme_sanitize_layout_width')) {
        return webnova_theme_sanitize_layout_width($value);
    }

    $value = sanitize_key($value);
    $presets = webnova_get_layout_width_presets();

    return isset($presets[$value]) ? $value : 'full';
}

function webnova_get_default_palette(): array
{
    $palette_path = WEBNOVA_STARTER_KIT_PATH . 'config/palette.php';
    $palette = file_exists($palette_path) ? require $palette_path : [];

    return is_array($palette) ? $palette : [];
}

function webnova_get_color_palette(): array
{
    $palette = webnova_get_default_palette();
    $aliases = [
        'secondary' => 'accent',
        'muted' => 'text_muted',
    ];

    foreach ($palette as $key => $value) {
        $option = get_option('webnova_' . sanitize_key((string) $key) . '_color', '');
        $color = sanitize_hex_color((string) $option);

        if ($color) {
            $palette[$key] = $color;
        }
    }

    foreach ($aliases as $option_key => $palette_key) {
        $option = get_option('webnova_' . sanitize_key($option_key) . '_color', '');
        $color = sanitize_hex_color((string) $option);

        if ($color) {
            $palette[$palette_key] = $color;
        }
    }

    return $palette;
}

function webnova_hex_to_rgb(string $hex): string
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
        return '0 0 0';
    }

    return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
}

function webnova_get_palette_css(): string
{
    $palette = webnova_get_color_palette();
    $variables = [];

    foreach ($palette as $key => $value) {
        $color = sanitize_hex_color((string) $value);

        if (! $color) {
            continue;
        }

        $css_key = str_replace('_', '-', sanitize_key((string) $key));
        $variables[] = '--wn-color-' . $css_key . ': ' . $color . ';';
        $variables[] = '--wn-color-' . $css_key . '-rgb: ' . webnova_hex_to_rgb($color) . ';';
    }

    if (empty($variables)) {
        return '';
    }

    return ':root{' . implode('', $variables) . '}';
}

function webnova_get_layout_width_css(): string
{
    if (function_exists('webnova_theme_get_layout_width_css')) {
        return webnova_theme_get_layout_width_css();
    }

    $layout_width = webnova_sanitize_layout_width((string) get_theme_mod('webnova_layout_width', 'full'));
    $presets = webnova_get_layout_width_presets();
    $preset = $presets[$layout_width] ?? $presets['full'];

    return ':root{'
        . '--wn-site-width:' . esc_html((string) $preset['site_width']) . ';'
        . '--wn-site-gutter:' . esc_html((string) $preset['site_gutter']) . ';'
        . '}';
}
