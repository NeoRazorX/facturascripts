<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Dinamic\Model;

/**
 * Class for the generation of accounting entries.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class AccountingGenerator
{

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
    protected $miniLog;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->dataBase = new DataBase();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
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
            'date' => date('d-m-Y'),
            'document' => '',
            'concept' => '',
            'editable' => true,
            'journal' => NULL,
            'channel' => NULL,
            'lines' => []
        ];
    }

    /**
     * Provides a basic structure for the generation of
     * accounting entries
     *
     * @param bool $withVAT
     * @return array
     */
    protected function getLine(bool $withVAT = false): array
    {
        $result = [
            'subaccount' => '',
            'offsetting' => NULL,
            'description' => NULL,
            'debit' => 0.00,
            'credit' => 0.00,
            'VAT' => []
        ];

        if ($withVAT) {
            $result['VAT'] = [
                'document' => '',
                'vat-id' => '',
                'tax-base' => 0.00,
                'pct-vat' => 0.00,
                'surcharge' => 0.00
            ];
        }

        return $result;
    }

    /**
     * Generates an accounting entry based on the data structure provided
     *
     * @param array $data
     */
    protected function AccountEntry(array $data): bool
    {
        $detail = new Model\Partida();
        $entry = new Model\Asiento();
        $entry->fecha = $data['fecha'];
        $entry->documento = $data['document'];
        $entry->concepto = $data['concept'];
        $entry->editable = $data['editable'];

        $inTransaction = $this->dataBase->inTransaction();
        try {
            $this->dataBase->beginTransaction();

            if (!$entry->save()) {
                return false;
            }

            $detail->idasiento = $entry->idasiento;

            foreach ($data['lines'] as $line) {
                $detail->idpartida = NULL;
                $detail->codsubcuenta = $line['subaccount'];
                $detail->codcontrapartida = $line['offsetting'];
                $detail->concepto = $line['description'] ?? $entry->concepto;
                $detail->debe = $line['debit'];
                $detail->haber = $line['credit'];

                if (count($line['VAT']) > 0) {
                    $detail->documento = $line['VAT']['document'] ?? $entry->documento;
                    $detail->cifnif = $line['VAT']['vat-id'];
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
}
