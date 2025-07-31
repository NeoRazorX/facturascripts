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
use FacturaScripts\Dinamic\Model\ReciboProveedor as DinReciboProveedor;

/**
 * Description of PagoProveedor
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PagoProveedor extends ModelClass
{
    use ModelTrait;
    use AccEntryRelationTrait;
    use PaymentRelationTrait;

    /** @var bool */
    protected $disable_accounting_generation = false;

    /** @var string */
    public $fecha;

    /** @var string */
    public $hora;

    /** @var int */
    public $idpago;

    /** @var int */
    public $idrecibo;

    /** @var float */
    public $importe;

    /** @var string */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
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

    public function getReceipt(): DinReciboProveedor
    {
        $receipt = new DinReciboProveedor();
        $receipt->load($this->idrecibo);
        return $receipt;
    }

    public function install(): string
    {
        // needed dependencies
        new Asiento();
        new ReciboProveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpago';
    }

    public static function tableName(): string
    {
        return 'pagosprov';
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
