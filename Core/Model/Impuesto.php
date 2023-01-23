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
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Dinamic\Model\Impuesto as DinImpuesto;

/**
 * A tax (VAT) that can be associated to articles, delivery notes lines,
 * invoices, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto extends Base\ModelClass
{

    use Base\ModelTrait;

    const TYPE_PERCENTAGE = 1;
    const TYPE_FIXED_VALUE = 2;

    /**
     * Primary key. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * @var string
     */
    public $codsubcuentarep;

    /**
     * @var string
     */
    public $codsubcuentarep_recargo;

    /**
     * @var string
     */
    public $codsubcuentasop;

    /**
     * @var string
     */
    public $codsubcuentasop_recargo;

    /**
     * Description of the tax.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Type of tax.
     *
     * @var int
     */
    public $tipo;

    /**
     * Value of VAT.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Value of the surcharge.
     *
     * @var float|int
     */
    public $recargo;

    public function clear()
    {
        parent::clear();
        $this->tipo = self::TYPE_PERCENTAGE;
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-tax');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Impuestos::clear();
            return true;
        }

        return false;
    }

    /**
     * Gets the input tax accounting subaccount indicated.
     * If it does not exist, the default tax is returned.
     *
     * @param string $subAccount
     *
     * @return static
     */
    public function inputVatFromSubAccount(string $subAccount)
    {
        return $this->getVatFromSubAccount('codsubcuentarep', $subAccount);
    }

    /**
     * Returns True if this is the default tax.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codimpuesto === $this->toolBox()->appSettings()->get('default', 'codimpuesto');
    }

    public static function primaryColumn(): string
    {
        return 'codimpuesto';
    }

    /**
     * Gets the output tax accounting subaccount indicated.
     * If it does not exist, the default tax is returned.
     *
     * @param string $subAccount
     *
     * @return static
     */
    public function outputVatFromSubAccount(string $subAccount)
    {
        return $this->getVatFromSubAccount('codsubcuentasop', $subAccount);
    }

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Impuestos::clear();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'impuestos';
    }

    public function test(): bool
    {
        $this->codimpuesto = self::toolBox()::utils()::noHtml($this->codimpuesto);
        if ($this->codimpuesto && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codimpuesto)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codimpuesto, '%column%' => 'codimpuesto', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        $this->codsubcuentarep = empty($this->codsubcuentarep) ? null : $this->codsubcuentarep;
        $this->codsubcuentarep_recargo = empty($this->codsubcuentarep_recargo) ? null : $this->codsubcuentarep_recargo;
        $this->codsubcuentasop = empty($this->codsubcuentasop) ? null : $this->codsubcuentasop;
        $this->codsubcuentasop_recargo = empty($this->codsubcuentasop_recargo) ? null : $this->codsubcuentasop_recargo;
        $this->descripcion = self::toolBox()::utils()::noHtml($this->descripcion);
        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
    
    /**
     * @param string $field
     * @param string $subAccount
     *
     * @return static
     */
    private function getVatFromSubAccount(string $field, string $subAccount)
    {
        $result = new DinImpuesto();
        $where = [new DataBaseWhere($field, $subAccount)];
        if ($result->loadFromCode('', $where)) {
            return $result;
        }

        $result->loadFromCode($this->toolBox()->appSettings()->get('default', 'codimpuesto'));
        return $result;
    }
}
