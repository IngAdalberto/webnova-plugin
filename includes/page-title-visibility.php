<?php
/**
 * Controla la visibilidad del titulo visual en paginas.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

const WEBNOVA_SHOW_PAGE_TITLE_META = '_webnova_show_page_title';
const WEBNOVA_SHOW_PRIMARY_MENU_META = '_webnova_show_primary_menu';
const WEBNOVA_SHOW_FOOTER_META = '_webnova_show_footer';

function webnova_starter_kit_register_page_title_meta(): void
{
    $meta_keys = [
        WEBNOVA_SHOW_PAGE_TITLE_META,
        WEBNOVA_SHOW_PRIMARY_MENU_META,
        WEBNOVA_SHOW_FOOTER_META,
    ];

    foreach ($meta_keys as $meta_key) {
        register_post_meta('page', $meta_key, [
            'type' => 'boolean',
            'single' => true,
            'default' => true,
            'show_in_rest' => true,
            'auth_callback' => function (...$args): bool {
                $post_id = (int) ($args[2] ?? 0);

                if ($post_id > 0) {
                    return current_user_can('edit_post', $post_id);
                }

                return current_user_can('edit_pages');
            },
        ]);
    }
}

function webnova_starter_kit_get_visibility_meta(int $post_id, string $meta_key): bool
{
    if (! metadata_exists('post', $post_id, $meta_key)) {
        return true;
    }

    $value = get_post_meta($post_id, $meta_key, true);

    return wp_validate_boolean($value);
}

function webnova_starter_kit_should_show_page_title(int $post_id): bool
{
    return webnova_starter_kit_get_visibility_meta($post_id, WEBNOVA_SHOW_PAGE_TITLE_META);
}

function webnova_starter_kit_should_show_header(int $post_id): bool
{
    return webnova_starter_kit_get_visibility_meta($post_id, WEBNOVA_SHOW_PRIMARY_MENU_META);
}

/**
 * Backward-compatible alias for integrations created with the original label.
 */
function webnova_starter_kit_should_show_primary_menu(int $post_id): bool
{
    return webnova_starter_kit_should_show_header($post_id);
}

function webnova_starter_kit_should_show_footer(int $post_id): bool
{
    return webnova_starter_kit_get_visibility_meta($post_id, WEBNOVA_SHOW_FOOTER_META);
}

function webnova_starter_kit_hide_page_title_block(string $block_content, array $block, $instance = null): string
{
    if (($block['blockName'] ?? '') !== 'core/post-title') {
        return $block_content;
    }

    if (! is_singular('page')) {
        return $block_content;
    }

    $post_id = get_queried_object_id();

    if ($post_id <= 0 || webnova_starter_kit_should_show_page_title($post_id)) {
        return $block_content;
    }

    // A Query Loop rendered inside the page changes the block context to the
    // queried entry. Its post titles must remain visible even when the page's
    // own template title is disabled.
    $context_post_id = isset($instance->context['postId'])
        ? (int) $instance->context['postId']
        : 0;

    if ($context_post_id > 0 && $context_post_id !== $post_id) {
        return $block_content;
    }

    return '';
}

function webnova_starter_kit_hide_page_template_parts(string $block_content, array $block): string
{
    if (! is_singular('page')) {
        return $block_content;
    }

    $post_id = get_queried_object_id();
    $slug = (string) ($block['attrs']['slug'] ?? '');

    if ($post_id <= 0) {
        return $block_content;
    }

    if ($slug === 'header' && ! webnova_starter_kit_should_show_header($post_id)) {
        return '';
    }

    if ($slug === 'footer' && ! webnova_starter_kit_should_show_footer($post_id)) {
        return '';
    }

    return $block_content;
}

add_action('init', 'webnova_starter_kit_register_page_title_meta');
add_filter('render_block', 'webnova_starter_kit_hide_page_title_block', 10, 3);
add_filter('render_block_core/template-part', 'webnova_starter_kit_hide_page_template_parts', 99, 2);
