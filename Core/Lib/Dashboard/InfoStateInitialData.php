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
namespace FacturaScripts\Core\Lib\Dashboard;

/**
 * Description of InfoStateInitialData
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class InfoStateInitialData
{

    /**
     * Generate some sample data.
     *
     * @param InfoStateComponent $parent
     */
    public static function generateData($parent)
    {
        $data = new self();
        $parent->saveData($data->getCustomerData());
        $parent->saveData($data->getSupplierData());
        $parent->saveData($data->getProductData());
    }

    /**
     * Return customer related data.
     *
     * @return mixed
     */
    protected function getCustomerData()
    {
        $result['model'] = 'Cliente';
        $result['icon'] = 'fa-users';
        $result['group'] = 'customers';
        $result['values'] = [
            ['name' => 'total', 'sql' => 'count(*)', 'type' => 'int'],
            ['name' => 'new', 'sql' => 'SUM(CASE WHEN EXTRACT(YEAR FROM fechaalta) = EXTRACT(YEAR FROM current_date) THEN 1 ELSE 0 END)', 'type' => 'int'],
            ['name' => 'suspended', 'sql' => 'SUM(CASE WHEN debaja THEN 1 ELSE 0 END)', 'type' => 'int'],
        ];

        return $result;
    }

    /**
     * Return supplier related data.
     *
     * @return mixed
     */
    protected function getSupplierData()
    {
        $result['model'] = 'Proveedor';
        $result['icon'] = 'fa-users';
        $result['group'] = 'suppliers';
        $result['values'] = [
            ['name' => 'total', 'sql' => 'count(*)', 'type' => 'int'],
            ['name' => 'is-creditor', 'sql' => 'SUM(CASE WHEN acreedor THEN 1 ELSE 0 END)', 'type' => 'int'],
            ['name' => 'suspended', 'sql' => 'SUM(CASE WHEN debaja THEN 1 ELSE 0 END)', 'type' => 'int'],
        ];

        return $result;
    }

    /**
     * Return product related data.
     *
     * @return mixed
     */
    protected function getProductData()
    {
        $result['model'] = 'Articulo';
        $result['icon'] = 'fa-cube';
        $result['group'] = 'products';
        $result['values'] = [
            ['name' => 'total', 'sql' => 'count(*)', 'type' => 'int'],
            ['name' => 'for-sale', 'sql' => 'SUM(CASE WHEN sevende THEN 1 ELSE 0 END)', 'type' => 'int'],
            ['name' => 'no-stock', 'sql' => 'SUM(CASE WHEN (sevende AND stockfis < stockmin) THEN 1 ELSE 0 END)', 'type' => 'int'],
            ['name' => 'locked', 'sql' => 'SUM(CASE WHEN bloqueado THEN 1 ELSE 0 END)', 'type' => 'int'],
        ];

        return $result;
    }
}
