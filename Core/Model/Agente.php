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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * The agent/employee is the one associated with a delivery note, invoice o box.
 * Each user can be associated with an agent, an an agent can
 * can be associated with several user of none at all.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Agente
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Primary key. Varchar (10).
     *
     * @var int
     */
    public $codagente;

    /**
     * Tax Identifier (CIF / NIF).
     *
     * @var string
     */
    public $dnicif;

    /**
     * Name of the agent or employee.
     *
     * @var string
     */
    public $nombre;

    /**
     * Last name of the agent or employee.
     *
     * @var string
     */
    public $apellidos;

    /**
     * Social security number.
     *
     * @var string
     */
    public $seg_social;

    /**
     * Position in the company.
     *
     * @var string
     */
    public $cargo;

    /**
     * Bank account.
     *
     * @var string
     */
    public $banco;

    /**
     * Birthdate.
     *
     * @var string
     */
    public $f_nacimiento;

    /**
     * Date of registration in the company.
     *
     * @var string
     */
    public $f_alta;

    /**
     * Date of withdrawal from the company.
     *
     * @var string
     */
    public $f_baja;

    /**
     * Percentage of the agent's commission. It is used in budgets, orders, delivery notes and invoices.
     *
     * @var float|int
     */
    public $porcomision;

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
    public function primaryColumn()
    {
        return 'codagente';
    }

    public function primaryDescriptionColumn() 
    {
        return 'nombre || \' \' || apellidos';
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
        $this->clearContactInformation();

        $this->codagente = null;
        $this->nombre = '';
        $this->apellidos = '';
        $this->dnicif = '';
        $this->porcomision = 0.00;
        $this->seg_social = null;
        $this->banco = null;
        $this->cargo = null;
        $this->f_alta = date('d-m-Y');
        $this->f_baja = null;
        $this->f_nacimiento = date('d-m-Y');
    }

    /**
     * Check employee / agent data, return TRUE if correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->apellidos = self::noHtml($this->apellidos);
        $this->banco = self::noHtml($this->banco);
        $this->cargo = self::noHtml($this->cargo);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->codpostal = self::noHtml($this->codpostal);
        $this->direccion = self::noHtml($this->direccion);
        $this->dnicif = self::noHtml($this->dnicif);
        $this->email = self::noHtml($this->email);
        $this->nombre = self::noHtml($this->nombre);
        $this->provincia = self::noHtml($this->provincia);
        $this->seg_social = self::noHtml($this->seg_social);
        $this->telefono = self::noHtml($this->telefono);

        if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 50)) {
            self::$miniLog->alert(self::$i18n->trans('agent-name-between-1-50'));

            return false;
        }

        if ($this->codagente === null) {
            $this->codagente = $this->newCode();
        }

        return true;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        return CSVImport::importTableSQL(static::tableName());
    }
}
