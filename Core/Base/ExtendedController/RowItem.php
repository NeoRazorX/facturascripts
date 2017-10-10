<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

/**
 * Description of RowItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItem implements VisualItemInterface
{

    /**
     * Tipo de row que se visualiza
     *
     * @var string
     */
    public $type;

    /**
     * Nombre del campo que al que se aplica las opciones
     *
     * @var string
     */
    public $fieldName;

    /**
     * Opciones para configurar la fila
     *
     * @var array
     */
    public $options;

    public function __construct()
    {
        $this->type = 'status';
        $this->fieldName = '';
        $this->options = [];
    }

    public function loadFromXML($row)
    {
        $row_atributes = $row->attributes();
        $this->type = (string) $row_atributes->type;
        $this->fieldName = (string) $row_atributes->fieldname;

        foreach ($row->option as $option) {
            $values = [];
            foreach ($option->attributes() as $key => $value) {
                $values[$key] = (string) $value;
            }
            $values['value'] = (string) $option;
            $this->options[] = $values;
            unset($values);
        }
    }

    public function loadFromJSON($row)
    {
        $this->type = (string) $row['type'];
        $this->fieldName = (string) $row['fieldName'];
        $this->options = (array) $row['options'];
    }

    public function getStatus($value)
    {
        foreach ($this->options as $option) {
            if (substr($option['value'], 0, 1) == '>' && $value > floatval(substr($option['value'], 1))) {
                return $option['color'];
            } else if (substr($option['value'], 0, 1) == '<' && $value < floatval(substr($option['value'], 1))) {
                return $option['color'];
            } else if ($option['value'] == $value) {
                return $option['color'];
            }
        }

        return 'table-light';
    }

    public function getHeaderHTML($value)
    {
        return $value;
    }
}
