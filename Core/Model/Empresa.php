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

/**
 * This class stores the main data of the company.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Empresa
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $id;

    /**
     * True -> activates the use of an equivalence surcharge on delivery notes and purchase invoices.
     *
     * @var bool
     */
    public $recequivalencia;

    /**
     * Name of the company administrator.
     *
     * @var string
     */
    public $administrador;

    /**
     * Tax identification code of the company.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Company name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Short name of the company, to show on the menu.
     *
     * @var string Name to show in the menu.
     */
    public $nombrecorto;

    /**
     * Start date of the activity.
     *
     * @var string
     */
    public $inicio_actividad;

    /**
     * VAT regime of the company.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'empresas';
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
     * Check the company's data, return TRUE if correct
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);
        $this->nombrecorto = self::noHtml($this->nombrecorto);
        $this->administrador = self::noHtml($this->administrador);
        $this->apartado = self::noHtml($this->apartado);
        $this->cifnif = self::noHtml($this->cifnif);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->codpostal = self::noHtml($this->codpostal);
        $this->direccion = self::noHtml($this->direccion);
        $this->email = self::noHtml($this->email);
        $this->fax = self::noHtml($this->fax);
        $this->provincia = self::noHtml($this->provincia);
        $this->telefono = self::noHtml($this->telefono);
        $this->web = self::noHtml($this->web);

        $lenName = strlen($this->nombre);
        if (($lenName == 0) || ($lenName > 99)) {
            self::$miniLog->alert(self::$i18n->trans('company-name-invalid'));

            return false;
        }

        if ($lenName < strlen($this->nombrecorto)) {
            self::$miniLog->alert(self::$i18n->trans('company-short-name-smaller-name'));

            return false;
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
        $num = mt_rand(1, 9999);

        return 'INSERT INTO ' . static::tableName() . ' (recequivalencia,web,email,fax,telefono,codpais,apartado,'
            . 'provincia,ciudad,codpostal,direccion,administrador,cifnif,nombre,nombrecorto)'
            . "VALUES (NULL,'https://www.facturascripts.com',"
            . "NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','','00000014Z',"
            . "'Empresa " . $num . " S.L.','E-" . $num . "');";
    }
}
