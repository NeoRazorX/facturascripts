<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Description of WidgetDatalist
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class WidgetDatalist extends WidgetText
{

    use Base\ListTrait;

    /**
     * Class constructor
     *
     * @param array $data
     */
    public function __construct($data)
    {
        if (!isset(static::$codeModel)) {
            static::$codeModel = new CodeModel();
        }

        parent::__construct($data);
        $this->translate = isset($data['translate']);

        foreach ($data['children'] as $child) {
            if ($child['tag'] !== 'values') {
                continue;
            }

            if (isset($child['source'])) {
                $this->setSourceData($child);
                break;
            }

            $this->setValuesFromArray($data['children'], $this->translate, !$this->required, 'text');
            break;
        }
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'datalist', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        $list = $this->fieldname . '-list-' . $this->getUniqueId();
        $html = '<input type="text" name="' . $this->fieldname . '" value="' . $this->value . '"'
            . ' class="' . $class . '"'
            . ' list="' . $list . '"'
            . $this->inputHtmlExtraParams()
            . '/>';

        $html .= '<datalist id="' . $list . '">';
        foreach ($this->values as $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $html .= '<option value="' . $title . '" />';
        }
        $html .= '</datalist>';
        return $html;
    }
}
