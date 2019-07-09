<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * A currency with its symbol and its conversion rate with respect to the euro.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Divisa extends Base\ModelClass
{

    use Base\ModelTrait;

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

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->descripcion = '';
        $this->tasaconv = 1.00;
        $this->tasaconvcompra = 1.00;
        $this->simbolo = '?';
    }

    /**
     * Removed currency from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            self::$miniLog->alert(self::$i18n->trans('cant-delete-default-currency'));
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns True if this is the default currency.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->coddivisa === AppSettings::get('default', 'coddivisa');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'coddivisa';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'divisas';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = Utils::noHtml($this->descripcion);
        $this->simbolo = Utils::noHtml($this->simbolo);

        if (!preg_match('/^[A-Z0-9]{1,3}$/i', $this->coddivisa)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-alphanumeric-code', ['%value%' => $this->coddivisa, '%column%' => 'coddivisa', '%min%' => '1', '%max%' => '3']));
        } elseif ($this->codiso !== null && !preg_match('/^[A-Z0-9]{1,5}$/i', $this->codiso)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-alphanumeric-code', ['%value%' => $this->codiso, '%column%' => 'codiso', '%min%' => '1', '%max%' => '5']));
        } elseif ($this->tasaconv === 0.0 || $this->tasaconvcompra === 0.0) {
            self::$miniLog->alert(self::$i18n->trans('conversion-rate-not-0'));
        } else {
            return parent::test();
        }

        return false;
    }
}
