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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * The line of a accounting entry.
 * It is related to a accounting entry and a sub-account.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Partida extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     * Amount of the tax base.
     *
     * @var float|int
     */
    public $baseimponible;

    /**
     * CIF / NIF of the item.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Counterparty code.
     *
     * @var string
     */
    public $codcontrapartida;

    /**
     * Currency code.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Serie code.
     *
     * @var string
     */
    public $codserie;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Concept.
     *
     * @var string
     */
    public $concepto;

    /**
     * Debit of the accounting entry.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Debit of the accounting entry in secondary currency.
     *
     * @var float|int
     */
    public $debeme;

    /**
     * Document of departure.
     *
     * @var string
     */
    public $documento;

    /**
     * Invoice number of the departure.
     *
     * @var string
     */
    public $factura;

    /**
     * Credit of the accounting entry.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Credit of the accounting entry in secondary currency.
     *
     * @var float|int
     */
    public $haberme;

    /**
     * Related accounting entry ID.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Identifier of the counterpart.
     *
     * @var int
     */
    public $idcontrapartida;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpartida;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * VAT percentage.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Visual order index
     *
     * @var int
     */
    public $orden;

    /**
     * True if it is dotted, but False.
     *
     * @var bool
     */
    public $punteada;

    /**
     * Equivalence surcharge percentage.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Value of the conversion rate.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->baseimponible = 0.0;
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->debe = 0.0;
        $this->debeme = 0.0;
        $this->haber = 0.0;
        $this->haberme = 0.0;
        $this->iva = 0.0;
        $this->orden = 0;
        $this->punteada = false;
        $this->recargo = 0.0;
        $this->tasaconv = 1.0;
    }

    /**
     * 
     * @return Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        $asiento->loadFromCode($this->idasiento);
        return $asiento;
    }

    /**
     * 
     * @param string $codsubcuenta
     *
     * @return Subcuenta
     */
    public function getSubcuenta($codsubcuenta = '')
    {
        $subcuenta = new Subcuenta();
        if (empty($codsubcuenta)) {
            $subcuenta->loadFromCode($this->idsubcuenta);
            return $subcuenta;
        }

        $asiento = $this->getAsiento();
        $where = [
            new DataBaseWhere('codejercicio', $asiento->codejercicio),
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta)
        ];
        $subcuenta->loadFromCode('', $where);
        return $subcuenta;
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
        new Asiento();
        new Subcuenta();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idpartida';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'partidas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->cifnif = Utils::noHtml($this->cifnif);
        $this->codsubcuenta = trim($this->codsubcuenta);
        $this->codcontrapartida = trim($this->codcontrapartida);
        $this->concepto = Utils::noHtml($this->concepto);
        $this->documento = Utils::noHtml($this->documento);

        if (strlen($this->concepto) < 1 || strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']));
            return false;
        }

        if ($this->testErrorInData()) {
            self::$miniLog->alert(self::$i18n->trans('accounting-data-missing'));
            return false;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return $this->getAsiento()->url($type, $list);
    }

    /**
     * This mehtod is called before this record is save (update) in the database
     * when some field value is changed.
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'codsubcuenta':
                $subcuenta = $this->getSubcuenta($this->codsubcuenta);
                $this->idsubcuenta = $subcuenta->idsubcuenta;
                break;

            case 'debe':
            case 'haber':
                $debit = $this->debe - $this->previousData['debe'];
                $credit = $this->haber - $this->previousData['haber'];

                /// update account balance
                $asiento = $this->getAsiento();
                $this->getSubcuenta()->updateBalance($asiento->fecha, $debit, $credit);
                break;
        }

        return parent::onChange($field);
    }

    /**
     * This method is called after this record is deleted from database.
     */
    protected function onDelete()
    {
        /// update account balance
        $asiento = $this->getAsiento();
        $this->getSubcuenta()->updateBalance($asiento->fecha, ($this->debe * -1), ($this->haber * -1));
    }

    /**
     * This method is called after this record is save (insert) in the database.
     */
    protected function onInsert()
    {
        /// update account balance
        $asiento = $this->getAsiento();
        $this->getSubcuenta()->updateBalance($asiento->fecha, $this->debe, $this->haber);
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = array())
    {
        $more = ['codsubcuenta', 'debe', 'haber'];
        parent::setPreviousData(array_merge($more, $fields));
    }

    /**
     * Check if exists error in accounting entry
     *
     * @return bool
     */
    protected function testErrorInData(): bool
    {
        if (empty($this->idasiento) || empty($this->codsubcuenta)) {
            return true;
        }

        if (empty($this->idsubcuenta)) {
            $subcuenta = $this->getSubcuenta($this->codsubcuenta);
            $this->idsubcuenta = $subcuenta->idsubcuenta;
        }

        return empty($this->idsubcuenta);
    }
}
