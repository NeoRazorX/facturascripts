<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
namespace FacturaScripts\Core\Model;

/**
 * Element of the Dashboard of FacturaScripts, each corresponds to a card.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class DashboardCard
{

    use Base\ModelTrait {
        url as private traitURL;
    }

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Nick of the user to whom the card is addressed.
     *
     * @var string
     */
    public $nick;

    /**
     * Description to shown on the card.
     *
     * @var string
     */
    public $fecha;

    /**
     * Date from which to show the card.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'dashboardcards';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->id = null;
        $this->nick = null;
        $this->fecha = date('d-m-Y');
        $this->descripcion = null;
    }

    /**
     * Returns the url where to see/modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitURL($type, 'EditDashboardCard');
    }
}
