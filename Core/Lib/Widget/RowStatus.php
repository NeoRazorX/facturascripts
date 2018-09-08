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

/**
 * Description of RowStatus
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowStatus
{

    /**
     *
     * @var string
     */
    public $fieldname;

    /**
     *
     * @var array
     */
    public $options = [];

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        $this->fieldname = empty($data['fieldname']) ? '' : $data['fieldname'];
        $this->options = $data['children'];
    }

    /**
     * 
     * @param object $model
     *
     * @return string
     */
    public function trClass($model)
    {
        foreach ($this->options as $opt) {
            $fieldname = isset($opt['fieldname']) ? $opt['fieldname'] : $this->fieldname;
            $value = isset($model->{$fieldname}) ? $model->{$fieldname} : null;

            $apply = false;
            switch ($opt['text'][0]) {
                case '<':
                    $matchValue = substr($opt['text'], 1) ?: '';
                    $apply = ((float) $value < (float) $matchValue);
                    break;

                case '>':
                    $matchValue = substr($opt['text'], 1) ?: '';
                    $apply = ((float) $value > (float) $matchValue);
                    break;

                default:
                    $apply = ($opt['text'] == $value);
                    break;
            }

            if ($apply) {
                return $this->colorToClass($opt['color']);
            }
        }

        return '';
    }

    /**
     * 
     * @param string $color
     *
     * @return string
     */
    protected function colorToClass($color)
    {
        switch ($color) {
            case 'danger':
            case 'dark':
            case 'info':
            case 'light':
            case 'primary':
            case 'secondary':
            case 'success':
            case 'warning':
                return 'table-' . $color;
        }

        return '';
    }
}
