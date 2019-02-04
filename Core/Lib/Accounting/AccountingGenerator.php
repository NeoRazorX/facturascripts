<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Dinamic\Model;

/**
 * Class for the generation of accounting entries.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AccountingGenerator
{

    /**
     * Accounting plan model
     *
     * @var Model\Cuenta
     */
    private $account;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Multi-language translator.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Manage the log of all controllers, models and database.
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->dataBase = new DataBase();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();

        $this->account = new Model\Cuenta();
    }

    /**
     * 
     * @param int    $length
     * @param string $value
     * @param string $prefix
     *
     * @return string
     */
    public function fillToLength(int $length, string $value, string $prefix = ''): string
    {
        $value2 = trim($value);
        $count = $length - strlen($prefix) - strlen($value2);
        if ($count < 1) {
            return $prefix . $value2;
        }

        return $prefix . str_repeat('0', $count) . $value2;
    }

    /**
     * Generates an accounting entry based on the data structure provided
     *
     * @param array $data
     *
     * @return bool
     */
    protected function accountEntry(array &$data): bool
    {
        $detail = new Model\Partida();
        $entry = new Model\Asiento();
        $entry->idasiento = $data['id'];
        $entry->fecha = $data['date'];
        $entry->documento = $data['document'];
        $entry->concepto = $data['concept'];
        $entry->editable = $data['editable'];
        $entry->importe = $data['total'];

        $inTransaction = $this->dataBase->inTransaction();
        try {
            $this->dataBase->beginTransaction();

            if (!empty($entry->idasiento)) {
                if (!$entry->delete()) {
                    return false;
                }
            }

            if (!$entry->save()) {
                return false;
            }

            $data['id'] = $entry->idasiento;
            $detail->idasiento = $entry->idasiento;

            foreach ($data['lines'] as $line) {
                $detail->idpartida = null;
                $detail->codsubcuenta = $line['subaccount'];
                $detail->codcontrapartida = $line['offsetting'];
                $detail->concepto = $line['description'] ?? $entry->concepto;
                $detail->debe = $line['debit'];
                $detail->haber = $line['credit'];

                if (count($line['VAT']) > 0) {
                    $detail->documento = $line['VAT']['document'] ?? $entry->documento;
                    $detail->cifnif = $line['VAT']['fiscal-number'];
                    $detail->baseimponible = $line['VAT']['tax-base'];
                    $detail->iva = $line['VAT']['pct-vat'];
                    $detail->recargo = $line['VAT']['surcharge'];
                }

                if (!$detail->save()) {
                    return false;
                }
            }

            if ($inTransaction === false) {
                $this->dataBase->commit();
            }
        } catch (\Exception $e) {
            $this->miniLog->error($e->getMessage());
            return false;
        } finally {
            if (!$inTransaction && $this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
                return false;
            }
        }
        return true;
    }

    /**
     * Provides a basic structure for the generation of
     * accounting entries
     *
     * @return array
     */
    protected function getEntry()
    {
        return [
            'id' => null,
            'date' => date($this->dataBase->dateStyle()),
            'document' => '',
            'concept' => '',
            'editable' => true,
            'journal' => null,
            'channel' => null,
            'total' => 0.00,
            'lines' => []
        ];
    }

    /**
     * Provides a basic structure for the generation of
     * accounting entries
     *
     * @param bool $withVAT
     *
     * @return array
     */
    protected function getLine(bool $withVAT = false): array
    {
        $result = [
            'subaccount' => '',
            'offsetting' => null,
            'description' => null,
            'debit' => 0.00,
            'credit' => 0.00,
            'VAT' => []
        ];

        if ($withVAT) {
            $result['VAT'] = [
                'document' => '',
                'fiscal-number' => '',
                'tax-base' => 0.00,
                'pct-vat' => 0.00,
                'surcharge' => 0.00
            ];
        }

        return $result;
    }

    /**
     * Search prefix account for indicate type into exercise and account plan
     *
     * @param string $exercise
     * @param string $type
     * @param string $default
     *
     * @return string
     */
    protected function getPrefixAccount(string $exercise, string $type, string $default): string
    {
        $where = [
            new DataBaseWhere('codejercicio', $exercise),
            new DataBaseWhere('codcuentaesp', $type)
        ];
        $this->account->loadFromCode('', $where);
        return $this->account->codcuenta ?? $default;
    }
}
