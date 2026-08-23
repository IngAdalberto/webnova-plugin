<?php
/**
 * Importador de configuraciones.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Settings_Importer
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
        $settings = $manifest['settings'] ?? [];

        if (empty($settings)) {
            return 0;
        }

        // Apply theme mods
        if (! empty($settings['theme_mods'])) {
            foreach ($settings['theme_mods'] as $key => $value) {
                if ($key === 'custom_logo') {
                    $media_key = 'media:' . sanitize_title($value);
                    $attach_id = $this->state_manager->get_item_id('media', $media_key);
                    if ($attach_id) {
                        set_theme_mod('custom_logo', $attach_id);
                    }
                    continue;
                }
                set_theme_mod($key, sanitize_text_field($value));
            }
        }

        $options = (array) ($settings['options'] ?? []);
        $allowed_options = [
            'blogname' => 'sanitize_text_field',
            'blogdescription' => 'sanitize_text_field',
        ];

        foreach ($allowed_options as $option_name => $sanitize_callback) {
            if (isset($options[$option_name])) {
                update_option($option_name, call_user_func($sanitize_callback, (string) $options[$option_name]));
            }
        }

        if (! empty($options['permalink_structure'])) {
            $permalink_structure = sanitize_text_field((string) $options['permalink_structure']);

            if ('/' === substr($permalink_structure, 0, 1) && '/' === substr($permalink_structure, -1)) {
                update_option('permalink_structure', $permalink_structure);
            }
        }

        // Apply site icon
        if (! empty($options['site_icon'])) {
            $media_key = 'media:' . sanitize_title($options['site_icon']);
            $attach_id = $this->state_manager->get_item_id('media', $media_key);
            if ($attach_id) {
                update_option('site_icon', $attach_id);
            }
        }

        // Apply front page and blog page
        $front_page_id = get_option('webnova_installer_front_page');
        if ($front_page_id) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $front_page_id);
            delete_option('webnova_installer_front_page');
        }

        if (! empty($settings['page_for_posts'])) {
            $news_page_slug = sanitize_title((string) $settings['page_for_posts']);
            $news_page_id = $this->state_manager->get_item_id('content', 'page:' . $news_page_slug);
            if ($news_page_id) {
                update_option('page_for_posts', $news_page_id);
            }
        } else {
            update_option('page_for_posts', 0);
        }

        return 1;
    }
}
