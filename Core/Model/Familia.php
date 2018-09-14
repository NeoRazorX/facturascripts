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
 * A family of products.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Familia extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Sub-account code for purchases.
     *
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Code for the shopping sub-account, but with IRPF.
     *
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Sub-account code for sales.
     *
     * @var string
     */
    public $codsubcuentaven;

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
     * Returns the daughter families.
     *
     * @param string $codmadre
     *
     * @return self[]
     */
    public function hijas($codmadre = '')
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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codfamilia';
    }

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
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codfamilia = Utils::noHtml($this->codfamilia);
        $this->descripcion = Utils::noHtml($this->descripcion);

        if (empty($this->codfamilia) || strlen($this->codfamilia) > 8) {
            self::$miniLog->alert(self::$i18n->trans('family-code-valid-length'));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            self::$miniLog->alert(self::$i18n->trans('family-desc-not-valid'));
        } elseif ($this->madre === $this->codfamilia) {
            self::$miniLog->alert(self::$i18n->trans('parent-family-cant-be-child'));
        } else {
            return parent::test();
        }

        return false;
    }

    /**
     * Complete the data in the list of families with the level.
     *
     * @param array  $familias
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
