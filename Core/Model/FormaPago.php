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

use FacturaScripts\Dinamic\Model\CuentaBanco as DinCuentaBanco;

/**
 * Payment method of an invoice, delivery note, order or estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Bank account identifier.
     *
     * @var string
     */
    public $codcuentabanco;

    /**
     * Primary key. Varchar (10).
     *
     * @var string
     */
    public $codpago;

    /**
     * Description of the payment method.
     *
     * @var string
     */
    public $descripcion;

    /**
     * To indicate if it is necessary to show the bank account of the client.
     *
     * @var bool
     */
    public $domiciliado;

    /**
     * Company identifier.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Indicate if pay or not
     *
     * @var bool
     */
    public $pagado;

    /**
     * Expiration period.
     *
     * @var int
     */
    public $plazovencimiento;

    /**
     * Type of expiration. varchar(10)
     *
     * @var string
     */
    public $tipovencimiento;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->domiciliado = false;
        $this->plazovencimiento = 0;
        $this->tipovencimiento = 'days';
    }

    /**
     * Removes payment method from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-payment-method');
            return false;
        }

        return parent::delete();
    }

    /**
     * Return the the banck account.
     *
     * @return DinCuentaBanco
     */
    public function getBankAccount()
    {
        $bank = new DinCuentaBanco();
        $bank->loadFromCode($this->codcuentabanco);
        return $bank;
    }

    /**
     * Returns the date with the expiration term applied.
     * 
     * @param string $date
     *
     * @return string
     */
    public function getExpiration($date)
    {
        return \date(self::DATE_STYLE, \strtotime($date . ' +' . $this->plazovencimiento . ' ' . $this->tipovencimiento));
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new CuentaBanco();

        return parent::install();
    }

    /**
     * Returns True if this is the default payment method.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpago === $this->toolBox()->appSettings()->get('default', 'codpago');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codpago';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'formaspago';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codpago = $this->toolBox()->utils()->noHtml($this->codpago);
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);

        if ($this->codpago && 1 !== \preg_match('/^[A-Z0-9_\+\.\-\s]{1,10}$/i', $this->codpago)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codpago, '%column%' => 'codpago', '%min%' => '1', '%max%' => '10']
            );
            return false;
        } elseif ($this->plazovencimiento < 0) {
            $this->toolBox()->i18nLog()->warning('number-expiration-invalid');
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        return parent::test();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codpago)) {
            $this->codpago = (string) $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
