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

/**
 * Una familia de artículos.
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
     * Descripción de la família
     *
     * @var string
     */
    public $descripcion;

    /**
     * Código de la familia madre.
     *
     * @var string
     */
    public $madre;

    /**
     * Nivel
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
            $this->miniLog->alert($this->i18n->trans('family-code-valid-length'));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            $this->miniLog->alert($this->i18n->trans('family-desc-not-valid'));
        } elseif ($this->madre === $this->codfamilia) {
            $this->miniLog->alert($this->i18n->trans('parent-family-cant-be-child'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Devuelve las famílias madre
     *
     * @return self[]
     */
    public function madres()
    {
        $famlist = [];

        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE madre IS NULL ORDER BY lower(descripcion) ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new self($d);
            }
        }

        if (empty($famlist)) {
            /// si la lista está vacía, ponemos madre a null en todas por si el usuario ha estado jugando
            $sql = 'UPDATE ' . static::tableName() . ' SET madre = NULL;';
            $this->dataBase->exec($sql);
        }

        return $famlist;
    }

    /**
     * Devuelve las famílias hijas
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
            . ' WHERE madre = ' . $this->dataBase->var2str($codmadre) . ' ORDER BY descripcion ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $famlist[] = new self($d);
            }
        }

        return $famlist;
    }

    /**
     * Aplicamos correcciones a la tabla.
     */
    public function fixDb()
    {
        /// comprobamos que las familias con madre, su madre exista.
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE madre IS NOT NULL;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $fam = $this->get($d['madre']);
                if (!$fam) {
                    /// si no existe, desvinculamos
                    $sql = 'UPDATE ' . static::tableName() . ' SET madre = null WHERE codfamilia = '
                        . $this->dataBase->var2str($d['codfamilia']) . ':';
                    $this->dataBase->exec($sql);
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
        return 'INSERT INTO ' . static::tableName() . " (codfamilia,descripcion) VALUES ('VARI','VARIOS');";
    }

    /**
     * Completa los datos de la lista de familias con el nivel
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
