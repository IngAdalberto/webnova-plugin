<?php
/**
 * Custom Post Types base.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    $common_args = [
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    ];

    register_post_type('wn_service', array_merge($common_args, [
        'labels' => [
            'name' => __('Servicios', 'webnova-starter-kit'),
            'singular_name' => __('Servicio', 'webnova-starter-kit'),
        ],
        'menu_icon' => 'dashicons-hammer',
        'has_archive' => true,
        'rewrite' => ['slug' => 'servicios'],
    ]));

    register_taxonomy('wn_project_category', ['wn_project'], [
        'labels' => [
            'name' => __('Áreas de Proyecto', 'webnova-starter-kit'),
            'singular_name' => __('Área de Proyecto', 'webnova-starter-kit'),
            'menu_name' => __('Áreas', 'webnova-starter-kit'),
            'all_items' => __('Todas las Áreas', 'webnova-starter-kit'),
            'edit_item' => __('Editar Área', 'webnova-starter-kit'),
            'view_item' => __('Ver Área', 'webnova-starter-kit'),
            'update_item' => __('Actualizar Área', 'webnova-starter-kit'),
            'add_new_item' => __('Añadir Nueva Área', 'webnova-starter-kit'),
            'new_item_name' => __('Nombre de la Nueva Área', 'webnova-starter-kit'),
            'search_items' => __('Buscar Áreas', 'webnova-starter-kit'),
            'not_found' => __('No se encontraron áreas.', 'webnova-starter-kit'),
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'area-proyecto'],
    ]);

    register_post_type('wn_project', array_merge($common_args, [
        'labels' => [
            'name' => __('Proyectos', 'webnova-starter-kit'),
            'singular_name' => __('Proyecto', 'webnova-starter-kit'),
        ],
        'menu_icon' => 'dashicons-portfolio',
        'has_archive' => true,
        'rewrite' => ['slug' => 'proyectos'],
    ]));

    register_post_type('wn_product', array_merge($common_args, [
        'labels' => [
            'name' => __('Productos', 'webnova-starter-kit'),
            'singular_name' => __('Producto', 'webnova-starter-kit'),
        ],
        'menu_icon' => 'dashicons-products',
        'has_archive' => true,
        'rewrite' => ['slug' => 'catalogo'],
    ]));

    register_taxonomy('wn_document_category', ['wn_document'], [
        'labels' => [
            'name' => __('Categorías de Documento', 'webnova-starter-kit'),
            'singular_name' => __('Categoría de Documento', 'webnova-starter-kit'),
            'menu_name' => __('Categorías', 'webnova-starter-kit'),
            'all_items' => __('Todas las Categorías', 'webnova-starter-kit'),
            'edit_item' => __('Editar Categoría', 'webnova-starter-kit'),
            'view_item' => __('Ver Categoría', 'webnova-starter-kit'),
            'update_item' => __('Actualizar Categoría', 'webnova-starter-kit'),
            'add_new_item' => __('Añadir Nueva Categoría', 'webnova-starter-kit'),
            'new_item_name' => __('Nombre de la Nueva Categoría', 'webnova-starter-kit'),
            'search_items' => __('Buscar Categorías', 'webnova-starter-kit'),
            'not_found' => __('No se encontraron categorías.', 'webnova-starter-kit'),
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'categoria-documento'],
    ]);

    register_post_type('wn_document', array_merge($common_args, [
        'labels' => [
            'name' => __('Documentos', 'webnova-starter-kit'),
            'singular_name' => __('Documento', 'webnova-starter-kit'),
            'all_items' => __('Todos los Documentos', 'webnova-starter-kit'),
            'add_new' => __('Añadir Nuevo', 'webnova-starter-kit'),
            'add_new_item' => __('Añadir Nuevo Documento', 'webnova-starter-kit'),
            'edit_item' => __('Editar Documento', 'webnova-starter-kit'),
            'view_item' => __('Ver Documento', 'webnova-starter-kit'),
            'search_items' => __('Buscar Documentos', 'webnova-starter-kit'),
            'not_found' => __('No se encontraron documentos.', 'webnova-starter-kit'),
        ],
        'menu_icon' => 'dashicons-media-document',
        'has_archive' => 'transparencia',
        'rewrite' => ['slug' => 'documento'],
    ]));
});
