<?php
/**
 * Gestor del estado de la instalacion.
 *
 * @package WebNovaStarterKit
 */

if (! defined('ABSPATH')) {
    exit;
}

class WebNova_Demo_State_Manager
{
    private const OPTION_NAME = 'webnova_demo_installer_state';

    public function get_state(): array
    {
        $state = get_option(self::OPTION_NAME, []);
        if (! is_array($state)) {
            $state = [];
        }

        return wp_parse_args($state, [
            'status'   => 'pending',
            'media'    => [],
            'terms'    => [],
            'content'  => [],
            'menus'    => [],
        ]);
    }

    private function update_state(array $state): void
    {
        update_option(self::OPTION_NAME, $state, false);
    }

    public function reset_state(): void
    {
        delete_option(self::OPTION_NAME);
    }

    public function mark_completed(): void
    {
        $state = $this->get_state();
        $state['status'] = 'completed';
        $this->update_state($state);
    }

    /**
     * Guarda la correspondencia entre una clave lógica y un ID.
     */
    public function set_item_id(string $type, string $key, int $id): void
    {
        $state = $this->get_state();
        
        if (! isset($state[$type])) {
            $state[$type] = [];
        }

        $state[$type][$key] = $id;
        $this->update_state($state);
    }

    /**
     * Obtiene el ID guardado para una clave lógica.
     */
    public function get_item_id(string $type, string $key): int
    {
        $state = $this->get_state();
        return (int) ($state[$type][$key] ?? 0);
    }
}
