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

function webnova_starter_kit_register_page_title_meta(): void
{
    register_post_meta('page', WEBNOVA_SHOW_PAGE_TITLE_META, [
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

function webnova_starter_kit_should_show_page_title(int $post_id): bool
{
    if (! metadata_exists('post', $post_id, WEBNOVA_SHOW_PAGE_TITLE_META)) {
        return true;
    }

    $value = get_post_meta($post_id, WEBNOVA_SHOW_PAGE_TITLE_META, true);

    return wp_validate_boolean($value);
}

function webnova_starter_kit_hide_page_title_block(string $block_content, array $block): string
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

    return '';
}

add_action('init', 'webnova_starter_kit_register_page_title_meta');
add_filter('render_block', 'webnova_starter_kit_hide_page_title_block', 10, 2);
