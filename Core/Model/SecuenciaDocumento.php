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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * Personalize the numeration and code of sale and purchase documents.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class SecuenciaDocumento extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $codejercicio;

    /**
     *
     * @var string
     */
    public $codserie;

    /**
     *
     * @var int
     */
    public $idempresa;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idsecuencia;

    /**
     *
     * @var int
     */
    public $longnumero;

    /**
     *
     * @var int
     */
    public $numero;

    /**
     *
     * @var string
     */
    public $patron;

    /**
     *
     * @var string
     */
    public $tipodoc;

    public function clear()
    {
        parent::clear();
        $this->longnumero = 6;
        $this->numero = 1;
        $this->patron = '{EJE}{SERIE}{0NUM}';
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Ejercicio();
        new Serie();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idsecuencia';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'secuencias_documentos';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->patron = Utils::noHtml($this->patron);
        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListFormatoDocumento?activetab=List')
    {
        return parent::url($type, $list);
    }
}
