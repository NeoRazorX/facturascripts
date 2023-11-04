<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI;

use Exception;
use FacturaScripts\Core\Template\UI\Component;
use FacturaScripts\Core\Tools;

class Button extends Component
{
    /** @var string */
    protected $color = 'secondary';

    /** @var int */
    protected $counter = 0;

    /** @var string */
    protected $description;

    /** @var string */
    protected $icon;

    /** @var string */
    protected $js_function = '';

    /** @var string */
    protected $label;

    /** @var string */
    protected $modal_id;

    public function label(): string
    {
        return $this->label;
    }

    public function linkModal(Modal $modal): self
    {
        // si el modal no tiene padre, no lo podemos enlazar
        if (empty($modal->parentId())) {
            throw new Exception('Add the modal to a section or tab before linking it to a button.');
        }

        $this->modal_id = $modal->id();

        return $this;
    }

    public function onClick(string $function, int $position = 0): self
    {
        $event = $this->addEvent('click', $function, $position);

        return $this->onClickJs('send_ui_event(\'' . $event->name() . '\')');
    }

    public function onClickJs(string $function): self
    {
        $this->js_function = $function;

        return $this;
    }

    public function render(string $context = ''): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . ' mr-1"></i> ' : '';
        $counter = empty($this->counter) ? '' : '<span class="badge badge-light ml-1">' . $this->counter . '</span> ';

        $attributes = '';
        if ($this->modal_id) {
            $attributes = ' data-toggle="modal" data-target="#' . $this->modal_id . '"';
        } else if ($this->js_function) {
            $attributes = ' onclick="' . $this->js_function . '"';
        }

        return '<button type="button" class="btn btn-' . $this->color . ' mr-1" id="'
            . $this->id() . '" title="' . $this->description . '"' . $attributes . '>'
            . $icon . $this->label . $counter
            . '</button>';
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function setCounter(int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }

    public function setDescription(string $description, array $params = []): self
    {
        $this->description = Tools::lang()->trans($description, $params);

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setLabel(string $label, array $params = []): self
    {
        $this->label = Tools::lang()->trans($label, $params);

        return $this;
    }
}
