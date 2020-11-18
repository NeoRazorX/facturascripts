<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of GroupItem
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class GroupItem extends VisualItem
{

    /**
     *
     * @var string
     */
    public $class;

    /**
     * Define the columns that the group includes
     *
     * @var ColumnItem[]
     */
    public $columns = [];

    /**
     * Icon used as the value or accompaining the group title
     *
     * @var string
     */
    public $icon;

    /**
     *
     * @var int
     */
    public $numcolumns;

    /**
     *
     * @var int
     */
    public $order;

    /**
     *
     * @var string
     */
    public $title;

    /**
     *
     * @var string
     */
    public $valign;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->class = isset($data['class']) ? $data['class'] : '';
        $this->icon = isset($data['icon']) ? $data['icon'] : '';
        $this->numcolumns = isset($data['numcolumns']) ? (int) $data['numcolumns'] : 0;
        $this->order = isset($data['order']) ? (int) $data['order'] : 0;
        $this->title = isset($data['title']) ? $data['title'] : '';
        $this->valign = isset($data['valign']) ? $data['valign'] : '';
        $this->loadColumns($data['children']);
    }

    /**
     *
     * @param object $model
     * @param bool   $forceReadOnly
     * @param bool   $onlyField
     *
     * @return string
     */
    public function edit($model, $forceReadOnly = false, $onlyField = false)
    {
        $divClass = $this->numcolumns > 0 ? $this->css('col-md-') . $this->numcolumns : $this->css('col');
        $divId = empty($this->id) ? '' : ' id="' . $this->id . '"';
        $rowClass = $this->css('form-row') . ' ' . $this->valign();

        $html = '<div' . $divId . ' class="' . $divClass . '"><div class="' . $rowClass . '">';
        if ($this->title) {
            $html .= $this->legend();
        }

        foreach ($this->columns as $col) {
            if ($forceReadOnly) {
                $col->widget->readonly = 'true';
            }
            $html .= $col->edit($model, $onlyField);
        }

        return $html . '</div></div>';
    }

    /**
     *
     * @param object $model
     * @param string $viewName
     *
     * @return string
     */
    public function modal($model, $viewName)
    {
        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        $html = '<form id="formModal' . $this->getUniqueId() . '" method="post" enctype="multipart/form-data">'
            . '<input type="hidden" name="activetab" value="' . $viewName . '"/>'
            . '<div class="modal" id="modal' . $this->name . '" tabindex="-1" role="dialog">'
            . '<div class="modal-dialog ' . $this->class . '" role="document">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . $icon . static::$i18n->trans($this->title) . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="' . $this->css('row') . '">';

        foreach ($this->columns as $col) {
            $html .= $col->edit($model);
        }

        $html .= '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
            . static::$i18n->trans('cancel')
            . '</button>'
            . '<button type="submit" name="action" value="' . $this->name . '" class="btn btn-primary">'
            . static::$i18n->trans('accept')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</form>';

        return $html;
    }

    /**
     *
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        foreach ($this->columns as $col) {
            $col->processFormData($model, $request);
        }
    }

    /**
     * Sorts the columns
     *
     * @param ColumnItem $column1
     * @param ColumnItem $column2
     *
     * @return int
     */
    public static function sortColumns($column1, $column2)
    {
        if ($column1->order === $column2->order) {
            return 0;
        }

        return $column1->order < $column2->order ? -1 : 1;
    }

    /**
     *
     * @return string
     */
    protected function legend()
    {
        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        return '<legend class="text-info mt-3">' . $icon . static::$i18n->trans($this->title) . '</legend>';
    }

    /**
     *
     * @param array $children
     */
    protected function loadColumns($children)
    {
        $columnClass = VisualItemLoadEngine::getNamespace() . 'ColumnItem';
        foreach ($children as $child) {
            if ($child['tag'] !== 'column') {
                continue;
            }

            $columnItem = new $columnClass($child);
            $this->columns[$columnItem->name] = $columnItem;
        }

        \uasort($this->columns, ['self', 'sortColumns']);
    }

    /**
     * 
     * @return string
     */
    protected function valign()
    {
        switch ($this->valign) {
            case 'bottom':
                return 'align-items-end';

            case 'center':
                return 'align-items-center';

            default:
                return '';
        }
    }
}
