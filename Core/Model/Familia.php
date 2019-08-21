<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
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

        if (!preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codfamilia)) {
            self::$miniLog->error(self::$i18n->trans('invalid-alphanumeric-code', ['%value%' => $this->codfamilia, '%column%' => 'codfamilia', '%min%' => '1', '%max%' => '8']));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            self::$miniLog->warning(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '100']));
        } elseif ($this->madre === $this->codfamilia) {
            self::$miniLog->warning(self::$i18n->trans('parent-family-cant-be-child'));
        } else {
            return parent::test();
        }

        return false;
    }
}
