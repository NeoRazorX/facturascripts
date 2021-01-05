<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Model to personalize the impresion of sales and buy documents.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class FormatoDocumento extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Foreign key with series table
     *
     * @var string
     */
    public $codserie;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Foreign key with table business
     *
     * @var int
     */
    public $idempresa;

    /**
     *
     * @var int
     */
    public $idlogo;

    /**
     *
     * @var string
     */
    public $nombre;

    /**
     * 
     *
     * @var string
     */
    public $texto;

    /**
     * 
     *
     * @var string
     */
    public $tipodoc;

    /**
     * 
     *
     * @var string
     */
    public $titulo;

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Serie();
        new Empresa();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'formatos_documentos';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->nombre = empty($this->nombre) ? $utils->noHtml($this->titulo) : $utils->noHtml($this->nombre);
        $this->texto = $utils->noHtml($this->texto);
        $this->titulo = $utils->noHtml($this->titulo);

        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListSecuenciaDocumento?activetab=List')
    {
        return parent::url($type, $list);
    }
}
