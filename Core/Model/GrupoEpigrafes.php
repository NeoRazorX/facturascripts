<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * First level of the accounting plan.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class GrupoEpigrafes
{

    use Base\ModelTrait {
        url as private traitUrl;
    }

    /**
     * Primary key.
     *
     * @var int
     */
    public $idgrupo;
    
    /**
     *Identificacion de la empresa
     *
     * @var int
     */
    public $idempresa;

    /**
     * Group to which it belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Description of the group of the epigraph.
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
        return 'co_gruposepigrafes';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idgrupo';
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
        /// we force the checking of the exercise table.
        new Ejercicio();

        return '';
    }

    /**
     * Returns the group's epigraphs.
     *
     * @return Epigrafe[]
     */
    public function getEpigrafes()
    {
        $epigrafe = new Epigrafe();

        return $epigrafe->allFromGrupo($this->idgrupo);
    }

    /**
     * Returns the epigraph group of the exercise code.
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|GrupoEpigrafes
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codgrupo = ' . self::$dataBase->var2str($cod)
            . ' AND codejercicio = ' . self::$dataBase->var2str($codejercicio) . ';';

        $grupo = self::$dataBase->select($sql);
        if (!empty($grupo)) {
            return new self($grupo[0]);
        }

        return false;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        if (strlen($this->codejercicio) > 0 && strlen($this->codgrupo) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }

        self::$miniLog->alert(self::$i18n->trans('missing-data-epigraph-group'));
        return false;
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
        return $this->traitUrl($type, 'ListCuenta&active=List');
    }
}
