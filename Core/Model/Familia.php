<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A family of products.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
     * Number of products
     *
     * @var int
     */
    public $numproductos;

    public function clear()
    {
        parent::clear();
        $this->numproductos = 0;
    }

    public static function primaryColumn(): string
    {
        return 'codfamilia';
    }

    /**
     * Get the accounting account for irpf purchases.
     *
     * @param string $code
     *
     * @return string
     */
    public static function purchaseIrpfSubAccount(string $code): string
    {
        return self::getSubaccountFromFamily($code, 'codsubcuentairpfcom');
    }

    /**
     * Get the accounting account for purchases.
     *
     * @param string $code
     *
     * @return string
     */
    public static function purchaseSubAccount(string $code): string
    {
        return static::getSubaccountFromFamily($code, 'codsubcuentacom');
    }

    /**
     * Get the accounting account for sales.
     *
     * @param string $code
     *
     * @return string
     */
    public static function saleSubAccount(string $code): string
    {
        return self::getSubaccountFromFamily($code, 'codsubcuentaven');
    }

    public static function tableName(): string
    {
        return 'familias';
    }

    public function test(): bool
    {
        // comprobamos codfamilia
        $this->codfamilia = self::toolBox()::utils()::noHtml($this->codfamilia);
        if ($this->codfamilia && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codfamilia)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codfamilia, '%column%' => 'codfamilia', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        // comprobamos descripción
        $this->descripcion = self::toolBox()::utils()::noHtml($this->descripcion);
        if (empty($this->descripcion) || strlen($this->descripcion) > 100) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '100']
            );
            return false;
        }

        return parent::test() && $this->testLoops() && $this->testAccounting();
    }

    private static function getSubaccountFromFamily(?string $code, string $field, Familia $model = null): string
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
            (string)$model->{$field};
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codfamilia)) {
            $this->codfamilia = $this->newCode();
        }

        return parent::saveInsert($values);
    }

    protected function testAccounting(): bool
    {
        // comprobamos las subcuentas vinculadas
        $subaccount = new DinSubcuenta();
        if ($this->codsubcuentacom) {
            $where = [new DataBaseWhere('codsubcuenta', $this->codsubcuentacom)];
            if (false === $subaccount->loadFromCode('', $where)) {
                $this->toolBox()->i18nLog()->warning('purchases-subaccount-not-found');
                return false;
            }
        }
        if (false === empty($this->codsubcuentairpfcom)) {
            $where = [new DataBaseWhere('codsubcuenta', $this->codsubcuentairpfcom)];
            if (false === $subaccount->loadFromCode('', $where)) {
                $this->toolBox()->i18nLog()->warning('irpf-subaccount-not-found');
                return false;
            }
        }
        if (false === empty($this->codsubcuentaven)) {
            $where = [new DataBaseWhere('codsubcuenta', $this->codsubcuentaven)];
            if (false === $subaccount->loadFromCode('', $where)) {
                $this->toolBox()->i18nLog()->warning('sales-subaccount-not-found');
                return false;
            }
        }

        return true;
    }

    protected function testLoops(): bool
    {
        if (empty($this->madre)) {
            return true;
        }

        // comprobamos que la familia no sea su propia madre
        if ($this->madre === $this->codfamilia) {
            $this->madre = null;
            return true;
        }

        // recorremos los ancestros de esta familia, si repetimos ancestro es que hay un bucle, y eso es un problema
        $ancestros = [$this->codfamilia];
        $fam = new static();
        $fam->madre = $this->madre;
        while ($fam->madre && $fam->loadFromCode($fam->madre)) {
            if (in_array($fam->codfamilia, $ancestros)) {
                return false;
            }
            $ancestros[] = $fam->codfamilia;
        }

        return true;
    }
}
