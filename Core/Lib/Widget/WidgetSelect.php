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
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of WidgetSelect
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class WidgetSelect extends BaseWidget
{

    use Base\ListTrait;

    /**
     *
     * @var string
     */
    protected $fieldtitle;

    /**
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
            } elseif (isset($child['start'])) {
                $this->setValuesFromRange($child['start'], $child['end'], $child['step']);
                break;
            }

            $this->setValuesFromArray($data['children'], $this->translate, !$this->required, 'text');
            break;
        }
    }

    /**
     * Obtains the configuration of the datasource used in obtaining data
     *
     * @return array
     */
    public function getDataSource(): array
    {
        return [
            'source' => $this->source,
            'fieldcode' => $this->fieldcode,
            'fieldtitle' => $this->fieldtitle,
        ];
    }

    /**
     *
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname, '');
        $model->{$this->fieldname} = ('' === $value) ? null : $value;
    }

    /**
     *
     * @param int $start
     * @param int $end
     * @param int $step
     */
    public function setValuesFromRange($start, $end, $step)
    {
        $values = \range($start, $end, $step);
        $this->setValuesFromArray($values);
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
        if ($this->readonly()) {
            return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
                . '<input type="text" value="' . $this->show() . '" class="' . $class . '" readonly=""/>';
        }

        $found = false;
        $html = '<select name="' . $this->fieldname . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '>';
        foreach ($this->values as $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];

            /// don't use strict comparation (===)
            if ($option['value'] == $this->value) {
                $found = true;
                $html .= '<option value="' . $option['value'] . '" selected="">' . $title . '</option>';
                continue;
            }

            $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
        }

        /// value not found?
        if (!$found && !empty($this->value)) {
            $html .= '<option value="' . $this->value . '" selected="">'
                . static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle)
                . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        if (null === $this->value) {
            return '-';
        }

        $selected = null;
        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            if ($option['value'] == $this->value) {
                $selected = $option['title'];
            }
        }

        if (null === $selected) {
            // value is not in $this->values
            $selected = static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle);
            $this->values[] = [
                'value' => $this->value,
                'title' => $selected
            ];
        }

        return $selected;
    }
}
