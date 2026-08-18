<?php
/**
 * Utilidades para leer contenido demo desde JSON.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Demo_Content
{
    public static function load_json(string $path)
    {
        if (! file_exists($path)) {
            return new WP_Error(
                'webnova_missing_json',
                sprintf(__('No existe el archivo requerido: %s', 'webnova-starter-kit'), basename($path))
            );
        }

        $contents = file_get_contents($path);
        $data = json_decode((string) $contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return new WP_Error(
                'webnova_invalid_json',
                sprintf(__('JSON invalido en %1$s: %2$s', 'webnova-starter-kit'), basename($path), json_last_error_msg())
            );
        }

        return $data;
    }
}
