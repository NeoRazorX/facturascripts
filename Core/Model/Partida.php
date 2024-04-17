<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\AccEntryRelationTrait;
use FacturaScripts\Core\Model\Base\ModelOnChangeClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * The line of an accounting entry.
 * It is related to an accounting entry and account.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Partida extends ModelOnChangeClass
{
    use ModelTrait;
    use AccEntryRelationTrait;

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
     * Code, not ID, of the related account.
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
     * Related account ID.
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
        $this->coddivisa = Tools::settings('default', 'coddivisa');
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
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::delete();
    }

    /**
     * @param string $codsubcuenta
     *
     * @return DinSubcuenta
     */
    public function getSubcuenta(string $codsubcuenta = ''): Subcuenta
    {
        $accEntry = $this->getAccountingEntry();
        $subCta = new DinSubcuenta();

        // get by parameter
        if (!empty($codsubcuenta)) {
            $where = [
                new DataBaseWhere('codejercicio', $accEntry->codejercicio),
                new DataBaseWhere('codsubcuenta', $codsubcuenta)
            ];
            $subCta->loadFromCode('', $where);
            return $subCta;
        }

        // get by id
        if (!empty($this->idsubcuenta) &&
            $subCta->loadFromCode($this->idsubcuenta) &&
            $subCta->codsubcuenta === $this->codsubcuenta &&
            $subCta->codejercicio === $accEntry->codejercicio) {
            return $subCta;
        }

        // get by code and exercise
        $where2 = [
            new DataBaseWhere('codejercicio', $accEntry->codejercicio),
            new DataBaseWhere('codsubcuenta', $this->codsubcuenta)
        ];
        $subCta->loadFromCode('', $where2);
        return $subCta;
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
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::save();
    }

    public function setAccount(Subcuenta $subAccount): Partida
    {
        $this->codsubcuenta = $subAccount->codsubcuenta;
        $this->idsubcuenta = $subAccount->idsubcuenta;

        return $this;
    }

    public function setCounterpart(Subcuenta $subAccount): Partida
    {
        $this->codcontrapartida = $subAccount->codsubcuenta;
        $this->idcontrapartida = $subAccount->idsubcuenta;

        return $this;
    }

    public function setDottedStatus(bool $value): Partida
    {
        $sql = 'UPDATE ' . self::tableName() . ' SET punteada = ' . self::$dataBase->var2str($value)
            . ' WHERE ' . self::primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue());

        if ($value !== $this->punteada && self::$dataBase->exec($sql)) {
            $this->punteada = $value;
        }

        return $this;
    }

    public static function tableName(): string
    {
        return 'partidas';
    }

    public function test(): bool
    {
        $this->cifnif = Tools::noHtml($this->cifnif);
        $this->codsubcuenta = Tools::noHtml($this->codsubcuenta);
        $this->codcontrapartida = Tools::noHtml($this->codcontrapartida);
        $this->concepto = Tools::noHtml($this->concepto);
        $this->documento = Tools::noHtml($this->documento);

        if (strlen($this->concepto) < 1 || strlen($this->concepto) > 255) {
            Tools::log()->warning('invalid-column-lenght', [
                '%column%' => 'concepto', '%min%' => '1', '%max%' => '255'
            ]);
            return false;
        }

        // set missing account id
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
     * This method is called before this record is saved (update) in the database
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

    protected function setPreviousData(array $fields = [])
    {
        $more = ['codcontrapartida', 'codsubcuenta', 'debe', 'haber', 'idcontrapartida', 'idsubcuenta'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
