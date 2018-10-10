<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of ColumnItem
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
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
     * @var integer
     */
    public $level;

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
    public $titleurl;

    /**
     * Field display object configuration
     *
     * @var BaseWidget
     */
    public $widget;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->description = isset($data['description']) ? $data['description'] : '';
        $this->display = isset($data['display']) ? $data['display'] : 'left';
        $this->level = isset($data['level']) ? (int) $data['level'] : 1;
        $this->numcolumns = isset($data['numcolumns']) ? (int) $data['numcolumns'] : 0;
        $this->order = isset($data['order']) ? (int) $data['order'] : 0;
        $this->title = isset($data['title']) ? $data['title'] : $this->name;
        $this->titleurl = isset($data['titleurl']) ? $data['titleurl'] : '';
        $this->loadWidget($data['children']);
    }

    /**
     *
     * @param object $model
     * @param int    $level
     *
     * @return string
     */
    public function edit($model, $level = 0)
    {
        if ($this->hiddeTo($level)) {
            return $this->widget->inputHidden($model);
        }

        $divClass = ($this->numcolumns > 0) ? 'col-md-' . $this->numcolumns : 'col';
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';
        return '<div' . $divID . ' class="' . $divClass . '">' . $this->widget->edit($model, $this->title, $this->description, $this->titleurl) . '</div>';
    }

    /**
     *
     * @param int $level
     *
     * @return boolean
     */
    public function hiddeTo($level)
    {
        if ($this->display === 'none') {
            return true;
        }

        return $level < $this->level;
    }

    /**
     * 
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $this->widget->processFormData($model, $request);
    }

    /**
     *
     * @param object $model
     * @param int    $level
     *
     * @return string
     */
    public function tableCell($model, $level = 0)
    {
        return $this->hiddeTo($level) ? '' : $this->widget->tableCell($model, $this->display);
    }

    /**
     *
     * @param level $level
     *
     * @return string
     */
    public function tableHeader($level = 0)
    {
        if ($this->hiddeTo($level)) {
            return '';
        }

        if (empty($this->titleurl)) {
            return '<th class="text-' . $this->display . '">' . static::$i18n->trans($this->title) . '</th>';
        }

        return '<th class="text-' . $this->display . '">'
            . '<a href="' . $this->titleurl . '">' . static::$i18n->trans($this->title) . '</a>'
            . '</th>';
    }

    /**
     *
     * @param array $children
     */
    protected function loadWidget($children)
    {
        foreach ($children as $child) {
            if ($child['tag'] !== 'widget') {
                continue;
            }

            $className = '\\FacturaScripts\\Dinamic\\Lib\\Widget\\Widget' . ucfirst($child['type']);
            if (class_exists($className)) {
                $this->widget = new $className($child);
            } else {
                $this->widget = new WidgetText($child);
            }

            break;
        }
    }
}
