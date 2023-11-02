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
use FacturaScripts\Core\UI\Widget\WidgetFilemanager;
use FacturaScripts\Dinamic\Lib\AssetManager;

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
        $addFileWidget = new WidgetFilemanager('add_file');
        $addFileWidget->setParent($this)->setLabel('add-file');

        return '<div class="container-fluid mt-3">'
            . '<div class="row">'
            . '<div class="col-12">' . $addFileWidget->render() . '</div>'
            . '</div>'
            . '<div class="form-row mt-3">'
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
                . '<div class="card-body p-2">'
                . '<div class="form-row">'
                . '<div class="col">'
                . '<h3 class="card-title">' . $item['name'] . '</h3>'
                . '</div>'
                . '<div class="col-auto">'
                . '<div class="dropdown">'
                . '<button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
                . '<i class="fas fa-ellipsis-v"></i>'
                . '</button>'
                . '<div class="dropdown-menu dropdown-menu-right">'
                . '<a class="dropdown-item" href="#"><i class="fas fa-download fa-fw mr-1"></i> Descargar</a>'
                . '<div class="dropdown-divider"></div>'
                . '<a class="dropdown-item" href="#"><i class="fas fa-unlink fa-fw mr-1"></i> Desvincular</a>'
                . '<a class="dropdown-item" href="#"><i class="fas fa-trash fa-fw mr-1"></i> Eliminar</a>'
                . '</div>'
                . '</div>'
                . '</div>'
                . '</div>'
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
