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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\AttachedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class WidgetLibrary extends BaseWidget
{
    /** @var string */
    public $accept;

    /** @param array $data */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->accept = $data['accept'] ?? '';

        if (empty($this->id)) {
            $this->id = $this->getUniqueId();
        }
    }

    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ?
            '' :
            '<small class="form-text text-muted">' . Tools::lang()->trans($description) . '</small>';
        $label = Tools::lang()->trans($title);
        $labelHtml = $this->onclickHtml($label, $titleurl);
        $icon = empty($this->icon) ? 'fas fa-file-upload' : $this->icon;

        $file = new AttachedFile();
        $file->loadFromCode($this->value);

        if ($this->readonly()) {
            return '<div class="form-group mb-2">'
                . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $this->value . '">'
                . $labelHtml
                . '<a href="' . $file->url() . '" class="btn btn-block btn-outline-secondary">'
                . '<i class="' . $icon . ' fa-fw"></i> ' . ($file->filename ? $file->shortFileName() : Tools::lang()->trans('select'))
                . '</a>'
                . $descriptionHtml
                . '</div>';
        }

        return '<div class="form-group mb-2">'
            . '<input type="hidden" id="' . $this->id . '" name="' . $this->fieldname . '" value="' . $this->value . '">'
            . $labelHtml
            . '<a href="#" class="btn btn-block btn-outline-secondary" data-toggle="modal" data-target="#modal_' . $this->id . '">'
            . '<i class="' . $icon . ' fa-fw"></i> ' . ($file->filename ? $file->shortFileName() : Tools::lang()->trans('select'))
            . '</a>'
            . $descriptionHtml
            . '</div>'
            . $this->renderModal($icon, $label)
            . "<script>\n"
            . "widgetLibrarySelectStr = '" . Tools::lang()->trans('select') . "';\n"
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
        $file->loadFromCode($this->value);

        return '<td class="' . $class . '">' . $this->onclickHtml($file->shortFileName()) . '</td>';
    }

    protected function assets()
    {
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/WidgetLibrary.js');
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
        if (!empty($this->value) && $model->loadFromCode($this->value)) {
            $list[] = $model;
        }

        // si tenemos el campo accept, filtramos por tipo de archivo
        $where = [];
        if (!empty($this->accept)) {
            foreach (explode(',', $this->accept) as $type) {
                $where[] = new DataBaseWhere('filename', '%' . $type, 'LIKE', 'OR');
            }
        }

        if ($query) {
            $where[] = new DataBaseWhere('filename', $query, 'LIKE', 'AND');
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

    protected function renderFileList(): string
    {
        $html = '<div id="list_' . $this->id . '" class="form-row pt-3">';

        foreach ($this->files() as $file) {
            $cssCard = $file->idfile == $this->value ? ' border-primary' : '';

            $html .= '<div class="col-6">'
                . '<div class="card ' . $cssCard . ' shadow-sm mb-2">'
                . '<div class="card-body p-2">';

            $info = '<p class="card-text small">'
                . Tools::bytes($file->size) . ', ' . $file->date . ' ' . $file->hour
                . '<a href="' . $file->url() . '" target="_blank" class="ml-2">'
                . '<i class="fa-solid fa-up-right-from-square"></i>'
                . '</a>'
                . '</p>';

            $js = "widgetLibrarySelect('" . $this->id . "', '" . $file->idfile . "');";

            if ($file->isImage()) {
                $html .= '<div class="media">'
                    . '<img src="' . $file->url('download-permanent') . '" class="mr-3" alt="' . $file->filename
                    . '" width="64" type="button" onclick="' . $js . '" title="' . Tools::lang()->trans('select') . '">'
                    . '<div class="media-body">'
                    . '<h5 class="text-break mt-0">' . $file->filename . '</h5>'
                    . $info
                    . '</div>'
                    . '</div>';
            } else {
                $html .= '<h5 class="card-title text-break mb-0" type="button" onclick="' . $js . '" title="'
                    . Tools::lang()->trans('select') . '">' . $file->filename . '</h5>' . $info;
            }

            $html .= '</div>'
                . '</div>'
                . '</div>';
        }

        return $html . '</div>';
    }

    protected function renderQueryFilter(): string
    {
        return '<div class="input-group mb-2">'
            . '<input type="text" id="modal_' . $this->id . '_q" class="form-control" placeholder="'
            . Tools::lang()->trans('search') . '" onkeydown="widgetLibrarySearchKp(\'' . $this->id . '\', event);">'
            . '<div class="input-group-append">'
            . '<button type="button" class="btn btn-primary" onclick="widgetLibrarySearch(\'' . $this->id . '\');">'
            . '<i class="fas fa-search"></i>'
            . '</button>'
            . '</div>'
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
            . '<i class="' . $icon . ' mr-1"></i> ' . $label
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body bg-light">'
            . '<div class="form-row">'
            . '<div class="col-12">'
            . '<div class="custom-file mb-2">'
            . '<input type="file" class="custom-file-input" id="modal_' . $this->id . '_f" accept="' . $this->accept
            . '" onchange="widgetLibraryUpload(\'' . $this->id . '\', this.files[0]);">'
            . '<label class="custom-file-label" for="modal_' . $this->id . '_f" data-browse="' . Tools::lang()->trans('select')
            . '">' . Tools::lang()->trans('add-file') . '</label>'
            . '</div>'
            . '</div>'
            . '<div class="col-6">' . $this->renderQueryFilter() . '</div>'
            . '<div class="col-6">' . $this->renderSortFilter() . '</div>'
            . '</div>'
            . $this->renderFileList()
            . '</div>'
            . '<div class="modal-footer p-2">' . $this->renderSelectNoneBtn() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderSelectNoneBtn(): string
    {
        if ($this->required) {
            return '';
        }

        return '<a href="#" class="btn btn-block btn-secondary" onclick="widgetLibrarySelect(\'' . $this->id . '\', \'\');">'
            . '<i class="fas fa-times mr-1"></i>' . Tools::lang()->trans('none')
            . '</a>';
    }

    protected function renderSortFilter(): string
    {
        return '<select class="form-control mb-2" id="modal_' . $this->id . '_s" onchange="widgetLibrarySearch(\'' . $this->id . '\');">'
            . '<option value="date-asc">' . Tools::lang()->trans('sort-by-date-asc') . '</option>'
            . '<option value="date-desc" selected>' . Tools::lang()->trans('sort-by-date-desc') . '</option>'
            . '</select>';
    }
}
