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
namespace FacturaScripts\Core\Model\Report;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\Partida;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generate PDF for Accounting Entry.
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingEntryReportPDF
{

    /**
     *
     * @var Asiento
     */
    protected $accountEntry;

    /**
     *
     * @var Partida[]
     */
    protected $lines;

    /**
     *
     * @var Response
     */
    protected $response;

    /**
     *
     * @var array
     */
    protected $totals;

    /**
     *
     * @var array
     */
    private $columns = [];

    /**
     *
     * @var array
     */
    private $modals = [];

    /**
     *
     * @var PageOption
     */
    private $pageOption;

    /**
     *
     * @var array
     */
    private $rows = [];

    /**
     * Class constructor.
     *
     * @param Asiento $accountEntry
     * @param Response $response
     */
    public function __construct(Asiento &$accountEntry, Response &$response)
    {
        $this->accountEntry = $accountEntry;
        $this->lines = $accountEntry->getLines();
        $this->response = $response;
        $this->totals = [
            'debit' => 0.00,
            'credit' => 0.00,
        ];

        $this->pageOption = new PageOption();
        VisualItemLoadEngine::installXML('EditAsiento', $this->pageOption);
        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->pageOption);
    }

    /**
     * Generate accounting entry pdf report.
     *
     * @return bool
     */
    public function generatePDF(): bool
    {
        $title = $this->toolBox()->i18n()->trans('accounting-entry') . '-' . $this->accountEntry->numero;
        $exportManager = new ExportManager();
        $exportManager->newDoc('PDF', $title);

        $this->insertAccountEntryModel($exportManager);
        $this->insertAccountEntryLines($exportManager);
        $this->insertVATRegister($exportManager);
        $this->insertTotals($exportManager);
        $exportManager->show($this->response);
        return true;
    }

    /**
     * Add account entry lines to report.
     *
     * @param ExportManager $exportManager
     */
    protected function insertAccountEntryLines(ExportManager &$exportManager)
    {
        $i18n = $this->toolBox()->i18n();
        $title = '<strong>' . $i18n->trans('lines') . '</strong>';
        $header = [
            $i18n->trans('subaccount'),
            $i18n->trans('concept'),
            $i18n->trans('debit'),
            $i18n->trans('credit'),
            $i18n->trans('balance'),
            $i18n->trans('counterpart'),
        ];
        $options = [
            $header[2] => ['display' => 'right'],
            $header[3] => ['display' => 'right'],
            $header[4] => ['display' => 'right'],
        ];

        $balance = 0.00;
        $data = [];
        foreach ($this->lines as $line) {
            $data[] = $this->dataFromLine($header, $line, $balance);
            $this->totals['debit'] += $line->debe;
            $this->totals['credit'] += $line->haber;
        }
        $exportManager->addTablePage($header, $data, $options, $title);
    }

    /**
     * Add account entry to report.
     *
     * @param ExportManager $exportManager
     */
    protected function insertAccountEntryModel(ExportManager &$exportManager)
    {
        $title = $this->toolBox()->i18n()->trans('accounting-entry');
        $exportManager->addModelPage($this->accountEntry, $this->columns, $title);
    }

    /**
     * Add account entry taxes lines to report.
     *
     * @param ExportManager $exportManager
     */
    protected function insertVATRegister(ExportManager &$exportManager)
    {
        $i18n = $this->toolBox()->i18n();
        $header = [
            $i18n->trans('serie'),
            $i18n->trans('invoice'),
            $i18n->trans('vat-document'),
            $i18n->trans('cifnif'),
            $i18n->trans('tax-base'),
            $i18n->trans('pct-vat'),
            $i18n->trans('imp-vat'),
            $i18n->trans('pct-surcharge'),
            $i18n->trans('imp-surcharge'),
        ];

        $data = [];
        foreach ($this->lines as $line) {
            if ($this->hasVatRegister($line)) {
                $data[] = $this->dataFromVATRegister($header, $line);
            }
        }

        if (empty($data)) {
            return;
        }

        $options = [
            $header[4] => ['display' => 'right'],
            $header[5] => ['display' => 'center'],
            $header[6] => ['display' => 'right'],
            $header[7] => ['display' => 'center'],
            $header[8] => ['display' => 'right'],
        ];

        $title = '<strong>' . $i18n->trans('VAT-register') . '</strong>';
        $exportManager->addTablePage($header, $data, $options, $title);
    }

    /**
     * Add totals footer to report.
     *
     * @param ExportManager $exportManager
     */
    protected function insertTotals(ExportManager &$exportManager)
    {
        $i18n = $this->toolBox()->i18n();
        $header = [
            $i18n->trans('debit'),
            $i18n->trans('credit'),
            $i18n->trans('difference'),
        ];
        $data = [
            $header[0] => $this->coinToString($this->totals['debit'], true) . ' ',
            $header[1] => $this->coinToString($this->totals['credit'], true) . ' ',
            $header[2] => $this->coinToString($this->totals['debit'] - $this->totals['credit'], true) . ' ',
        ];
        $options = [
            $header[0] => ['display' => 'center'],
            $header[1] => ['display' => 'center'],
            $header[2] => ['display' => 'center'],
        ];
        $title = '<strong>' . $i18n->trans('totals') . '</strong>';
        $exportManager->addTablePage( $header, [$data], $options, $title );
    }

    /**
     *
     * @return ToolBox
     */
    protected function toolBox(): ToolBox
    {
        return new ToolBox();
    }

    /**
     * Converts an amount in currency format.
     * If not stated otherwise, zero values are not printed.
     *
     * @param float $value
     * @param bool $emptyValue
     * @return string
     */
    private function coinToString(float $value, bool $emptyValue = false): string
    {
        if (empty($value) && false === $emptyValue) {
            return ' ';
        }
        return $this->toolBox()->coins()->format($value);
    }

    /**
     * Get a row data from a accounting entry line.
     * Accumulates the amount of the line in the total balance.
     *
     * @param array $header
     * @param Partida $line
     * @param float $balance
     * @return array
     */
    private function dataFromLine(array &$header, Partida &$line, float &$balance): array
    {
        $balance += $line->debe - $line->haber;
        return [
            $header[0] => $line->codsubcuenta,
            $header[1] => $line->concepto,
            $header[2] => $this->coinToString($line->debe) . ' ',
            $header[3] => $this->coinToString($line->haber) . ' ',
            $header[4] => $this->coinToString($balance, true) . ' ',
            $header[5] => $line->codcontrapartida,
        ];
    }

    /**
     * Get a row data from vat register of the line.
     *
     * @param array $header
     * @param Partida $line
     */
    private function dataFromVATRegister(array &$header, Partida &$line)
    {
        return [
            $header[0] => $line->codserie,
            $header[1] => $line->factura,
            $header[2] => $line->documento,
            $header[3] => $line->cifnif,
            $header[4] => $this->coinToString($line->baseimponible, true),
            $header[5] => $line->iva,
            $header[6] => $this->coinToString($line->baseimponible * ($line->iva / 100), true) . ' ',
            $header[7] => $line->recargo,
            $header[8] => $this->coinToString($line->baseimponible * ($line->recargo / 100), true) . ' ',
        ];
    }

    /**
     *
     * @param Partida $line
     * @return bool
     */
    private function hasVatRegister(Partida &$line): bool
    {
        return false === empty($line->baseimponible)
            || false === empty($line->iva)
            || false === empty($line->recargo)
            || false === empty($line->codserie)
            || false === empty($line->factura);
    }
}
