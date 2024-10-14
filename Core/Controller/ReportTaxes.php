<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of ReportTaxes
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReportTaxes extends Controller
{
    const MAX_TOTAL_DIFF = 0.05;

    /** @var string */
    public $coddivisa;

    /** @var string */
    public $codpais;

    /** @var string */
    public $codserie;

    /** @var string */
    public $datefrom;

    /** @var string */
    public $dateto;

    /** @var Divisa */
    public $divisa;

    /** @var string */
    public $format;

    /** @var int */
    public $idempresa;

    /** @var Pais */
    public $pais;

    /** @var Serie */
    public $serie;

    /** @var string */
    public $source;

    /** @var string */
    public $typeDate;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'taxes';
        $data['menu'] = 'reports';
        $data['icon'] = 'fa-solid fa-wallet';
        return $data;
    }

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->divisa = new Divisa();
        $this->pais = new Pais();
        $this->serie = new Serie();
        $this->initFilters();

        if ('export' === $this->request->request->get('action')) {
            $this->exportAction();
        }
    }

    protected function exportAction(): void
    {
        $i18n = Tools::lang();
        $data = $this->getReportData();
        if (empty($data)) {
            Tools::log()->warning('no-data');
            return;
        }

        // prepare lines
        $lastCode = '';
        $lines = [];
        foreach ($data as $row) {
            $hide = $row['codigo'] === $lastCode && $this->format === 'PDF';
            $num2title = $this->source === 'sales' ? 'number2' : 'numproveedor';

            $lines[] = [
                $i18n->trans('serie') => $hide ? '' : $row['codserie'],
                $i18n->trans('code') => $hide ? '' : $row['codigo'],
                $i18n->trans($num2title) => $hide ? '' : $row['numero2'],
                $i18n->trans('date') => $hide ? '' : Tools::date($row['fecha']),
                $i18n->trans('name') => $hide ? '' : Tools::fixHtml($row['nombre']),
                $i18n->trans('cifnif') => $hide ? '' : $row['cifnif'],
                $i18n->trans('net') => $this->exportFieldFormat('number', $row['neto']),
                $i18n->trans('pct-tax') => $this->exportFieldFormat('number', $row['iva']),
                $i18n->trans('tax') => $this->exportFieldFormat('number', $row['totaliva']),
                $i18n->trans('pct-surcharge') => $this->exportFieldFormat('number', $row['recargo']),
                $i18n->trans('surcharge') => $this->exportFieldFormat('number', $row['totalrecargo']),
                $i18n->trans('pct-irpf') => $this->exportFieldFormat('number', $row['irpf']),
                $i18n->trans('irpf') => $this->exportFieldFormat('number', $row['totalirpf']),
                $i18n->trans('supplied-amount') => $this->exportFieldFormat('number', $row['suplidos']),
                $i18n->trans('total') => $hide ? '' : $this->exportFieldFormat('number', $row['total'])
            ];

            $lastCode = $row['codigo'];
        }

        $totalsData = $this->getTotals($data);
        if (false === $this->validateTotals($totalsData)) {
            return;
        }

        // prepare totals
        $totals = [];
        foreach ($totalsData as $row) {
            $total = $row['neto'] + $row['totaliva'] + $row['totalrecargo'] - $row['totalirpf'] - $row['suplidos'];
            $totals[] = [
                $i18n->trans('net') => $this->exportFieldFormat('number', $row['neto']),
                $i18n->trans('pct-tax') => $this->exportFieldFormat('percentage', $row['iva']),
                $i18n->trans('tax') => $this->exportFieldFormat('number', $row['totaliva']),
                $i18n->trans('pct-surcharge') => $this->exportFieldFormat('percentage', $row['recargo']),
                $i18n->trans('surcharge') => $this->exportFieldFormat('number', $row['totalrecargo']),
                $i18n->trans('pct-irpf') => $this->exportFieldFormat('percentage', $row['irpf']),
                $i18n->trans('irpf') => $this->exportFieldFormat('number', $row['totalirpf']),
                $i18n->trans('supplied-amount') => $this->exportFieldFormat('number', $row['suplidos']),
                $i18n->trans('total') => $this->exportFieldFormat('number', $total)
            ];
        }

        $this->setTemplate(false);
        $this->processLayout($lines, $totals);
    }

    protected function exportFieldFormat(string $format, string $value): string
    {
        switch ($format) {
            case 'number':
                return $this->format === 'PDF' ? Tools::number($value) : $value;

            case 'percentage':
                return $this->format === 'PDF' ? Tools::number($value) . ' %' : $value;

            default:
                return $value;
        }
    }

    protected function getQuarterDate(bool $start): string
    {
        $month = (int)date('m');

        // si la fecha actual es de enero, seleccionamos el trimestre anterior
        if ($month === 1) {
            return $start ?
                date('Y-10-01', strtotime('-1 year')) :
                date('Y-12-31', strtotime('-1 year'));
        }

        // comprobamos si la fecha actual está en el primer trimestre o justo en el siguiente mes
        if ($month >= 1 && $month <= 4) {
            return $start ? date('Y-01-01') : date('Y-03-31');
        }

        // comprobamos si la fecha actual está en el segundo trimestre o justo en el siguiente mes
        if ($month >= 4 && $month <= 7) {
            return $start ? date('Y-04-01') : date('Y-06-30');
        }

        // comprobamos si la fecha actual está en el tercer trimestre o justo en el siguiente mes
        if ($month >= 7 && $month <= 10) {
            return $start ? date('Y-07-01') : date('Y-09-30');
        }

        // la fecha actual está en el cuarto trimestre
        return $start ? date('Y-10-01') : date('Y-12-31');
    }

    protected function getReportData(): array
    {
        $sql = '';
        $numCol = strtolower(FS_DB_TYPE) == 'postgresql' ? 'CAST(f.numero as integer)' : 'CAST(f.numero as unsigned)';
        $columnDate = $this->typeDate === 'create' ? 'f.fecha' : 'COALESCE(f.fechadevengo, f.fecha)';
        switch ($this->source) {
            case 'purchases':
                $sql .= 'SELECT f.codserie, f.codigo, f.numproveedor AS numero2, f.fecha, f.fechadevengo, f.nombre, f.cifnif, l.pvptotal,'
                    . ' l.iva, l.recargo, l.irpf, l.suplido, f.dtopor1, f.dtopor2, f.total, f.operacion'
                    . ' FROM lineasfacturasprov AS l'
                    . ' LEFT JOIN facturasprov AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND ' . $columnDate . ' >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND ' . $columnDate . ' <= ' . $this->dataBase->var2str($this->dateto)
                    . ' AND (l.pvptotal <> 0.00 OR l.iva <> 0.00)'
                    . ' AND f.coddivisa = ' . $this->dataBase->var2str($this->coddivisa);
                break;

            case 'sales':
                $sql .= 'SELECT f.codserie, f.codigo, f.numero2, f.fecha, f.fechadevengo, f.nombrecliente AS nombre, f.cifnif, l.pvptotal,'
                    . ' l.iva, l.recargo, l.irpf, l.suplido, f.dtopor1, f.dtopor2, f.total, f.operacion'
                    . ' FROM lineasfacturascli AS l'
                    . ' LEFT JOIN facturascli AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND ' . $columnDate . ' >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND ' . $columnDate . ' <= ' . $this->dataBase->var2str($this->dateto)
                    . ' AND (l.pvptotal <> 0.00 OR l.iva <> 0.00)'
                    . ' AND f.coddivisa = ' . $this->dataBase->var2str($this->coddivisa);
                if ($this->codpais) {
                    $sql .= ' AND codpais = ' . $this->dataBase->var2str($this->codpais);
                }
                break;

            default:
                Tools::log()->warning('wrong-source');
                return [];
        }
        if ($this->codserie) {
            $sql .= ' AND codserie = ' . $this->dataBase->var2str($this->codserie);
        }
        $sql .= ' ORDER BY ' . $columnDate . ', ' . $numCol . ' ASC;';

        $data = [];
        foreach ($this->dataBase->select($sql) as $row) {
            $pvpTotal = floatval($row['pvptotal']) * (100 - floatval($row['dtopor1'])) * (100 - floatval($row['dtopor2'])) / 10000;
            $code = $row['codigo'] . '-' . $row['iva'] . '-' . $row['recargo'] . '-' . $row['irpf'] . '-' . $row['suplido'];
            if (isset($data[$code])) {
                $data[$code]['neto'] += $row['suplido'] ? 0 : $pvpTotal;
                $data[$code]['totaliva'] += $row['suplido'] || $row['operacion'] === InvoiceOperation::INTRA_COMMUNITY ? 0 : (float)$row['iva'] * $pvpTotal / 100;
                $data[$code]['totalrecargo'] += $row['suplido'] ? 0 : (float)$row['recargo'] * $pvpTotal / 100;
                $data[$code]['totalirpf'] += $row['suplido'] ? 0 : (float)$row['irpf'] * $pvpTotal / 100;
                $data[$code]['suplidos'] += $row['suplido'] ? $pvpTotal : 0;
                continue;
            }

            $data[$code] = [
                'codserie' => $row['codserie'],
                'codigo' => $row['codigo'],
                'numero2' => $row['numero2'],
                'fecha' => $this->typeDate == 'create' ?
                    $row['fecha'] :
                    $row['fechadevengo'] ?? $row['fecha'],
                'nombre' => $row['nombre'],
                'cifnif' => $row['cifnif'],
                'neto' => $row['suplido'] ? 0 : $pvpTotal,
                'iva' => $row['suplido'] ? 0 : (float)$row['iva'],
                'totaliva' => $row['suplido'] || $row['operacion'] === InvoiceOperation::INTRA_COMMUNITY ? 0 : (float)$row['iva'] * $pvpTotal / 100,
                'recargo' => $row['suplido'] ? 0 : (float)$row['recargo'],
                'totalrecargo' => $row['suplido'] ? 0 : (float)$row['recargo'] * $pvpTotal / 100,
                'irpf' => $row['suplido'] ? 0 : (float)$row['irpf'],
                'totalirpf' => $row['suplido'] ? 0 : (float)$row['irpf'] * $pvpTotal / 100,
                'suplidos' => $row['suplido'] ? $pvpTotal : 0,
                'total' => (float)$row['total']
            ];
        }

        // round
        foreach ($data as $key => $value) {
            $data[$key]['neto'] = round($value['neto'], FS_NF0);
            $data[$key]['totaliva'] = round($value['totaliva'], FS_NF0);
            $data[$key]['totalrecargo'] = round($value['totalrecargo'], FS_NF0);
            $data[$key]['totalirpf'] = round($value['totalirpf'], FS_NF0);
            $data[$key]['suplidos'] = round($value['suplidos'], FS_NF0);
        }

        return $data;
    }

    protected function getTotals(array $data): array
    {
        $totals = [];
        foreach ($data as $row) {
            $code = $row['iva'] . '-' . $row['recargo'] . '-' . $row['irpf'];
            if (isset($totals[$code])) {
                $totals[$code]['neto'] += $row['neto'];
                $totals[$code]['totaliva'] += $row['totaliva'];
                $totals[$code]['totalrecargo'] += $row['totalrecargo'];
                $totals[$code]['totalirpf'] += $row['totalirpf'];
                $totals[$code]['suplidos'] += $row['suplidos'];
                continue;
            }

            $totals[$code] = [
                'neto' => $row['neto'],
                'iva' => $row['iva'],
                'totaliva' => $row['totaliva'],
                'recargo' => $row['recargo'],
                'totalrecargo' => $row['totalrecargo'],
                'irpf' => $row['irpf'],
                'totalirpf' => $row['totalirpf'],
                'suplidos' => $row['suplidos']
            ];
        }

        return $totals;
    }

    protected function initFilters(): void
    {
        $this->coddivisa = $this->request->request->get(
            'coddivisa',
            Tools::settings('default', 'coddivisa')
        );

        $this->codpais = $this->request->request->get('codpais', '');
        $this->codserie = $this->request->request->get('codserie', '');
        $this->datefrom = $this->request->request->get('datefrom', $this->getQuarterDate(true));
        $this->dateto = $this->request->request->get('dateto', $this->getQuarterDate(false));

        $this->idempresa = (int)$this->request->request->get(
            'idempresa',
            Tools::settings('default', 'idempresa')
        );

        $this->format = $this->request->request->get('format');
        $this->source = $this->request->request->get('source');
        $this->typeDate = $this->request->request->get('type-date');
    }

    protected function processLayout(array &$lines, array &$totals): void
    {
        $i18n = Tools::lang();
        $exportManager = new ExportManager();
        $exportManager->setOrientation('landscape');
        $exportManager->newDoc($this->format, $i18n->trans('taxes'));

        // add information table
        $exportManager->addTablePage(
            [
                $i18n->trans('report'),
                $i18n->trans('currency'),
                $i18n->trans('date'),
                $i18n->trans('from-date'),
                $i18n->trans('until-date')
            ],
            [[
                $i18n->trans('report') => $i18n->trans('taxes') . ' ' . $i18n->trans($this->source),
                $i18n->trans('currency') => Divisas::get($this->coddivisa)->descripcion,
                $i18n->trans('date') => $i18n->trans($this->typeDate === 'create' ? 'creation-date' : 'accrual-date'),
                $i18n->trans('from-date') => Tools::date($this->datefrom),
                $i18n->trans('until-date') => Tools::date($this->dateto)
            ]]
        );

        $options = [
            $i18n->trans('net') => ['display' => 'right'],
            $i18n->trans('pct-tax') => ['display' => 'right'],
            $i18n->trans('tax') => ['display' => 'right'],
            $i18n->trans('pct-surcharge') => ['display' => 'right'],
            $i18n->trans('surcharge') => ['display' => 'right'],
            $i18n->trans('pct-irpf') => ['display' => 'right'],
            $i18n->trans('irpf') => ['display' => 'right'],
            $i18n->trans('supplied-amount') => ['display' => 'right'],
            $i18n->trans('total') => ['display' => 'right']
        ];

        // add lines table
        $this->reduceLines($lines);
        $headers = empty($lines) ? [] : array_keys(end($lines));
        $exportManager->addTablePage($headers, $lines, $options);

        // add totals table
        $headTotals = empty($totals) ? [] : array_keys(end($totals));
        $exportManager->addTablePage($headTotals, $totals, $options);

        $exportManager->show($this->response);
    }

    protected function reduceLines(array &$lines): void
    {
        $i18n = Tools::lang();
        $zero = Tools::number(0);
        $numero2 = $recargo = $totalRecargo = $irpf = $totalIrpf = $suplidos = false;
        foreach ($lines as $row) {
            if (!empty($row[$i18n->trans('number2')])) {
                $numero2 = true;
            }

            if ($row[$i18n->trans('pct-surcharge')] !== $zero) {
                $recargo = true;
            }

            if ($row[$i18n->trans('surcharge')] !== $zero) {
                $totalRecargo = true;
            }

            if ($row[$i18n->trans('pct-irpf')] !== $zero) {
                $irpf = true;
            }

            if ($row[$i18n->trans('irpf')] !== $zero) {
                $totalIrpf = true;
            }

            if ($row[$i18n->trans('supplied-amount')] !== $zero) {
                $suplidos = true;
            }
        }

        foreach (array_keys($lines) as $key) {
            if (false === $numero2) {
                unset($lines[$key][$i18n->trans('number2')]);
            }

            if (false === $recargo) {
                unset($lines[$key][$i18n->trans('pct-surcharge')]);
            }

            if (false === $totalRecargo) {
                unset($lines[$key][$i18n->trans('surcharge')]);
            }

            if (false === $irpf) {
                unset($lines[$key][$i18n->trans('pct-irpf')]);
            }

            if (false === $totalIrpf) {
                unset($lines[$key][$i18n->trans('irpf')]);
            }

            if (false === $suplidos) {
                unset($lines[$key][$i18n->trans('supplied-amount')]);
            }
        }
    }

    protected function validateTotals(array $totalsData): bool
    {
        // sum totals from the given data
        $neto = $totalIva = $totalRecargo = 0.0;
        foreach ($totalsData as $row) {
            $neto += $row['neto'];
            $totalIva += $row['totaliva'];
            $totalRecargo += $row['totalrecargo'];
        }

        // gets totals from the database
        $neto2 = $totalIva2 = $totalRecargo2 = 0.0;
        $tableName = $this->source === 'sales' ? 'facturascli' : 'facturasprov';
        $columnDate = $this->typeDate === 'create' ? 'fecha' : 'COALESCE(fechadevengo, fecha)';
        $sql = 'SELECT SUM(neto) as neto, SUM(totaliva) as t1, SUM(totalrecargo) as t2'
            . ' FROM ' . $tableName
            . ' WHERE idempresa = ' . $this->dataBase->var2str($this->idempresa)
            . ' AND ' . $columnDate . ' >= ' . $this->dataBase->var2str($this->datefrom)
            . ' AND ' . $columnDate . ' <= ' . $this->dataBase->var2str($this->dateto)
            . ' AND coddivisa = ' . $this->dataBase->var2str($this->coddivisa);
        if ($this->codserie) {
            $sql .= ' AND codserie = ' . $this->dataBase->var2str($this->codserie);
        }
        if ($this->codpais && $this->source === 'sales') {
            $sql .= ' AND codpais = ' . $this->dataBase->var2str($this->codpais);
        }
        foreach ($this->dataBase->selectLimit($sql) as $row) {
            $neto2 += (float)$row['neto'];
            $totalIva2 += (float)$row['t1'];
            $totalRecargo2 += (float)$row['t2'];
        }

        // compare
        $result = true;
        if (abs($neto - $neto2) > self::MAX_TOTAL_DIFF) {
            Tools::log()->error('calculated-net-diff', ['%net%' => $neto, '%net2%' => $neto2]);
            $result = false;
        }

        if (abs($totalIva - $totalIva2) > self::MAX_TOTAL_DIFF) {
            Tools::log()->error('calculated-tax-diff', ['%tax%' => $totalIva, '%tax2%' => $totalIva2]);
            $result = false;
        }

        if (abs($totalRecargo - $totalRecargo2) > self::MAX_TOTAL_DIFF) {
            Tools::log()->error('calculated-surcharge-diff', [
                '%surcharge%' => $totalRecargo, '%surcharge2%' => $totalRecargo2
            ]);
            $result = false;
        }

        return $result;
    }
}
