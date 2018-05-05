<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Dinamic\Model;
use ParseCsv\Csv;

/**
 * Description of AccountingPlanImport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Raul Jimenez <comercial@nazcanetworks.com>
 */
class AccountingPlanImport
{

    /**
     * Exercise related to the accounting plan.
     *
     * @var Model\Ejercicio
     */
    private $ejercicio;

    /**
     * System translator.
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Manage the log of the entire application.
     *
     * @var MiniLog
     */
    private $miniLog;

    public function __construct()
    {
        $this->ejercicio = new Model\Ejercicio();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
    }

    /**
     * Import data from CSV file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importCSV(string $filePath, string $codejercicio)
    {
        if (!$this->ejercicio->loadFromCode($codejercicio)) {
            $this->miniLog->error($this->i18n->trans('error'));
            return;
        }

        $this->processCsvData($filePath);
    }

    /**
     * Import data from XML file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importXML(string $filePath, string $codejercicio)
    {
        if (!$this->ejercicio->loadFromCode($codejercicio)) {
            $this->miniLog->error($this->i18n->trans('error'));
            return;
        }

        $data = $this->getData($filePath);
        if ($data->count() > 0) {
            $this->importEpigrafeGroup($data->grupo_epigrafes);
            $this->importEpigrafe($data->epigrafe);
            $this->importCuenta($data->cuenta);
            $this->importSubcuenta($data->subcuenta);
        }
    }

    /**
     * Insert/update and account in accounting plan.
     * 
     * @param string $code
     * @param string $definition
     * @param string $parentCode
     */
    private function createAccount(string $code, string $definition, string $parentCode = '')
    {
        $account = new Model\Cuenta();
        $parent = new Model\Cuenta();

        /// the account exists?
        $where = [
            new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
            new DataBaseWhere('codcuenta', $code)
        ];
        $account->loadFromCode('', $where);

        if (!empty($parentCode)) {
            $whereParent = [
                new DatabaseWhere('codejercicio', $this->ejercicio->codejercicio),
                new DataBaseWhere('codcuenta', $parentCode)
            ];
            if ($parent->loadFromCode('', $whereParent)) {
                $account->parent_codcuenta = $parent->codcuenta;
                $account->parent_idcuenta = $parent->idcuenta;
            } else {
                $this->miniLog->alert($this->i18n->trans('parent-error'));
            }
        }

        $account->codejercicio = $this->ejercicio->codejercicio;
        $account->codcuenta = $code;
        $account->descripcion = $definition;
        $account->save();
    }

    /**
     * Insert or update an account in accounting Plan.
     *
     * @param string $code
     * @param string $description
     * @param string $parentCode
     */
    private function createSubaccount(string $code, string $description, string $parentCode)
    {
        $subaccount = new Model\Subcuenta();
        $account = new Model\Cuenta();
        $whereAccount = [
            new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
            new DataBaseWhere('codcuenta', $parentCode)
        ];

        /// the account exist?
        if (!$account->loadFromCode('', $whereAccount)) {
            $this->miniLog->error($this->i18n->trans('error'));
            return;
        }

        /// the subaccount exists?
        $where = [
            new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
            new DataBaseWhere('codsubcuenta', $code)
        ];
        $subaccount->loadFromCode('', $where);

        $subaccount->codejercicio = $this->ejercicio->codejercicio;
        $subaccount->idcuenta = $account->idcuenta;
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codsubcuenta = $code;
        $subaccount->descripcion = $description;
        $subaccount->save();
    }

    /**
     * returns an array width the content of xml file
     *
     * @param string $filePath
     *
     * @return \SimpleXMLElement|array
     */
    private function getData(string $filePath)
    {
        if (file_exists($filePath)) {
            return simplexml_load_string(file_get_contents($filePath));
        }

        return [];
    }

    /**
     * Insert Groups of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importEpigrafeGroup($data)
    {
        foreach ($data as $xmlEpigrafeGroup) {
            $epigrafeGroupElement = (array) $xmlEpigrafeGroup;
            $this->createAccount($epigrafeGroupElement['codgrupo'], base64_decode($epigrafeGroupElement['descripcion']));
        }
    }

    /**
     * insert Epigrafe of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importEpigrafe($data)
    {
        foreach ($data as $xmlEpigrafeElement) {
            $epigrafeElement = (array) $xmlEpigrafeElement;
            $this->createAccount($epigrafeElement['codepigrafe'], base64_decode($epigrafeElement['descripcion']), $epigrafeElement['codgrupo']);
        }
    }

    /**
     * insert Cuenta of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importCuenta($data)
    {
        foreach ($data as $xmlAccount) {
            $accountElement = (array) $xmlAccount;
            $this->createSubaccount($accountElement['codcuenta'], base64_decode($accountElement['descripcion']), $accountElement['codepigrafe']);
        }
    }

    /**
     * Import subaccounts of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importSubcuenta($data)
    {
        foreach ($data as $xmlSubaccountElement) {
            $subaccountElement = (array) $xmlSubaccountElement;
            $this->createSubaccount($subaccountElement['codsubcuenta'], base64_decode($subaccountElement['descripcion']), $subaccountElement['codcuenta']);
        }
    }

    /**
     * Load accounting plan from CSV File and imports in accounting plan.
     *
     * @param string $filePath
     */
    private function processCsvData(string $filePath)
    {
        if (!file_exists($filePath)) {
            $this->miniLog->error($this->i18n->trans('error'));
        }

        $csv = new Csv();
        $csv->auto($filePath);
        $accountPlan = [];
        $length = [];
        foreach ($csv->data as $value) {
            $key = $value[$csv->titles[0]];
            if (strlen($key) > 0) {
                $length[] = strlen($key);
                $accountPlan[$key] = utf8_encode($value[$csv->titles[1]]);
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
                    $this->createAccount($key, $value);
                    break;

                case $maxLength:
                    $parentCode = $this->searchParent($keys, $key);
                    $this->createSubaccount($key, $value, $parentCode);
                    break;

                default:
                    $parentCode = $this->searchParent($keys, $key);
                    $this->createAccount($key, $value, $parentCode);
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
    private function searchParent(array &$accountCodes, string $account): string
    {
        $parentCode = '';
        foreach ($accountCodes as $code) {
            $strCode = (string) $code;
            if ($strCode === $account) {
                continue;
            } elseif (strpos($account, $strCode) === 0 && strlen($strCode) > strlen($parentCode)) {
                $parentCode = $code;
            }
        }

        return $parentCode;
    }
}
