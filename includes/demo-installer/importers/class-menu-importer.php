<?php
/**
 * Importador de menus.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Menu_Importer
{
    private $validator;
    private $state_manager;

    public function __construct($validator, $state_manager)
    {
        $this->validator = $validator;
        $this->state_manager = $state_manager;
    }

    public function import()
    {
        $manifest = $this->validator->get_manifest_data();
        $menus = $manifest['menus'] ?? [];

        if (empty($menus)) {
            return 0;
        }

        $count = 0;

        foreach ($menus as $key => $menu_data) {
            $slug = sanitize_title((string) ($menu_data['location'] ?? $key));
            if ($slug === '') {
                continue;
            }

            $key = 'menu:' . $slug;
            $menu_obj = wp_get_nav_menu_object($slug);

            if (! $menu_obj) {
                $menu_id = wp_create_nav_menu(sanitize_text_field((string) ($menu_data['name'] ?? $slug)));
                if (is_wp_error($menu_id)) {
                    continue;
                }
            } else {
                $menu_id = (int) $menu_obj->term_id;
            }

            $this->state_manager->set_item_id('menus', $key, $menu_id);
            update_term_meta($menu_id, '_webnova_demo_key', $key);

            $this->import_menu_items($menu_id, (array) ($menu_data['items'] ?? []));

            $locations = get_theme_mod('nav_menu_locations', []);
            $locations[$slug] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);

            $count++;
        }

        return $count;
    }

    private function import_menu_items(int $menu_id, array $items, int $parent_id = 0): void
    {
        foreach ($items as $item) {
            $page_slug = sanitize_title((string) ($item['slug'] ?? ''));
            $object_id = 0;

            if ($page_slug !== '') {
                $page_id = $this->state_manager->get_item_id('content', 'page:' . $page_slug);
                if (! $page_id) {
                    $page = get_page_by_path($page_slug);
                    $page_id = $page ? $page->ID : 0;
                }
                $object_id = $page_id;
            }

            if ($object_id === 0 && empty($item['url'])) {
                continue; // No puede enlazarse a nada
            }

            $menu_item_data = [
                'menu-item-title'   => sanitize_text_field((string) ($item['label'] ?? '')),
                'menu-item-classes' => sanitize_text_field((string) ($item['classes'] ?? '')),
                'menu-item-status'  => 'publish',
                'menu-item-parent-id' => $parent_id,
            ];

            if ($object_id > 0) {
                $menu_item_data['menu-item-object-id'] = $object_id;
                $menu_item_data['menu-item-object'] = 'page';
                $menu_item_data['menu-item-type'] = 'post_type';
            } else {
                $menu_item_data['menu-item-url'] = esc_url_raw((string) $item['url']);
                $menu_item_data['menu-item-type'] = 'custom';
            }

            // Evitar duplicados revisando si ya existe por el titulo y el padre
            $existing_items = wp_get_nav_menu_items($menu_id);
            $found = false;
            if ($existing_items) {
                foreach ($existing_items as $existing) {
                    if ($existing->title === $menu_item_data['menu-item-title'] && (int)$existing->menu_item_parent === $parent_id) {
                        $found = $existing->ID;
                        break;
                    }
                }
            }

            if (! $found) {
                $item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
                if (! is_wp_error($item_id) && ! empty($item['children'])) {
                    $this->import_menu_items($menu_id, (array) $item['children'], (int) $item_id);
                }
            } else {
                if (! empty($item['children'])) {
                    $this->import_menu_items($menu_id, (array) $item['children'], (int) $found);
                }
            }
        }
    }
}
