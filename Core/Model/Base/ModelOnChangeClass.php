<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * ModelOnChangeClass is a class with methods to model the changes of values in the properties of the model.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class ModelOnChangeClass extends ModelClass
{

    /**
     * Previous data array.
     *
     * @var array
     */
    protected $previousData = [];

    /**
     * Class constructor.
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
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $this->onDelete();
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * Loads a record from database.
     * 
     * @param string $code
     * @param array  $where
     * @param array  $orderby
     * 
     * @return bool
     */
    public function loadFromCode($code, array $where = [], array $orderby = [])
    {
        if (parent::loadFromCode($code, $where, $orderby)) {
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * This methos is called before save (update) when some field has changed.
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if ($this->pipe('onChange', $field) === false) {
            return false;
        }

        return true;
    }

    /**
     * This method is called after a record is removed from the database.
     */
    protected function onDelete()
    {
        $this->pipe('onDelete');
    }

    /**
     * This method is called after a new record is saved on the database (saveInsert).
     */
    protected function onInsert()
    {
        $this->pipe('onInsert');
    }

    /**
     * This method is called after a record is updated on the database (saveUpdate).
     */
    protected function onUpdate()
    {
        $this->pipe('onUpdate');
    }

    /**
     * Inserts this data as a new record in database.
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            $this->onInsert();
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * Updates the data of this record in the database.
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        foreach (\array_keys($this->previousData) as $field) {
            if ($this->{$field} != $this->previousData[$field] && !$this->onChange($field)) {
                return false;
            }
        }

        if (parent::saveUpdate($values)) {
            $this->onUpdate();
            $this->setPreviousData();
            return true;
        }

        return false;
    }

    /**
     * Saves previous values.
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = $this->pipe('setPreviousDataMore');
        if (\is_array($more)) {
            foreach ($more as $key) {
                $fields[] = $key;
            }
        }

        foreach ($fields as $field) {
            $this->previousData[$field] = isset($this->{$field}) ? $this->{$field} : null;
        }
    }
}
