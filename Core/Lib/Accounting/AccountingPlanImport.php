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
    private static $i18n;

    /**
     * Manage the log of the entire application.
     *
     * @var MiniLog
     */
    private static $miniLog;

    public function __construct()
    {
        if (!isset(self::$miniLog)) {
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Import data from XML file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importXML($filePath, $codejercicio)
    {


        $this->ejercicio = new Model\Ejercicio();
        $this->ejercicio->loadFromCode($codejercicio);

        $data = $this->getData($filePath);
        if ($data->count() > 0) {
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
     *
     * @return \SimpleXMLElement|array
     */
    private function getData($filePath)
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
            $this->createAccount($epigrafeGroupElement['codgrupo'], \base64_decode($epigrafeGroupElement['descripcion']), null);
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
     * Import data from CSV file.
     *
     * @param string $filePath
     * @param string $codejercicio
     */
    public function importCSV($filePath, $codejercicio)
    {
        $this->ejercicio = new Model\Ejercicio();
        $this->ejercicio->loadFromCode($codejercicio);
        $this->getCsvData($filePath);
    }

    /**
     * Load accounting plan from CSV File and imports in accounting plan
     * @param file $filePath
     * @param integer $exerciseCode
     */
    private function getCsvData($filePath)
    {
        if (file_exists($filePath)) {
            $csv = new Csv();
            $csv->auto($filePath);
            $accountPlan=[];
            $length=[];
            foreach ($csv->data as $key => $value) {
                $length[] = strlen($value[$csv->titles[0]]);
                $accountPlan[$value[$csv->titles[0]]] = utf8_encode($value[$csv->titles[1]]);
            }
          
            $length = array_unique($length);
            sort($length);
            $minLength = min($length);
            $maxLength = max($length);
            $length = array_reverse($length);
            $keys = array_keys($accountPlan);
            foreach ($accountPlan as $key => $value) {
                switch (strlen($key)) {
                    case $minLength:
                        $this->createAccount($key, $value, null);
                        break;
                    case $maxLength:
                        $parentCode = $this->searchParent($keys, $length, $key);

                        $this->createSubaccount($key, $value, $parentCode);
                        break;
                    default:
                        $parentCode = $this->searchParent($keys, $length, $key);

                        $this->createAccount($key, $value, $parentCode);
                        break;
                }
            }
        }
    }

    /**
     * Search the parent of account in a accounting Plan
     * 
     * @param array $accountCodes
     * @param array $length
     * @param string $account
     * @return string
     */
    private function searchParent($accountCodes, $length, $account)
    {
        $parentCode = null;
        $parents = array();
        $accountLength=strlen($account);
        for ($i = 0; $i < count($length); $i++) {
            if ($length[$i] < $accountLength) {
                $pattern = '/^' . substr($account, 0, $length[$i]) . '{0,' . ($length[$i]) . '}$/';
                $parents = array_merge($parents, preg_grep($pattern, $accountCodes));
                if (count($parents) > 0) {
                    return $parents[0];
                }
            }
        }
        return $parentCode;
    }

    /**
     * 
     * Insert/update and account in accounting plan
     * @param string $code
     * @param string $definition
     * @param string $parentCode
     */
    private function createAccount($code, $definition, $parentCode)
    {
        $account = new Model\Cuenta();
        $parent = new Model\Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
            new DataBaseWhere('codcuenta', $code)
        ];
        $account->loadFromCode('', $where);
        if ($parentCode !== null) {
            $whereParent = [
                new DatabaseWhere('codejercicio', $this->ejercicio->codejercicio),
                new DataBaseWhere('codcuenta', $parentCode)
            ];
            $parent->loadfromCode('', $whereParent);
            if (empty($parent)) {
                self::$miniLog->alert(self::$i18n->trans('parent-error'));
            } else {
                $account->parent_codcuenta = $parent->codcuenta;
                $account->parent_idcuenta = $parent->idcuenta;     
            }
        }
        $account->codejercicio = $this->ejercicio->codejercicio;
        $account->codcuenta = $code;
        $account->descripcion = $definition;
        $account->save();
    }
    /**
     * 
     * Insert or update an account in accounting Plan
     * @param string $code
     * @param string $description
     * @param string $parentCode
     */
    private function createSubaccount($code, $description, $parentCode)
    {
        $subaccount = new Model\Subcuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
            new DataBaseWhere('codsubcuenta', $code)
        ];
        if (empty($subaccount->all($where))) {
            $whereAccount = [
                new DataBaseWhere('codejercicio', $this->ejercicio->codejercicio),
                new DataBaseWhere('codcuenta', $parentCode)
            ];
            $account = new Model\Cuenta();
            $account->loadFromCode('', $whereAccount);
            if (empty($account)) {
                self::$miniLog->alert(self::$i18n->trans('account-error'));
            } else {
                $subaccount = new Model\Subcuenta();
                $subaccount->codejercicio = $this->ejercicio->codejercicio;
                $subaccount->idcuenta = $account->idcuenta;
                $subaccount->codcuenta = $account->codcuenta;
                $subaccount->codsubcuenta = $code;
                $subaccount->descripcion = $description;
                $subaccount->save();
            }
        }
    }
}
