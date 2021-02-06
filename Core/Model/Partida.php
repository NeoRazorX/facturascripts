<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

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
    use Base\AccEntryRelationTrait;

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
        $this->coddivisa = $this->toolBox()->appSettings()->get('default', 'coddivisa');
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
     * @param string $codsubcuenta
     *
     * @return DinSubcuenta
     */
    public function getSubcuenta($codsubcuenta = '')
    {
        $accEntry = $this->getAccountingEntry();
        $subcta = new DinSubcuenta();

        /// get by parameter
        if (!empty($codsubcuenta)) {
            $where = [
                new DataBaseWhere('codejercicio', $accEntry->codejercicio),
                new DataBaseWhere('codsubcuenta', $codsubcuenta)
            ];
            $subcta->loadFromCode('', $where);
            return $subcta;
        }

        /// get by id
        if (!empty($this->idsubcuenta) &&
            $subcta->loadFromCode($this->idsubcuenta) &&
            $subcta->codsubcuenta === $this->codsubcuenta &&
            $subcta->codejercicio === $accEntry->codejercicio) {
            return $subcta;
        }

        /// get by code and exercise
        $where2 = [
            new DataBaseWhere('codejercicio', $accEntry->codejercicio),
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta)
        ];
        $subcta->loadFromCode('', $where2);
        return $subcta;
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
        new DinDivisa();
        new DinAsiento();
        new DinSubcuenta();

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
     *
     * @param Subcuenta $subaccount
     */
    public function setAccount($subaccount)
    {
        $this->codsubcuenta = $subaccount->codsubcuenta;
        $this->idsubcuenta = $subaccount->idsubcuenta;
    }

    /**
     *
     * @param Subcuenta $subaccount
     */
    public function setCounterpart($subaccount)
    {
        $this->codcontrapartida = $subaccount->codsubcuenta;
        $this->idcontrapartida = $subaccount->idsubcuenta;
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
        $utils = $this->toolBox()->utils();
        $this->cifnif = $utils->noHtml($this->cifnif);
        $this->codsubcuenta = \trim($this->codsubcuenta);
        $this->codcontrapartida = \trim($this->codcontrapartida);
        $this->concepto = $utils->noHtml($this->concepto);
        $this->documento = $utils->noHtml($this->documento);

        if (\strlen($this->concepto) < 1 || \strlen($this->concepto) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']);
            return false;
        }

        /// set missing subaccount id
        if (empty($this->idsubcuenta)) {
            $this->idsubcuenta = $this->getSubcuenta()->idsubcuenta;
        }

        /// set missing contrapartida id
        if (!empty($this->codcontrapartida) && empty($this->idcontrapartida)) {
            $this->idcontrapartida = $this->getSubcuenta($this->codcontrapartida)->idsubcuenta;
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
        return $this->getAccountingEntry()->url($type, $list);
    }

    /**
     * This method is called before this record is save (update) in the database
     * when some field value is changed.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'codcontrapartida':
                $this->idcontrapartida = $this->getSubcuenta($this->codcontrapartida)->idsubcuenta;
                break;

            case 'codsubcuenta':
                $this->idsubcuenta = $this->getSubcuenta($this->codsubcuenta)->idsubcuenta;
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
        $this->updateBalance($this->idsubcuenta);
        parent::onDelete();
    }

    /**
     * This method is called after this record is save (insert) in the database.
     */
    protected function onInsert()
    {
        /// update account balance
        $this->updateBalance($this->idsubcuenta);
        parent::onInsert();
    }

    /**
     * This method is called after a record is updated on the database.
     */
    protected function onUpdate()
    {
        $this->updateBalance($this->idsubcuenta);

        /// Has the subaccount been changed? Then we recalculate the balance of the old one too.
        if ($this->previousData['idsubcuenta'] != $this->idsubcuenta) {
            $this->updateBalance($this->previousData['idsubcuenta']);
        }

        parent::onUpdate();
    }

    /**
     *
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['codcontrapartida', 'codsubcuenta', 'debe', 'haber', 'idcontrapartida', 'idsubcuenta'];
        parent::setPreviousData(\array_merge($more, $fields));
    }

    /**
     * Update the subaccount balance.
     *
     * @param int $idsubaccount
     */
    private function updateBalance($idsubaccount)
    {
        $subaccount = new DinSubcuenta();
        if ($subaccount->loadFromCode($idsubaccount)) {
            $subaccount->updateBalance();
        }
    }
}
