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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A currency with its symbol and its conversion rate with respect to the euro.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Divisa extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key. Varchar (3).
     *
     * @var string
     */
    public $coddivisa;

    /**
     * ISO 4217 code in number: http://en.wikipedia.org/wiki/ISO_4217
     *
     * @var string
     */
    public $codiso;

    /**
     * Currency description.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Conversion rate to the euro.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Conversion rate to the euro (for purchases).
     *
     * @var float|int
     */
    public $tasaconvcompra;

    /**
     * Symbol representing the currency.
     *
     * @var string
     */
    public $simbolo;

    public function clear()
    {
        parent::clear();
        $this->descripcion = '';
        $this->tasaconv = 1.00;
        $this->tasaconvcompra = 1.00;
        $this->simbolo = '?';
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-currency');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Divisas::clear();
            return true;
        }

        return false;
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

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Divisas::clear();
            return true;
        }

        return false;
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
