<?php
/**
 * Actualizaciones vía GitHub para WebNova Starter Kit.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Private_Update_Checker
{
    public const SLUG = 'webnova-starter-kit';
    public const TRANSIENT_KEY = 'webnova_starter_kit_update_metadata';
    public const LAST_CHECK_OPTION = 'webnova_starter_kit_update_last_check';
    public const LAST_ERROR_OPTION = 'webnova_starter_kit_update_last_error';

    private string $plugin_file;
    private string $plugin_basename;
    private string $version;

    public function __construct(string $plugin_file, string $version)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->version = $version;
    }

    public function hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'filter_update_transient']);
        add_filter('site_transient_update_plugins', [$this, 'filter_update_transient']);
        add_filter('plugins_api', [$this, 'filter_plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_github_zip_folder_name'], 10, 3);
    }

    public function filter_update_transient($transient): object
    {
        if (! is_object($transient)) {
            $transient = (object) [];
        }

        $transient->checked = isset($transient->checked) && is_array($transient->checked)
            ? $transient->checked
            : [];
        $transient->response = isset($transient->response) && is_array($transient->response)
            ? $transient->response
            : [];
        $transient->no_update = isset($transient->no_update) && is_array($transient->no_update)
            ? $transient->no_update
            : [];

        $transient->checked[$this->plugin_basename] = $this->version;
        unset($transient->response[$this->plugin_basename]);
        unset($transient->no_update[$this->plugin_basename]);

        $metadata = $this->get_metadata();

        if (is_wp_error($metadata) || ! $this->has_update($metadata)) {
            return $transient;
        }

        $transient->response[$this->plugin_basename] = (object) [
            'id' => $this->plugin_basename,
            'slug' => self::SLUG,
            'plugin' => $this->plugin_basename,
            'new_version' => (string) $metadata['version'],
            'url' => (string) ($metadata['details_url'] ?? $metadata['homepage'] ?? 'https://github.com/' . WEBNOVA_STARTER_KIT_GITHUB_REPO),
            'package' => (string) ($metadata['download_url'] ?? ''),
            'tested' => (string) ($metadata['tested'] ?? ''),
            'requires' => (string) ($metadata['requires'] ?? ''),
            'requires_php' => (string) ($metadata['requires_php'] ?? ''),
        ];

        return $transient;
    }

    public function filter_plugin_info(mixed $result, string $action, object $args): mixed
    {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== self::SLUG) {
            return $result;
        }

        $metadata = $this->get_metadata();

        if (is_wp_error($metadata)) {
            return $result;
        }

        return (object) [
            'name' => (string) ($metadata['name'] ?? 'WebNova Starter Kit'),
            'slug' => self::SLUG,
            'version' => (string) $metadata['version'],
            'author' => (string) ($metadata['author'] ?? 'Agencia WebNova'),
            'homepage' => (string) ($metadata['homepage'] ?? 'https://github.com/' . WEBNOVA_STARTER_KIT_GITHUB_REPO),
            'requires' => (string) ($metadata['requires'] ?? ''),
            'tested' => (string) ($metadata['tested'] ?? ''),
            'requires_php' => (string) ($metadata['requires_php'] ?? ''),
            'download_link' => (string) ($metadata['download_url'] ?? ''),
            'last_updated' => (string) ($metadata['last_updated'] ?? ''),
            'sections' => [
                'description' => wp_kses_post((string) ($metadata['description'] ?? '')),
                'changelog' => wp_kses_post((string) ($metadata['changelog'] ?? '')),
            ],
        ];
    }

    /**
     * Corrige el nombre de la carpeta al extraer el .zip de GitHub.
     */
    public function fix_github_zip_folder_name($source, $remote_source, $upgrader)
    {
        global $wp_filesystem;

        if (! is_string($source) || ! is_string($remote_source) || ! $wp_filesystem) {
            return $source;
        }

        $plugin_dir_name = dirname($this->plugin_basename);
        $main_file_name = basename($this->plugin_basename);
        $source = trailingslashit($source);

        if ($wp_filesystem->exists($source . $main_file_name)) {
            $plugin_data = get_file_data($source . $main_file_name, ['Plugin Name' => 'Plugin Name']);

            if (! empty($plugin_data['Plugin Name']) && stripos($plugin_data['Plugin Name'], 'WebNova') !== false) {
                $corrected_source = trailingslashit($remote_source) . $plugin_dir_name . '/';

                if (wp_normalize_path($source) !== wp_normalize_path($corrected_source)) {
                    if ($wp_filesystem->move($source, $corrected_source, true)) {
                        return $corrected_source;
                    }

                    return new WP_Error('rename_failed', __('No se pudo renombrar la carpeta extraída de GitHub.', 'webnova-starter-kit'));
                }
            }
        }

        return $source;
    }

    public function get_status(bool $force = false): array
    {
        $metadata = $this->get_metadata($force);

        if (is_wp_error($metadata)) {
            return [
                'installed_version' => $this->version,
                'latest_version' => '',
                'status' => 'error',
                'message' => __('No fue posible consultar las actualizaciones en este momento.', 'webnova-starter-kit'),
            ];
        }

        return [
            'installed_version' => $this->version,
            'latest_version' => (string) $metadata['version'],
            'status' => $this->has_update($metadata) ? 'available' : 'current',
            'message' => $this->has_update($metadata)
                ? __('Actualizacion disponible.', 'webnova-starter-kit')
                : __('Actualizado.', 'webnova-starter-kit'),
        ];
    }

    public function get_metadata(bool $force = false): array|WP_Error
    {
        if (! $force) {
            $cached = get_site_transient(self::TRANSIENT_KEY);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $url = self::get_update_url();

        if ($url === '') {
            $error = new WP_Error('webnova_missing_update_url', 'missing_update_url');
            update_option(self::LAST_ERROR_OPTION, $error->get_error_code(), false);

            return $error;
        }

        $request_args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ],
        ];

        // Soporte opcional para repositorios privados mediante token
        if (defined('WEBNOVA_STARTER_KIT_GITHUB_TOKEN') && WEBNOVA_STARTER_KIT_GITHUB_TOKEN) {
            $request_args['headers']['Authorization'] = 'token ' . WEBNOVA_STARTER_KIT_GITHUB_TOKEN;
        }

        $response = wp_remote_get(
            $url,
            (array) apply_filters('webnova_starter_kit_update_request_args', $request_args, $url)
        );

        update_option(self::LAST_CHECK_OPTION, time(), false);

        if (is_wp_error($response)) {
            update_option(self::LAST_ERROR_OPTION, $response->get_error_code(), false);

            return $response;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            $error = new WP_Error('webnova_update_http_error', 'http_error_' . $status_code);
            update_option(self::LAST_ERROR_OPTION, $error->get_error_code(), false);

            return $error;
        }

        $metadata = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($metadata) || empty($metadata['tag_name'])) {
            $error = new WP_Error('webnova_invalid_update_payload', 'invalid_payload');
            update_option(self::LAST_ERROR_OPTION, $error->get_error_code(), false);

            return $error;
        }

        $metadata = $this->sanitize_metadata($metadata);
        set_site_transient(self::TRANSIENT_KEY, $metadata, 6 * HOUR_IN_SECONDS);
        delete_option(self::LAST_ERROR_OPTION);

        return $metadata;
    }

    public function has_update(array $metadata): bool
    {
        return ! empty($metadata['download_url'])
            && version_compare($this->normalize_version((string) $metadata['version']), $this->version, '>');
    }

    public static function get_update_url(): string
    {
        if (! defined('WEBNOVA_STARTER_KIT_GITHUB_REPO')) {
            return '';
        }

        $repo = trim((string) WEBNOVA_STARTER_KIT_GITHUB_REPO, '/');
        return esc_url_raw('https://api.github.com/repos/' . $repo . '/releases/latest');
    }

    public static function clear_cache(): void
    {
        delete_site_transient(self::TRANSIENT_KEY);
        delete_site_transient('update_plugins');
    }

    private function sanitize_metadata(array $metadata): array
    {
        $version = $this->normalize_version((string) $metadata['tag_name']);
        $download_url = $metadata['zipball_url'] ?? '';

        if (!empty($metadata['assets']) && is_array($metadata['assets'])) {
            $zip_assets = [];

            foreach ($metadata['assets'] as $asset) {
                if (isset($asset['name'], $asset['browser_download_url']) && str_ends_with($asset['name'], '.zip')) {
                    $zip_assets[(string) $asset['name']] = (string) $asset['browser_download_url'];
                }
            }

            $preferred_names = [
                'webnova-starter-kit-' . $version . '.zip',
                'webnova-plugin-' . $version . '.zip',
                'webnova-core-' . $version . '.zip',
            ];

            foreach ($preferred_names as $preferred_name) {
                if (isset($zip_assets[$preferred_name])) {
                    $download_url = $zip_assets[$preferred_name];
                    break;
                }
            }

            if ($download_url === ($metadata['zipball_url'] ?? '')) {
                foreach ($zip_assets as $asset_name => $asset_url) {
                    if (! preg_match('/(?:upgrade|bridge|puente|from-)/i', $asset_name)) {
                        $download_url = $asset_url;
                        break;
                    }
                }
            }
        }

        return [
            'name' => sanitize_text_field('WebNova Starter Kit'),
            'slug' => sanitize_key(self::SLUG),
            'version' => $version,
            'download_url' => esc_url_raw($download_url),
            'details_url' => esc_url_raw((string) ($metadata['html_url'] ?? '')),
            'homepage' => esc_url_raw('https://github.com/' . WEBNOVA_STARTER_KIT_GITHUB_REPO),
            'author' => sanitize_text_field('Agencia WebNova'),
            'requires' => '6.5',
            'tested' => '7.0',
            'requires_php' => '8.1',
            'last_updated' => sanitize_text_field((string) ($metadata['published_at'] ?? '')),
            'description' => wp_kses_post((string) ($metadata['body'] ?? 'Actualización desde GitHub.')),
            'changelog' => wp_kses_post((string) ($metadata['body'] ?? '')),
        ];
    }

    private function normalize_version(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
