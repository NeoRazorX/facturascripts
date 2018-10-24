<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * Payment method of an invoice, delivery note, order or estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Code of the associated bank account.
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
     * Sub-account code.
     *
     * @var string
     */
    public $codsubcuenta;

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
     * Paid -> mark the invoices generated as paid.
     *
     * @var string
     */
    public $genrecibos;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * True (default) -> display the data in sales documents,
     * including the associated bank account.
     *
     * @var bool
     */
    public $imprimir;

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
        $this->idempresa = AppSettings::get('default', 'idempresa');
        $this->domiciliado = false;
        $this->genrecibos = 'Emitidos';
        $this->imprimir = true;
        $this->plazovencimiento = 0;
        $this->tipovencimiento = 'days';
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
        new CuentaBanco();     // Install: CuentaBanco() + Empresa()
        return parent::install();
    }

    /**
     * Returns True if is the default payment method for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpago === AppSettings::get('default', 'codpago');
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
        $this->descripcion = Utils::noHtml($this->descripcion);

        /// we check the expiration validity
        if ($this->plazovencimiento < 0) {
            self::$miniLog->alert(self::$i18n->trans('number-expiration-invalid'));
            return false;
        }

        return parent::test();
    }
}
