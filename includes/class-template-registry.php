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

        foreach (glob($this->templates_path . '*', GLOB_ONLYDIR) ?: [] as $directory) {
            $manifest = WebNova_Starter_Kit_Demo_Content::load_json(trailingslashit($directory) . 'manifest.json');

            if (is_wp_error($manifest) || empty($manifest['id'])) {
                continue;
            }

            $id = sanitize_key((string) $manifest['id']);
            $templates[$id] = array_merge($manifest, [
                'id' => $id,
                'path' => trailingslashit($directory),
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
        $path = $this->templates_path . $template_id . '/';

        if (! is_dir($path)) {
            return new WP_Error('webnova_template_not_found', __('La plantilla solicitada no existe.', 'webnova-starter-kit'));
        }

        $manifest = WebNova_Starter_Kit_Demo_Content::load_json($path . 'manifest.json');

        if (is_wp_error($manifest)) {
            return $manifest;
        }

        if (empty($manifest['id']) || sanitize_key((string) $manifest['id']) !== $template_id) {
            return new WP_Error('webnova_invalid_manifest', __('El manifest de la plantilla no coincide con la carpeta solicitada.', 'webnova-starter-kit'));
        }

        return array_merge($manifest, [
            'id' => $template_id,
            'path' => $path,
        ]);
    }
}
