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
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of RowItemStatus
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItemStatus extends RowItem
{

    /**
     * Field name to which the options are applied to
     *
     * @var string
     */
    public $fieldName;

    /**
     * Options for the field configuration
     *
     * @var array
     */
    public $options;

    /**
     * RowItemStatus constructor.
     */
    public function __construct()
    {
        parent::__construct('status');
        $this->fieldName = '';
        $this->options = [];
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $row
     */
    public function loadFromXML($row)
    {
        $row_atributes = $row->attributes();
        $this->fieldName = (string) $row_atributes->fieldname;

        foreach ($row->option as $option) {
            $values = $this->getAttributesFromXML($option);
            $this->options[] = $values;
            unset($values);
        }
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $row
     */
    public function loadFromJSON($row)
    {
        $this->type = (string) $row['type'];
        $this->fieldName = (string) $row['fieldName'];
        $this->options = (array) $row['options'];
    }

    /**
     * Returns the status for a given value
     *
     * @param string $value
     *
     * @return string
     */
    public function getStatus($value)
    {
        foreach ($this->options as $option) {
            switch ($option['value'][0]) {
                case '>':
                    if ($value > (float) substr($option['value'], 1)) {
                        return $option['color'];
                    }
                    break;

                case '<':
                    if ($value < (float) substr($option['value'], 1)) {
                        return $option['color'];
                    }
                    break;

                default:
                    /// don't use strict comparation (===)
                    if ($option['value'] == $value) {
                        return $option['color'];
                    }
            }
        }

        return 'table-light';
    }
}
