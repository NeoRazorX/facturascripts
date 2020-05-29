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

/**
 * Description of WidgetTextarea
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class WidgetTextarea extends WidgetText
{

    /**
     * Indicates the number of rows value
     *
     * @var int
     */
    protected $rows;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->rows = (int) ($data['rows'] ?? 3);
    }

    /**
     * 
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $inputHtml = $this->inputHtml();
        $labelHtml = '<label>' . $this->onclickHtml(static::$i18n->trans($title), $titleurl) . '</label>';

        return '<div class="form-group">'
            . $labelHtml
            . $inputHtml
            . $descriptionHtml
            . '</div>';
    }

    /**
     * 
     * @param object $model
     * @param string $display
     *
     * @return string
     */
    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = 'text-' . $display;
        $value = $this->show();
        $final = \mb_strlen($value) > 60 ? \mb_substr($value, 0, 60) . '...' : $value;

        return '<td class="' . $this->tableCellClass($class) . '">' . $this->onclickHtml($final) . '</td>';
    }

    /**
     * 
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        return '<textarea rows="' . $this->rows . '" name="' . $this->fieldname . '" class="' . $class . '"'
            . $this->inputHtmlExtraParams() . '>' . $this->value . '</textarea>';
    }
}
