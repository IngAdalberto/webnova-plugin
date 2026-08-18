<?php
/**
 * Biblioteca de patrones reutilizables WebNova.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Pattern_Library
{
    private array $patterns_paths = [];
    private ?array $patterns = null;

    public function __construct(array|string $patterns_paths)
    {
        $paths = is_array($patterns_paths) ? $patterns_paths : [$patterns_paths];
        foreach ($paths as $path) {
            $this->patterns_paths[] = trailingslashit((string) $path);
        }
    }

    public function get_pattern_content(string $slug)
    {
        $pattern = $this->get_pattern($slug);

        if (is_wp_error($pattern)) {
            return $pattern;
        }

        return $this->render_pattern_file((string) $pattern['file']);
    }

    public function get_patterns(): array
    {
        if ($this->patterns !== null) {
            return $this->patterns;
        }

        $this->patterns = [];

        foreach ($this->get_pattern_files() as $file) {
            $metadata = $this->get_pattern_metadata($file);

            if (empty($metadata['slug'])) {
                continue;
            }

            if (isset($this->patterns[$metadata['slug']])) {
                // Se encontró un slug duplicado. Se conserva el primero encontrado por orden de prioridad.
                // Priority: Plugin -> Child Theme -> Parent Theme.
                continue;
            }

            $this->patterns[$metadata['slug']] = array_merge($metadata, [
                'file' => $file,
            ]);
        }

        ksort($this->patterns);

        return $this->patterns;
    }

    public function get_pattern(string $slug)
    {
        $slug = $this->normalize_pattern_slug($slug);
        $patterns = $this->get_patterns();

        if (empty($patterns[$slug])) {
            return new WP_Error(
                'webnova_pattern_not_found',
                sprintf(__('No se encontro la seccion %s.', 'webnova-starter-kit'), $slug)
            );
        }

        return $patterns[$slug];
    }

    private function get_pattern_files(): array
    {
        $files = [];

        foreach ($this->patterns_paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    private function get_pattern_metadata(string $file): array
    {
        $headers = get_file_data($file, [
            'title' => 'Title',
            'slug' => 'Slug',
            'categories' => 'Categories',
            'description' => 'Description',
            'keywords' => 'Keywords',
        ]);

        $categories = array_filter(array_map('sanitize_key', array_map('trim', explode(',', (string) ($headers['categories'] ?? '')))));
        $keywords = array_filter(array_map('sanitize_key', array_map('trim', explode(',', (string) ($headers['keywords'] ?? '')))));

        return [
            'title' => sanitize_text_field((string) ($headers['title'] ?? '')),
            'slug' => $this->normalize_pattern_slug((string) ($headers['slug'] ?? '')),
            'categories' => $categories,
            'description' => sanitize_text_field((string) ($headers['description'] ?? '')),
            'keywords' => $keywords,
        ];
    }

    private function normalize_pattern_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_\-\/]/', '', $slug);

        return is_string($slug) ? trim($slug, '/') : '';
    }

    private function render_pattern_file(string $file): string
    {
        if (! file_exists($file)) {
            return '';
        }

        ob_start();
        include $file;

        return trim((string) ob_get_clean());
    }
}
