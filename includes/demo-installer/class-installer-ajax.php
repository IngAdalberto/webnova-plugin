<?php
/**
 * Controlador de AJAX para el instalador.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Installer_Ajax
{
    private $state_manager;
    private $validator;
    private $media_importer;
    private $term_importer;
    private $content_importer;
    private $menu_importer;
    private $settings_importer;
    private $uninstall_manager;

    public function __construct(
        $state_manager,
        $validator,
        $media_importer,
        $term_importer,
        $content_importer,
        $menu_importer,
        $settings_importer,
        $uninstall_manager
    ) {
        $this->state_manager     = $state_manager;
        $this->validator         = $validator;
        $this->media_importer    = $media_importer;
        $this->term_importer     = $term_importer;
        $this->content_importer  = $content_importer;
        $this->menu_importer     = $menu_importer;
        $this->settings_importer = $settings_importer;
        $this->uninstall_manager = $uninstall_manager;
    }

    public function hooks(): void
    {
        add_action('wp_ajax_webnova_installer_validate', [$this, 'handle_validate']);
        add_action('wp_ajax_webnova_installer_media', [$this, 'handle_media']);
        add_action('wp_ajax_webnova_installer_terms', [$this, 'handle_terms']);
        add_action('wp_ajax_webnova_installer_content', [$this, 'handle_content']);
        add_action('wp_ajax_webnova_installer_menus', [$this, 'handle_menus']);
        add_action('wp_ajax_webnova_installer_settings', [$this, 'handle_settings']);
        add_action('wp_ajax_webnova_installer_finalize', [$this, 'handle_finalize']);
        add_action('wp_ajax_webnova_installer_uninstall', [$this, 'handle_uninstall']);
    }

    private function verify_request(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes.', 'webnova-starter-kit'));
        }

        if (! check_ajax_referer('webnova_demo_installer_action', false, false)) {
            wp_send_json_error(__('Nonce inválido.', 'webnova-starter-kit'));
        }
    }

    public function handle_validate(): void
    {
        $this->verify_request();
        
        $result = $this->validator->validate();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $this->state_manager->reset_state();

        wp_send_json_success(['message' => __('Validación completada.', 'webnova-starter-kit')]);
    }

    public function handle_media(): void
    {
        $this->verify_request();
        
        $result = $this->media_importer->import();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => sprintf(__('Importados/verificados %d recursos de medios.', 'webnova-starter-kit'), $result)]);
    }

    public function handle_terms(): void
    {
        $this->verify_request();
        
        $result = $this->term_importer->import();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => sprintf(__('Importados/verificados %d términos y categorías.', 'webnova-starter-kit'), $result)]);
    }

    public function handle_content(): void
    {
        $this->verify_request();
        
        $result = $this->content_importer->import();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => sprintf(__('Importados/verificados %d elementos de contenido.', 'webnova-starter-kit'), $result)]);
    }

    public function handle_menus(): void
    {
        $this->verify_request();
        
        $result = $this->menu_importer->import();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => sprintf(__('Importados/verificados %d menús.', 'webnova-starter-kit'), $result)]);
    }

    public function handle_settings(): void
    {
        $this->verify_request();
        
        $result = $this->settings_importer->import();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => __('Configuraciones aplicadas correctamente.', 'webnova-starter-kit')]);
    }

    public function handle_finalize(): void
    {
        $this->verify_request();
        
        flush_rewrite_rules();
        $this->state_manager->mark_completed();

        wp_send_json_success(['message' => __('Instalación y regeneración finalizadas.', 'webnova-starter-kit')]);
    }

    public function handle_uninstall(): void
    {
        $this->verify_request();
        
        $result = $this->uninstall_manager->uninstall();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => sprintf(__('Eliminados %d elementos de contenido demo.', 'webnova-starter-kit'), $result)]);
    }
}
