<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Description of ModelOnChangeClass
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class ModelOnChangeClass extends ModelClass
{

    /**
     *
     * @var array
     */
    protected $previousData;

    /**
     * 
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setPreviousData();
    }

    /**
     * 
     * @param string $cod
     * @param array  $where
     * @param array  $orderby
     * 
     * @return bool
     */
    public function loadFromCode($cod, array $where = [], array $orderby = [])
    {
        if (parent::loadFromCode($cod, $where, $orderby)) {
            $this->setPreviousData();
            return true;
        }

        return false;
    }
    
    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        return true;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        foreach (array_keys($this->previousData) as $field) {
            if ($this->{$field} != $this->previousData[$field] && !$this->onChange($field)) {
                return false;
            }
        }

        if (parent::saveUpdate($values)) {
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        foreach ($fields as $field) {
            $this->previousData[$field] = $this->{$field};
        }
    }
}
