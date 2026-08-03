<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\AccEntryRelationTrait;
use FacturaScripts\Core\Model\Base\PaymentRelationTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\PaymentToAccounting;
use FacturaScripts\Dinamic\Model\ReciboCliente as DinReciboCliente;

/**
 * Description of PagoCliente
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PagoCliente extends ModelClass
{
    use ModelTrait;
    use AccEntryRelationTrait;
    use PaymentRelationTrait;

    /** @var string Identificador del pago en un sistema externo. */
    public $customid;

    /** @var string Estado del pago asignado por un sistema externo. */
    public $customstatus;

    /** @var bool Indica si se debe omitir la generación automática del asiento contable. */
    protected $disable_accounting_generation = false;

    /** @var string Fecha en la que se realizó el pago del cliente. */
    public $fecha;

    /** @var float Gastos bancarios asociados al pago. */
    public $gastos;

    /** @var string Hora en la que se realizó el pago del cliente. */
    public $hora;

    /** @var int Identificador único del pago del cliente. */
    public $idpago;

    /** @var int Identificador del recibo de cliente pagado. */
    public $idrecibo;

    /** @var float Importe del pago realizado. */
    public $importe;

    /** @var string Nombre del usuario que registró el pago. */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->gastos = 0.0;
        $this->hora = Tools::hour();
        $this->importe = 0.0;
        $this->nick = Session::user()->nick;
    }

    public function delete(): bool
    {
        // si no podemos eliminar el asiento, no eliminamos el pago
        $entry = $this->getAccountingEntry();
        $entry->editable = true;
        if ($entry->exists() && false === $entry->delete()) {
            Tools::log()->warning('cant-remove-accounting-entry');
            return false;
        }

        return parent::delete();
    }

    public function disableAccountingGeneration(bool $value = true): void
    {
        $this->disable_accounting_generation = $value;
    }

    public function getReceipt(): DinReciboCliente
    {
        $receipt = new DinReciboCliente();
        $receipt->load($this->idrecibo);
        return $receipt;
    }

    public function install(): string
    {
        // needed dependencies
        new Asiento();
        new ReciboCliente();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpago';
    }

    public static function tableName(): string
    {
        return 'pagoscli';
    }

    public function test(): bool
    {
        $this->customid = Tools::noHtml($this->customid);
        $this->customstatus = Tools::noHtml($this->customstatus);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->idasiento) ? $this->getReceipt()->url() : $this->getAccountingEntry()->url();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->idasiento) && !$this->disable_accounting_generation) {
            $tool = new PaymentToAccounting();
            $tool->generate($this);
        }

        return parent::saveInsert();
    }
}
