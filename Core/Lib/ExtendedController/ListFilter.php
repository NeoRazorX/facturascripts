<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * LisFilter definition for its use in ListController.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListFilter
{

    /**
     * Values and configuration options for the filter
     *
     * @var array
     */
    public $options;

    /**
     * Indicates the filter type
     *
     * @var string
     */
    public $type;

    /**
     * ListFilter constructor.
     *
     * @param string $type
     * @param array  $options
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
            'different-than' => '<>',
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
     * @param DataBaseWhere[] $where
     * @param string          $key
     */
    public function getDataBaseWhere(&$where, $key = '')
    {
        switch ($this->type) {
            case 'autocomplete':
            case 'select':
                if ($this->hasValue()) {
                    // we use the key value because the field value indicate is the text field of the source data
                    $where[] = new DataBaseWhere($key, $this->options['value']);
                }
                break;

            case 'checkbox':
                $operator = $this->options['inverse'] ? '!=' : '=';
                if ($this->options['matchValue'] === null) {
                    $operator = $this->options['inverse'] ? 'IS NOT' : 'IS';
                }
                if ($this->hasValue()) {
                    $where[] = new DataBaseWhere($this->options['field'], $this->options['matchValue'], $operator);
                }
                break;

            default:
                if ($this->hasValue('valueFrom')) {
                    $where[] = new DataBaseWhere(
                        $this->options['field'], $this->options['valueFrom'], $this->options['operatorFrom']
                    );
                }
                if ($this->hasValue('valueTo')) {
                    $where[] = new DataBaseWhere(
                        $this->options['field'], $this->options['valueTo'], $this->options['operatorTo']
                    );
                }
        }
    }

    /**
     * Builds a string with the parameters contained in the URL of the controller call
     *
     * @param string $key
     * @param string $join
     *
     * @return string
     */
    public function getParams($key, $join): string
    {
        $result = '';
        switch ($this->type) {
            case 'autocomplete':
            case 'select':
            case 'checkbox':
                if ($this->hasValue()) {
                    $result .= $join . $key . '=' . $this->options['value'];
                }
                break;

            default:
                if ($this->hasValue('valueFrom')) {
                    $result .= $join . $key . '-from=' . $this->options['valueFrom'];
                    $result .= '&' . $key . '-from-operator=' . $this->options['operatorFrom'];
                    $join = '&';
                }

                if ($this->hasValue('valueTo')) {
                    $result .= $join . $key . '-to=' . $this->options['valueTo'];
                    $result .= '&' . $key . '-to-operator=' . $this->options['operatorTo'];
                }
        }

        return $result;
    }

    /**
     * Creates and returns a select type filter.
     * 
     * @param string $table
     * @param string $fieldcode
     * @param string $fieldtitle
     * @param string $value
     * @param array  $where
     * 
     * @return ListFilter
     */
    public static function newSelectFilter($table, $fieldcode, $fieldtitle, $value, $where = []): ListFilter
    {
        $options = ['table' => $table, 'fieldcode' => $fieldcode, 'fieldtitle' => $fieldtitle, 'value' => $value, 'where' => $where];

        return new self('select', $options);
    }

    /**
     * Creates and returns an autocomplete type filter.
     * 
     * @param string $table
     * @param string $fieldcode
     * @param string $fieldtitle
     * @param string $value
     * @param array  $where
     * 
     * @return ListFilter
     */
    public static function newAutocompleteFilter($table, $fieldcode, $fieldtitle, $value, $where = []): ListFilter
    {
        $options = ['table' => $table, 'fieldcode' => $fieldcode, 'fieldtitle' => $fieldtitle, 'value' => $value, 'where' => $where];

        return new self('autocomplete', $options);
    }

    /**
     * Creates and returns a checkbox type filter
     *
     * @param string $field
     * @param string $value
     * @param string $label
     * @param bool   $inverse
     * @param mixed  $matchValue
     *
     * @return ListFilter
     */
    public static function newCheckboxFilter($field, $value, $label, $inverse, $matchValue): ListFilter
    {
        $options = [
            'label' => $label,
            'field' => $field,
            'value' => $value,
            'inverse' => $inverse,
            'matchValue' => $matchValue,
        ];

        return new self('checkbox', $options);
    }

    /**
     * Creates and returns a filter of the specified type [text|number|datepicker]
     *
     * @param string $type    ('text' | 'datepicker' | 'number')
     * @param array  $options (['field', 'label', 'valueFrom', 'operatorFrom', 'valueTo', 'operatorTo'])
     *
     * @return ListFilter
     */
    public static function newStandardFilter($type, $options): ListFilter
    {
        if ($type === 'number') {
            $options['valueFrom'] = self::checkNumberValue($options['valueFrom']);
            $options['valueTo'] = self::checkNumberValue($options['valueTo']);
        }

        return new self($type, $options);
    }

    /**
     * Check if option value is not null or empty
     *
     * @param string $key
     * 
     * @return bool
     */
    private function hasValue($key = 'value'): bool
    {
        $value = $this->options[$key];
        return (($value !== null) && ($value !== ''));
    }

    /**
     * If number is integer, return the number without decimal part.
     * Else, return the number with decimal part.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function checkNumberValue($value): string
    {
        $values = explode('.', $value, 1);

        return count($values) === 1 ? $values[0] : $values[0] . '.' . $values[1];
    }
}
