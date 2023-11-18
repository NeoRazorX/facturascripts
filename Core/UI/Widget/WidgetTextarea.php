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

namespace FacturaScripts\Core\UI\Widget;

use FacturaScripts\Core\Template\UI\Widget;
use FacturaScripts\Core\Tools;

class WidgetTextarea extends Widget
{
    /** @var string */
    protected $placeholder = '';

    /** @var int */
    protected $rows = 2;

    public function render(string $context = ''): string
    {
        switch ($context) {
            default:
                return $this->renderInput();

            case 'td':
                $maxLength = 60;
                if (strlen($this->value) > $maxLength) {
                    return '<td class="text-' . $this->align . '" title="' . $this->value . '">'
                        . Tools::textBreak($this->value, 60) . '</td>';
                }
                return '<td class="text-' . $this->align . '">' . $this->value . '</td>';

            case 'th':
                return '<th class="text-' . $this->align . '">' . $this->label . '</th>';
        }
    }

    public function setPlaceholder(string $placeholder, array $params = []): self
    {
        $this->placeholder = Tools::lang()->trans($placeholder, $params);

        return $this;
    }

    public function setRows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    protected function renderInput(): string
    {
        $attributes = $this->required ? ' required' : '';

        return '<div class="form-group">'
            . '<label for="' . $this->id() . '">' . $this->label . '</label>'
            . '<textarea name="' . $this->field . '" class="form-control" id="' . $this->id() . '" placeholder="'
            . $this->placeholder . '" rows="' . $this->rows . '"' . $attributes . '>'
            . $this->value . '</textarea>'
            . '</div>';
    }
}
