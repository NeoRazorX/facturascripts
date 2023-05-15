<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ReportTaxes
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReportTaxes extends Controller
{
    const MAX_TOTAL_DIFF = 0.05;

    /** @var string */
    public $codpais;

    /** @var string */
    public $codserie;

    /** @var string */
    public $datefrom;

    /** @var string */
    public $dateto;

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

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'taxes';
        $data['menu'] = 'reports';
        $data['icon'] = 'fas fa-wallet';
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
        $this->pais = new Pais();
        $this->serie = new Serie();
        $this->initFilters();
        if ('export' === $this->request->request->get('action')) {
            $this->exportAction();
        }
    }

    protected function exportAction()
    {
        $data = $this->getReportData();
        if (empty($data)) {
            $this->toolBox()->i18nLog()->warning('no-data');
            return;
        }

        // prepare lines
        $lastCode = '';
        $lines = [];
        foreach ($data as $row) {
            $hide = $row['codigo'] === $lastCode && $this->format === 'PDF';
            $lines[] = [
                'serie' => $hide ? '' : $row['codserie'],
                'codigo' => $hide ? '' : $row['codigo'],
                'numero2' => $hide ? '' : $row['numero2'],
                'fecha' => $hide ? '' : date(User::DATE_STYLE, strtotime($row['fecha'])),
                'nombre' => $hide ? '' : $this->toolBox()->utils()->fixHtml($row['nombre']),
                'cifnif' => $hide ? '' : $row['cifnif'],
                'neto' => $this->exportFieldFormat('number', $row['neto']),
                'iva' => $this->exportFieldFormat('number', $row['iva']),
                'totaliva' => $this->exportFieldFormat('number', $row['totaliva']),
                'recargo' => $this->exportFieldFormat('number', $row['recargo']),
                'totalrecargo' => $this->exportFieldFormat('number', $row['totalrecargo']),
                'irpf' => $this->exportFieldFormat('number', $row['irpf']),
                'totalirpf' => $this->exportFieldFormat('number', $row['totalirpf']),
                'suplidos' => $this->exportFieldFormat('number', $row['suplidos']),
                'total' => $hide ? '' : $this->exportFieldFormat('number', $row['total'])
            ];

            $lastCode = $row['codigo'];
        }

        $totalsData = $this->getTotals($data);
        if (false === $this->validateTotals($totalsData)) {
            $this->toolBox()->i18nLog()->error('wrong-total-tax-calculation');
            return;
        }

        // prepare totals
        $totals = [];
        foreach ($totalsData as $row) {
            $total = $row['neto'] + $row['totaliva'] + $row['totalrecargo'] - $row['totalirpf'] - $row['suplidos'];
            $totals[] = [
                'neto' => $this->exportFieldFormat('coins', $row['neto']),
                'iva' => $this->exportFieldFormat('percentage', $row['iva']),
                'totaliva' => $this->exportFieldFormat('coins', $row['totaliva']),
                'recargo' => $this->exportFieldFormat('percentage', $row['recargo']),
                'totalrecargo' => $this->exportFieldFormat('coins', $row['totalrecargo']),
                'irpf' => $this->exportFieldFormat('percentage', $row['irpf']),
                'totalirpf' => $this->exportFieldFormat('coins', $row['totalirpf']),
                'suplidos' => $this->exportFieldFormat('coins', $row['suplidos']),
                'total' => $this->exportFieldFormat('coins', $total)
            ];
        }

        $this->setTemplate(false);
        $this->processLayout($lines, $totals);
    }

    protected function exportFieldFormat(string $format, string $value): string
    {
        switch ($format) {
            case 'coins':
                return $this->format === 'PDF' ? $this->toolBox()->coins()->format($value) : $value;

            case 'number':
                return $this->format === 'PDF' ? $this->toolBox()->numbers()->format($value) : $value;

            case 'percentage':
                return $this->format === 'PDF' ? $this->toolBox()->numbers()->format($value) . ' %' : $value;

            default:
                return $value;
        }
    }

    protected function getReportData(): array
    {
        $sql = '';
        $numCol = strtolower(FS_DB_TYPE) == 'postgresql' ? 'CAST(f.numero as integer)' : 'CAST(f.numero as unsigned)';
        switch ($this->source) {
            case 'purchases':
                $sql .= 'SELECT f.codserie, f.codigo, f.numproveedor AS numero2, f.fecha, f.nombre, f.cifnif, l.pvptotal,'
                    . ' l.iva, l.recargo, l.irpf, l.suplido, f.dtopor1, f.dtopor2, f.total'
                    . ' FROM lineasfacturasprov AS l'
                    . ' LEFT JOIN facturasprov AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND f.fecha >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND f.fecha <= ' . $this->dataBase->var2str($this->dateto)
                    . ' AND (l.pvptotal <> 0.00 OR l.iva <> 0.00)';
                break;

            case 'sales':
                $sql .= 'SELECT f.codserie, f.codigo, f.numero2, f.fecha, f.nombrecliente AS nombre, f.cifnif, l.pvptotal,'
                    . ' l.iva, l.recargo, l.irpf, l.suplido, f.dtopor1, f.dtopor2, f.total'
                    . ' FROM lineasfacturascli AS l'
                    . ' LEFT JOIN facturascli AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND f.fecha >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND f.fecha <= ' . $this->dataBase->var2str($this->dateto)
                    . ' AND (l.pvptotal <> 0.00 OR l.iva <> 0.00)';
                if ($this->codpais) {
                    $sql .= ' AND codpais = ' . $this->dataBase->var2str($this->codpais);
                }
                break;

            default:
                return [];
        }
        if ($this->codserie) {
            $sql .= ' AND codserie = ' . $this->dataBase->var2str($this->codserie);
        }
        $sql .= ' ORDER BY f.fecha, ' . $numCol . ' ASC;';

        $data = [];
        foreach ($this->dataBase->select($sql) as $row) {
            $pvpTotal = floatval($row['pvptotal']) * (100 - floatval($row['dtopor1'])) * (100 - floatval($row['dtopor2'])) / 10000;
            $code = $row['codigo'] . '-' . $row['iva'] . '-' . $row['recargo'] . '-' . $row['irpf'] . '-' . $row['suplido'];
            if (isset($data[$code])) {
                $data[$code]['neto'] += $row['suplido'] ? 0 : $pvpTotal;
                $data[$code]['totaliva'] += (float)$row['iva'] * $pvpTotal / 100;
                $data[$code]['totalrecargo'] += (float)$row['recargo'] * $pvpTotal / 100;
                $data[$code]['totalirpf'] += (float)$row['irpf'] * $pvpTotal / 100;
                $data[$code]['suplidos'] += $row['suplido'] ? $pvpTotal : 0;
                continue;
            }

            $data[$code] = [
                'codserie' => $row['codserie'],
                'codigo' => $row['codigo'],
                'numero2' => $row['numero2'],
                'fecha' => $row['fecha'],
                'nombre' => $row['nombre'],
                'cifnif' => $row['cifnif'],
                'neto' => $row['suplido'] ? 0 : $pvpTotal,
                'iva' => (float)$row['iva'],
                'totaliva' => (float)$row['iva'] * $pvpTotal / 100,
                'recargo' => (float)$row['recargo'],
                'totalrecargo' => (float)$row['recargo'] * $pvpTotal / 100,
                'irpf' => (float)$row['irpf'],
                'totalirpf' => (float)$row['irpf'] * $pvpTotal / 100,
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

    protected function initFilters()
    {
        $this->codpais = $this->request->request->get('codpais', '');
        $this->codserie = $this->request->request->get('codserie', '');
        $this->datefrom = $this->request->request->get('datefrom', date('Y-m-01'));
        $this->dateto = $this->request->request->get('dateto', date('Y-m-t'));
        $this->idempresa = (int)$this->request->request->get('idempresa', $this->empresa->idempresa);
        $this->format = $this->request->request->get('format');
        $this->source = $this->request->request->get('source');
    }

    protected function processLayout(array &$lines, array &$totals)
    {
        $i18n = $this->toolBox()->i18n();
        $exportManager = new ExportManager();
        $exportManager->setOrientation('landscape');
        $exportManager->newDoc($this->format, $i18n->trans('taxes'));

        // add information table
        $exportManager->addTablePage([$i18n->trans('report'), $i18n->trans('from-date'), $i18n->trans('until-date')], [
            [
                $i18n->trans('report') => $i18n->trans('taxes') . ' ' . $i18n->trans($this->source),
                $i18n->trans('from-date') => date(User::DATE_STYLE, strtotime($this->datefrom)),
                $i18n->trans('until-date') => date(User::DATE_STYLE, strtotime($this->dateto))
            ]
        ]);

        $options = [
            'neto' => ['display' => 'right'],
            'iva' => ['display' => 'right'],
            'totaliva' => ['display' => 'right'],
            'recargo' => ['display' => 'right'],
            'totalrecargo' => ['display' => 'right'],
            'irpf' => ['display' => 'right'],
            'totalirpf' => ['display' => 'right'],
            'suplidos' => ['display' => 'right'],
            'total' => ['display' => 'right']
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

    protected function reduceLines(array &$lines)
    {
        $zero = $this->toolBox()->numbers()->format(0);
        $numero2 = $recargo = $totalrecargo = $irpf = $totalirpf = $suplidos = false;
        foreach ($lines as $row) {
            if (!empty($row['numero2'])) {
                $numero2 = true;
            }

            if ($row['recargo'] !== $zero) {
                $recargo = true;
            }

            if ($row['totalrecargo'] !== $zero) {
                $totalrecargo = true;
            }

            if ($row['irpf'] !== $zero) {
                $irpf = true;
            }

            if ($row['totalirpf'] !== $zero) {
                $totalirpf = true;
            }

            if ($row['suplidos'] !== $zero) {
                $suplidos = true;
            }
        }

        foreach (array_keys($lines) as $key) {
            if (false === $numero2) {
                unset($lines[$key]['numero2']);
            }

            if (false === $recargo) {
                unset($lines[$key]['recargo']);
            }

            if (false === $totalrecargo) {
                unset($lines[$key]['totalrecargo']);
            }

            if (false === $irpf) {
                unset($lines[$key]['irpf']);
            }

            if (false === $totalirpf) {
                unset($lines[$key]['totalirpf']);
            }

            if (false === $suplidos) {
                unset($lines[$key]['suplidos']);
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
        $sql = 'SELECT SUM(neto) as neto, SUM(totaliva) as t1, SUM(totalrecargo) as t2 FROM ' . $tableName
            . ' WHERE idempresa = ' . $this->dataBase->var2str($this->idempresa)
            . ' AND fecha >= ' . $this->dataBase->var2str($this->datefrom)
            . ' AND fecha <= ' . $this->dataBase->var2str($this->dateto);
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
        return abs($neto - $neto2) <= self::MAX_TOTAL_DIFF &&
            abs($totalIva - $totalIva2) <= self::MAX_TOTAL_DIFF &&
            abs($totalRecargo - $totalRecargo2) <= self::MAX_TOTAL_DIFF;
    }
}
