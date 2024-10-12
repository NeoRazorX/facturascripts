<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Description of ColumnItem
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class ColumnItem extends VisualItem
{
    /**
     * Additional text that explains the field to the user
     *
     * @var string
     */
    public $description;

    /**
     * State and alignment of the display configuration
     * (left|right|center|none)
     *
     * @var string
     */
    public $display;

    /**
     * Indicates the security level of the column
     *
     * @var int
     */
    public $level;

    /**
     * @var int
     */
    public $numcolumns;

    /**
     * @var int
     */
    public $order;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $titleurl;

    /**
     * Field display object configuration
     *
     * @var BaseWidget
     */
    public $widget;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->description = $data['description'] ?? '';

        $this->display = $data['display'] ?? 'start';
        switch ($this->display){
            case 'left':
                $this->display = 'start';
                break;
            case 'right':
                $this->display = 'end';
                break;
        }

        $this->level = isset($data['level']) ? (int)$data['level'] : 0;
        $this->numcolumns = isset($data['numcolumns']) ? (int)$data['numcolumns'] : 0;
        $this->order = isset($data['order']) ? (int)$data['order'] : 0;
        $this->title = $data['title'] ?? $this->name;
        $this->titleurl = $data['titleurl'] ?? '';
        $this->loadWidget($data['children']);
    }

    /**
     * @param object $model
     * @param bool $onlyField
     *
     * @return string
     */
    public function edit($model, bool $onlyField = false): string
    {
        if ($this->hidden()) {
            return $this->widget->inputHidden($model);
        }

        // para los checkbox forzamos el col-sm-auto
        $colAuto = $this->widget->getType() === 'checkbox' ? 'col-sm-auto' : 'col-sm';

        $divClass = $this->numcolumns > 0 ? $this->css('col-md-') . $this->numcolumns : $this->css($colAuto);
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';
        $editHtml = $onlyField ? $this->widget->edit($model) : $this->widget->edit($model, $this->title, $this->description, $this->titleurl);
        return '<div' . $divID . ' class="' . $divClass . '">'
            . $editHtml
            . '</div>';
    }

    /**
     * Returns CSS percentage width
     *
     * @return string
     */
    public function htmlWidth(): string
    {
        if ($this->numcolumns < 1 || $this->numcolumns > 11) {
            return '100%';
        }

        return round((100.00 / 12 * $this->numcolumns), 5) . '%';
    }

    public function hidden(): bool
    {
        if ($this->display === 'none') {
            return true;
        }

        return $this->getLevel() < $this->level;
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $this->widget->processFormData($model, $request);
    }

    /**
     * @param object $model
     *
     * @return string
     */
    public function tableCell($model): string
    {
        return $this->hidden() ? '' : $this->widget->tableCell($model, $this->display);
    }

    public function tableHeader(ListView $currentView): string
    {
        if ($this->hidden()) {
            return '';
        }

        $content = '';

        // recorremos las opciones de ordenación
        $orderKey = 0;
        $orderMode = '';
        foreach ($currentView->orderOptions as $key => $orderBy) {
            // si no es esta columna, pasamos a la siguiente
            if ($orderBy['fields'][0] !== $this->widget->fieldname) {
                continue;
            }

            // si está seleccionada, guardamos el modo de ordenación
            if ($currentView->orderKey == $key) {
                $orderMode = $orderBy['type'];
                continue;
            }

            // si no está seleccionada, guardamos la clave de ordenación
            $orderMode = empty($orderMode) ? '-' : $orderMode;
            $orderKey = $key;
        }

        switch ($orderMode) {
            case '-':
                $content .= '<a href="#" onclick="listViewSetOrder(\'' . $currentView->getViewName() . '\', \''
                    . $orderKey . '\');" title="' . Tools::lang()->trans('sort-by-column') . '"><i class="fa-solid fa-sort"></i> '
                    . Tools::lang()->trans($this->title) . '</a>';
                break;

            case 'ASC':
                $content .= '<a href="#" onclick="listViewSetOrder(\'' . $currentView->getViewName() . '\', \''
                    . $orderKey . '\');" title="' . Tools::lang()->trans('sorted-asc') . '"><i class="fa-solid fa-angles-up"></i> '
                    . Tools::lang()->trans($this->title) . '</a>';
                break;

            case 'DESC':
                $content .= '<a href="#" onclick="listViewSetOrder(\'' . $currentView->getViewName() . '\', \''
                    . $orderKey . '\');" title="' . Tools::lang()->trans('sorted-desc') . '"><i class="fa-solid fa-angles-down"></i> '
                    . Tools::lang()->trans($this->title) . '</a>';
                break;

            default:
                $content .= Tools::lang()->trans($this->title);
                break;
        }

        // si tiene url, la añadimos como otro enlace
        if (!empty($this->titleurl)) {
            $content .= ' <a href="' . $this->titleurl . '"><i class="fa-solid fa-circle-question"></i></a>';
        }

        return '<th class="text-' . $this->display . '">' . $content . '</th>';
    }

    protected function loadWidget(array $children): void
    {
        foreach ($children as $child) {
            if ($child['tag'] !== 'widget') {
                continue;
            }

            $className = VisualItemLoadEngine::getNamespace() . 'Widget' . ucfirst($child['type']);
            if (class_exists($className)) {
                $this->widget = new $className($child);
                break;
            }

            $defaultWidget = VisualItemLoadEngine::getNamespace() . 'WidgetText';
            $this->widget = new $defaultWidget($child);
            break;
        }
    }
}
