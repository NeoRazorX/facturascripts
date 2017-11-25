<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Base\DataBase;

/**
 * Description of ListFilter
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListFilter
{
    /**
     * Indicates the filter type
     *
     * @var string
     */
    public $type;

    /**
     * Values and configuration options for the filter
     *
     * @var array
     */
    public $options;

    /**
     * Class constructor
     *
     * @param string $type
     * @param array $options
     */
    public function __construct($type, $options)
    {
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * List of available operators
     *
     * @return array
     */
    public function getFilterOperators()
    {
        return [
            'like-than' => '=',
            'greater-than' => '>=',
            'smaller-than' => '<=',
            'different-than' => '<>'
        ];
    }

    /**
     * Returns the special class to add to the input in the filters form
     *
     * @return string
     */
    public function getSpecialClass()
    {
        switch ($this->type) {
            case 'datepicker':
                return 'datepicker';

            default:
                return '';
        }
    }

    /**
     * Returns the onekeypress JavaScript function in case the inputs accept only a value set
     *
     * @return string
     */
    public function getKeyboardFilter()
    {
        switch ($this->type) {
            case 'number':
                /// enter + number + ','
                return 'onkeypress="return event.charCode == 13 || '
                    . '(event.charCode >= 48 && event.charCode <= 57) || '
                    . ' event.charCode == 46"';

            case 'datepicker':
                /// enter + number + '-' + '/'
                return 'onkeypress="return event.charCode == 13 || '
                    . '(event.charCode >= 48 && event.charCode <= 57) || '
                    . ' event.charCode == 45 || '
                    . ' event.charCode == 47"';

            default:
                return '';
        }
    }

    /**
     * Adds $where to the informed filters in DataBaseWhere format
     *
     * @param array $where
     * @param string $key
     */
    public function getDataBaseWhere(&$where, $key = '')
    {
        switch ($this->type) {
            case 'select':
                if ($this->options['value'] != '') {
                    // we use the key value because the field value indicate is the text field of the source data
                    $where[] = new DataBase\DataBaseWhere($key, $this->options['value']);
                }
                break;

            case 'checkbox':
                if ($this->options['value'] != '') {
                    $checked = (bool) (($this->options['inverse']) ? !$this->options['value'] : $this->options['value']);
                    $where[] = new DataBase\DataBaseWhere($this->options['field'], $checked);
                }
                break;

            default:
                if ($this->options['valueFrom'] != '') {
                    $where[] = new DataBase\DataBaseWhere(
                        $this->options['field'], $this->options['valueFrom'], $this->options['operatorFrom']);
                }
                if ($this->options['valueTo'] != '') {
                    $where[] = new DataBase\DataBaseWhere(
                        $this->options['field'], $this->options['valueTo'], $this->options['operatorTo']);
                }
        }
    }

    /**
     * Builds a string with the parameters contained in the URL of the controller call
     *
     * @param string $key
     * @return string
     */
    public function getParams($key)
    {
        $result = '';
        switch ($this->type) {
            case 'select':
            case 'checkbox':
                if ($this->options['value'] !== '') {
                    $result .= '&' . $key . '=' . $this->options['value'];
                }
                break;

            default:
                if ($this->options['valueFrom'] !== '') {
                    $result .= '&' . $key . '-from=' . $this->options['valueFrom'];
                    $result .= '&' . $key . '-from-operator=' . $this->options['operatorFrom'];
                }

                if ($this->options['valueTo'] !== '') {
                    $result .= '&' . $key . '-to=' . $this->options['valueTo'];
                    $result .= '&' . $key . '-to-operator=' . $this->options['operatorTo'];
                }
        }
        return $result;
    }

    /**
     * Creates and returns a select type filter
     *
     * @param string $field
     * @param string $value
     * @param string $table
     * @param string $where
     * @return ListFilter
     */
    public static function newSelectFilter($field, $value, $table, $where)
    {
        $options = ['field' => $field, 'value' => $value, 'table' => $table, 'where' => $where];
        return new ListFilter('select', $options);
    }

    /**
     * Creates and returns a checkbox type filter
     *
     * @param string $field
     * @param string $value
     * @param string $label
     * @param boolean $inverse
     * @return ListFilter
     */
    public static function newCheckboxFilter($field, $value, $label, $inverse)
    {
        $options = ['label' => $label, 'field' => $field, 'value' => $value, 'inverse' => $inverse];
        return new ListFilter('checkbox', $options);
    }

    /**
     * TODO: Por completar
     *
     * @param $value
     *
     * @return string
     */
    private static function checkNumberValue($value)
    {
        $values = explode('.', $value, 1);
        return count($values) === 1 ? $values[0] : $values[0] . '.' . $values[1];
    }

    /**
     * Creates and returns a filter of the specified type [text|number|datepicker]
     *
     * @param string $type    ('text' | 'datepicker' | 'number')
     * @param array $options  (['field', 'label', 'valueFrom', 'operatorFrom', 'valueTo', 'operatorTo'])
     * @return ListFilter
     */
    public static function newStandardFilter($type, $options)
    {
        if ($type === 'number') {
            $options['valueFrom'] = self::checkNumberValue($options['valueFrom']);
            $options['valueTo'] = self::checkNumberValue($options['valueTo']);
        }

        return new ListFilter($type, $options);
    }
}
