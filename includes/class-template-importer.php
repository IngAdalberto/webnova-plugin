<?php
/**
 * Importador principal de plantillas.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Template_Importer
{
    private WebNova_Starter_Kit_Template_Registry $registry;
    private WebNova_Pattern_Library $pattern_library;
    private bool $update_existing_pages = false;

    public function __construct(WebNova_Starter_Kit_Template_Registry $registry, ?WebNova_Pattern_Library $pattern_library = null)
    {
        $this->registry = $registry;
        $this->pattern_library = $pattern_library ?: new WebNova_Pattern_Library(WEBNOVA_STARTER_KIT_PATH . 'patterns');
    }

    public function import(string $template_id, bool $update_existing = false)
    {
        $this->update_existing_pages = $update_existing;
        $template = $this->registry->get_template($template_id);

        if (is_wp_error($template)) {
            return $template;
        }

        foreach (['menus.json', 'settings.json'] as $file) {
            if (! file_exists($template['path'] . $file)) {
                return new WP_Error('webnova_missing_json', sprintf(__('No existe %s para esta plantilla.', 'webnova-starter-kit'), $file));
            }
        }

        $layout_path = $template['path'] . 'layout.json';
        $pages_path = $template['path'] . 'pages.json';

        if (file_exists($layout_path)) {
            $page_source = WebNova_Starter_Kit_Demo_Content::load_json($layout_path);
            $uses_layout = true;
        } elseif (file_exists($pages_path)) {
            $page_source = WebNova_Starter_Kit_Demo_Content::load_json($pages_path);
            $uses_layout = false;
        } else {
            return new WP_Error('webnova_missing_json', __('No existe layout.json ni pages.json para esta plantilla.', 'webnova-starter-kit'));
        }

        $menus = WebNova_Starter_Kit_Demo_Content::load_json($template['path'] . 'menus.json');
        $settings = WebNova_Starter_Kit_Demo_Content::load_json($template['path'] . 'settings.json');

        foreach ([$page_source, $menus, $settings] as $data) {
            if (is_wp_error($data)) {
                return $data;
            }
        }

        $page_result = $uses_layout
            ? $this->import_layout_pages((array) ($page_source['pages'] ?? []))
            : $this->import_pages((array) $page_source);

        if (is_wp_error($page_result)) {
            return $page_result;
        }

        $menu_builder = new WebNova_Starter_Kit_Menu_Builder();
        $settings_applier = new WebNova_Starter_Kit_Settings_Applier();

        return [
            'template_name' => (string) ($template['name'] ?? $template_id),
            'source' => $uses_layout ? 'layout.json' : 'pages.json',
            'pages' => $page_result,
            'menus' => $menu_builder->import($menus, $page_result['page_ids']),
            'settings' => $settings_applier->apply($settings, $page_result['page_ids']),
        ];
    }

    private function import_layout_pages(array $pages)
    {
        $prepared_pages = [];
        $sections_used = [];
        $sections_missing = [];

        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            $content_parts = [];
            $page_title = sanitize_text_field((string) ($page['title'] ?? ''));

            foreach ((array) ($page['sections'] ?? []) as $section_slug) {
                $section_slug = $this->normalize_section_slug((string) $section_slug);

                if ($section_slug === '') {
                    continue;
                }

                $section_content = $this->pattern_library->get_pattern_content($section_slug);

                if (is_wp_error($section_content) || trim((string) $section_content) === '') {
                    $sections_missing[] = $page_title !== ''
                        ? sprintf('%s (%s)', $section_slug, $page_title)
                        : $section_slug;
                    continue;
                }

                $sections_used[] = $section_slug;
                $content_parts[] = (string) $section_content;
            }

            $page['content'] = implode("\n\n", $content_parts);
            $prepared_pages[] = $page;
        }

        $result = $this->import_pages($prepared_pages);

        if (is_wp_error($result)) {
            return $result;
        }

        $result['sections_used'] = array_values(array_unique($sections_used));
        $result['sections_missing'] = array_values(array_unique($sections_missing));

        return $result;
    }

    private function import_pages(array $pages)
    {
        $result = [
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'page_ids' => [],
            'sections_used' => [],
            'sections_missing' => [],
        ];

        foreach ($pages as $page) {
            if (! is_array($page) || empty($page['title']) || empty($page['slug'])) {
                continue;
            }

            $slug = sanitize_title((string) $page['slug']);
            $existing = get_page_by_path($slug, OBJECT, 'page');
            $can_update = $this->update_existing_pages || ! empty($page['update_existing']);

            if ($existing instanceof WP_Post) {
                $result['page_ids'][$slug] = (int) $existing->ID;

                if ($can_update) {
                    $updated = wp_update_post([
                        'ID' => (int) $existing->ID,
                        'post_title' => sanitize_text_field((string) $page['title']),
                        'post_content' => (string) ($page['content'] ?? ''),
                        'post_status' => sanitize_key((string) ($page['status'] ?? 'publish')),
                    ], true);

                    if (is_wp_error($updated)) {
                        return $updated;
                    }

                    $result['updated'][] = (string) $page['title'];
                } else {
                    $result['skipped'][] = (string) $page['title'];
                }
            } else {
                $page_id = wp_insert_post([
                    'post_title' => sanitize_text_field((string) $page['title']),
                    'post_name' => $slug,
                    'post_content' => (string) ($page['content'] ?? ''),
                    'post_status' => sanitize_key((string) ($page['status'] ?? 'publish')),
                    'post_type' => 'page',
                ], true);

                if (is_wp_error($page_id) || (int) $page_id <= 0) {
                    return is_wp_error($page_id)
                        ? $page_id
                        : new WP_Error('webnova_page_create_failed', sprintf(__('No se pudo crear la pagina %s.', 'webnova-starter-kit'), $page['title']));
                }

                $result['page_ids'][$slug] = (int) $page_id;
                $result['created'][] = (string) $page['title'];
            }

            if (! empty($page['is_front_page']) && ! empty($result['page_ids'][$slug])) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', (int) $result['page_ids'][$slug]);
            }
        }

        return $result;
    }

    private function normalize_section_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_\-\/]/', '', $slug);

        return is_string($slug) ? trim($slug, '/') : '';
    }
}
