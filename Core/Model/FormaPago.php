<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CuentaBanco as DinCuentaBanco;

/**
 * Payment method of an invoice, delivery note, order or estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago extends ModelClass
{
    use ModelTrait;

    /** @var bool */
    public $activa;

    /** @var string */
    public $codcuentabanco;

    /** @var string */
    public $codpago;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $domiciliado;

    /** @var int */
    public $idempresa;

    /** @var bool */
    public $imprimir;

    /** @var bool */
    public $pagado;

    /** @var int */
    public $plazovencimiento;

    /** @var string */
    public $tipovencimiento;

    public function clear(): void
    {
        parent::clear();
        $this->activa = true;
        $this->domiciliado = false;
        $this->imprimir = true;
        $this->plazovencimiento = 0;
        $this->tipovencimiento = 'days';
    }

    public function clearCache(): void
    {
        parent::clearCache();
        FormasPago::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-payment-method');
            return false;
        }

        return parent::delete();
    }

    /**
     * Return the bank account.
     *
     * @return DinCuentaBanco
     */
    public function getBankAccount(): CuentaBanco
    {
        $bank = new DinCuentaBanco();
        $bank->load($this->codcuentabanco);
        return $bank;
    }

    public function getSubcuenta(string $codejercicio, bool $create): Subcuenta
    {
        return $this->getBankAccount()->getSubcuenta($codejercicio, $create);
    }

    public function getSubcuentaGastos(string $codejercicio, bool $create): Subcuenta
    {
        return $this->getBankAccount()->getSubcuentaGastos($codejercicio, $create);
    }

    /**
     * Returns the date with the expiration term applied.
     *
     * @param string $date
     *
     * @return string
     */
    public function getExpiration(string $date): string
    {
        return Tools::date($date . ' +' . $this->plazovencimiento . ' ' . $this->tipovencimiento);
    }

    public function install(): string
    {
        // needed dependencies
        new CuentaBanco();

        return parent::install();
    }

    /**
     * Returns True if this is the default payment method.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codpago === Tools::settings('default', 'codpago');
    }

    public static function primaryColumn(): string
    {
        return 'codpago';
    }

    public static function tableName(): string
    {
        return 'formaspago';
    }

    public function test(): bool
    {
        $this->codpago = Tools::noHtml($this->codpago);
        $this->descripcion = Tools::noHtml($this->descripcion);

        if ($this->codpago && 1 !== preg_match('/^[A-Z0-9_\+\.\-\s]{1,10}$/i', $this->codpago)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codpago, '%column%' => 'codpago', '%min%' => '1', '%max%' => '10']
            );
            return false;
        } elseif ($this->plazovencimiento < 0) {
            Tools::log()->warning('number-expiration-invalid');
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codpago)) {
            $this->codpago = $this->newLetterCode();
        }

        return parent::saveInsert();
    }

    private function newLetterCode(): string
    {
        $desc = preg_replace('/[^A-Z0-9_\+\.\-]/i', '', strtoupper($this->descripcion ?? ''));
        $prefix = substr($desc, 0, 4);

        // try the first 4 letters of the description
        if (strlen($prefix) === 4 && false === $this->codpagoExists($prefix)) {
            return $prefix;
        }

        // try the 4-letter prefix + digit (2 to 9)
        if (strlen($prefix) === 4) {
            for ($digit = 2; $digit <= 9; $digit++) {
                $candidate = $prefix . $digit;
                if (false === $this->codpagoExists($candidate)) {
                    return $candidate;
                }
            }
        }

        return (string)$this->newCode();
    }

    private function codpagoExists(string $codpago): bool
    {
        foreach (FormasPago::all() as $formaPago) {
            if (strtoupper($formaPago->codpago) === strtoupper($codpago)) {
                return true;
            }
        }

        return false;
    }
}
