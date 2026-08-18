<?php
/**
 * Importador de contenidos (paginas, CPTs, entradas).
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Content_Importer
{
    private $validator;
    private $state_manager;
    private $pattern_library;

    public function __construct($validator, $state_manager, $pattern_library = null)
    {
        $this->validator = $validator;
        $this->state_manager = $state_manager;
        
        if ($pattern_library) {
            $this->pattern_library = $pattern_library;
        } else {
            $paths = [WEBNOVA_STARTER_KIT_PATH . 'patterns'];
            if (function_exists('get_template_directory')) {
                $paths[] = get_template_directory() . '/patterns';
            }
            $this->pattern_library = new WebNova_Pattern_Library($paths);
        }
    }

    public function import()
    {
        $manifest = $this->validator->get_manifest_data();
        $data = $manifest['layout'] ?? [];
        
        $count = 0;
        $count += $this->import_type($data['pages'] ?? [], 'page', 'page');
        $count += $this->import_type($data['programs'] ?? [], 'wn_program', 'program');
        $count += $this->import_type($data['projects'] ?? [], 'wn_project', 'project');
        $count += $this->import_type($data['documents'] ?? [], 'wn_document', 'document');
        $count += $this->import_type($data['posts'] ?? [], 'post', 'post');

        return $count;
    }

    private function import_type(array $items, string $post_type, string $prefix): int
    {
        if (empty($items)) {
            return 0;
        }

        $count = 0;

        foreach ($items as $item) {
            $slug = sanitize_title($item['slug'] ?? (string) ($item['title'] ?? ''));
            if (empty($slug)) {
                continue;
            }

            $key = $prefix . ':' . $slug;
            $existing_id = $this->state_manager->get_item_id('content', $key);

            if ($existing_id > 0 && get_post($existing_id)) {
                $this->update_post($existing_id, $item, $post_type, $key);
                $count++;
                continue;
            }

            // Check if already in DB via meta
            $existing = get_posts([
                'post_type' => $post_type,
                'post_status' => 'any',
                'meta_key' => '_webnova_demo_key',
                'meta_value' => $key,
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);

            if (! empty($existing)) {
                $this->state_manager->set_item_id('content', $key, $existing[0]);
                $this->update_post($existing[0], $item, $post_type, $key);
                $count++;
                continue;
            }

            // Insert new post
            $post_id = wp_insert_post([
                'post_type' => $post_type,
                'post_name' => $slug,
                'post_title' => sanitize_text_field((string) ($item['title'] ?? '')),
                'post_content' => $this->build_content($item),
                'post_excerpt' => sanitize_text_field((string) ($item['excerpt'] ?? '')),
                'post_status' => sanitize_key((string) ($item['status'] ?? 'publish')),
            ]);

            if (! is_wp_error($post_id)) {
                $this->state_manager->set_item_id('content', $key, $post_id);
                update_post_meta($post_id, '_webnova_demo_key', $key);
                $this->assign_metadata($post_id, $item);
                $count++;
            }
        }

        return $count;
    }

    private function update_post(int $post_id, array $item, string $post_type, string $key): void
    {
        // Actualizamos el contenido para reflejar los nuevos patrones si es necesario
        if (isset($item['sections']) || isset($item['content'])) {
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $this->build_content($item),
            ]);
        }
        
        $this->assign_metadata($post_id, $item);
    }

    private function build_content(array $item): string
    {
        if (isset($item['content'])) {
            return (string) $item['content'];
        }

        $sections = (array) ($item['sections'] ?? []);
        $content_parts = [];

        foreach ($sections as $section) {
            $section_content = $this->pattern_library->get_pattern_content($section);
            if (! is_wp_error($section_content)) {
                $content_parts[] = (string) $section_content;
            }
        }

        return implode("\n\n", $content_parts);
    }

    private function assign_metadata(int $post_id, array $item): void
    {
        // Featured image
        if (! empty($item['thumbnail'])) {
            $media_key = 'media:' . sanitize_title($item['thumbnail']);
            $attach_id = $this->state_manager->get_item_id('media', $media_key);
            if ($attach_id) {
                set_post_thumbnail($post_id, $attach_id);
            }
        }

        // Category
        if (! empty($item['category'])) {
            $cat_name = sanitize_text_field($item['category']);
            $term = term_exists($cat_name, 'category');
            if ($term) {
                $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
                wp_set_object_terms($post_id, $term_id, 'category');
            }
        }

        // Document specific
        if (! empty($item['file'])) {
            $media_key = 'media:' . sanitize_title($item['file']);
            $attach_id = $this->state_manager->get_item_id('media', $media_key);
            if ($attach_id) {
                update_post_meta($post_id, '_wn_document_file', wp_get_attachment_url($attach_id));
            }
        }

        // Is Front Page
        if (! empty($item['is_front_page'])) {
            update_option('webnova_installer_front_page', $post_id);
        }
    }
}
