<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Daniel Fernández Giménez      <contacto@danielfg.es>
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

    /** @var string */
    protected $groupSource = '';

    /** @var string */
    protected $groupFieldcode = '';

    /** @var string */
    protected $groupTitle = '';

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
            'limit' => $this->limit,
            'group_source' => $this->groupSource,
            'group_fieldcode' => $this->groupFieldcode,
            'group_title' => $this->groupTitle,
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
            $model->{$this->fieldname} = implode(',', unserialize($value));
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
    public function setValuesFromArray(array $items, bool $translate = false, bool $addEmpty = false, string $col1 = 'value', string $col2 = 'title', string $col3 = '')
    {
        $this->values = [];

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
                $entry = [
                    'value' => $item[$col1],
                    'title' => $item[$col2] ?? $item[$col1],
                ];
                if ($col3 !== '' && isset($item[$col3])) {
                    $entry['group'] = $item[$col3];
                }
                $this->values[] = $entry;
            }
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    public function setValuesFromArrayKeys(array $values, bool $translate = false, bool $addEmpty = false, array $groups = [])
    {
        $this->values = [];

        if ($addEmpty && false === $this->multiple) {
            $this->values = [['value' => null, 'title' => '------']];
        }

        foreach ($values as $key => $value) {
            $entry = ['value' => $key, 'title' => $value];
            if (isset($groups[$key])) {
                $entry['group'] = $groups[$key];
            }
            $this->values[] = $entry;
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * @param array $rows
     * @param bool $translate
     * @param array $groups array indexado por code => etiqueta del grupo
     */
    public function setValuesFromCodeModel(array $rows, bool $translate = false, array $groups = [])
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            $entry = [
                'value' => $codeModel->code,
                'title' => $codeModel->description,
            ];
            if (isset($groups[$codeModel->code])) {
                $entry['group'] = $groups[$codeModel->code];
            }
            $this->values[] = $entry;
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * @param int $start
     * @param int $end
     * @param int $step
     * @param array $groups array indexado por valor numérico => etiqueta del grupo
     */
    public function setValuesFromRange(int $start, int $end, int $step, array $groups = [])
    {
        $values = range($start, $end, $step);

        if (empty($groups)) {
            $this->setValuesFromArray($values);
            return;
        }

        $this->values = [];
        foreach ($values as $val) {
            $entry = ['value' => $val, 'title' => $val];
            if (isset($groups[$val])) {
                $entry['group'] = $groups[$val];
            }
            $this->values[] = $entry;
        }
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
    private function applyTranslations(): void
    {
        foreach ($this->values as $key => $value) {
            if (empty($value['title']) || '------' === $value['title']) {
                continue;
            }

            $this->values[$key]['title'] = Tools::trans($value['title']);
        }
    }

    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addCss($route . '/node_modules/select2/dist/css/select2.min.css?v=5');
        AssetManager::addCss($route . '/node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css?v=5');
        AssetManager::addJs($route . '/node_modules/select2/dist/js/select2.min.js?v=5', 2);
        AssetManager::addJs($route . '/Dinamic/Assets/JS/WidgetSelect.js?v=5');
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $campoRequeridoNoSeleccionado = false;
        if($this->required && empty($this->value)){
            $campoRequeridoNoSeleccionado = true;
        }

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
            . ($campoRequeridoNoSeleccionado ? ' data-campo-requerido-no-seleccionado="true"' : '')
            . '>';

        $found = false;
        $hasGroups = false;
        foreach ($this->values as $option) {
            if (!empty($option['group'])) {
                $hasGroups = true;
                break;
            }
        }

        if ($hasGroups) {
            // Separar opciones sin grupo (ej. la opción vacía) de las agrupadas
            $ungrouped = [];
            $grouped = [];
            foreach ($this->values as $option) {
                if (empty($option['group'])) {
                    $ungrouped[] = $option;
                } else {
                    $grouped[$option['group']][] = $option;
                }
            }

            // Opciones sin grupo primero
            foreach ($ungrouped as $option) {
                $title = empty($option['title']) ? $option['value'] : $option['title'];
                if ($this->valuesMatch($option['value'], $this->value) && (!$found || $this->multiple)) {
                    $found = true;
                    $html .= '<option value="' . $option['value'] . '" selected>' . $title . '</option>';
                    continue;
                }
                $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
            }

            // Opciones agrupadas dentro de <optgroup>
            foreach ($grouped as $groupLabel => $options) {
                $html .= '<optgroup label="' . Tools::noHtml($groupLabel) . '">';
                foreach ($options as $option) {
                    $title = empty($option['title']) ? $option['value'] : $option['title'];
                    if ($this->valuesMatch($option['value'], $this->value) && (!$found || $this->multiple)) {
                        $found = true;
                        $html .= '<option value="' . $option['value'] . '" selected>' . $title . '</option>';
                        continue;
                    }
                    $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
                }
                $html .= '</optgroup>';
            }
        } else {
            foreach ($this->values as $option) {
                $title = empty($option['title']) ? $option['value'] : $option['title'];

                if ($this->valuesMatch($option['value'], $this->value) && (!$found || $this->multiple)) {
                    $found = true;
                    $html .= '<option value="' . $option['value'] . '" selected>' . $title . '</option>';
                    continue;
                }

                $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
            }
        }

        // value not found?
        // don't use strict comparison (===)
        if (!$this->multiple && !$found && $this->value != '' && !empty($this->source)) {
            $html .= '<option value="' . $this->value . '" selected>'
                . static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle)
                . '</option>';
        }

        $html .= '</select>';

        if($campoRequeridoNoSeleccionado){
            $html .= '<div class="form-text marquee-container">
                <span class="marquee-text text-danger fw-bold text-uppercase">
                    ' . Tools::trans("required") . '                    
                </span>
            </div';
        }

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
        $this->limit = $child['limit'] ?? CodeModel::getlimit();
        $this->groupSource = $child['group_source'] ?? '';
        $this->groupFieldcode = $child['group_fieldcode'] ?? '';
        $this->groupTitle = $child['group_title'] ?? '';

        if ($loadData && $this->source) {
            static::$codeModel::setLimit($this->limit);
            $values = static::$codeModel->all($this->source, $this->fieldcode, $this->fieldtitle, !$this->required);

            if (!empty($this->groupSource) && !empty($this->groupFieldcode) && !empty($this->groupTitle)) {
                // Mapa de group_fieldcode => group_title
                $groupLabelRows = static::$codeModel->all($this->groupSource, $this->groupFieldcode, $this->groupTitle, false);
                $groupLabelMap = [];
                foreach ($groupLabelRows as $row) {
                    $groupLabelMap[$row->code] = $row->description;
                }

                // Mapa de fieldcode => group_fieldcode (valor del campo de agrupación en cada registro)
                static::$codeModel::setLimit($this->limit);
                $groupFieldRows = static::$codeModel->all($this->source, $this->fieldcode, $this->groupFieldcode, false);
                $groupFieldMap = [];
                foreach ($groupFieldRows as $row) {
                    $groupFieldMap[$row->code] = $row->description;
                }

                // Construir mapa fieldcode => etiqueta del grupo
                $groups = [];
                foreach ($values as $row) {
                    $groupFieldValue = $groupFieldMap[$row->code] ?? null;
                    $groups[$row->code] = $groupLabelMap[$groupFieldValue] ?? '';
                }

                $this->setValuesFromCodeModel($values, $this->translate, $groups);
            } else {
                $this->setValuesFromCodeModel($values, $this->translate);
            }
        }
    }

    /**
     * Compares two values for equality, normalizing booleans to strings
     * and using strict string comparison to avoid type juggling issues.
     *
     * @param mixed $value1
     * @param mixed $value2
     * @return bool
     */
    private function valuesMatch($value1, $value2): bool
    {
        // normalize boolean values to string
        if (is_bool($value1)) {
            $value1 = $value1 ? '1' : '0';
        }
        if (is_bool($value2)) {
            $value2 = $value2 ? '1' : '0';
        }

        // use string comparison to avoid type juggling (e.g., "01" != "1")
        return (string)$value1 === (string)$value2;
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
            if ($this->valuesMatch($option['value'], $this->value)) {
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
