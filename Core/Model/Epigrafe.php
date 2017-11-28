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
 * Segundo nivel del plan contable.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Epigrafe
{

    use Base\ModelTrait {
        url as private traitURL;
    }

    /**
     * Lista de grupos
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
     * Existen varias versiones de la contabilidad de Eneboo/Abanq,
     * en una tenemos grupos, epigrafes, cuentas y subcuentas: 4 niveles.
     * En la otra tenemos epígrafes (con hijos), cuentas y subcuentas: multi-nivel.
     * FacturaScripts usa un híbrido: grupos, epígrafes (con hijos), cuentas
     * y subcuentas.
     *
     * @var int
     */
    public $idpadre;

    /**
     * Código de epígrafe
     *
     * @var string
     */
    public $codepigrafe;

    /**
     * Identificador de grupo
     *
     * @var int
     */
    public $idgrupo;

    /**
     * Código de ejercicio
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Descripción del epígrafe.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Grupo al que pertenece.
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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idepigrafe';
    }

    /**
     * Devuelve el codepigrade del epigrafe padre o false si no lo hay
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
     * Devuelve los epígrafes hijo
     *
     * @return array
     */
    public function hijos()
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE idpadre = ' . $this->dataBase->var2str($this->idepigrafe)
            . ' ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new self($ep);
            }
        }

        return $epilist;
    }

    /**
     * Devuelve las cuentas del epígrafe
     *
     * @return Cuenta[]
     */
    public function getCuentas()
    {
        $cuenta = new Cuenta();

        return $cuenta->fullFromEpigrafe($this->idepigrafe);
    }

    /**
     * Obtiene el primer epígrafe del ejercicio
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|Epigrafe
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codepigrafe = ' . $this->dataBase->var2str($cod)
            . ' AND codejercicio = ' . $this->dataBase->var2str($codejercicio) . ';';

        $data = $this->dataBase->select($sql);
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
        $this->miniLog->alert($this->i18n->trans('missing-epigraph-data'));

        return false;
    }

    /**
     * Devuelve todos los epígrafes del ejercicios sin idpadre ni idgrupo
     *
     * @param string $codejercicio
     *
     * @return self[]
     */
    public function superFromEjercicio($codejercicio)
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE codejercicio = ' . $this->dataBase->var2str($codejercicio)
            . ' AND idpadre IS NULL AND idgrupo IS NULL ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new self($ep);
            }
        }

        return $epilist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $sql = 'UPDATE ' . static::tableName()
            . ' SET idgrupo = NULL WHERE idgrupo NOT IN (SELECT idgrupo FROM co_gruposepigrafes);';
        $this->dataBase->exec($sql);
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
        /// forzamos los creación de la tabla de grupos
        new GrupoEpigrafes();

        return '';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitURL($type, 'ListCuenta&active=List');
    }
}
