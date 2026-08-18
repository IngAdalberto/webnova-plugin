<?php
/**
 * Constructor de menus de plantillas.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Menu_Builder
{
    public function import(array $menus, array $page_ids): array
    {
        $result = [
            'created' => [],
            'reused' => [],
            'items_added' => [],
            'items_skipped' => [],
        ];

        foreach ($menus as $menu_key => $menu_config) {
            if (! is_array($menu_config) || empty($menu_config['name'])) {
                continue;
            }

            $menu_name = sanitize_text_field((string) $menu_config['name']);
            $menu = wp_get_nav_menu_object($menu_name);

            if ($menu) {
                $menu_id = (int) $menu->term_id;
                $result['reused'][] = $menu_name;
            } else {
                $menu_id = (int) wp_create_nav_menu($menu_name);

                if ($menu_id <= 0) {
                    continue;
                }

                $result['created'][] = $menu_name;
            }

            foreach ((array) ($menu_config['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $added = $this->add_menu_item($menu_id, $item, $page_ids);
                $label = sanitize_text_field((string) ($item['label'] ?? ''));

                if ($added) {
                    $result['items_added'][] = $label;
                } else {
                    $result['items_skipped'][] = $label;
                }
            }

            if (! empty($menu_config['location'])) {
                $locations = get_theme_mod('nav_menu_locations', []);
                $locations[sanitize_key((string) $menu_config['location'])] = $menu_id;
                set_theme_mod('nav_menu_locations', $locations);
            }
        }

        return $result;
    }

    private function add_menu_item(int $menu_id, array $item, array $page_ids): bool
    {
        $type = sanitize_key((string) ($item['type'] ?? ''));
        $label = sanitize_text_field((string) ($item['label'] ?? ''));

        if ($type === 'page') {
            $slug = sanitize_title((string) ($item['slug'] ?? ''));
            $page_id = (int) ($page_ids[$slug] ?? 0);

            if ($page_id <= 0 || $this->menu_has_page($menu_id, $page_id)) {
                return false;
            }

            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-object-id' => $page_id,
                'menu-item-object' => 'page',
                'menu-item-type' => 'post_type',
                'menu-item-title' => $label,
                'menu-item-status' => 'publish',
            ]);

            return true;
        }

        if ($type === 'anchor' || $type === 'custom') {
            $url = esc_url_raw((string) ($item['url'] ?? ''));

            if ($url === '' || $this->menu_has_custom_url($menu_id, $url)) {
                return false;
            }

            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $label,
                'menu-item-url' => $url,
                'menu-item-type' => 'custom',
                'menu-item-status' => 'publish',
            ]);

            return true;
        }

        return false;
    }

    private function menu_has_page(int $menu_id, int $page_id): bool
    {
        foreach (wp_get_nav_menu_items($menu_id) ?: [] as $item) {
            if ((string) $item->type === 'post_type' && (int) $item->object_id === $page_id) {
                return true;
            }
        }

        return false;
    }

    private function menu_has_custom_url(int $menu_id, string $url): bool
    {
        foreach (wp_get_nav_menu_items($menu_id) ?: [] as $item) {
            if ((string) $item->type === 'custom' && untrailingslashit((string) $item->url) === untrailingslashit($url)) {
                return true;
            }
        }

        return false;
    }
}
