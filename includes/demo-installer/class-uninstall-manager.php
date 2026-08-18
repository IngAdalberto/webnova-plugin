<?php
/**
 * Gestor de desinstalacion.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Uninstall_Manager
{
    private $state_manager;

    public function __construct($state_manager)
    {
        $this->state_manager = $state_manager;
    }

    public function uninstall()
    {
        $count = 0;

        // Eliminar Posts (Pages, Programs, Projects, Docs, News, Attachments)
        $posts_to_delete = get_posts([
            'post_type' => 'any',
            'post_status' => 'any',
            'meta_key' => '_webnova_demo_key',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        foreach ($posts_to_delete as $post_id) {
            wp_delete_post($post_id, true);
            $count++;
        }

        // Eliminar Terminos y Menus
        $terms_to_delete = get_terms([
            'taxonomy' => get_taxonomies(),
            'meta_key' => '_webnova_demo_key',
            'hide_empty' => false,
            'fields' => 'ids'
        ]);

        foreach ($terms_to_delete as $term_id) {
            $term = get_term($term_id);
            if ($term && ! is_wp_error($term)) {
                wp_delete_term($term_id, $term->taxonomy);
                $count++;
            }
        }

        // Limpiar estado
        $this->state_manager->reset_state();

        return $count;
    }
}
