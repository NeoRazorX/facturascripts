<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ToolBox;
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
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <comercial@nazcanetworks.com>
 */
class AccountingPlanImport
{

    /**
     *
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
     *
     * @param string $filePath
     * @param string $codejercicio
     *
     * @return bool
     */
    public function importCSV(string $filePath, string $codejercicio)
    {
        if (!$this->exercise->loadFromCode($codejercicio)) {
            $this->toolBox()->i18nLog()->error('exercise-not-found');
            return false;
        }

        if (!file_exists($filePath)) {
            $this->toolBox()->i18nLog()->warning('file-not-found', ['%fileName%' => $filePath]);
            return false;
        }

        // start transaction
        $this->dataBase->beginTransaction();
        $return = true;

        try {
            $this->updateSpecialAccounts();
            $this->processCsvData($filePath);

            // confirm data
            $this->dataBase->commit();
        } catch (Exception $exp) {
            $this->toolBox()->log()->error($exp->getMessage());
            $return = false;
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }

        return $return;
    }

    /**
     * Import data from XML file.
     *
     * @param string $filePath
     * @param string $codejercicio
     *
     * @return bool
     */
    public function importXML(string $filePath, string $codejercicio)
    {
        if (!$this->exercise->loadFromCode($codejercicio)) {
            $this->toolBox()->i18nLog()->error('exercise-not-found');
            return false;
        }

        $data = $this->getData($filePath);
        if (\is_array($data) || $data->count() == 0) {
            return false;
        }

        // start transaction
        $this->dataBase->beginTransaction();
        $return = true;

        try {
            $this->updateSpecialAccounts();
            $this->importEpigrafeGroup($data->grupo_epigrafes);
            $this->importEpigrafe($data->epigrafe);
            $this->importCuenta($data->cuenta);
            $this->importSubcuenta($data->subcuenta);

            // confirm data
            $this->dataBase->commit();
        } catch (Exception $exp) {
            $this->toolBox()->log()->error($exp->getMessage());
            $return = false;
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }

        return $return;
    }

    /**
     * Insert/update and account in accounting plan.
     *
     * @param string $code
     * @param string $definition
     * @param string $parentCode
     * @param string $codcuentaesp
     *
     * @return bool
     */
    protected function createAccount(string $code, string $definition, string $parentCode = '', string $codcuentaesp = '')
    {
        $account = new Cuenta();

        /// the account exists?
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
     *
     * @param string $code
     * @param string $description
     * @param string $parentCode
     * @param string $codcuentaesp
     *
     * @return bool
     */
    protected function createSubaccount(string $code, string $description, string $parentCode, string $codcuentaesp = '')
    {
        $subaccount = new Subcuenta();

        /// the subaccount exists?
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codsubcuenta', $code)
        ];
        if ($subaccount->loadFromCode('', $where)) {
            return true;
        }

        /// update exercise configuration
        if ($this->exercise->longsubcuenta != \strlen($code)) {
            $this->exercise->longsubcuenta = \strlen($code);
            $this->exercise->save();
            $subaccount->clearExerciseCache();
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
        if (\file_exists($filePath)) {
            return \simplexml_load_string(\file_get_contents($filePath));
        }

        return [];
    }

    /**
     * insert Cuenta of accounting plan
     *
     * @param SimpleXMLElement $data
     */
    protected function importCuenta($data)
    {
        foreach ($data as $xmlAccount) {
            $accountElement = (array) $xmlAccount;
            $this->createAccount($accountElement['codcuenta'], \base64_decode($accountElement['descripcion']), $accountElement['codepigrafe'], $accountElement['idcuentaesp']);
        }
    }

    /**
     * insert Epigrafe of accounting plan
     *
     * @param SimpleXMLElement $data
     */
    protected function importEpigrafe($data)
    {
        foreach ($data as $xmlEpigrafeElement) {
            $epigrafeElement = (array) $xmlEpigrafeElement;
            $this->createAccount($epigrafeElement['codepigrafe'], \base64_decode($epigrafeElement['descripcion']), $epigrafeElement['codgrupo']);
        }
    }

    /**
     * Insert Groups of accounting plan
     *
     * @param SimpleXMLElement $data
     */
    protected function importEpigrafeGroup($data)
    {
        foreach ($data as $xmlEpigrafeGroup) {
            $epigrafeGroupElement = (array) $xmlEpigrafeGroup;
            $this->createAccount($epigrafeGroupElement['codgrupo'], \base64_decode($epigrafeGroupElement['descripcion']));
        }
    }

    /**
     * Import subaccounts of accounting plan
     *
     * @param SimpleXMLElement $data
     */
    protected function importSubcuenta($data)
    {
        foreach ($data as $xmlSubaccountElement) {
            $subaccountElement = (array) $xmlSubaccountElement;
            $this->createSubaccount($subaccountElement['codsubcuenta'], \base64_decode($subaccountElement['descripcion']), $subaccountElement['codcuenta']);
        }
    }

    /**
     * Load accounting plan from CSV File and imports in accounting plan.
     *
     * @param string $filePath
     */
    protected function processCsvData(string $filePath)
    {
        $csv = new Csv();
        $csv->auto($filePath);

        $length = [];
        $accountPlan = [];
        foreach ($csv->data as $value) {
            $key = $value[$csv->titles[0]];
            if (\strlen($key) > 0) {
                $code = $value[$csv->titles[0]];
                $accountPlan[$code] = [
                    'descripcion' => $value[$csv->titles[1]],
                    'codcuentaesp' => $value[$csv->titles[2]]
                ];
                $length[] = \strlen($code);
            }
        }

        $lengths = \array_unique($length);
        \sort($lengths);
        $minLength = \min($lengths);
        $maxLength = \max($lengths);
        $keys = \array_keys($accountPlan);
        \ksort($accountPlan);

        foreach ($accountPlan as $key => $value) {
            switch (\strlen($key)) {
                case $minLength:
                    $this->createAccount($key, $value['descripcion'], '', $value['codcuentaesp']);
                    break;

                case $maxLength:
                    $parentCode = $this->searchParent($keys, $key);
                    $this->createSubaccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;

                default:
                    $parentCode = $this->searchParent($keys, $key);
                    $this->createAccount($key, $value['descripcion'], $parentCode, $value['codcuentaesp']);
                    break;
            }
        }
    }

    /**
     * Search the parent of account in a accounting Plan.
     *
     * @param array  $accountCodes
     * @param string $account
     *
     * @return string
     */
    protected function searchParent(array &$accountCodes, string $account): string
    {
        $parentCode = '';
        foreach ($accountCodes as $code) {
            $strCode = (string) $code;
            if ($strCode === $account) {
                continue;
            } elseif (\strpos($account, $strCode) === 0 && \strlen($strCode) > \strlen($parentCode)) {
                $parentCode = $code;
            }
        }

        return $parentCode;
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
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
