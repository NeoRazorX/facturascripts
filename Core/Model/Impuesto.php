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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cuenta as DinCuenta;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * A tax (VAT) that can be associated to articles, delivery notes lines,
 * invoices, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto extends ModelClass
{
    use ModelTrait;

    const SPECIAL_TAX_IMPACTED_ACCOUNT = 'IVAREP';
    const SPECIAL_TAX_SUPPORTED_ACCOUNT = 'IVASOP';
    const TYPE_PERCENTAGE = 1;
    const TYPE_FIXED_VALUE = 2;

    /** @var bool */
    public $activo;

    /** @var string */
    public $codimpuesto;

    /**
     * Código de la subcuenta de IVA repercutido.
     * @var string
     */
    public $codsubcuentarep;

    /**
     * Código de la subcuenta de IVA repercutido intracomunitario.
     * @var string
     */
    public $codsubcuentarepintra;

    /**
     * Código de la subcuenta de recargo de equivalencia para el IVA repercutido.
     * @var string
     */
    public $codsubcuentarepre;

    /**
     * Código de la subcuenta de IVA soportado.
     * @var string
     */
    public $codsubcuentasop;

    /**
     * Código de la subcuenta de IVA soportado intracomunitario.
     * @var string
     */
    public $codsubcuentasopintra;

    /**
     * Código de la subcuenta de recargo de equivalencia para el IVA soportado.
     * @var string
     */
    public $codsubcuentasopre;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $operacion;

    /** @var int */
    public $tipo;

    /** @var float */
    public $iva;

    /** @var float */
    public $recargo;

    public function clear(): void
    {
        parent::clear();
        $this->activo = true;
        $this->iva = 0.0;
        $this->recargo = 0.0;
        $this->tipo = self::TYPE_PERCENTAGE;
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Impuestos::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-tax');
            return false;
        }

        return parent::delete();
    }

    public function getInputIntraTaxAccount(string $codejercicio): DinSubcuenta
    {
        return $this->codsubcuentasopintra ?
            $this->getSubAccount($codejercicio, $this->codsubcuentasopintra, static::SPECIAL_TAX_SUPPORTED_ACCOUNT) :
            $this->getInputTaxAccount($codejercicio);
    }

    public function getInputSurchargeAccount(string $codejercicio): DinSubcuenta
    {
        // si tenemos una cuenta definida, la devolvemos
        return $this->codsubcuentasopre ?
            $this->getSubAccount($codejercicio, $this->codsubcuentasopre, static::SPECIAL_TAX_SUPPORTED_ACCOUNT) :
            $this->getInputTaxAccount($codejercicio);
    }

    public function getInputTaxAccount(string $codejercicio): DinSubcuenta
    {
        // si tenemos una cuenta definida, la devolvemos
        return $this->codsubcuentasop ?
            $this->getSubAccount($codejercicio, $this->codsubcuentasop, static::SPECIAL_TAX_SUPPORTED_ACCOUNT) :
            $this->getSpecialSubAccount($codejercicio, static::SPECIAL_TAX_SUPPORTED_ACCOUNT);
    }

    public function getOutputIntraTaxAccount(string $codejercicio): DinSubcuenta
    {
        return $this->codsubcuentarepintra ?
            $this->getSubAccount($codejercicio, $this->codsubcuentarepintra, static::SPECIAL_TAX_IMPACTED_ACCOUNT) :
            $this->getOutputTaxAccount($codejercicio);
    }

    public function getOutputSurchargeAccount(string $codejercicio): DinSubcuenta
    {
        // si tenemos una cuenta definida, la devolvemos
        return $this->codsubcuentarepre ?
            $this->getSubAccount($codejercicio, $this->codsubcuentarepre, static::SPECIAL_TAX_IMPACTED_ACCOUNT) :
            $this->getOutputTaxAccount($codejercicio);
    }

    public function getOutputTaxAccount(string $codejercicio): DinSubcuenta
    {
        // si tenemos una cuenta definida, la devolvemos
        return $this->codsubcuentarep ?
            $this->getSubAccount($codejercicio, $this->codsubcuentarep, static::SPECIAL_TAX_IMPACTED_ACCOUNT) :
            $this->getSpecialSubAccount($codejercicio, static::SPECIAL_TAX_IMPACTED_ACCOUNT);
    }

    public function isDefault(): bool
    {
        return $this->codimpuesto === Tools::settings('default', 'codimpuesto');
    }

    public static function primaryColumn(): string
    {
        return 'codimpuesto';
    }

    public static function tableName(): string
    {
        return 'impuestos';
    }

    public function test(): bool
    {
        $this->codimpuesto = Tools::noHtml($this->codimpuesto);
        if ($this->codimpuesto && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codimpuesto)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codimpuesto, '%column%' => 'codimpuesto', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        $this->codsubcuentarep = empty($this->codsubcuentarep) ? null : $this->codsubcuentarep;
        $this->codsubcuentarepintra = empty($this->codsubcuentarepintra) ? null : $this->codsubcuentarepintra;
        $this->codsubcuentarepre = empty($this->codsubcuentarepre) ? null : $this->codsubcuentarepre;
        $this->codsubcuentasop = empty($this->codsubcuentasop) ? null : $this->codsubcuentasop;
        $this->codsubcuentasopintra = empty($this->codsubcuentasopintra) ? null : $this->codsubcuentasopintra;
        $this->codsubcuentasopre = empty($this->codsubcuentasopre) ? null : $this->codsubcuentasopre;
        $this->descripcion = Tools::noHtml($this->descripcion);

        return parent::test();
    }

    protected function getSpecialSubAccount(string $codejercicio, string $codcuentaesp): DinSubcuenta
    {
        // buscamos una subcuenta marcada con esa cuenta especial
        $subcuenta = new DinSubcuenta();
        $whereSubcuenta = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codcuentaesp', $codcuentaesp),
        ];
        if ($subcuenta->loadWhere($whereSubcuenta)) {
            return $subcuenta;
        }

        // no hay subcuenta especial, devolvemos la primera de la cuenta especial
        $cuenta = new DinCuenta();
        $whereCuenta = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codcuentaesp', $codcuentaesp),
        ];
        if ($cuenta->loadWhere($whereCuenta)) {
            foreach ($cuenta->getSubcuentas() as $subcuenta) {
                return $subcuenta;
            }
        }

        // no hemos encontrado la cuenta, la devolvemos vacía
        return new DinSubcuenta();
    }

    protected function getSubAccount(string $codejercicio, string $codsubcuenta, string $codcuentaesp): DinSubcuenta
    {
        $subcuenta = new DinSubcuenta();
        $whereSubcuenta = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codsubcuenta', $codsubcuenta),
        ];
        if ($subcuenta->loadWhere($whereSubcuenta)) {
            return $subcuenta;
        }

        // no hemos encontrado la subcuenta, la creamos, pero primero necesitamos la cuenta
        $cuenta = new DinCuenta();
        $whereCuenta = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codcuentaesp', $codcuentaesp),
        ];
        if ($cuenta->loadWhere($whereCuenta)) {
            // creamos la subcuenta
            $subcuenta->codejercicio = $codejercicio;
            $subcuenta->codcuenta = $cuenta->codcuenta;
            $subcuenta->codsubcuenta = $codsubcuenta;
            $subcuenta->descripcion = $this->descripcion;
            $subcuenta->save();
            return $subcuenta;
        }

        // no hemos encontrado la cuenta, la devolvemos vacía
        return $subcuenta;
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = $this->newLetterCode();
        }

        return parent::saveInsert();
    }

    private function newLetterCode(): string
    {
        $desc = preg_replace('/[^A-Z]/i', '', strtoupper($this->descripcion ?? ''));
        $pct = (int)$this->iva;

        // try 3 letters + percentage
        $prefix3 = substr($desc, 0, 3);
        if (strlen($prefix3) === 3) {
            $candidate = $prefix3 . $pct;
            if (false === $this->codimpuestoExists($candidate)) {
                return $candidate;
            }
        }

        // try 4 letters + percentage
        $prefix4 = substr($desc, 0, 4);
        if (strlen($prefix4) === 4) {
            $candidate = $prefix4 . $pct;
            if (false === $this->codimpuestoExists($candidate)) {
                return $candidate;
            }
        }

        return (string)$this->newCode();
    }

    private function codimpuestoExists(string $codimpuesto): bool
    {
        foreach (Impuestos::all() as $impuesto) {
            if (strtoupper($impuesto->codimpuesto) === strtoupper($codimpuesto)) {
                return true;
            }
        }

        return false;
    }
}
