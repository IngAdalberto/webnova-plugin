<?php
/**
 * Vista de la pagina del instalador.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap webnova-installer-wrap">
    <h1><?php esc_html_e('Instalador del Demo Institucional', 'webnova-starter-kit'); ?></h1>
    
    <div class="notice notice-warning inline">
        <p><?php esc_html_e('Esta herramienta instalará el contenido del demo institucional WebNova. Es un proceso asíncrono e idempotente.', 'webnova-starter-kit'); ?></p>
    </div>

    <div class="webnova-installer-container">
        <div class="webnova-installer-sidebar">
            <div class="webnova-installer-card">
                <h2><?php esc_html_e('Demo Institucional', 'webnova-starter-kit'); ?></h2>
                <p><?php esc_html_e('Versión: 1.0.0', 'webnova-starter-kit'); ?></p>
                <button type="button" id="webnova-btn-install" class="button button-primary button-hero">
                    <?php esc_html_e('Ejecutar Instalación', 'webnova-starter-kit'); ?>
                </button>
                <button type="button" id="webnova-btn-uninstall" class="button button-secondary">
                    <?php esc_html_e('Desinstalar Demo', 'webnova-starter-kit'); ?>
                </button>
            </div>
            
            <div class="webnova-installer-card">
                <h3><?php esc_html_e('Progreso', 'webnova-starter-kit'); ?></h3>
                <ul id="webnova-installer-steps" class="webnova-steps-list">
                    <li data-step="validate" class="pending"><?php esc_html_e('1. Validación previa', 'webnova-starter-kit'); ?></li>
                    <li data-step="media" class="pending"><?php esc_html_e('2. Importación de medios', 'webnova-starter-kit'); ?></li>
                    <li data-step="terms" class="pending"><?php esc_html_e('3. Creación de términos', 'webnova-starter-kit'); ?></li>
                    <li data-step="content" class="pending"><?php esc_html_e('4. Creación de contenidos', 'webnova-starter-kit'); ?></li>
                    <li data-step="menus" class="pending"><?php esc_html_e('5. Creación de menús', 'webnova-starter-kit'); ?></li>
                    <li data-step="settings" class="pending"><?php esc_html_e('6. Aplicación de opciones', 'webnova-starter-kit'); ?></li>
                    <li data-step="finalize" class="pending"><?php esc_html_e('7. Finalización', 'webnova-starter-kit'); ?></li>
                </ul>
            </div>
        </div>

        <div class="webnova-installer-main">
            <div class="webnova-installer-log-box">
                <h3><?php esc_html_e('Registro de Actividad', 'webnova-starter-kit'); ?></h3>
                <div id="webnova-installer-log" class="webnova-log" aria-live="polite">
                    <p><?php esc_html_e('Esperando acción...', 'webnova-starter-kit'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
