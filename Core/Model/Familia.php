<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * Get the accounting sub-account for purchases.
     *
     * @param string $code
     *
     * @return string
     */
    public static function purchaseSubAccount($code)
    {
        return static::getSubaccountFromFamily($code, 'codsubcuentacom');
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
        $utils = $this->toolBox()->utils();
        $this->codfamilia = $utils->noHtml($this->codfamilia);
        $this->descripcion = $utils->noHtml($this->descripcion);

        if ($this->codfamilia && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codfamilia)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codfamilia, '%column%' => 'codfamilia', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        if (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '100']
            );
            return false;
        }

        if ($this->madre === $this->codfamilia) {
            $this->madre = null;
        }

        return parent::test();
    }

    /**
     * Get the accounting sub-account for sales.
     *
     * @param string $code
     *
     * @return string
     */
    public static function saleSubAccount($code)
    {
        return self::getSubaccountFromFamily($code, 'codsubcuentaven');
    }

    /**
     *
     * @param string  $code
     * @param string  $field
     * @param Familia $model
     *
     * @return string
     */
    private static function getSubaccountFromFamily($code, $field, $model = null)
    {
        if (empty($code)) {
            return '';
        }

        if (!isset($model)) {
            $model = new Familia();
        }

        if (false === $model->loadFromCode($code)) {
            return '';
        }

        return empty($model->{$field}) && $model->madre != $code ?
            self::getSubaccountFromFamily($model->madre, $field, $model) :
            $model->{$field};
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codfamilia)) {
            $this->codfamilia = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
