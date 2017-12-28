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
 * Second level of the accounting plan.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Epigrafe
{

    use Base\ModelTrait {
        url as private traitUrl;
    }

    /**
     * List of groups.
     *
     * @var GrupoEpigrafes[]
     */
    private static $grupos;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idepigrafe;
    
    /**
     *Identificacion de la empresa
     *
     * @var int
     */
    public $idempresa;

    /**
     * There are several versions of the accounting of Eneboo / Abanq,
     * in one we have groups, epigraphs, accounts and sub-accounts: 4 levels.
     * In the other we have epigraphs (with children), accounts and sub-accounts: multi-level.
     * FacturaScripts uses a hybrid: groups, epigraphs (with children), accounts
     * and subaccounts.
     *
     * @var int
     */
    public $idpadre;

    /**
     * Code of epigraph.
     *
     * @var string
     */
    public $codepigrafe;

    /**
     * Group identifier.
     *
     * @var int
     */
    public $idgrupo;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Description of the epigraph.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Group to which it belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_epigrafes';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idepigrafe';
    }

    /**
     * Returns the codepigrade of the parent epigraph or false if there is not one.
     *
     * @return bool
     */
    public function codpadre()
    {
        $cod = false;

        if ($this->idpadre) {
            $padre = $this->get($this->idpadre);
            if ($padre) {
                $cod = $padre->codepigrafe;
            }
        }

        return $cod;
    }

    /**
     * Returns the child's epigraphs.
     *
     * @return array
     */
    public function hijos()
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE idpadre = ' . self::$dataBase->var2str($this->idepigrafe)
            . ' ORDER BY codepigrafe ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new self($ep);
            }
        }

        return $epilist;
    }

    /**
     * Returns the accounts of the epigraph.
     *
     * @return Cuenta[]
     */
    public function getCuentas()
    {
        $cuenta = new Cuenta();

        return $cuenta->fullFromEpigrafe($this->idepigrafe);
    }

    /**
     * Obtain the first section of the exercise.
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|Epigrafe
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codepigrafe = ' . self::$dataBase->var2str($cod)
            . ' AND codejercicio = ' . self::$dataBase->var2str($codejercicio) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
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

        if (strlen($this->codepigrafe) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }
        self::$miniLog->alert(self::$i18n->trans('missing-epigraph-data'));

        return false;
    }

    /**
     * Returns all the epigraphs of the exercises without idpadre or idgrupo.
     *
     * @param string $codejercicio
     *
     * @return self[]
     */
    public function superFromEjercicio($codejercicio)
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codejercicio = ' . self::$dataBase->var2str($codejercicio)
            . ' AND idpadre IS NULL AND idgrupo IS NULL ORDER BY codepigrafe ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new self($ep);
            }
        }

        return $epilist;
    }

    /**
     * Apply corrections to the table.
     */
    public function fixDb()
    {
        $sql = 'UPDATE ' . static::tableName()
            . ' SET idgrupo = NULL WHERE idgrupo NOT IN (SELECT idgrupo FROM co_gruposepigrafes);';
        self::$dataBase->exec($sql);
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
        /// force the creation of the group table
        new GrupoEpigrafes();

        return '';
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
