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

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Description of WidgetSelect
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Daniel Fernández Giménez      <hola@danielfg.es>
 */
class WidgetSelect extends BaseWidget
{
    /** @var CodeModel */
    protected static $codeModel;

    /** @var string */
    protected $fieldcode;

    /** @var string */
    protected $fieldfilter;

    /** @var string */
    protected $fieldtitle;

    /** @var int */
    protected $limit;

    /** @var bool */
    public $multiple;

    /** @var string */
    protected $parent;

    /** @var string */
    protected $source;

    /** @var bool */
    protected $translate;

    /** @var array */
    public $values = [];

    public function __construct(array $data)
    {
        if (!isset(static::$codeModel)) {
            static::$codeModel = new CodeModel();
        }

        parent::__construct($data);
        $this->parent = $data['parent'] ?? '';
        $this->translate = isset($data['translate']);
        $this->multiple = isset($data['multiple']) && strtolower($data['multiple']) === 'true';

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
            'fieldfilter' => $this->fieldfilter,
            'fieldtitle' => $this->fieldtitle,
            'limit' => $this->limit
        ];
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $value = $request->request->get($this->fieldname, '');

        if ('' === $value) {
            $model->{$this->fieldname} = null;
        } elseif ($this->multiple && false === $this->readonly()) {
            $model->{$this->fieldname} = implode(',', $value);
        } else {
            $model->{$this->fieldname} = $value;
        }
    }

    /**
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must use the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array $items
     * @param bool $translate
     * @param bool $addEmpty
     * @param string $col1
     * @param string $col2
     */
    public function setValuesFromArray(array $items, bool $translate = false, bool $addEmpty = false, string $col1 = 'value', string $col2 = 'title')
    {
        if ($addEmpty && false === $this->multiple) {
            $this->values = [['value' => null, 'title' => '------']];
        }

        foreach ($items as $item) {
            if (false === is_array($item)) {
                $this->values[] = ['value' => $item, 'title' => $item];
                continue;
            } elseif (isset($item['tag']) && $item['tag'] !== 'values') {
                continue;
            }

            if (isset($item[$col1])) {
                $this->values[] = [
                    'value' => $item[$col1],
                    'title' => $item[$col2] ?? $item[$col1]
                ];
            }
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    public function setValuesFromArrayKeys(array $values, bool $translate = false, bool $addEmpty = false)
    {
        if ($addEmpty && false === $this->multiple) {
            $this->values = [['value' => null, 'title' => '------']];
        }

        foreach ($values as $key => $value) {
            $this->values[] = [
                'value' => $key,
                'title' => $value
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * Loads the value list from an array with value and title (description)
     *
     * @param array $rows
     * @param bool $translate
     */
    public function setValuesFromCodeModel(array $rows, bool $translate = false)
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $codeModel->description
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * @param int $start
     * @param int $end
     * @param int $step
     */
    public function setValuesFromRange(int $start, int $end, int $step)
    {
        $values = range($start, $end, $step);
        $this->setValuesFromArray($values);
    }

    /**
     * @param object $model
     * @param string $display
     *
     * @return string
     */
    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = $this->combineClasses($this->tableCellClass('text-' . $display), $this->class);
        return $this->multiple
            ? '<td class="' . $class . '">' . $this->show() . '</td>'
            : '<td class="' . $class . '">' . $this->onclickHtml($this->show()) . '</td>';
    }

    /**
     *  Translate the fixed titles, if they exist
     */
    private function applyTranslations()
    {
        foreach ($this->values as $key => $value) {
            if (empty($value['title']) || '------' === $value['title']) {
                continue;
            }

            $this->values[$key]['title'] = Tools::lang()->trans($value['title']);
        }
    }

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/select2/dist/css/select2.min.css');
        AssetManager::add('css', FS_ROUTE . '/node_modules/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/select2/dist/js/select2.min.js', 2);
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetSelect.js');
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-select select2'), $this->class, $extraClass);

        if ($this->parent) {
            $class .= ' parentSelect';
        }

        $html = '';
        $name = '';
        if ($this->readonly()) {
            $html .= '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '">';
        } else {
            $name = $this->multiple
                ? ' name="' . $this->fieldname . '[]"'
                : ' name="' . $this->fieldname . '"';
        }

        $html .= '<select'
            . $name
            . ' id="' . $this->id . '"'
            . ' class="' . $class . '"'
            . $this->inputHtmlExtraParams()
            . ' parent="' . $this->parent . '"'
            . ' value="' . $this->value . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' data-limit="' . $this->limit . '"'
            . '>';

        // guardamos el valor original en otra variable para modificarla
        $modelValues = $this->value;

        // si el value del modelo es un booleano, lo convertimos a string
        if (is_bool($modelValues)) {
            $modelValues = $modelValues ? '1' : '0';
        }

        // separamos el value del modelo por comas para poder seleccionar varios valores
        // necesario si activamos el modo multiple
        $modelValues = explode(',', $modelValues);

        $found = false;
        foreach ($this->values as $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];

            if (in_array($option['value'], $modelValues) && (!$found || $this->multiple)) {
                $found = true;
                $html .= '<option value="' . $option['value'] . '" selected>' . $title . '</option>';
                continue;
            }

            $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
        }

        // value not found?
        // don't use strict comparison (===)
        if (!$this->multiple && !$found && $this->value != '' && !empty($this->source)) {
            $html .= '<option value="' . $this->value . '" selected>'
                . static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle)
                . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $params = parent::inputHtmlExtraParams();
        $params .= $this->multiple ? ' multiple' : '';
        $params .= $this->readonly() ? ' disabled' : '';
        return $params;
    }

    /**
     * Set datasource data and Load data from Model into values array.
     *
     * @param array $child
     * @param bool $loadData
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        $this->source = $child['source'];
        $this->fieldcode = $child['fieldcode'] ?? 'id';
        $this->fieldfilter = $child['fieldfilter'] ?? $this->fieldfilter;
        $this->fieldtitle = $child['fieldtitle'] ?? $this->fieldcode;
        $this->limit = $child['limit'] ?? CodeModel::ALL_LIMIT;
        if ($loadData && $this->source) {
            static::$codeModel::setLimit($this->limit);
            $values = static::$codeModel->all($this->source, $this->fieldcode, $this->fieldtitle, !$this->required);
            $this->setValuesFromCodeModel($values, $this->translate);
        }
    }

    /**
     * @return string
     */
    protected function show()
    {
        if (null === $this->value) {
            return '-';
        }

        if ($this->multiple) {
            $array = [];
            foreach ($this->values as $option) {
                $title = empty($option['title']) ? $option['value'] : $option['title'];

                // don't use strict comparison (===)
                if (!empty($this->value) && in_array($option['value'], explode(',', $this->value))) {
                    $array[] = $title;
                }
            }

            $txt = implode(', ', $array);
            if (strlen($txt) < 20) {
                return $txt;
            }

            $txtBreak = substr($txt, 0, 20);
            return '<span data-bs-toggle="tooltip" data-html="true" title="' . $txt . '">' . $txtBreak . '...</span>';
        }

        $selected = null;
        foreach ($this->values as $option) {
            // don't use strict comparation (===)
            if ($option['value'] == $this->value) {
                $selected = $option['title'];
            }
        }

        if (null === $selected) {
            // value is not in $this->values
            $selected = $this->source ?
                static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle) :
                $this->value;

            $this->values[] = [
                'value' => $this->value,
                'title' => $selected
            ];
        }

        return $selected;
    }
}
