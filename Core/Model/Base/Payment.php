<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\PaymentToAccounting;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Description of Payment
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Payment extends ModelClass
{
    use PaymentRelationTrait;
    use AccEntryRelationTrait;

    /**
     * @var bool
     */
    protected $disableAccountingGeneration = false;

    /**
     * @var string
     */
    public $fecha;

    /**
     * @var string
     */
    public $hora;

    /**
     * @var int
     */
    public $idpago;

    /**
     * @var int
     */
    public $idrecibo;

    /**
     * @var float
     */
    public $importe;

    /**
     * @var string
     */
    public $nick;

    abstract public function getReceipt();

    public function clear()
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
        $this->importe = 0.0;
    }

    public function delete(): bool
    {
        // remove accounting
        $acEntry = $this->getAccountingEntry();
        $acEntry->editable = true;
        if ($acEntry->exists() && false === $acEntry->delete()) {
            Tools::log()->warning('cant-remove-accounting-entry');
            return false;
        }

        return parent::delete();
    }

    public function disableAccountingGeneration(bool $value = true): void
    {
        $this->disableAccountingGeneration = $value;
    }

    public function install(): string
    {
        // needed dependencies
        new Asiento();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpago';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->idasiento) ? $this->getReceipt()->url() : $this->getAccountingEntry()->url();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->idasiento) && !$this->disableAccountingGeneration) {
            $tool = new PaymentToAccounting();
            $tool->generate($this);
        }

        return parent::saveInsert($values);
    }
}
