<?php
/**
 * Seeder secundario para WP-CLI.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

function webnova_get_default_config_path(string $template_type): string
{
    $repo_root = dirname(WEBNOVA_CORE_PATH, 4);

    return $repo_root . '/config/' . $template_type . '.json';
}

function webnova_load_template_config(string $template_type, string $config_path = ''): array
{
    $path = $config_path !== '' ? $config_path : webnova_get_default_config_path($template_type);

    if (! file_exists($path)) {
        return [
            'error' => sprintf('No existe el archivo de configuracion: %s', $path),
        ];
    }

    $json = file_get_contents($path);
    $data = json_decode((string) $json, true);

    if (! is_array($data)) {
        return [
            'error' => sprintf('JSON invalido: %s', $path),
        ];
    }

    return $data;
}

function webnova_setup_site_from_config(array $config): array
{
    $created_pages = [];

    webnova_trash_default_content();

    if (! empty($config['site_title'])) {
        update_option('blogname', sanitize_text_field((string) $config['site_title']));
    }

    if (! empty($config['site_description'])) {
        update_option('blogdescription', sanitize_text_field((string) $config['site_description']));
    }

    if (! empty($config['whatsapp_demo']) || ! empty($config['email_demo'])) {
        update_option('webnova_core_options', [
            'whatsapp_phone' => sanitize_text_field((string) ($config['whatsapp_demo'] ?? '')),
            'email' => sanitize_email((string) ($config['email_demo'] ?? '')),
        ]);
    }

    foreach (($config['pages'] ?? []) as $page) {
        if (! is_array($page) || empty($page['title']) || empty($page['slug'])) {
            continue;
        }

        $created_pages[$page['slug']] = webnova_create_or_update_page($page);
    }

    webnova_assign_front_page($created_pages, (string) ($config['front_page'] ?? 'inicio'));
    webnova_create_menu((array) ($config['menu'] ?? []), $created_pages);
    webnova_seed_content((array) ($config['content_types'] ?? []));

    flush_rewrite_rules();

    return $created_pages;
}

function webnova_create_or_update_page(array $page): int
{
    $slug = sanitize_title((string) $page['slug']);
    $existing = get_page_by_path($slug, OBJECT, 'page');
    $content = (string) ($page['content'] ?? '');

    $post_data = [
        'post_title' => sanitize_text_field((string) $page['title']),
        'post_name' => $slug,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'page',
    ];

    if ($existing instanceof WP_Post) {
        $post_data['ID'] = $existing->ID;

        return (int) wp_update_post($post_data);
    }

    return (int) wp_insert_post($post_data);
}

function webnova_assign_front_page(array $created_pages, string $front_page_slug): void
{
    if (empty($created_pages[$front_page_slug])) {
        return;
    }

    update_option('show_on_front', 'page');
    update_option('page_on_front', (int) $created_pages[$front_page_slug]);
}

function webnova_create_menu(array $menu_items, array $created_pages): void
{
    $menu_name = 'Menu principal';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($menu_name);

    foreach (wp_get_nav_menu_items($menu_id) ?: [] as $existing_item) {
        wp_delete_post($existing_item->ID, true);
    }

    foreach ($menu_items as $item) {
        $slug = is_array($item) ? (string) ($item['slug'] ?? '') : (string) $item;

        if ($slug === '' || empty($created_pages[$slug])) {
            continue;
        }

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-object-id' => (int) $created_pages[$slug],
            'menu-item-object' => 'page',
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ]);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $registered = get_registered_nav_menus();

    if (! empty($registered)) {
        $first_location = (string) array_key_first($registered);
        $locations[$first_location] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    webnova_create_block_navigation($menu_items, $created_pages);
}

function webnova_create_block_navigation(array $menu_items, array $created_pages): void
{
    if (! post_type_exists('wp_navigation')) {
        return;
    }

    $blocks = [];

    foreach ($menu_items as $item) {
        if (! is_array($item)) {
            continue;
        }

        $slug = (string) ($item['slug'] ?? '');

        if ($slug === '' || empty($created_pages[$slug])) {
            continue;
        }

        $page_id = (int) $created_pages[$slug];
        $label = (string) ($item['label'] ?? get_the_title($page_id));
        $attrs = [
            'label' => $label,
            'type' => 'page',
            'id' => $page_id,
            'url' => get_permalink($page_id),
            'kind' => 'post-type',
        ];

        $blocks[] = '<!-- wp:navigation-link ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ' /-->';
    }

    if (empty($blocks)) {
        return;
    }

    $content = implode("\n", $blocks);
    $existing = get_posts([
        'post_type' => 'wp_navigation',
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby' => 'ID',
        'order' => 'ASC',
    ]);

    $post_data = [
        'post_title' => 'Menu principal',
        'post_name' => 'menu-principal',
        'post_status' => 'publish',
        'post_type' => 'wp_navigation',
        'post_content' => wp_slash($content),
    ];

    if (! empty($existing[0]) && $existing[0] instanceof WP_Post) {
        $post_data['ID'] = $existing[0]->ID;
        wp_update_post($post_data);

        return;
    }

    wp_insert_post($post_data);
}

function webnova_trash_default_content(): void
{
    $default_slugs = ['hello-world', 'sample-page', 'pagina-ejemplo'];

    foreach ($default_slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, ['post', 'page']);

        if ($post instanceof WP_Post) {
            wp_trash_post($post->ID);
        }
    }
}

function webnova_seed_content(array $content_types): void
{
    foreach ($content_types as $post_type => $items) {
        if (! post_type_exists((string) $post_type) || ! is_array($items)) {
            continue;
        }

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['title'])) {
                continue;
            }

            $slug = sanitize_title((string) ($item['slug'] ?? $item['title']));
            $existing = get_page_by_path($slug, OBJECT, (string) $post_type);

            if ($existing instanceof WP_Post) {
                continue;
            }

            wp_insert_post([
                'post_type' => (string) $post_type,
                'post_status' => 'publish',
                'post_title' => sanitize_text_field((string) $item['title']),
                'post_name' => $slug,
                'post_excerpt' => sanitize_text_field((string) ($item['excerpt'] ?? '')),
                'post_content' => (string) ($item['content'] ?? ''),
            ]);
        }
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('webnova setup', function (array $args, array $assoc_args): void {
        $template_type = (string) ($args[0] ?? '');
        $config_path = (string) ($assoc_args['config'] ?? '');

        if ($template_type === '') {
            WP_CLI::error('Uso: wp webnova setup <landing-empresarial|sitio-institucional|sitio-comercial> [--config=ruta.json]');
        }

        $config = webnova_load_template_config($template_type, $config_path);

        if (! empty($config['error'])) {
            WP_CLI::error((string) $config['error']);
        }

        $pages = webnova_setup_site_from_config($config);

        WP_CLI::success(sprintf(
            'Sitio base "%s" configurado. Paginas creadas/actualizadas: %d',
            $template_type,
            count($pages)
        ));
    });
}
