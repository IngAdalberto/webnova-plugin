<?php
/**
 * Importador de medios para el demo.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Media_Importer
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
        $media = (array) ($manifest['images'] ?? []);
        $documents = (array) ($manifest['documents'] ?? []);

        if (empty($media) && empty($documents)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $count = 0;

        foreach ($media as $item) {
            $filename = sanitize_file_name(basename($item['local_file']));
            $file_path = wp_normalize_path(WEBNOVA_CORE_PATH . $item['local_file']);
            $count += $this->import_file($filename, $file_path, $item['alt'] ?? '', 'image');
        }

        foreach ($documents as $doc) {
            $filename = sanitize_file_name(basename($doc['local_file']));
            $file_path = wp_normalize_path(WEBNOVA_CORE_PATH . $doc['local_file']);
            $count += $this->import_file($filename, $file_path, $doc['title'] ?? '', 'document');
        }

        return $count;
    }

    private function import_file(string $filename, string $file_path, string $title_alt, string $type): int
    {
        $key = 'media:' . sanitize_title($filename);
        $existing_id = $this->state_manager->get_item_id('media', $key);

        if ($existing_id > 0 && get_post($existing_id)) {
            return 1;
        }

        // Check if attachment exists by _webnova_demo_key
        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'any',
            'meta_key' => '_webnova_demo_key',
            'meta_value' => $key,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if (! empty($existing)) {
            $this->state_manager->set_item_id('media', $key, $existing[0]);
            return 1;
        }

        if (! file_exists($file_path)) {
            return 0; // Skip silent error or handle? Requirement: "Si falla un recurso..." just skip or warn.
        }

        $upload = wp_upload_bits($filename, null, file_get_contents($file_path));

        if (! empty($upload['error'])) {
            return 0;
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($filename, null)['type'],
            'post_title'     => sanitize_text_field($title_alt),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'meta_input'     => [
                '_webnova_demo_key' => $key
            ]
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attachment_id)) {
            return 0;
        }

        if ($type === 'image') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($title_alt));
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);
        }

        $this->state_manager->set_item_id('media', $key, $attachment_id);

        return 1;
    }
}
