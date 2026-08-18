<?php
/**
 * Pagina de administracion del instalador del demo institucional.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Installer_Admin_Page
{
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'webnova-starter-kit',
            __('Instalador Demo', 'webnova-starter-kit'),
            __('Instalador Demo', 'webnova-starter-kit'),
            'manage_options',
            'webnova-demo-installer',
            [$this, 'render']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'webnova_page_webnova-demo-installer') {
            return;
        }

        wp_enqueue_style(
            'webnova-demo-installer',
            WEBNOVA_STARTER_KIT_URL . 'assets/css/demo-installer/demo-installer.css',
            [],
            WEBNOVA_STARTER_KIT_VERSION
        );

        wp_enqueue_script(
            'webnova-demo-installer',
            WEBNOVA_STARTER_KIT_URL . 'assets/js/demo-installer/demo-installer.js',
            ['jquery'],
            WEBNOVA_STARTER_KIT_VERSION,
            true
        );

        wp_localize_script('webnova-demo-installer', 'webnovaInstallerSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('webnova_demo_installer_action'),
            'texts'    => [
                'confirm_uninstall' => __('¿Estás seguro de que deseas desinstalar el contenido demo? Esto moverá los elementos a la papelera o los eliminará permanentemente.', 'webnova-starter-kit'),
                'error_generic'     => __('Ocurrió un error inesperado de red o servidor.', 'webnova-starter-kit'),
            ]
        ]);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para ver esta pagina.', 'webnova-starter-kit'), 403);
        }

        require_once WEBNOVA_STARTER_KIT_PATH . 'admin/demo-installer/views/installer-page.php';
    }
}
