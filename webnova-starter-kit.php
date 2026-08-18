<?php
/**
 * Bootstrap del plugin WebNova Starter Kit.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once WEBNOVA_STARTER_KIT_PATH . 'includes/helpers.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/assets.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/post-types.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/shortcodes.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/settings.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/page-title-visibility.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-demo-content.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-pattern-library.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-pattern-registry.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-template-registry.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-menu-builder.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-settings-applier.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-template-importer.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-private-update-checker.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-admin-page.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/class-updates-admin-page.php';

// Demo Installer
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/class-installer-ajax.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/class-state-manager.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/class-installer-validator.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/class-uninstall-manager.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/importers/class-media-importer.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/importers/class-term-importer.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/importers/class-content-importer.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/importers/class-menu-importer.php';
require_once WEBNOVA_STARTER_KIT_PATH . 'includes/demo-installer/importers/class-settings-importer.php';

if (defined('WP_CLI') && WP_CLI && file_exists(WEBNOVA_STARTER_KIT_PATH . 'includes/legacy/setup-wp-cli.php')) {
    require_once WEBNOVA_STARTER_KIT_PATH . 'includes/legacy/setup-wp-cli.php';
}

add_action('plugins_loaded', function (): void {
    $update_checker = new WebNova_Starter_Kit_Private_Update_Checker(WEBNOVA_STARTER_KIT_FILE, WEBNOVA_STARTER_KIT_VERSION);
    $update_checker->hooks();

    $theme_update_checker_file = WP_CONTENT_DIR . '/themes/webnova-theme/inc/class-private-update-checker.php';

    if (file_exists($theme_update_checker_file)) {
        require_once $theme_update_checker_file;

        if (class_exists('WebNova_Theme_Private_Update_Checker') && ! defined('WEBNOVA_THEME_UPDATE_CHECKER_REGISTERED')) {
            $theme = wp_get_theme('webnova-theme');
            $theme_version = $theme->exists() ? (string) $theme->get('Version') : '0.0.0';
            $theme_update_checker = new WebNova_Theme_Private_Update_Checker('webnova-theme', $theme_version);
            $theme_update_checker->hooks();

            define('WEBNOVA_THEME_UPDATE_CHECKER_REGISTERED', true);
        }
    }

    $pattern_paths = [
        WEBNOVA_STARTER_KIT_PATH . 'patterns',
    ];

    if (function_exists('get_stylesheet_directory')) {
        $child_path = get_stylesheet_directory() . '/patterns';
        if (is_dir($child_path)) {
            $pattern_paths[] = $child_path;
        }
    }

    if (function_exists('get_template_directory')) {
        $parent_path = get_template_directory() . '/patterns';
        if (is_dir($parent_path) && !in_array($parent_path, $pattern_paths, true)) {
            $pattern_paths[] = $parent_path;
        }
    }

    $pattern_library = new WebNova_Pattern_Library($pattern_paths);
    $pattern_registry = new WebNova_Pattern_Registry($pattern_library);
    $pattern_registry->hooks();

    $registry = new WebNova_Starter_Kit_Template_Registry(WEBNOVA_CORE_PATH . 'demo/manifests/');
    $importer = new WebNova_Starter_Kit_Template_Importer($registry, $pattern_library);
    $admin_page = new WebNova_Starter_Kit_Admin_Page($registry, $importer);
    $admin_page->hooks();

    $updates_page = new WebNova_Updates_Admin_Page($update_checker);
    $updates_page->hooks();

    // Init Demo Installer
    $demo_state = new WebNova_Demo_State_Manager();
    // Path inicial por defecto, será sobreescrito por la solicitud AJAX
    $demo_manifest_path = WEBNOVA_CORE_PATH . 'demo/manifests/institutional.json';
    
    $demo_validator = new WebNova_Demo_Installer_Validator($demo_manifest_path);
    $demo_media = new WebNova_Demo_Media_Importer($demo_validator, $demo_state);
    $demo_terms = new WebNova_Demo_Term_Importer($demo_validator, $demo_state);
    $demo_content = new WebNova_Demo_Content_Importer($demo_validator, $demo_state);
    $demo_menu = new WebNova_Demo_Menu_Importer($demo_validator, $demo_state);
    $demo_settings = new WebNova_Demo_Settings_Importer($demo_validator, $demo_state);
    $demo_uninstall = new WebNova_Demo_Uninstall_Manager($demo_state);

    $demo_ajax = new WebNova_Demo_Installer_Ajax(
        $registry,
        $demo_state,
        $demo_validator,
        $demo_media,
        $demo_terms,
        $demo_content,
        $demo_menu,
        $demo_settings,
        $demo_uninstall
    );
    $demo_ajax->hooks();
});
