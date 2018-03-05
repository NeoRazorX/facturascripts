<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * The agent/employee is the one associated with a delivery note, invoice o box.
 * Each user can be associated with an agent, an an agent can
 * can be associated with several user of none at all.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Agente extends Base\Contact
{

    use Base\ModelTrait;

    /**
     * Last name of the agent or employee.
     *
     * @var string
     */
    public $apellidos;

    /**
     * Bank account.
     *
     * @var string
     */
    public $banco;

    /**
     * Position in the company.
     *
     * @var string
     */
    public $cargo;

    /**
     * Contact city.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Primary key. Varchar (10).
     *
     * @var int
     */
    public $codagente;

    /**
     * Contact country.
     *
     * @var string
     */
    public $codpais;

    /**
     * Postal code of the contact.
     *
     * @var string
     */
    public $codpostal;

    /**
     * True -> the customer no longer buys us or we do not want anything with him.
     *
     * @var boolean
     */
    public $debaja;

    /**
     * Address of the contact.
     *
     * @var string
     */
    public $direccion;

    /**
     * Date of withdrawal from the company.
     *
     * @var string
     */
    public $fechabaja;

    /**
     * Birthdate.
     *
     * @var string
     */
    public $fechanacimiento;

    /**
     * Percentage of the agent's commission. It is used in budgets, orders, delivery notes and invoices.
     *
     * @var float|int
     */
    public $porcomision;

    /**
     * Contact province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Social security number.
     *
     * @var string
     */
    public $seg_social;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'agentes';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codagente';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns name + agent's last name.
     *
     * @return string
     */
    public function primaryDescription()
    {
        return $this->nombre . ' ' . $this->apellidos;
    }

    /**
     * Reset values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->porcomision = 0.00;
    }

    /**
     * Check employee / agent data, return TRUE if correct.
     *
     * @return bool
     */
    public function test()
    {
        parent::test();
        $this->apellidos = Utils::noHtml($this->apellidos);
        $this->banco = Utils::noHtml($this->banco);
        $this->cargo = Utils::noHtml($this->cargo);
        $this->ciudad = Utils::noHtml($this->ciudad);
        $this->codpostal = Utils::noHtml($this->codpostal);
        $this->direccion = Utils::noHtml($this->direccion);
        $this->provincia = Utils::noHtml($this->provincia);
        $this->seg_social = Utils::noHtml($this->seg_social);

        if (empty($this->codagente)) {
            $this->codagente = $this->newCode();
        }

        if ($this->debaja && empty($this->fechabaja)) {
            $this->fechabaja = date('d-m-Y');
        }

        return true;
    }
}
