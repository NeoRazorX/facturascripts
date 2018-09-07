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

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\User;

/**
 * Description of ColumnItem
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class ColumnItem
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
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Indicates the security level of the column
     *
     * @var integer
     */
    public $level;

    /**
     *
     * @var string
     */
    public $name;

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
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->description = isset($data['description']) ? $data['description'] : '';
        $this->display = isset($data['display']) ? $data['display'] : 'left';
        $this->level = isset($data['level']) ? (int) $data['level'] : 1;
        $this->name = $data['name'];
        $this->numcolumns = isset($data['numcolumns']) ? (int) $data['numcolumns'] : 0;
        $this->order = isset($data['order']) ? (int) $data['order'] : 0;
        $this->title = isset($data['title']) ? $data['title'] : $this->name;
        $this->loadWidget($data['children']);
    }

    /**
     * 
     * @param object $model
     *
     * @return string
     */
    public function edit($model)
    {
        $divClass = ($this->numcolumns > 0) ? 'col-md-' . $this->numcolumns : 'col';
        return '<div class="' . $divClass . '">' . $this->widget->edit($model, $this->title, $this->description) . '</div>';
    }

    /**
     * 
     * @param User $user
     *
     * @return boolean
     */
    public function hiddeTo($user)
    {
        if ($this->display === 'none') {
            return true;
        }

        return $user->level < $this->level;
    }

    public function tableCell($model, $user)
    {
        if ($this->hiddeTo($user)) {
            return '';
        }

        return $this->widget->tableCell($model, $this->display);
    }

    public function tableHeader($user)
    {
        if ($this->hiddeTo($user)) {
            return '';
        }

        return '<th class="text-' . $this->display . '">' . static::$i18n->trans($this->title) . '</th>';
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

            $className = '\\FacturaScripts\\Core\\Lib\\Widget\\Widget' . ucfirst($child['type']);
            if (class_exists($className)) {
                $this->widget = new $className($child);
            } else {
                $this->widget = new WidgetText($child);
            }

            break;
        }
    }
}
