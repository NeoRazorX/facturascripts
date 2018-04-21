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

namespace FacturaScripts\Core\Lib\API;

use FacturaScripts\Core\Lib\API\Base\APIResourceClass;

/**
 * TestAPI to test API functionality
 *
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <rsanjoseo@gmail.com>
 */
class Calculate extends APIResourceClass
{
    public function help()
    {
        $data = array();
        $data['name'] = 'Perform simple arithmetic operations';
        $data['subname'] = 'Indicate operator and two or more operands';
        $data['options'] = 'Available operators:';
        $data['sum'] = 'sum';
        $data['subtraction'] = 'subtraction';
        $data['multiplication'] = 'multiplication';
        $data['division'] = 'division';
        $data['sample'] = 'facturascripts.com/api/3/calculates/subtraction/10/2/1';
        $data['result'] = 'Proccess 10-2-1 and return 7';
        $this->returnResult($data);
    }

    /**
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    public function getResources(): array
    {
        $ret = array();
        $ret['calculates'] = $this->setResource('Calculadora');
        return $ret;
    }

    /**
     * Overwrite and IGNORE the original method of the ancestor.
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public function processResource(string $name): bool
    {
        $params = $this->params;
        if (count($params) === 0) {
            $this->help();
            return true;
        }

        $operator = array_shift($params);
        $result = (float) array_shift($params);
        foreach ($params as $_value) {
            $value = (float) $_value;
            switch ($operator) {
                case 'sum':
                    $result += $value;
                    break;
                case 'subtraction':
                    $result -= $value;
                    break;
                case 'multiplication':
                    $result *= $value;
                    break;
                case 'division':
                    if ($value === 0) {
                        $this->setError('No se puede dividir entre 0');
                        return false;
                    }
                    $result /= $value;
                    break;
                default:
                    $this->setError("Bad Operator: $operator");
                    return false;
            }
        }
        $this->returnResult(array($operator => $result));
        return true;
    }
}
