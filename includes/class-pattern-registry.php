<?php
/**
 * Registro de categorias y patrones Gutenberg WebNova.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Pattern_Registry
{
    private WebNova_Pattern_Library $library;

    private array $categories = [
        'webnova' => 'WebNova',
        'webnova-sections' => 'WebNova / Secciones',
        'webnova-hero' => 'WebNova / Hero',
        'webnova-services' => 'WebNova / Servicios',
        'webnova-benefits' => 'WebNova / Beneficios',
        'webnova-pricing' => 'WebNova / Precios',
        'webnova-gallery' => 'WebNova / Galeria',
        'webnova-cta' => 'WebNova / CTA',
        'webnova-contact' => 'WebNova / Contacto',
        'webnova-footer' => 'WebNova / Footer',
        'webnova-process' => 'WebNova / Proceso',
        'webnova-cards' => 'WebNova / Cards',
    ];

    public function __construct(WebNova_Pattern_Library $library)
    {
        $this->library = $library;
    }

    public function hooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        $this->register_categories();
        $this->register_patterns();
    }

    private function register_categories(): void
    {
        if (! function_exists('register_block_pattern_category')) {
            return;
        }

        foreach ($this->categories as $slug => $label) {
            register_block_pattern_category($slug, [
                'label' => __($label, 'webnova-starter-kit'),
            ]);
        }
    }

    private function register_patterns(): void
    {
        if (! function_exists('register_block_pattern')) {
            return;
        }

        foreach ($this->library->get_patterns() as $pattern) {
            if (empty($pattern['slug']) || empty($pattern['title'])) {
                continue;
            }

            $content = $this->library->get_pattern_content((string) $pattern['slug']);

            if (is_wp_error($content) || trim((string) $content) === '') {
                continue;
            }

            register_block_pattern((string) $pattern['slug'], [
                'title' => (string) $pattern['title'],
                'categories' => (array) ($pattern['categories'] ?? []),
                'description' => (string) ($pattern['description'] ?? ''),
                'keywords' => (array) ($pattern['keywords'] ?? []),
                'content' => (string) $content,
            ]);
        }
    }
}
