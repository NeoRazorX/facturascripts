<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Familias;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A family of products.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Familia extends ModelClass
{
    use ModelTrait;

    /** Código identificativo de la familia de productos. @var string */
    public $codfamilia;

    /** Código de la subcuenta contable utilizada para compras. @var string */
    public $codsubcuentacom;

    /** Código de la subcuenta de compras utilizada cuando se aplica IRPF. @var string */
    public $codsubcuentairpfcom;

    /** Código de la subcuenta contable utilizada para ventas. @var string */
    public $codsubcuentaven;

    /** Descripción de la familia de productos. @var string */
    public $descripcion;

    /** Código de la familia superior. @var string */
    public $madre;

    /** Número de productos asociados a la familia. @var int */
    public $numproductos;

    public function changeId($new_id): bool
    {
        // nos guardamos las subfamilias
        $subFamilias = $this->getSubFamilias();

        // les quitamos la madre
        foreach ($subFamilias as $subFamilia) {
            $subFamilia->madre = null;
            $subFamilia->save();
        }

        if (false === parent::changeId($new_id)) {
            // les volvemos a poner la madre
            foreach ($subFamilias as $subFamilia) {
                $subFamilia->madre = $this->codfamilia;
                $subFamilia->save();
            }
            return false;
        }

        // actualizamos las subfamilias
        foreach ($subFamilias as $subFamilia) {
            $subFamilia->madre = $new_id;
            $subFamilia->save();
        }

        return true;
    }

    public function clear(): void
    {
        parent::clear();
        $this->numproductos = 0;
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Familias::clear();
    }

    /**
     * @return static[]
     */
    public function getSubFamilias(): array
    {
        $orderBy = ['descripcion' => 'ASC'];
        return static::allWhereEq('madre', $this->codfamilia, $orderBy);
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
        $this->codfamilia = Tools::noHtml($this->codfamilia);
        if ($this->codfamilia && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codfamilia)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codfamilia, '%column%' => 'codfamilia', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        // comprobamos descripción
        $this->descripcion = Tools::noHtml($this->descripcion);
        if (empty($this->descripcion)) {
            Tools::log()->warning('field-required', ['%field%' => 'descripcion']);
            return false;
        }

        return parent::test() && $this->testLoops() && $this->testAccounting();
    }

    private static function getSubaccountFromFamily(?string $code, string $field, ?Familia $model = null): string
    {
        if (empty($code)) {
            return '';
        }

        if (!isset($model)) {
            $model = new Familia();
        }

        if (false === $model->load($code)) {
            return '';
        }

        return empty($model->{$field}) && $model->madre != $code ?
            self::getSubaccountFromFamily($model->madre, $field, $model) :
            (string)$model->{$field};
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codfamilia)) {
            $this->codfamilia = $this->newCode();
        }

        return parent::saveInsert();
    }

    protected function testAccounting(): bool
    {
        // comprobamos las subcuentas vinculadas
        $subAccount = new DinSubcuenta();
        if ($this->codsubcuentacom) {
            if (false === $subAccount->loadWhereEq('codsubcuenta', $this->codsubcuentacom)) {
                Tools::log()->warning('family-purchases-subaccount-not-found', [
                    '%family%' => $this->codfamilia,
                    '%subaccount%' => $this->codsubcuentacom
                ]);
                return false;
            }
        }
        if (false === empty($this->codsubcuentairpfcom)) {
            if (false === $subAccount->loadWhereEq('codsubcuenta', $this->codsubcuentairpfcom)) {
                Tools::log()->warning('irpf-subaccount-not-found');
                return false;
            }
        }
        if (false === empty($this->codsubcuentaven)) {
            if (false === $subAccount->loadWhereEq('codsubcuenta', $this->codsubcuentaven)) {
                Tools::log()->warning('sales-subaccount-not-found');
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
        while ($fam->madre && $fam->load($fam->madre)) {
            if (in_array($fam->codfamilia, $ancestros)) {
                Tools::log()->warning('parent-family-cant-be-child');
                return false;
            }

            $ancestros[] = $fam->codfamilia;
        }

        return true;
    }
}
