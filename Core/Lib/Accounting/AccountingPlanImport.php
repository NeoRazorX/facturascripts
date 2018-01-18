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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Epigrafe;
use FacturaScripts\Core\Model\Balance;
use FacturaScripts\Core\Model\BalanceCuenta;
use FacturaScripts\Core\Model\BalanceCuentaA;
use FacturaScripts\Core\Model\GrupoEpigrafes;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of AccountingPlanImport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Raul Jimenez <comercial@nazcanetworks.com>
 */
class AccountingPlanImport
{

    /**
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    private $ejercicio;

    /**
     * Import data from XML file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importXML($filePath, $codejercicio)
    {

        $this->ejercicio = new Ejercicio();
        $this->ejercicio->codejercicio = $codejercicio;

        $data = $this->getData($filePath);

        if ($data->count() > 0) {
            $this->importBalance($data->balance);
            $this->importBalanceCuenta($data->balance_cuenta);
            $this->importBalanceCuentaA($data->balance_cuenta_a);
            $this->importEpigrafeGroup($data->grupo_epigrafes);
            $this->importEpigrafe($data->epigrafe);
            $this->importCuenta($data->cuenta);
            $this->importSubcuenta($data->subcuenta);
        }
    }

    /**
     * returns an array width the content of xml file
     *
     * @param string $filePath
     * @return array object
     *
     */
    private function getData($filePath)
    {
        if (file_exists($filePath)) {
            return simplexml_load_file(($filePath));
        } else {
            return [];
        }
    }

    /**
     * insert into system balance definition
     * @param array  $data
     */
    private function importBalance($data)
    {
        $bal = new Balance();

        if ($bal->count() == 0) {
            foreach ($data as $balance) {
                $bal = new Balance();
                if (!$bal->get($balance->codbalance)) {
                    $bal->codbalance = $balance->codbalance;
                    $bal->naturaleza = $balance->naturaleza;
                    $bal->nivel1 = $balance->nivel1;
                    $bal->descripcion1 = base64_decode($balance->descripcion1);
                    $bal->nivel2 = $balance->nivel2;
                    $bal->descripcion2 = base64_decode($balance->descripcion2);
                    $bal->orden3 = $balance->orden3;
                    $bal->nivel3 = $balance->nivel3;
                    $bal->descripcion3 = base64_decode($balance->descripcion3);
                    $bal->nivel4 = $balance->nivel4;
                    $bal->descripcion4 = base64_decode($balance->descripcion4);
                    $bal->descripcion4ba = base64_decode($balance->descripcion4ba);
                    $bal->save();
                }
            }
        }
    }

    /**
     * Insert counts of balance definition
     * @param array $data
     */
    private function importBalanceCuenta($data)
    {
        $balCuenta = new BalanceCuenta();
        $all_bcs = $balCuenta->all();
        $arr = [];
        $where = [];
        foreach ($data as $bcta) {
            $arr = (array) $bcta;
            $where[] = new DataBaseWhere('codbalance', $arr['codbalance']);
            $where[] = new DataBaseWhere('codcuenta', $arr['codcuenta']);

            if (!$balCuenta->all($where)) {
                $balCuenta = new BalanceCuenta();
                $balCuenta->codbalance = $bcta->codbalance;
                $balCuenta->codcuenta = $bcta->codcuenta;
                $balCuenta->desccuenta = base64_decode($bcta->descripcion);
                $balCuenta->save();
            }
        }
    }

    /**
     * Insert counts of abbreviate balance definition
     * @param array $data
     */
    private function importBalanceCuentaA($data)
    {
        $balCuenta = new BalanceCuentaA();
        $all_bcs = $balCuenta->all();
        $arr = [];
        $where = [];
        foreach ($data as $bcta) {
            $arr = (array) $bcta;
            $where[] = new DataBaseWhere('codbalance', $arr['codbalance']);
            $where[] = new DataBaseWhere('codcuenta', $arr['codcuenta']);
            if (!$balCuenta->all($where)) {
                $balCuenta = new BalanceCuentaA();
                $balCuenta->codbalance = $bcta->codbalance;
                $balCuenta->codcuenta = $bcta->codcuenta;
                $balCuenta->desccuenta = base64_decode($bcta->descripcion);
                $balCuenta->save();
            }
        }
    }

    /**
     * Insert Groups of accounting plan
     *
     * @param array $data
     */
    private function importEpigrafeGroup($data)
    {
        $gepig = new GrupoEpigrafes();
        $arr = [];
        $where = [];
        foreach ($data as $grupoEpig) {
            $arr = (array) $grupoEpig;
            $where[] = new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio);
            $where[] = new DataBaseWhere('codgrupo', $arr['codgrupo']);
            $where[] = new DatabaseWhere('idempresa', AppSettings::get('default', 'idempresa'));

            if (!$gepig->all($where)) {
                $gepig = new GrupoEpigrafes();
                $gepig->idempresa = AppSettings::get('default', 'idempresa');
                $gepig->codejercicio = $this->ejercicio->codejercicio;
                $gepig->codgrupo = $grupoEpig->codgrupo;
                $gepig->descripcion = \base64_decode($grupoEpig->descripcion);
                $gepig->save();
            }
        }
    }

    /**
     * insert Epigrafe of accounting plan
     *
     * @param array $data
     */
    private function importEpigrafe($data)
    {
        $epig = new Epigrafe();
        $arr = [];
        $where = [];
        foreach ($data as $epigrafe) {
            $arr = (array) $epigrafe;
            $where[] = new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio);
            $where[] = new DataBaseWhere('codepigrafe', $arr['codepigrafe']);
            $where[] = new DatabaseWhere('idempresa', AppSettings::get('default', 'idempresa'));

            if (!$epig->all($where)) {
                $epig = new Epigrafe();
                $epig->idempresa = AppSettings::get('default', 'idempresa');
                $epig->codejercicio = $this->ejercicio->codejercicio;
                $epig->codgrupo = $epigrafe->codgrupo;
                $epig->codepigrafe = $epigrafe->codepigrafe;
                $epig->descripcion = base64_decode($epigrafe->descripcion);

                $epig->save();
            }
        }
    }

    /**
     * insert Cuenta of accounting plan
     *
     * @param array $data
     */
    private function importCuenta($data)
    {
        $cta = new Cuenta();
        $arr = [];
        $where = [];
        $epigrafe = new Epigrafe();
        foreach ($data as $cuenta) {
            $arr = (array) $cuenta;
            $where[] = new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio);
            $where[] = new DataBaseWhere('codcuenta', $arr['codcuenta']);
            $where[] = new DatabaseWhere('idempresa', AppSettings::get('default', 'idempresa'));
            if (!$cta->all($where)) {
                $cta = new Cuenta();
                $cta->codejercicio = $this->ejercicio->codejercicio;
                $cta->idempresa = AppSettings::get('default', 'idempresa');
                $cta->codepigrafe = $cuenta->codepigrafe;
                $cta->codcuenta = $cuenta->codcuenta;
                $cta->descripcion = base64_decode($cuenta->descripcion);
                $cta->idcuentaesp = $cuenta->idcuentaesp;
                $cta->idepigrafe = $epigrafe->getByCodigo($cuenta->codepigrafe, $cta->codejercicio)->idepigrafe;
                $cta->save();
            }
        }
    }

    /**
     * Import subaccounts of accounting plan
     *
     * @param array $data
     */
    private function importSubcuenta($data)
    {
        $subcta = new Subcuenta();
        $arr = [];
        $where = [];

        foreach ($data as $subcuenta) {
            $arr = (array) $subcuenta;
            $where[] = new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio);
            $where[] = new DataBaseWhere('codcuenta', $arr['codcuenta']);
            $where[] = new DataBaseWhere('codsubcuenta', $arr['codsubcuenta']);
            $where[] = new DatabaseWhere('idempresa', AppSettings::get('default', 'idempresa'));
            if (!$subcta->all($where)) {
                $subcta = new Subcuenta();
                $subcta->codejercicio = $this->ejercicio->codejercicio;
                $subcta->idempresa = AppSettings::get('default', 'idempresa');
                $subcta->codcuenta = $subcuenta->codcuenta;
                $subcta->codsubcuenta = $subcuenta->codsubcuenta;
                $subcta->descripcion = base64_decode(($subcuenta->descripcion));
                $subcta->coddivisa = $subcuenta->coddivisa;
                $subcta->save();
            }
        }
    }

    /**
     * Import data from CSV file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importCSV($filePath, $codejercicio)
    {
        /**
         * TODO: read CSV file and import GrupoEpigrafe, Epigrafe, Cuenta and Subcuenta
         * data.
         */
    }
}
