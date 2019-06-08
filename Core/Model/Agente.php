<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * The agent/employee is the one associated with a delivery note, invoice o box.
 * Each user can be associated with an agent, an an agent can
 * can be associated with several user of none at all.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa    <jcuello@artextrading.com>
 */
class Agente extends Base\Contact
{

    use Base\ModelTrait;

    /**
     * Position in the company.
     *
     * @var string
     */
    public $cargo;

    /**
     * Primary key. Varchar (10).
     *
     * @var int
     */
    public $codagente;

    /**
     * True -> the agent no longer buys us or we do not want anything with him.
     *
     * @var boolean
     */
    public $debaja;

    /**
     * Date of withdrawal from the company.
     *
     * @var string
     */
    public $fechabaja;

    /**
     * Default contact data
     *
     * @var integer
     */
    public $idcontacto;

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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'agentes';
    }

    /**
     * Check employee / agent data, return TRUE if correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->cargo = Utils::noHtml($this->cargo);
        $this->debaja = (!empty($this->fechabaja));

        if (empty($this->codagente)) {
            $this->codagente = $this->newCode();
        }

        return parent::test();
    }
}
