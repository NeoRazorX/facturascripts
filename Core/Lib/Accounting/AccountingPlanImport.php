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

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\DataBase;

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
     * @var Ejercicio
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

        $this->ejercicio = new Model\Ejercicio();
        $this->ejercicio->codejercicio = $codejercicio;

        $data = $this->getData($filePath);

        if ($data->count() > 0)
        {

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
     *
     */
    private function getData($filePath)
    {
        if (file_exists($filePath))
        {
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
        $epigrafe_group = new model\Cuenta();
        $epigrafe_group_element = [];

        foreach ($data as $xml_epigrafe_group) {
            $epigrafe_group_element = (array) $xml_epigrafe_group;
            $where = [new Database\DataBaseWhere('codejercicio',
                    $this->ejercicio->codejercicio),
                new Database\DataBaseWhere('codcuenta',
                    $epigrafe_group_element['codgrupo'])
            ];

            if (empty($epigrafe_group->all($where)))
            {
                $epigrafe_group = new Model\Cuenta();

                $epigrafe_group->codejercicio = $this->ejercicio->codejercicio;
                $epigrafe_group->codcuenta = $epigrafe_group_element['codgrupo'];
                $epigrafe_group->descripcion = \base64_decode($epigrafe_group_element['descripcion']);
                $epigrafe_group->save();
            }
        }
    }

    /**
     * insert Epigrafe of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importEpigrafe($data)
    {
        $epigrafe = new Model\Cuenta();
        $epigrafe_element = [];

        foreach ($data as $xml_epigrafe_element) {
            $epigrafe_element = (array) $xml_epigrafe_element;
            $where = [new Database\DataBaseWhere('codejercicio',
                    $this->ejercicio->codejercicio),
                new Database\DataBaseWhere('codcuenta',
                    $epigrafe_element['codepigrafe'])
            ];

            if (empty($epigrafe->all($where)))
            {
                $wherepadre = [new Database\DatabaseWhere('codejercicio',
                        $this->ejercicio->codejercicio),
                    new DataBase\DataBaseWhere('codcuenta',
                        $epigrafe_element['codgrupo'])
                ];
                $epigrafe_group = new Model\Cuenta();
                $epigrafe_group->loadfromCode(NULL, $wherepadre);

                if (empty($epigrafe_group))
                {
                    self::$miniLog->alert(self::$i18n->trans('epigrafe-group-error'));
                }
                else
                {
                    $epigrafe = new Model\Cuenta();

                    $epigrafe->codejercicio = $this->ejercicio->codejercicio;
                    $epigrafe->parent_codcuenta = $epigrafe_group->codcuenta;
                    $epigrafe->parent_idcuenta = $epigrafe_group->idcuenta;
                    $epigrafe->codcuenta = $epigrafe_element['codepigrafe'];
                    $epigrafe->descripcion = base64_decode($epigrafe_element['descripcion']);

                    $epigrafe->save();
                }
            }
        }
    }

    /**
     * insert Cuenta of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importCuenta($data)
    {
        $account = new Model\Cuenta();
        $account_element = [];

        $epigrafe = new Model\Cuenta();
        foreach ($data as $xml_account) {
            $account_element = (array) $xml_account;
            $where = [new Database\DataBaseWhere('codejercicio',
                    $this->ejercicio->codejercicio),
                new Database\DataBaseWhere('codcuenta',
                    $account_element['codcuenta'])
            ];
            if (empty($account->all($where)))
            {
                $wherepadre = [new Database\DataBaseWhere('codejercicio',
                        $this->ejercicio->codejercicio),
                    new DataBase\DataBaseWhere('codcuenta',
                        $account_element['codepigrafe'])
                ];
                $epigrafe->loadFromCode(NULL, $wherepadre);
                if (empty($epigrafe))
                {
                    self::$miniLog->alert(self::$i18n->trans('epigrafe-error'));
                }
                else
                {
                    $account = new Model\Cuenta();
                    $account->codejercicio = $this->ejercicio->codejercicio;
                    $account->parent_idcuenta = $epigrafe->idcuenta;
                    $account->parent_codcuenta = $epigrafe->codcuenta;

                    $account->codcuenta = $account_element['codcuenta'];
                    $account->descripcion = base64_decode($account_element['descripcion']);


                    $account->save();
                }
            }
        }
    }

    /**
     * Import subaccounts of accounting plan
     *
     * @param \SimpleXMLElement $data
     */
    private function importSubcuenta($data)
    {
        $subaccount = new Model\Subcuenta();
        $subaccount_element = [];


        foreach ($data as $xml_subaccount_element) {
            $subaccount_element = (array) $xml_subaccount_element;
            $where = [new Database\DataBaseWhere('codejercicio',
                    $this->ejercicio->codejercicio),
                new Database\DataBaseWhere('codsubcuenta',
                    $subaccount_element['codsubcuenta'])
            ];

            if (empty($subaccount->all($where)))
            {
                $account = new Model\Cuenta();
                $where_account = [new DataBase\DataBaseWhere('codejercicio',
                        $this->ejercicio->codejercicio),
                    new DataBase\DataBaseWhere('codcuenta',
                        $subaccount_element['codcuenta'])
                ];
                $account->loadFromCode(NULL, $where_account);
                if (empty($account))
                {
                    self::$miniLog->alert(self::$i18n->trans('account-error'));
                }
                else
                {
                    $subaccount = new Model\Subcuenta();
                    $subaccount->codejercicio = $this->ejercicio->codejercicio;
                    $subaccount->idcuenta = $account->idcuenta;
                    $subaccount->codcuenta = $subaccount_element['codcuenta'];
                    $subaccount->codsubcuenta = $subaccount_element['codsubcuenta'];
                    $subaccount->descripcion = base64_decode($subaccount_element['descripcion']);
                    $subaccount->coddivisa = $subaccount_element['coddivisa'];
                    $subaccount->save();
                }
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