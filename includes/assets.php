<?php
/**
 * Carga de assets para patrones WebNova.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

function webnova_starter_kit_enqueue_pattern_styles(): void
{
    static $palette_css_added = false;
    static $layout_css_added = false;

    $stylesheet_path = WEBNOVA_STARTER_KIT_PATH . 'assets/css/webnova-patterns.css';

    wp_enqueue_style(
        'webnova-patterns',
        WEBNOVA_STARTER_KIT_URL . 'assets/css/webnova-patterns.css',
        [],
        file_exists($stylesheet_path) ? (string) filemtime($stylesheet_path) : WEBNOVA_STARTER_KIT_VERSION
    );

    $palette_css = webnova_get_palette_css();

    if (! $palette_css_added && $palette_css !== '') {
        wp_add_inline_style('webnova-patterns', $palette_css);
        $palette_css_added = true;
    }

    if (! $layout_css_added) {
        wp_add_inline_style('webnova-patterns', webnova_get_layout_width_css());
        $layout_css_added = true;
    }
}

function webnova_starter_kit_enqueue_editor_styles(): void
{
    webnova_starter_kit_enqueue_pattern_styles();

    $stylesheet_path = WEBNOVA_STARTER_KIT_PATH . 'assets/css/webnova-editor.css';

    wp_enqueue_style(
        'webnova-editor',
        WEBNOVA_STARTER_KIT_URL . 'assets/css/webnova-editor.css',
        ['webnova-patterns'],
        file_exists($stylesheet_path) ? (string) filemtime($stylesheet_path) : WEBNOVA_STARTER_KIT_VERSION
    );
}

function webnova_starter_kit_enqueue_page_title_visibility_editor_assets(): void
{
    $screen = get_current_screen();

    if (! $screen || $screen->post_type !== 'page') {
        return;
    }

    wp_enqueue_script(
        'webnova-page-title-visibility',
        WEBNOVA_STARTER_KIT_URL . 'assets/js/page-title-visibility.js',
        [
            'wp-components',
            'wp-data',
            'wp-edit-post',
            'wp-element',
            'wp-plugins',
        ],
        WEBNOVA_STARTER_KIT_VERSION,
        true
    );
}

add_action('enqueue_block_assets', 'webnova_starter_kit_enqueue_pattern_styles', 20);
add_action('enqueue_block_editor_assets', 'webnova_starter_kit_enqueue_editor_styles');
add_action('enqueue_block_editor_assets', 'webnova_starter_kit_enqueue_page_title_visibility_editor_assets');
