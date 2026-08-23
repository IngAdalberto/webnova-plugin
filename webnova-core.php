<?php
/**
 * Plugin Name: WebNova Starter Kit
 * Plugin URI: https://agenciawebnova.com
 * Description: Importador de plantillas prediseñadas para sitios creados por Agencia WebNova.
 * Version: 1.2.1
 * Author: Agencia WebNova
 * Text Domain: webnova-starter-kit
 * Requires at least: 6.6
 * Requires PHP: 8.1
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WEBNOVA_CORE_VERSION', '1.2.1');
define('WEBNOVA_CORE_FILE', __FILE__);
define('WEBNOVA_CORE_PATH', plugin_dir_path(__FILE__));
define('WEBNOVA_CORE_URL', plugin_dir_url(__FILE__));

define('WEBNOVA_STARTER_KIT_VERSION', WEBNOVA_CORE_VERSION);
define('WEBNOVA_STARTER_KIT_FILE', WEBNOVA_CORE_FILE);
define('WEBNOVA_STARTER_KIT_PATH', WEBNOVA_CORE_PATH);
define('WEBNOVA_STARTER_KIT_URL', WEBNOVA_CORE_URL);

// Repositorio de GitHub para actualizaciones
define('WEBNOVA_STARTER_KIT_GITHUB_REPO', 'IngAdalberto/webnova-plugin');

require_once WEBNOVA_CORE_PATH . 'webnova-starter-kit.php';
