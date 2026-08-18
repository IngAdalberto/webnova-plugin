<?php
/**
 * Pantalla de estado y actualizaciones WebNova.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Updates_Admin_Page
{
    private WebNova_Starter_Kit_Private_Update_Checker $plugin_checker;

    public function __construct(WebNova_Starter_Kit_Private_Update_Checker $plugin_checker)
    {
        $this->plugin_checker = $plugin_checker;
    }

    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu'], 20);
        add_action('admin_post_webnova_force_update_check', [$this, 'handle_force_check']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'webnova-starter-kit',
            __('Estado y Actualizaciones', 'webnova-starter-kit'),
            __('Estado y Actualizaciones', 'webnova-starter-kit'),
            'manage_options',
            'webnova-updates',
            [$this, 'render']
        );
    }

    public function handle_force_check(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para consultar actualizaciones.', 'webnova-starter-kit'), 403);
        }

        check_admin_referer('webnova_force_update_check');

        WebNova_Starter_Kit_Private_Update_Checker::clear_cache();

        if (class_exists('WebNova_Theme_Private_Update_Checker')) {
            WebNova_Theme_Private_Update_Checker::clear_cache();
        } else {
            delete_site_transient('webnova_theme_update_metadata');
            delete_site_transient('update_themes');
        }

        $this->plugin_checker->get_metadata(true);
        wp_update_plugins();
        wp_update_themes();

        set_transient(
            'webnova_update_check_' . get_current_user_id(),
            __('Comprobacion de actualizaciones completada.', 'webnova-starter-kit'),
            MINUTE_IN_SECONDS * 5
        );

        wp_safe_redirect(admin_url('admin.php?page=webnova-updates&checked=1'));
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para ver esta pagina.', 'webnova-starter-kit'), 403);
        }

        $notice = get_transient('webnova_update_check_' . get_current_user_id());
        delete_transient('webnova_update_check_' . get_current_user_id());

        $plugin_status = $this->plugin_checker->get_status();
        $theme_status = $this->get_theme_status();
        ?>
        <div class="wrap webnova-starter-kit">
            <h1><?php esc_html_e('Estado y Actualizaciones', 'webnova-starter-kit'); ?></h1>
            <p class="description">
                <?php esc_html_e('Resumen de versiones WebNova y acceso al flujo nativo de actualizaciones de WordPress.', 'webnova-starter-kit'); ?>
            </p>

            <?php if (! empty($notice)) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html((string) $notice); ?></p></div>
            <?php endif; ?>

            <div class="webnova-status-grid">
                <?php $this->render_product_card(__('WebNova Starter Kit', 'webnova-starter-kit'), $plugin_status); ?>
                <?php $this->render_product_card(__('WebNova Theme', 'webnova-starter-kit'), $theme_status); ?>
            </div>

            <section class="webnova-settings-card">
                <h2><?php esc_html_e('Entorno', 'webnova-starter-kit'); ?></h2>
                <table class="widefat striped webnova-status-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Version de WordPress', 'webnova-starter-kit'); ?></th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Version de PHP', 'webnova-starter-kit'); ?></th>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Ultima comprobacion', 'webnova-starter-kit'); ?></th>
                            <td><?php echo esc_html($this->format_last_check()); ?></td>
                        </tr>
                    </tbody>
                </table>

                <p class="webnova-actions">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('update-core.php')); ?>">
                        <?php esc_html_e('Ir a actualizaciones de WordPress', 'webnova-starter-kit'); ?>
                    </a>
                </p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('webnova_force_update_check'); ?>
                    <input type="hidden" name="action" value="webnova_force_update_check" />
                    <?php submit_button(__('Forzar nueva comprobacion', 'webnova-starter-kit'), 'secondary', 'submit', false); ?>
                </form>
            </section>
        </div>
        <?php
    }

    private function render_product_card(string $title, array $status): void
    {
        $status_class = 'webnova-status-' . sanitize_html_class((string) $status['status']);
        ?>
        <section class="webnova-settings-card <?php echo esc_attr($status_class); ?>">
            <h2><?php echo esc_html($title); ?></h2>
            <table class="widefat striped webnova-status-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Version instalada', 'webnova-starter-kit'); ?></th>
                        <td><?php echo esc_html((string) $status['installed_version']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Ultima version disponible', 'webnova-starter-kit'); ?></th>
                        <td><?php echo esc_html((string) ($status['latest_version'] ?: __('No disponible', 'webnova-starter-kit'))); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Estado', 'webnova-starter-kit'); ?></th>
                        <td><strong><?php echo esc_html((string) $status['message']); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </section>
        <?php
    }

    private function get_theme_status(): array
    {
        $theme = wp_get_theme('webnova-theme');

        if (! $theme->exists()) {
            return [
                'installed_version' => '',
                'latest_version' => '',
                'status' => 'error',
                'message' => __('El tema WebNova no esta instalado.', 'webnova-starter-kit'),
            ];
        }

        if (class_exists('WebNova_Theme_Private_Update_Checker')) {
            $checker = new WebNova_Theme_Private_Update_Checker($theme->get_stylesheet(), (string) $theme->get('Version'));

            return $checker->get_status();
        }

        $cached = get_site_transient('webnova_theme_update_metadata');

        if (! is_array($cached) || empty($cached['version'])) {
            return [
                'installed_version' => (string) $theme->get('Version'),
                'latest_version' => '',
                'status' => 'error',
                'message' => __('No fue posible consultar las actualizaciones del tema.', 'webnova-starter-kit'),
            ];
        }

        $has_update = ! empty($cached['download_url'])
            && version_compare(ltrim((string) $cached['version'], 'vV'), (string) $theme->get('Version'), '>');

        return [
            'installed_version' => (string) $theme->get('Version'),
            'latest_version' => ltrim((string) $cached['version'], 'vV'),
            'status' => $has_update ? 'available' : 'current',
            'message' => $has_update
                ? __('Actualizacion disponible.', 'webnova-starter-kit')
                : __('Actualizado.', 'webnova-starter-kit'),
        ];
    }

    private function format_last_check(): string
    {
        $timestamps = array_filter([
            (int) get_option(WebNova_Starter_Kit_Private_Update_Checker::LAST_CHECK_OPTION, 0),
            (int) get_option('webnova_theme_update_last_check', 0),
        ]);

        if (empty($timestamps)) {
            return __('Sin comprobaciones registradas.', 'webnova-starter-kit');
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), max($timestamps));
    }
}
