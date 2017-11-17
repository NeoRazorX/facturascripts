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
 * Esta clase almacena los principales datos de la empresa.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Empresa
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Clave primaria. Integer.
     *
     * @var int
     */
    public $id;

    /**
     * TRUE -> activa el uso de recargo de equivalencia en los albaranes y facturas de compra.
     *
     * @var bool
     */
    public $recequivalencia;

    /**
     * Nombre del administrador de la empresa.
     *
     * @var string
     */
    public $administrador;

    /**
     * Código de identificación fiscal dela empresa.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Nombre de la empresa.
     *
     * @var string
     */
    public $nombre;

    /**
     * Nombre corto de la empresa, para mostrar en el menú
     *
     * @var string Nombre a mostrar en el menú de facturaScripts.
     */
    public $nombrecorto;

    /**
     * Fecha de inicio de la actividad.
     *
     * @var string
     */
    public $inicio_actividad;

    /**
     * Régimen de IVA de la empresa.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'empresas';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Comprueba los datos de la empresa, devuelve TRUE si es correcto
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
            $this->miniLog->alert($this->i18n->trans('company-name-invalid'));

            return false;
        }

        if ($lenName < strlen($this->nombrecorto)) {
            $this->miniLog->alert($this->i18n->trans('company-short-name-smaller-name'));

            return false;
        }

        return true;
    }

    /**
     * Crea la consulta necesaria para dotar de datos a la empresa en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        $num = mt_rand(1, 9999);

        return 'INSERT INTO ' . $this->tableName() . ' (recequivalencia,web,email,fax,telefono,codpais,apartado,provincia,'
            . 'ciudad,codpostal,direccion,administrador,cifnif,nombre,nombrecorto)'
            . "VALUES (NULL,'https://www.facturascripts.com',"
            . "NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','','00000014Z',"
            . "'Empresa " . $num . " S.L.','E-" . $num . "');";
    }
}
