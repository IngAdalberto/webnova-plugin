<?php
/**
 * Importador de terminos y categorias.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_Term_Importer
{
    private $validator;
    private $state_manager;

    public function __construct($validator, $state_manager)
    {
        $this->validator = $validator;
        $this->state_manager = $state_manager;
    }

    public function import()
    {
        $data = $this->validator->load_data('layout.json');
        $taxonomies = (array) ($data['taxonomies'] ?? []);

        if (empty($taxonomies)) {
            return 0;
        }

        $count = 0;

        foreach ($taxonomies as $tax => $terms) {
            if (! taxonomy_exists($tax)) {
                continue;
            }

            foreach ($terms as $term_name) {
                $term_name = sanitize_text_field($term_name);
                $key = 'term:' . $tax . ':' . sanitize_title($term_name);
                
                $existing_id = $this->state_manager->get_item_id('terms', $key);
                if ($existing_id > 0) {
                    $count++;
                    continue;
                }

                $term_exists = term_exists($term_name, $tax);
                
                if ($term_exists !== 0 && $term_exists !== null) {
                    $term_id = is_array($term_exists) ? (int) $term_exists['term_id'] : (int) $term_exists;
                    $this->state_manager->set_item_id('terms', $key, $term_id);
                    // Guardamos la meta de que este termino fue administrado por el demo,
                    // aunque si el usuario ya lo tenía, al desinstalar deberíamos ser cuidadosos.
                    // Omitimos la meta si ya existía para no borrarlo luego.
                    $count++;
                    continue;
                }

                $inserted = wp_insert_term($term_name, $tax);

                if (! is_wp_error($inserted)) {
                    $term_id = (int) $inserted['term_id'];
                    $this->state_manager->set_item_id('terms', $key, $term_id);
                    update_term_meta($term_id, '_webnova_demo_key', $key);
                    $count++;
                }
            }
        }

        return $count;
    }
}
