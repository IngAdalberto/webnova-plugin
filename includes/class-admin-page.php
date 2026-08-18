<?php
/**
 * Pagina de administracion del starter kit.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Starter_Kit_Admin_Page
{
    private WebNova_Starter_Kit_Template_Registry $registry;
    private WebNova_Starter_Kit_Template_Importer $importer;

    public function __construct(WebNova_Starter_Kit_Template_Registry $registry, WebNova_Starter_Kit_Template_Importer $importer)
    {
        $this->registry = $registry;
        $this->importer = $importer;
    }

    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_webnova_import_template', [$this, 'handle_import']);
        add_action('admin_post_webnova_save_starter_settings', [$this, 'handle_save_settings']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('WebNova Starter Kit', 'webnova-starter-kit'),
            __('WebNova', 'webnova-starter-kit'),
            'manage_options',
            'webnova-starter-kit',
            [$this, 'render'],
            'dashicons-layout',
            58
        );

        add_submenu_page(
            'webnova-starter-kit',
            __('WebNova Starter Kit', 'webnova-starter-kit'),
            __('Starter Kit', 'webnova-starter-kit'),
            'manage_options',
            'webnova-starter-kit',
            [$this, 'render']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if (! in_array($hook, ['toplevel_page_webnova-starter-kit', 'webnova_page_webnova-updates'], true)) {
            return;
        }

        wp_enqueue_style(
            'webnova-starter-kit-admin',
            WEBNOVA_STARTER_KIT_URL . 'assets/css/admin.css',
            [],
            WEBNOVA_STARTER_KIT_VERSION
        );
    }

    public function handle_import(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para importar plantillas.', 'webnova-starter-kit'), 403);
        }

        check_admin_referer('webnova_import_template');

        $template_id = sanitize_key((string) ($_POST['template_id'] ?? ''));
        $update_existing = ! empty($_POST['update_existing_pages']);
        $result = $template_id === ''
            ? new WP_Error('webnova_empty_template', __('Debes seleccionar una plantilla valida.', 'webnova-starter-kit'))
            : $this->importer->import($template_id, $update_existing);

        $key = 'webnova_import_' . get_current_user_id();
        set_transient($key, $result, MINUTE_IN_SECONDS * 5);

        wp_safe_redirect(admin_url('admin.php?page=webnova-starter-kit&webnova_import=1'));
        exit;
    }

    public function handle_save_settings(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para guardar ajustes.', 'webnova-starter-kit'), 403);
        }

        check_admin_referer('webnova_save_starter_settings');

        set_theme_mod(
            'webnova_layout_width',
            webnova_sanitize_layout_width((string) ($_POST['layout_width'] ?? 'full'))
        );

        $key = 'webnova_settings_' . get_current_user_id();
        set_transient($key, __('Configuracion guardada.', 'webnova-starter-kit'), MINUTE_IN_SECONDS * 5);

        wp_safe_redirect(admin_url('admin.php?page=webnova-starter-kit&tab=settings&webnova_settings=1'));
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para ver esta pagina.', 'webnova-starter-kit'), 403);
        }

        $notice = get_transient('webnova_import_' . get_current_user_id());
        $settings_notice = get_transient('webnova_settings_' . get_current_user_id());
        delete_transient('webnova_import_' . get_current_user_id());
        delete_transient('webnova_settings_' . get_current_user_id());
        $active_tab = $this->get_active_tab();
        ?>
        <div class="wrap webnova-starter-kit">
            <h1><?php esc_html_e('WebNova Starter Kit', 'webnova-starter-kit'); ?></h1>
            <p class="description">
                <?php esc_html_e('Selecciona una plantilla predisenada para crear paginas, menus, configuracion basica y contenido demo desde el administrador de WordPress.', 'webnova-starter-kit'); ?>
            </p>

            <?php $this->render_notice($notice); ?>
            <?php $this->render_settings_notice($settings_notice); ?>

            <?php $this->render_tabs($active_tab); ?>

            <?php
            if ($active_tab === 'settings') {
                $this->render_settings_tab();
            } else {
                $this->render_importer_tab();
            }
            ?>
        </div>
        <?php
    }

    private function get_active_tab(): string
    {
        $tab = sanitize_key((string) ($_GET['tab'] ?? 'importer'));

        return in_array($tab, ['importer', 'settings'], true) ? $tab : 'importer';
    }

    private function render_tabs(string $active_tab): void
    {
        $tabs = [
            'importer' => __('Importador de plantillas', 'webnova-starter-kit'),
            'settings' => __('Configuraciones', 'webnova-starter-kit'),
        ];
        ?>
        <nav class="nav-tab-wrapper webnova-tabs" aria-label="<?php esc_attr_e('Secciones WebNova Starter Kit', 'webnova-starter-kit'); ?>">
            <?php foreach ($tabs as $tab => $label) : ?>
                <a
                    class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>"
                    href="<?php echo esc_url(admin_url('admin.php?page=webnova-starter-kit&tab=' . $tab)); ?>"
                >
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    private function render_importer_tab(): void
    {
        $templates = $this->registry->get_templates();
        ?>
        <div class="notice notice-warning inline">
            <p><?php esc_html_e('Esta accion creara paginas, menus y configuraciones demo. Se recomienda usarla en una instalacion limpia.', 'webnova-starter-kit'); ?></p>
        </div>

        <div class="webnova-template-grid">
            <?php foreach ($templates as $template) : ?>
                <section class="webnova-template-card">
                    <h2><?php echo esc_html((string) ($template['name'] ?? '')); ?></h2>
                    <p><?php echo esc_html((string) ($template['description'] ?? '')); ?></p>
                    <dl>
                        <dt><?php esc_html_e('Tipo de sitio', 'webnova-starter-kit'); ?></dt>
                        <dd><?php echo esc_html((string) ($template['type'] ?? '')); ?></dd>
                        <dt><?php esc_html_e('Version', 'webnova-starter-kit'); ?></dt>
                        <dd><?php echo esc_html((string) ($template['version'] ?? '')); ?></dd>
                    </dl>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('webnova_import_template'); ?>
                        <input type="hidden" name="action" value="webnova_import_template" />
                        <input type="hidden" name="template_id" value="<?php echo esc_attr((string) $template['id']); ?>" />
                        <div class="webnova-import-option">
                            <label>
                                <input type="checkbox" name="update_existing_pages" value="1" />
                                <?php esc_html_e('Sobrescribir paginas existentes', 'webnova-starter-kit'); ?>
                            </label>
                        </div>
                        <?php submit_button(__('Importar plantilla', 'webnova-starter-kit'), 'primary', 'submit', false); ?>
                    </form>
                </section>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_settings_tab(): void
    {
        $presets = webnova_get_layout_width_presets();
        $current = webnova_sanitize_layout_width((string) get_theme_mod('webnova_layout_width', 'full'));
        ?>
        <section class="webnova-settings-card">
            <h2><?php esc_html_e('Configuraciones generales', 'webnova-starter-kit'); ?></h2>
            <p><?php esc_html_e('Ajustes globales de WebNova para este sitio. Aqui iremos agregando nuevas opciones del starter kit.', 'webnova-starter-kit'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('webnova_save_starter_settings'); ?>
                <input type="hidden" name="action" value="webnova_save_starter_settings" />

                <div class="webnova-setting-row">
                    <div>
                        <h3><?php esc_html_e('Ancho visual del sitio', 'webnova-starter-kit'); ?></h3>
                        <p><?php esc_html_e('Define si las secciones WebNova ocupan todo el ancho o si quedan centradas con margenes laterales para este cliente.', 'webnova-starter-kit'); ?></p>
                    </div>
                    <div class="webnova-layout-options">
                        <?php foreach ($presets as $key => $preset) : ?>
                            <label class="webnova-layout-option">
                                <input
                                    type="radio"
                                    name="layout_width"
                                    value="<?php echo esc_attr((string) $key); ?>"
                                    <?php checked($current, (string) $key); ?>
                                />
                                <span>
                                    <strong><?php echo esc_html((string) ($preset['label'] ?? '')); ?></strong>
                                    <small><?php echo esc_html((string) ($preset['description'] ?? '')); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php submit_button(__('Guardar configuraciones', 'webnova-starter-kit')); ?>
            </form>
        </section>
        <?php
    }

    private function render_notice(mixed $notice): void
    {
        if (empty($notice)) {
            return;
        }

        if (is_wp_error($notice)) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html($notice->get_error_message())
            );
            return;
        }

        $pages = (array) ($notice['pages'] ?? []);
        $menus = (array) ($notice['menus'] ?? []);
        $settings = (array) ($notice['settings'] ?? []);
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php esc_html_e('Plantilla importada:', 'webnova-starter-kit'); ?></strong>
                <?php echo esc_html((string) ($notice['template_name'] ?? '')); ?>
            </p>
            <ul>
                <li><?php echo esc_html(sprintf(__('Paginas creadas: %s', 'webnova-starter-kit'), $this->format_list((array) ($pages['created'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Paginas actualizadas: %s', 'webnova-starter-kit'), $this->format_list((array) ($pages['updated'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Paginas omitidas porque ya existian: %s', 'webnova-starter-kit'), $this->format_list((array) ($pages['skipped'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Secciones usadas: %s', 'webnova-starter-kit'), $this->format_list((array) ($pages['sections_used'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Secciones no encontradas: %s', 'webnova-starter-kit'), $this->format_list((array) ($pages['sections_missing'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Menus creados: %s', 'webnova-starter-kit'), $this->format_list((array) ($menus['created'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Menus reutilizados: %s', 'webnova-starter-kit'), $this->format_list((array) ($menus['reused'] ?? [])))); ?></li>
                <li><?php echo esc_html(sprintf(__('Configuracion aplicada: %s', 'webnova-starter-kit'), $this->format_list($settings))); ?></li>
            </ul>
        </div>
        <?php
    }

    private function render_settings_notice(mixed $notice): void
    {
        if (empty($notice)) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html((string) $notice)
        );
    }

    private function format_list(array $items): string
    {
        $items = array_filter(array_map('sanitize_text_field', $items));

        return empty($items) ? __('Ninguna', 'webnova-starter-kit') : implode(', ', $items);
    }
}
