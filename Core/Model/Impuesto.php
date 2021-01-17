<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * A tax (VAT) that can be associated to articles, delivery notes lines,
 * invoices, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto extends Base\ModelClass
{

    use Base\ModelTrait;

    const TYPE_PENCENTAGE = 1;
    const TYPE_FIXED_VALUE = 2;

    /**
     * Primary key. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     *
     * @var string
     */
    public $codsubcuentarep;

    /**
     *
     * @var string
     */
    public $codsubcuentasop;

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

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->tipo = self::TYPE_PENCENTAGE;
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Removes tax from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-tax');
            return false;
        }

        return parent::delete();
    }

    /**
     * Gets the input tax accounting subaccount indicated.
     * If it does not exist, the default tax is returned.
     * 
     * @param string $subAccount
     *
     * @return self
     */
    public function inputVatFromSubAccount($subAccount)
    {
        return $this->getVatFromSubAccount('codsubcuentarep', $subAccount);
    }

    /**
     * Returns True if this is the default tax.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codimpuesto === $this->toolBox()->appSettings()->get('default', 'codimpuesto');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codimpuesto';
    }

    /**
     * Gets the output tax accounting subaccount indicated.
     * If it does not exist, the default tax is returned.
     * 
     * @param string $subAccount
     *
     * @return self
     */
    public function outputVatFromSubAccount($subAccount)
    {
        return $this->getVatFromSubAccount('codsubcuentasop', $subAccount);
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestos';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codimpuesto = \trim($this->codimpuesto);
        if ($this->codimpuesto && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codimpuesto)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codimpuesto, '%column%' => 'codimpuesto', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        $this->codsubcuentarep = empty($this->codsubcuentarep) ? null : $this->codsubcuentarep;
        $this->codsubcuentasop = empty($this->codsubcuentasop) ? null : $this->codsubcuentasop;
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        return parent::test();
    }

    /**
     * 
     * @param string $field
     * @param string $subAccount
     *
     * @return static
     */
    private function getVatFromSubAccount($field, $subAccount)
    {
        $result = new Impuesto();
        $where = [new DataBaseWhere($field, $subAccount)];
        if ($result->loadFromCode('', $where)) {
            return $result;
        }

        $result->loadFromCode($this->toolBox()->appSettings()->get('default', 'codimpuesto'));
        return $result;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = (string) $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
