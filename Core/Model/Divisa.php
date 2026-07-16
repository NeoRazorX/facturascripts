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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A currency with its symbol and its conversion rate with respect to the euro.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Divisa extends ModelClass
{
    use ModelTrait;

    /** Código identificativo de la divisa. @var string */
    public $coddivisa;

    /** Código numérico de la divisa según la norma ISO 4217. @var string */
    public $codiso;

    /** Descripción de la divisa. @var string */
    public $descripcion;

    /** Tasa de conversión de la divisa con respecto al euro. @var float|int */
    public $tasaconv;

    /** Tasa de conversión con respecto al euro utilizada en compras. @var float|int */
    public $tasaconvcompra;

    /** Símbolo utilizado para representar la divisa. @var string */
    public $simbolo;

    public function clear(): void
    {
        parent::clear();
        $this->descripcion = '';
        $this->tasaconv = 1.00;
        $this->tasaconvcompra = 1.00;
        $this->simbolo = '?';
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Divisas::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-currency');
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns True if this is the default currency.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->coddivisa === Tools::settings('default', 'coddivisa');
    }

    public static function primaryColumn(): string
    {
        return 'coddivisa';
    }

    public static function tableName(): string
    {
        return 'divisas';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->simbolo = Tools::noHtml($this->simbolo);

        if ($this->coddivisa && 1 !== preg_match('/^[A-Z0-9]{1,3}$/i', $this->coddivisa)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->coddivisa, '%column%' => 'coddivisa', '%min%' => '1', '%max%' => '3']
            );
        } elseif ($this->codiso !== null && 1 !== preg_match('/^[A-Z0-9]{1,5}$/i', $this->codiso)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codiso, '%column%' => 'codiso', '%min%' => '1', '%max%' => '5']
            );
        } elseif ($this->tasaconv === 0.0 || $this->tasaconvcompra === 0.0) {
            Tools::log()->warning('conversion-rate-not-0');
        } else {
            return parent::test();
        }

        return false;
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
