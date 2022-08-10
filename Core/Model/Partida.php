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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * The line of an accounting entry.
 * It is related to an accounting entry and a sub-account.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
     * @var float
     */
    public $saldo;

    /**
     * Value of the conversion rate.
     *
     * @var float|int
     */
    public $tasaconv;

    public function clear()
    {
        parent::clear();
        $this->baseimponible = 0.0;
        $this->coddivisa = $this->toolBox()->appSettings()->get('default', 'coddivisa');
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->orden = 0;
        $this->punteada = false;
        $this->recargo = 0.0;
        $this->saldo = 0.0;
        $this->tasaconv = 1.0;
    }

    public function delete(): bool
    {
        $entry = $this->getAccountingEntry();
        if (false === $entry->editable) {
            return false;
        }

        $exercise = $entry->getExercise();
        if (false === $exercise->isOpened()) {
            self::toolBox()::i18nLog()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::delete();
    }

    /**
     * @param string $codsubcuenta
     *
     * @return DinSubcuenta
     */
    public function getSubcuenta(string $codsubcuenta = '')
    {
        $accEntry = $this->getAccountingEntry();
        $subcta = new DinSubcuenta();

        // get by parameter
        if (!empty($codsubcuenta)) {
            $where = [
                new DataBaseWhere('codejercicio', $accEntry->codejercicio),
                new DataBaseWhere('codsubcuenta', $codsubcuenta)
            ];
            $subcta->loadFromCode('', $where);
            return $subcta;
        }

        // get by id
        if (!empty($this->idsubcuenta) &&
            $subcta->loadFromCode($this->idsubcuenta) &&
            $subcta->codsubcuenta === $this->codsubcuenta &&
            $subcta->codejercicio === $accEntry->codejercicio) {
            return $subcta;
        }

        // get by code and exercise
        $where2 = [
            new DataBaseWhere('codejercicio', $accEntry->codejercicio),
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta)
        ];
        $subcta->loadFromCode('', $where2);
        return $subcta;
    }

    public function install(): string
    {
        new DinDivisa();
        new DinAsiento();
        new DinSubcuenta();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpartida';
    }

    public function save(): bool
    {
        $entry = $this->getAccountingEntry();
        if (false === $entry->editable) {
            return false;
        }

        $exercise = $entry->getExercise();
        if (false === $exercise->isOpened()) {
            self::toolBox()::i18nLog()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::save();
    }

    /**
     * @param Subcuenta $subaccount
     */
    public function setAccount($subaccount)
    {
        $this->codsubcuenta = $subaccount->codsubcuenta;
        $this->idsubcuenta = $subaccount->idsubcuenta;
    }

    /**
     * @param Subcuenta $subaccount
     */
    public function setCounterpart($subaccount)
    {
        $this->codcontrapartida = $subaccount->codsubcuenta;
        $this->idcontrapartida = $subaccount->idsubcuenta;
    }

    /**
     * Set dotted status to indicated value.
     *
     * @param bool $value
     */
    public function setDottedStatus(bool $value)
    {
        $sql = 'UPDATE ' . self::tableName() . ' SET punteada = ' . self::$dataBase->var2str($value)
            . ' WHERE ' . self::primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue());

        if ($value !== $this->punteada && self::$dataBase->exec($sql)) {
            $this->punteada = $value;
        }
    }

    public static function tableName(): string
    {
        return 'partidas';
    }

    public function test(): bool
    {
        $utils = $this->toolBox()->utils();
        $this->cifnif = $utils->noHtml($this->cifnif);
        $this->codsubcuenta = $utils->noHtml($this->codsubcuenta);
        $this->codcontrapartida = $utils->noHtml($this->codcontrapartida);
        $this->concepto = $utils->noHtml($this->concepto);
        $this->documento = $utils->noHtml($this->documento);

        if (strlen($this->concepto) < 1 || strlen($this->concepto) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'concepto', '%min%' => '1', '%max%' => '255']);
            return false;
        }

        // set missing subaccount id
        if (empty($this->idsubcuenta)) {
            $this->idsubcuenta = $this->getSubcuenta()->idsubcuenta;
        }

        // set missing contrapartida id
        if (!empty($this->codcontrapartida) && empty($this->idcontrapartida)) {
            $this->idcontrapartida = $this->getSubcuenta($this->codcontrapartida)->idsubcuenta;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
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
        // update account balance
        $this->updateBalance($this->idsubcuenta);
        parent::onDelete();
    }

    /**
     * This method is called after this record is save (insert) in the database.
     */
    protected function onInsert()
    {
        // update account balance
        $this->updateBalance($this->idsubcuenta);
        parent::onInsert();
    }

    /**
     * This method is called after a record is updated on the database.
     */
    protected function onUpdate()
    {
        // if the subaccount has changed, we update the balances of the new and old
        if ($this->previousData['idsubcuenta'] != $this->idsubcuenta) {
            $this->updateBalance($this->idsubcuenta);
            $this->updateBalance($this->previousData['idsubcuenta']);
            parent::onUpdate();
        }

        // if debit or credit has changed, we recalculate the subaccount balance
        if ($this->previousData['debe'] != $this->debe || $this->previousData['haber'] != $this->haber) {
            $this->updateBalance($this->idsubcuenta);
        }

        parent::onUpdate();
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['codcontrapartida', 'codsubcuenta', 'debe', 'haber', 'idcontrapartida', 'idsubcuenta'];
        parent::setPreviousData(array_merge($more, $fields));
    }

    /**
     * Update the subaccount balance.
     *
     * @param int $idsubcuenta
     */
    private function updateBalance(int $idsubcuenta)
    {
        $subaccount = new DinSubcuenta();
        if ($subaccount->loadFromCode($idsubcuenta)) {
            $subaccount->updateBalance();
        }
    }
}
