<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $this->display = $data['display'] ?? 'left';
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

    /**
     * @return string
     */
    public function tableHeader(): string
    {
        if ($this->hidden()) {
            return '';
        }

        if (empty($this->titleurl)) {
            return '<th class="text-' . $this->display . '">' . static::$i18n->trans($this->title) . '</th>';
        }

        return '<th class="text-' . $this->display . '">'
            . '<a href="' . $this->titleurl . '">' . static::$i18n->trans($this->title) . '</a>'
            . '</th>';
    }

    protected function loadWidget(array $children)
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
