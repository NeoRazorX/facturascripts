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

namespace FacturaScripts\Core\UI\Tab;

use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Core\Tools;

class TabFiles extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-folder-open';

        // añadimos datos de prueba
        foreach (range(1, rand(2, 12)) as $i) {
            $this->data[] = [
                'id' => $i,
                'name' => 'Archivo ' . $i,
                'size' => rand(100, 1000000),
                'date' => Tools::dateTime('-' . rand(1, 100) . ' days'),
                'user' => 'Usuario ' . $i,
            ];

            $this->counter++;
        }
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(string $context = ''): string
    {
        return '<div class="container-fluid mt-4">'
            . '<div class="row">'
            . '<div class="col-12">'
            . '<div class="btn btn-lg btn-block btn-secondary">'
            . '<i class="fas fa-file-upload mr-1"></i> ' . Tools::lang()->trans('upload-files')
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="form-row mt-4">'
            . $this->renderFileCards()
            . '</div>'
            . '</div>';
    }

    protected function renderFileCards(): string
    {
        $html = '';

        foreach ($this->data as $item) {
            $html .= '<div class="col-sm-3">'
                . '<div class="card shadow mb-2">'
                . '<div class="card-body">'
                . '<h3 class="card-title">' . $item['name'] . '</h3>'
                . '<p class="card-text">' . Tools::bytes($item['size']) . '</p>'
                . '<p class="card-text">' . Tools::date($item['date']) . '</p>'
                . '<p class="card-text">' . $item['user'] . '</p>'
                . '</div>'
                . '</div>'
                . '</div>';
        }

        return $html;
    }
}
