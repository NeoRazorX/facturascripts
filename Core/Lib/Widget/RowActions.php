<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;

/**
 * Description of RowActions
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowActions extends VisualItem
{

    /**
     * @var array
     */
    protected $children;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->children = $data['children'] ?? [];
    }

    /**
     * Adds a new button.
     *
     * @param array $btnArray
     */
    public function addButton(array $btnArray)
    {
        if (!isset($btnArray['tag'])) {
            $btnArray['tag'] = 'button';
        }

        $this->children[] = $btnArray;
    }

    public function render(bool $small = false, string $viewName = ''): string
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] !== 'button') {
                continue;
            }

            $button = new RowButton($child);
            $html .= $button->render($small, $viewName);
        }

        return $html;
    }

    public function renderTop(): string
    {
        $html = '';
        foreach ($this->children as $child) {
            if ($child['tag'] !== 'button') {
                continue;
            }

            $button = new RowButton($child);
            $html .= $button->renderTop();
        }

        return $html;
    }
}
