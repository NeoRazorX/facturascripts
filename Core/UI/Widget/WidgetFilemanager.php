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
use FacturaScripts\Dinamic\Model\AttachedFile;

class WidgetFilemanager extends Widget
{
    public function render(string $context = ''): string
    {
        switch ($context) {
            default:
                return $this->renderInput();

            case 'td':
                return '<td class="text-' . $this->align . '">' . $this->value . '</td>';

            case 'th':
                return '<th class="text-' . $this->align . '">' . $this->label . '</th>';
        }
    }

    /** @return AttachedFile[] */
    protected function files(): array
    {
        $attachedFile = new AttachedFile();
        $orderBy = ['date' => 'DESC', 'hour' => 'DESC'];
        return $attachedFile->all([], $orderBy);
    }

    protected function renderInput(): string
    {
        return '<div class="form-group">'
            . '<label for="' . $this->id() . '">' . $this->label . '</label>'
            . '<button type="button" id="' . $this->id() . '" class="btn btn-secondary btn-block"'
            . ' data-toggle="modal" data-target="#modal_' . $this->id() . '">'
            . '<i class="fas fa-folder-open mr-1"></i> ' . $this->label . '</button>'
            . '</div>'
            . $this->renderModal();
    }

    protected function renderFileList(): string
    {
        $html = '<div class="form-row">';

        foreach ($this->files() as $file) {
            $html .= '<div class="col-6">'
                . '<div class="card shadow-sm mb-2">'
                . '<div class="card-body p-2">';

            $info = '<p class="card-text text-muted small">'
                . Tools::bytes($file->size) . ', ' . $file->date . ' ' . $file->hour
                . '<a href="' . $file->url() . '" target="_blank" class="ml-2">'
                . '<i class="fa-solid fa-up-right-from-square"></i>'
                . '</a>'
                . '</p>';

            if ($file->isImage()) {
                $html .= '<div class="media">'
                    . '<img src="' . $file->url('download') . '" class="mr-3" alt="' . $file->filename . '" width="64">'
                    . '<div class="media-body">'
                    . '<h5 class="mt-0">' . $file->filename . '</h5>'
                    . $info
                    . '</div>'
                    . '</div>';
            } else {
                $html .= '<h5 class="card-title mb-0">' . $file->filename . '</h5>' . $info;
            }

            $html .= '</div>'
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
            . '<h5 class="modal-title" id="modal_' . $this->id() . '_label">'
            . '<i class="fas fa-folder-open mr-1"></i> ' . $this->label
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . '<div class="col-4">'
            . '<input type="text" class="form-control mb-2" placeholder="' . Tools::lang()->trans('search') . '">'
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
