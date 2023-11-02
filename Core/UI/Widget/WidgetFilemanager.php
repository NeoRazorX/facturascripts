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

class WidgetFilemanager extends Widget
{
    /** @var array */
    protected $files = [];

    public function __construct(string $name, ?string $field = null, ?string $label = null)
    {
        parent::__construct($name, $field, $label);

        // cargamos algunos archivos de prueba
        foreach (range(1, rand(3, 50)) as $i) {
            $this->files[] = [
                'name' => 'Archivo ' . $i,
                'type' => 'file',
                'size' => rand(1000, 1000000),
                'date' => date('Y-m-d H:i:s', rand(0, time())),
            ];
        }
    }

    public function render(string $context = ''): string
    {
        return '<div class="form-group">'
            . '<label for="' . $this->id() . '">' . $this->label('true') . '</label>'
            . '<button type="button" id="' . $this->id() . '" class="btn btn-secondary btn-block"'
            . ' data-toggle="modal" data-target="#modal_' . $this->id() . '">'
            . '<i class="fas fa-folder-open mr-1"></i> ' . $this->label(true) . '</button>'
            . '</div>'
            . $this->renderModal();
    }

    protected function renderFileList(): string
    {
        $html = '<div class="form-row">';

        foreach ($this->files as $file) {
            $html .= '<div class="col-6">'
                . '<div class="card shadow-sm mb-2">'
                . '<div class="card-body p-2">'
                . '<h5 class="card-title mb-0">' . $file['name'] . '</h5>'
                . '<p class="card-text">'
                . '<small class="text-muted">' . Tools::bytes($file['size'])
                . ', ' . Tools::dateTime($file['date']) . '</small>'
                . '</p>'
                . '</div>'
                . '</div>'
                . '</div>';
        }

        return $html . '</div>';
    }

    protected function renderModal(): string
    {
        return '<div class="modal fade" id="modal_' . $this->id() . '" tabindex="-1" aria-labelledby="modal_'
            . $this->id() . '_label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="modal_' . $this->id() . '_label"><i class="fas fa-folder-open mr-1"></i> '
            . $this->label(true) . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . '<div class="col-4">'
            . '<input type="text" class="form-control mb-2" placeholder="Search">'
            . '</div>'
            . '<div class="col-4">'
            . '<select class="form-control mb-2">'
            . '<option>All types</option>'
            . '<option>------</option>'
            . '<option>Type 1</option>'
            . '<option>Type 2</option>'
            . '<option>Type 3</option>'
            . '</select>'
            . '</div>'
            . '<div class="col-4">'
            . '<select class="form-control mb-2">'
            . '<option>Sort by 1</option>'
            . '<option>Sort by 2</option>'
            . '<option>Sort by 3</option>'
            . '</select>'
            . '</div>'
            . '</div>'
            . $this->renderFileList()
            . '</div>'
            . '<div class="modal-footer">'
            . '<input type="file" class="form-control-file">'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
