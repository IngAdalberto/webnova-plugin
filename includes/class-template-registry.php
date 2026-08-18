<?php
/**
 * Registro de plantillas disponibles.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Template_Registry
{
    private string $templates_path;

    public function __construct(string $templates_path)
    {
        $this->templates_path = trailingslashit($templates_path);
    }

    public function get_templates(): array
    {
        $templates = [];

        foreach (glob($this->templates_path . '*.json') ?: [] as $file) {
            $manifest = WebNova_Starter_Kit_Demo_Content::load_json($file);

            if (is_wp_error($manifest) || empty($manifest['id'])) {
                continue;
            }

            $id = sanitize_key((string) $manifest['id']);
            $templates[$id] = array_merge($manifest, [
                'id' => $id,
                'path' => trailingslashit(dirname($file)),
                'manifest_file' => $file,
            ]);
        }

        uasort($templates, function (array $a, array $b): int {
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $templates;
    }

    public function get_template(string $template_id)
    {
        $template_id = sanitize_key($template_id);
        $templates = $this->get_templates();

        if (! isset($templates[$template_id])) {
            return new WP_Error('webnova_template_not_found', __('La plantilla solicitada no existe.', 'webnova-starter-kit'));
        }

        return $templates[$template_id];
    }
}
