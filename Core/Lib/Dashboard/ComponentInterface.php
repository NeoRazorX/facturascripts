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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Dashboard;

/**
 * Interface ComponentInterface
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface ComponentInterface
{

    /**
     * Sets the special fields for the component and their initial values.
     *
     * @return mixed
     */
    public static function getPropertiesFields();

    /**
     * Load data of component for user to put into dashboard.
     */
    public function loadData();

    /**
     * Data persists in the database, modifying if the record existed or inserting
     * in case the primary key does not exist.
     *
     * @param array $data
     */
    public function saveData($data);

    /**
     * Return the template to use for this component.
     *
     * @return string|false
     */
    public function getTemplate();

    /**
     * Return the number of columns to display width this component.
     *
     * @return string
     */
    public function getNumColumns();

    /**
     * Return the class name to render this component.
     *
     * @return string
     */
    public function getCardClass();

    /**
     * Return the URL to this component.
     *
     * @param string $id
     *
     * @return string
     */
    public function url($id);
}
