<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\AttachedFile;

class WidgetLibrary extends BaseWidget
{
    /** @var string */
    public $accept;

    /** @param array $data */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->accept = $data['accept'] ?? '';
    }

    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);

        // obtenemos un nuevo ID cada vez
        $this->id = $this->getUniqueId();

        $descriptionHtml = empty($description) ?
            '' :
            '<small class="form-text text-muted">' . Tools::trans($description) . '</small>';
        $label = Tools::trans($title);
        $labelHtml = $this->onclickHtml($label, $titleurl);
        $icon = empty($this->icon) ? 'fa-solid fa-file-upload' : $this->icon;

        $file = new AttachedFile();
        $file->load($this->value);

        if ($this->readonly()) {
            return '<div class="mb-3">'
                . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $this->value . '">'
                . $labelHtml
                . '<a href="' . $file->url() . '" class="btn w-100 btn-outline-secondary">'
                . '<i class="' . $icon . ' fa-fw"></i> ' . ($file->filename ? $file->shortFileName() : Tools::trans('select'))
                . '</a>'
                . $descriptionHtml
                . '</div>';
        }

        return '<div class="mb-3" id="' . $this->id . '">'
            . '<input type="hidden" class="input-hidden" name="' . $this->fieldname . '" value="' . $this->value . '">'
            . $labelHtml
            . '<a href="#" class="btn w-100 btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal_' . $this->id . '">'
            . '<i class="' . $icon . ' fa-fw"></i> '
            . '<span class="file-name">'
            . ($file->filename ? $file->shortFileName() : Tools::trans('select'))
            . '</span>'
            . '</a>'
            . $descriptionHtml
            . '</div>'
            . $this->renderModal($icon, $label)
            . "<script>\n"
            . "widgetLibrarySelectStr = '" . Tools::trans('select') . "';\n"
            . "</script>\n";
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname, '');
        $model->{$this->fieldname} = ('' === $value) ? null : $value;
    }

    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = $this->combineClasses($this->tableCellClass('text-' . $display), $this->class);

        $file = new AttachedFile();
        $file->load($this->value);

        return '<td class="' . $class . '">' . $this->onclickHtml($file->shortFileName()) . '</td>';
    }

    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addJs($route . '/Dinamic/Assets/JS/WidgetLibrary.js?v=' . Tools::date());
    }

    /**
     * @param string $query
     * @param string $sort
     * @return AttachedFile[]
     */
    public function files(string $query = '', string $sort = 'date-desc'): array
    {
        $list = [];

        $model = new AttachedFile();

        // añadimos el archivo seleccionado
        if (!empty($this->value) && $model->load($this->value)) {
            $list[] = $model;
        }

        // si tenemos el campo accept, filtramos por tipo de archivo
        $where = [];
        if (!empty($this->accept)) {
            $sub = [];
            foreach (explode(',', $this->accept) as $type) {
                $sub[] = Where::orLike('filename', '%' . $type);
            }
            $where[] = Where::sub($sub);
        }

        if ($query) {
            $where[] = Where::like('filename', $query);
        }

        switch ($sort) {
            default:
                $orderBy = ['date' => 'DESC', 'hour' => 'DESC'];
                break;

            case 'date-asc':
                $orderBy = ['date' => 'ASC', 'hour' => 'ASC'];
                break;
        }

        foreach ($model->all($where, $orderBy) as $file) {
            // excluimos el archivo seleccionado
            if ($file->idfile === $model->idfile) {
                continue;
            }

            $list[] = $file;
        }

        return $list;
    }

    public function renderFileList(array $files = [], ?int $selected_value = null, ?string $id = null): string
    {
        $html = '';

        $files = empty($files) ? $this->files() : $files;
        foreach ($files as $file) {
            $cssCard = $file->idfile == $this->value || $file->idfile == $selected_value
                ? ' border-primary'
                : '';

            $html .= '<div class="col-6">'
                . '<div class="file card ' . $cssCard . ' shadow-sm mb-2" data-idfile="' . $file->idfile . '">'
                . '<div class="card-body p-2">';

            $info = '<div class="small text-muted">'
                . '<span class="text-nowrap">' . Tools::bytes($file->size) . '</span>'
                . '<br><span class="text-nowrap">' . $file->date . ' ' . $file->hour . '</span>'
                . '<a href="' . $file->url() . '" target="_blank" class="ms-2">'
                . '<i class="fa-solid fa-up-right-from-square"></i>'
                . '</a>'
                . '</div>';

            $js = "widgetLibrarySelect('" . $id . "', '" . $file->idfile . "', '" . $file->shortFileName() . "');";

            if ($file->isImage()) {
                $html .= '<div class="d-flex">'
                    . '<img loading="lazy" src="' . $file->url('download-permanent') . '" class="me-3 flex-shrink-0" alt="' . $file->filename
                    . '" width="64" height="64" style="object-fit: cover; cursor: pointer;" onclick="' . $js . '" title="' . Tools::trans('select') . '">'
                    . '<div class="flex-grow-1 min-width-0">'
                    . '<h6 class="text-truncate mb-1" style="cursor: pointer;" onclick="' . $js . '" title="' . Tools::trans('select') . '">' . $file->filename . '</h6>'
                    . $info
                    . '</div>'
                    . '</div>';
            } else {
                $html .= '<h6 class="card-title text-truncate mb-1" style="cursor: pointer;" onclick="' . $js . '" title="'
                    . Tools::trans('select') . '">' . $file->filename . '</h6>' . $info;
            }

            $html .= '</div>'
                . '</div>'
                . '</div>';
        }

        return $html;
    }

    public function uploadFile(UploadedFile $uploadFile): AttachedFile
    {
        if (false === $uploadFile->isValid()) {
            return new AttachedFile();
        }

        // exclude php files
        if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
            return new AttachedFile();
        }

        // check if the file already exists
        $destiny = FS_FOLDER . '/MyFiles/';
        $destinyName = $uploadFile->getClientOriginalName();
        if (file_exists($destiny . $destinyName)) {
            $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
        }

        // move the file to the MyFiles folder
        if ($uploadFile->move($destiny, $destinyName)) {
            $file = new AttachedFile();
            $file->path = $destinyName;
            if ($file->save()) {
                return $file;
            }
        }

        return new AttachedFile();
    }

    protected function renderQueryFilter(): string
    {
        return '<div class="input-group mb-2">'
            . '<input type="text" id="modal_' . $this->id . '_q" class="form-control" placeholder="'
            . Tools::trans('search') . '" onkeydown="widgetLibrarySearchKp(\'' . $this->id . '\', event);">'
            . '<button type="button" class="btn btn-primary" onclick="widgetLibrarySearch(\'' . $this->id . '\');">'
            . '<i class="fa-solid fa-search"></i>'
            . '</button>'
            . '</div>';
    }

    protected function renderModal(string $icon, string $label): string
    {
        return '<div class="modal fade" id="modal_' . $this->id . '" tabindex="-1" aria-labelledby="modal_'
            . $this->id . '_label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-scrollable modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="modal_' . $this->id . '_label">'
            . '<i class="' . $icon . ' me-1"></i> ' . $label
            . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body bg-light">'
            . '<div class="row g-3">'
            . '<div class="col-6">' . $this->renderQueryFilter() . '</div>'
            . '<div class="col-6">' . $this->renderSortFilter() . '</div>'
            . '</div>'
            . '<div id="list_' . $this->id . '" class="row g-3 pt-3">'
            . $this->renderFileList([], $this->value, $this->id)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer flex-column align-items-stretch p-3">'
            . '<div class="mb-2">'
            . '<label for="modal_' . $this->id . '_f" class="form-label small text-muted">'
            . Tools::trans('select-file-to-add')
            . '</label>'
            . '<input type="file" class="form-control" id="modal_' . $this->id . '_f" accept="' . $this->accept
            . '" onchange="widgetLibraryUpload(\'' . $this->id . '\', this.files[0]);">'
            . '</div>'
            . $this->renderSelectNoneBtn()
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderSelectNoneBtn(): string
    {
        if ($this->required) {
            return '';
        }

        return '<button type="button" class="btn btn-secondary w-100" onclick="widgetLibrarySelect(\'' . $this->id . '\', \'\', \'' . Tools::trans('select') . '\'); return false;">'
            . '<i class="fa-solid fa-times me-1"></i>' . Tools::trans('none')
            . '</button>';
    }

    protected function renderSortFilter(): string
    {
        return '<select class="form-select mb-2" id="modal_' . $this->id . '_s" onchange="widgetLibrarySearch(\'' . $this->id . '\');">'
            . '<option value="date-asc">' . Tools::trans('sort-by-date-asc') . '</option>'
            . '<option value="date-desc" selected>' . Tools::trans('sort-by-date-desc') . '</option>'
            . '</select>';
    }
}
