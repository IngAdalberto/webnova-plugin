<?php
/**
 * Validador para el instalador del demo institucional.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Installer_Validator
{
    private $manifest_path;
    private $data = null;

    public function __construct(string $manifest_path)
    {
        $this->manifest_path = $manifest_path;
    }

    public function set_manifest_path(string $path): void
    {
        $this->manifest_path = $path;
        $this->data = null;
    }

    public function validate()
    {
        if (! file_exists($this->manifest_path)) {
            return new WP_Error(
                'webnova_missing_file',
                sprintf(__('Archivo de manifiesto no encontrado en %s.', 'webnova-starter-kit'), $this->manifest_path)
            );
        }

        $content = file_get_contents($this->manifest_path);
        $this->data = json_decode((string) $content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'webnova_invalid_json',
                __('El archivo de manifiesto no tiene formato JSON válido.', 'webnova-starter-kit')
            );
        }

        return true;
    }

    public function get_manifest_data()
    {
        if ($this->data === null) {
            $this->validate();
        }
        return $this->data ?: [];
    }

    // Compatibilidad hacia atrás (o refactor)
    public function load_data(string $section = '')
    {
        $data = $this->get_manifest_data();
        if (empty($section)) {
            return $data;
        }
        return $data[$section] ?? [];
    }
}
