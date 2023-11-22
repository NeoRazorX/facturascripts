<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaEspecial;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;
use ParseCsv\Csv;
use SimpleXMLElement;

/**
 * Description of AccountingPlanImport
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @author       Raul Jimenez             <comercial@nazcanetworks.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class AccountingPlanImport
{
    /**
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Exercise related to the accounting plan.
     *
     * @var Ejercicio
     */
    protected $exercise;

    public function __construct()
    {
        $this->dataBase = new DataBase();
        $this->exercise = new Ejercicio();
    }

    /**
     * Import data from CSV file.
     */
    public function importCSV(string $filePath, string $codejercicio): bool
    {
        if (false === $this->exercise->loadFromCode($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return false;
        }

        if (false === file_exists($filePath)) {
            Tools::log()->warning('file-not-found', ['%fileName%' => $filePath]);
            return false;
        }

        try {
            $this->dataBase->beginTransaction();

            $this->updateSpecialAccounts();
            if (false === $this->processCsvData($filePath)) {
                $this->dataBase->rollback();
                return false;
            }

            $this->dataBase->commit();
            return true;
        } catch (Exception $exp) {
            $this->dataBase->rollback();
            Tools::log()->error($exp->getLine() . ' -> ' . $exp->getMessage());
            return false;
        }
    }

    /**
     * Import data from XML file.
     */
    public function importXML(string $filePath, string $codejercicio): bool
    {
        if (false === $this->exercise->loadFromCode($codejercicio)) {
            Tools::log()->error('exercise-not-found');
            return false;
        }

        $data = $this->getData($filePath);
        if (is_array($data) || $data->count() == 0) {
            return false;
        }

        try {
            $this->dataBase->beginTransaction();

            $this->updateSpecialAccounts();
            if (false === $this->importEpigrafeGroup($data->grupo_epigrafes)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importEpigrafe($data->epigrafes)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importCuenta($data->cuenta)) {
                $this->dataBase->rollback();
                return false;
            }
            if (false === $this->importSubcuenta($data->subcuenta)) {
                $this->dataBase->rollback();
                return false;
            }

            $this->dataBase->commit();
            return true;
        } catch (Exception $exp) {
            $this->dataBase->rollback();
            Tools::log()->error($exp->getLine() . ' -> ' . $exp->getMessage());
            return false;
        }
    }

    /**
     * Insert/update and account in accounting plan.
     */
    protected function createAccount(string $code, string $definition, ?string $parentCode = '', ?string $codcuentaesp = ''): bool
    {
        // the account exists?
        $account = new Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codcuenta', $code)
        ];
        if ($account->loadFromCode('', $where)) {
            return true;
        }

        $account->codcuenta = $code;
        $account->codcuentaesp = empty($codcuentaesp) ? null : $codcuentaesp;
        $account->codejercicio = $this->exercise->codejercicio;
        $account->descripcion = $definition;
        $account->parent_codcuenta = empty($parentCode) ? null : $parentCode;
        return $account->save();
    }

    /**
     * Insert or update an account in accounting Plan.
     */
    protected function createSubaccount(string $code, string $description, string $parentCode, ?string $codcuentaesp = ''): bool
    {
        // the subaccount exists?
        $subaccount = new Subcuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codsubcuenta', $code)
        ];
        if ($subaccount->loadFromCode('', $where)) {
            return true;
        }

        // update exercise configuration
        if ($this->exercise->longsubcuenta != strlen($code)) {
            $this->exercise->longsubcuenta = strlen($code);
            if (false === $this->exercise->save()) {
                return false;
            }
        }

        $subaccount->codcuenta = $parentCode;
        $subaccount->codcuentaesp = empty($codcuentaesp) ? null : $codcuentaesp;
        $subaccount->codejercicio = $this->exercise->codejercicio;
        $subaccount->codsubcuenta = $code;
        $subaccount->descripcion = $description;
        return $subaccount->save();
    }

    /**
     * returns an array width the content of xml file
     *
     * @param string $filePath
     *
     * @return SimpleXMLElement|array
     */
    protected function getData(string $filePath)
    {
        if (file_exists($filePath)) {
            return simplexml_load_string(file_get_contents($filePath));
        }

        return [];
    }

    /**
     * insert Cuenta of accounting plan
     */
    protected function importCuenta(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlAccount) {
            $item = (array)$xmlAccount;
            if (false === $this->createAccount($item['codcuenta'], base64_decode($item['descripcion']), $item['codepigrafe'], $item['idcuentaesp'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * insert Epigrafe of accounting plan
     */
    protected function importEpigrafe(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlEpigrafeElement) {
            $item = (array)$xmlEpigrafeElement;
            if (false === $this->createAccount($item['codepigrafe'], base64_decode($item['descripcion']), $item['codgrupo'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Insert Groups of accounting plan
     */
    protected function importEpigrafeGroup(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlEpigrafeGroup) {
            $item = (array)$xmlEpigrafeGroup;
            if (false === $this->createAccount($item['codgrupo'], base64_decode($item['descripcion']))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Import subaccounts of accounting plan
     */
    protected function importSubcuenta(SimpleXMLElement $data): bool
    {
        foreach ($data as $xmlSubaccountElement) {
            $item = (array)$xmlSubaccountElement;
            if (false === $this->createSubaccount($item['codsubcuenta'], base64_decode($item['descripcion']), $item['codcuenta'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Load accounting plan from CSV File and imports in accounting plan.
     */
    protected function processCsvData(string $filePath): bool
    {
        $csv = new Csv();
        $csv->auto($filePath);

        $length = [];
        $accountPlan = [];
        foreach ($csv->data as $value) {
            $key0 = $value[$csv->titles[0]] ?? $value[0];
            if (strlen($key0) > 0) {
                $accountPlan[$key0] = [
                    'descripcion' => $value[$csv->titles[1]] ?? $value[1],
                    'codcuentaesp' => $value[$csv->titles[2]] ?? $value[2]
                ];
                $length[] = strlen($key0);
            }
        }

        $lengths = array_unique($length);
        sort($lengths);
        $minLength = min($lengths);
        $maxLength = max($lengths);
        $keys = array_keys($accountPlan);
        ksort($accountPlan);

        foreach ($accountPlan as $key => $value) {
            switch (strlen($key)) {
                case $minLength:
                    $ok = $this->createAccount($key, $value['descripcion'], '', $value['codcuentaesp']);
                    break;

                case $maxLength:
                    $parentCode = $this->searchParent($keys, $key);
                    $ok = $this->createSubaccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;

                default:
                    $parentCode = $this->searchParent($keys, $key);
                    $ok = $this->createAccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;
            }

            if (false === $ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Search the parent of account in accounting Plan.
     */
    protected function searchParent(array &$accountCodes, string $account): string
    {
        $parentCode = '';
        foreach ($accountCodes as $code) {
            $strCode = (string)$code;
            if ($strCode === $account) {
                continue;
            } elseif (strpos($account, $strCode) === 0 && strlen($strCode) > strlen($parentCode)) {
                $parentCode = $code;
            }
        }

        return $parentCode;
    }

    /**
     * Update special accounts from data file.
     */
    protected function updateSpecialAccounts()
    {
        $sql = CSVImport::updateTableSQL(CuentaEspecial::tableName());
        if (!empty($sql) && $this->dataBase->tableExists(CuentaEspecial::tableName())) {
            $this->dataBase->exec($sql);
        }
    }
}
