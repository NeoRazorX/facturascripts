<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015         Pablo Peralta
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * A family of products.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Familia
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Family's description.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Mother family code.
     *
     * @var string
     */
    public $madre;

    /**
     * Level.
     *
     * @var string
     */
    public $nivel;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'familias';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codfamilia';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codfamilia = self::noHtml($this->codfamilia);
        $this->descripcion = self::noHtml($this->descripcion);
        $this->madre = self::noHtml($this->madre);

        if (empty($this->codfamilia) || strlen($this->codfamilia) > 8) {
            self::$miniLog->alert(self::$i18n->trans('family-code-valid-length'));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            self::$miniLog->alert(self::$i18n->trans('family-desc-not-valid'));
        } elseif ($this->madre === $this->codfamilia) {
            self::$miniLog->alert(self::$i18n->trans('parent-family-cant-be-child'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Returns the mother families.
     *
     * @return self[]
     */
    public function madres()
    {
        $famlist = [];

        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE madre IS NULL ORDER BY lower(descripcion) ASC;';
        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new self($d);
            }
        }

        if (empty($famlist)) {
            /// if the list is empty, we put mother to null in all in case the user has been playing
            $sql = 'UPDATE ' . static::tableName() . ' SET madre = NULL;';
            self::$dataBase->exec($sql);
        }

        return $famlist;
    }

    /**
     * Returns the daughter families.
     *
     * @param string|bool $codmadre
     *
     * @return self[]
     */
    public function hijas($codmadre = false)
    {
        $famlist = [];

        if (!empty($codmadre)) {
            $codmadre = $this->codfamilia;
        }

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE madre = ' . self::$dataBase->var2str($codmadre) . ' ORDER BY descripcion ASC;';
        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new self($d);
            }
        }

        return $famlist;
    }

    /**
     * Apply corrections to the table.
     */
    public function fixDb()
    {
        /// we check that families with mother, their mother exists.
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE madre IS NOT NULL;';
        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $fam = $this->get($d['madre']);
                if (!$fam) {
                    /// if it does not exist, we disassociate
                    $sql = 'UPDATE ' . static::tableName() . ' SET madre = null WHERE codfamilia = '
                        . self::$dataBase->var2str($d['codfamilia']) . ':';
                    self::$dataBase->exec($sql);
                }
            }
        }
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

    /**
     * Complete the data in the list of families with the level.
     *
     * @param array $familias
     * @param string $madre
     * @param string $nivel
     *
     * @return array
     */
    private function auxAll(&$familias, $madre, $nivel)
    {
        $subfamilias = [];

        foreach ($familias as $fam) {
            if ($fam['madre'] === $madre) {
                $fam['nivel'] = $nivel;
                $subfamilias[] = $fam;
                foreach ($this->auxAll($familias, $fam['codfamilia'], '&nbsp;&nbsp;' . $nivel) as $value) {
                    $subfamilias[] = $value;
                }
            }
        }

        return $subfamilias;
    }
}
