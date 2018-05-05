<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Partida extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpartida;

    /**
     * Related accounting entry ID.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuenta;

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
     * Identifier of the counterpart.
     *
     * @var int
     */
    public $idcontrapartida;

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
     * Value of the conversion rate.
     *
     * @var float|int
     */
    public $tasaconv;

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
     * Document of departure.
     *
     * @var string
     */
    public $documento;

    /**
     * CIF / NIF of the item.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Serie code.
     *
     * @var string
     */
    public $codserie;

    /**
     * Invoice of the departure.
     *
     * @var
     */
    public $factura;

    /**
     * Amount of the tax base.
     *
     * @var float|int
     */
    public $baseimponible;

    /**
     * VAT percentage.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Equivalence surcharge percentage.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * True if it is dotted, but False.
     *
     * @var bool
     */
    public $punteada;

    /**
     * Visual order index
     *
     * @var int
     */
    public $orden;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idpartida';
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

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->punteada = false;
        $this->orden = 0;
        $this->tasaconv = 1.0;
        $this->coddivisa = AppSettings::get('default', 'coddivisa');

        $this->debe = 0.0;
        $this->debeme = 0.0;
        $this->haber = 0.0;
        $this->haberme = 0.0;

        $this->baseimponible = 0.0;
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Load de ID for subaccount
     *
     * @param string $code
     * @param string $exercise
     *
     * @return int|null
     */
    private function getIdSubAccount($code, $exercise)
    {
        if (empty($code) || empty($exercise)) {
            return NULL;
        }

        $where = [
            new DataBaseWhere('codejercicio', $exercise),
            new DataBaseWhere('codsubcuenta', $code)
        ];

        $account = new Subcuenta();
        $account->loadFromCode('', $where);
        return $account->idsubcuenta;
    }

    /**
     * Check if exists error in accounting entry
     *
     * @return bool
     */
    private function testErrorInData(): bool
    {
        return empty($this->idasiento) || empty($this->codsubcuenta) || empty($this->debe + $this->haber);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->codsubcuenta = trim($this->codsubcuenta);
        $this->codcontrapartida = trim($this->codcontrapartida);

        if ($this->testErrorInData()) {
            self::$miniLog->alert(self::$i18n->trans('accounting-data-missing'));
            return false;
        }

        if (strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('concept-too-large'));
            return false;
        }

        $accounting = new Asiento();
        if (!$accounting->loadFromCode($this->idasiento)) {
            self::$miniLog->alert(self::$i18n->trans('accounting-entry-error'));
            return false;
        }

        $this->idsubcuenta = $this->getIdSubAccount($this->codsubcuenta, $accounting->codejercicio);
        if (empty($this->idsubcuenta)) {
            self::$miniLog->alert(self::$i18n->trans('account-data-error'));
            return false;
        }

        $this->idcontrapartida = $this->getIdSubAccount($this->codsubcuenta, $accounting->codejercicio);
        if (!empty($this->codcontrapartida) && empty($this->idcontrapartida)) {
            self::$miniLog->alert(self::$i18n->trans('offsetting-account-data-error'));
            return false;
        }

        $defaultCurrency = AppSettings::get('default', 'coddivisa');
        if ($this->coddivisa !== $defaultCurrency) {
            $this->debeme = $this->debe * $this->tasaconv;
            $this->haberme = $this->haber * $this->tasaconv;
        }

        $this->concepto = Utils::noHtml($this->concepto);
        $this->documento = Utils::noHtml($this->documento);
        $this->cifnif = Utils::noHtml($this->cifnif);

        return parent::test();
    }

    /**
     * Get accounting date
     *
     * @return string
     */
    private function getAccountingDate(): string
    {
        $accounting = new Asiento();
        $accounting->loadFromCode($this->idasiento);
        return $accounting->fecha;
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        $date = $this->getAccountingDate();
        $account = new Subcuenta();
        $account->idsubcuenta = $this->idsubcuenta;

        $inTransaction = self::$dataBase->inTransaction();
        try {
            if ($inTransaction === false) {
                self::$dataBase->beginTransaction();
            }

            /// main insert
            if (!parent::saveInsert($values)) {
                return false;
            }

            /// update account balance
            if (!$account->updateBalance($date, $this->debe, $this->haber)) {
                return false;
            }

            /// save transaction
            if ($inTransaction === false) {
                self::$dataBase->commit();
            }
        } catch (\Exception $e) {
            self::$miniLog->error($e->getMessage());
            return false;
        } finally {
            if (!$inTransaction && self::$dataBase->inTransaction()) {
                self::$dataBase->rollback();
                return false;
            }
        }
        return true;
    }

    protected function saveUpdate(array $values = [])
    {
        // Search for the difference in the amounts
        $entry = new Partida();
        $entry->loadFromCode($this->idpartida);
        $debit = (isset($values['debe']) ? $values['debe'] : $this->debe) - $entry->debe;
        $credit = (isset($values['haber']) ? $values['haber'] : $this->haber) - $entry->haber;

        // Get data to update balance
        $date = $this->getAccountingDate();
        $account = new Subcuenta();
        $account->idsubcuenta = $this->idsubcuenta;

        $inTransaction = self::$dataBase->inTransaction();
        try {
            if ($inTransaction === false) {
                self::$dataBase->beginTransaction();
            }

            /// main update
            if (!parent::saveUpdate($values)) {
                return false;
            }

            /// update account balance
            if (!$account->updateBalance($date, $debit, $credit)) {
                return false;
            }

            /// save transaction
            if ($inTransaction === false) {
                self::$dataBase->commit();
            }
        } catch (\Exception $e) {
            self::$miniLog->error($e->getMessage());
            return false;
        } finally {
            if (!$inTransaction && self::$dataBase->inTransaction()) {
                self::$dataBase->rollback();
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes the data from the database record.
     *
     * @return bool
     */
    public function delete()
    {
        $date = $this->getAccountingDate();
        $account = new Subcuenta();
        $account->idsubcuenta = $this->idsubcuenta;

        $inTransaction = self::$dataBase->inTransaction();
        try {
            if ($inTransaction === false) {
                self::$dataBase->beginTransaction();
            }

            /// update account balance
            if (!$account->updateBalance($date, ($this->debe * -1), ($this->haber * -1))) {
                return false;
            }

            /// main delete
            if (!parent::delete()) {
                return false;
            }

            /// save transaction
            if ($inTransaction === false) {
                self::$dataBase->commit();
            }
        } catch (\Exception $e) {
            self::$miniLog->error($e->getMessage());
            return false;
        } finally {
            if (!$inTransaction && self::$dataBase->inTransaction()) {
                self::$dataBase->rollback();
                return false;
            }
        }
        return true;
    }
}
