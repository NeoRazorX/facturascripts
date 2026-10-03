<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\UI;

use FacturaScripts\Core\Tools;

/**
 * Menú desplegable Bootstrap. Los items pueden ser enlaces, eventos del form
 * contenedor o eventos de página, además de separadores.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIDropdown extends UIComponent
{
    protected string $label = '';
    protected array $labelParams = [];
    protected string $icon = '';
    protected string $color = 'outline-secondary';

    /** @var array<array{type: string, label?: string, icon?: string, url?: string, event?: string, pageScope?: bool}> */
    protected array $items = [];

    protected function defaultTemplate(): string
    {
        return 'UI/Dropdown.html.twig';
    }

    /** Clave i18n del texto del botón. */
    public function label(string $key, array $params = []): static
    {
        $this->label = $key;
        $this->labelParams = $params;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /** Item enlace. */
    public function item(string $labelKey, string $url, string $icon = ''): static
    {
        $this->items[] = ['type' => 'link', 'label' => $labelKey, 'url' => $url, 'icon' => $icon];
        return $this;
    }

    /** Item que dispara un evento del form contenedor. */
    public function itemAction(string $labelKey, string $event, string $icon = ''): static
    {
        $this->items[] = ['type' => 'event', 'label' => $labelKey, 'event' => $event, 'icon' => $icon, 'pageScope' => false];
        return $this;
    }

    /** Item que dispara un evento de página. */
    public function itemPageAction(string $labelKey, string $event, string $icon = ''): static
    {
        $this->items[] = ['type' => 'event', 'label' => $labelKey, 'event' => $event, 'icon' => $icon, 'pageScope' => true];
        return $this;
    }

    public function divider(): static
    {
        $this->items[] = ['type' => 'divider'];
        return $this;
    }

    public function labelText(): string
    {
        return $this->label === '' ? '' : Tools::lang()->trans($this->label, $this->labelParams);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /** Items resueltos para la plantilla, con el eventId completo calculado. */
    public function resolvedItems(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            if ($item['type'] === 'event') {
                $item['eventId'] = $item['pageScope']
                    ? UIPage::EVENT_SCOPE . ':' . $item['event']
                    : $this->form()?->eventId($item['event']) ?? UIPage::EVENT_SCOPE . ':' . $item['event'];
                $item['scopeNone'] = $item['pageScope'];
            }
            $item['labelText'] = isset($item['label']) ? Tools::lang()->trans($item['label']) : '';
            $result[] = $item;
        }
        return $result;
    }

    public function colClass(): string
    {
        return $this->cols <= 0 ? 'col-12 col-sm-auto' : parent::colClass();
    }
}
