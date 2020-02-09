<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Set of tools for the management of accounting sub-accounts
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class SubAccountTools
{

    /**
     * All tax special codes
     */
    const SPECIAL_GROUP_TAX_ALL = 0;

    /**
     * Only input tax special codes
     */
    const SPECIAL_GROUP_TAX_INPUT = 1;

    /**
     * Only output tax special codes
     */
    const SPECIAL_GROUP_TAX_OUTPUT = 2;

    /**
     * Returns a list of sub-accounts for the exercise and search term informed.
     * 
     * @param array $keys
     *      source: source table name. default 'subcuentas'.
     *      fieldcode: source field name. default 'codsubcuenta'.
     *      fieldtitle: source field title. default 'descripcion'.
     *      term: user key filter.
     *      codejercicio: fiscal year filter.
     *
     * @return array
     */
    public static function autocompleteAction($keys): array
    {
        // prepare working data
        $source = [];
        $source['source'] = $keys['source'] ?? Subcuenta::tableName();
        $source['fieldcode'] = $keys['fieldcode'] ?? 'codsubcuenta';
        $source['fieldtitle'] = $keys['fieldtitle'] ?? 'descripcion';

        $fields = $source['fieldcode'] . '|' . $source['fieldtitle'];
        $where = [
            new DataBaseWhere('codejercicio', $keys['codejercicio']),
            new DataBaseWhere($fields, mb_strtolower($keys['term'], 'UTF8'), 'LIKE')
        ];

        // search for subaccounts data
        $results = [];
        $codeModel = new CodeModel();
        foreach ($codeModel->all($source['source'], $source['fieldcode'], $source['fieldtitle'], false, $where) as $row) {
            $results[] = ['key' => $row->code, 'value' => $row->description];
        }

        // for empty value
        if (empty($results)) {
            $i18n = new Translator();
            $results[] = ['key' => null, 'value' => $i18n->trans('no-data')];
        }

        // return subaccount list
        return $results;
    }

    /**
     * Indicates whether the subaccount has associated taxes.
     * 
     * @param Subcuenta|string $subAccount
     *
     * @return bool
     */
    public function hasTax($subAccount)
    {
        $specialAccount = $subAccount instanceof Subcuenta ? $subAccount->getSpecialAccountCode() : $subAccount;
        return $this->isInputTax($specialAccount) || $this->isOutputTax($specialAccount);
    }

    /**
     * Indicates whether the special account type belongs to the group of input tax accounts.
     *
     * @param string $specialAccount
     *
     * @return bool
     */
    public function isInputTax($specialAccount)
    {
        return in_array($specialAccount, $this->specialAccountsForGroup(self::SPECIAL_GROUP_TAX_INPUT));
    }

    /**
     * Indicates whether the special account type belongs to the group of output tax accounts.
     *
     * @param string $specialAccount
     *
     * @return bool
     */
    public function isOutputTax($specialAccount)
    {
        return in_array($specialAccount, $this->specialAccountsForGroup(self::SPECIAL_GROUP_TAX_OUTPUT));
    }

    /**
     * Get array of specials Tax code accounts for selected group.
     *
     * @param int $group
     *
     * @return array
     */
    public function specialAccountsForGroup(int $group): array
    {
        switch ($group) {
            case self::SPECIAL_GROUP_TAX_ALL:
                return ['IVASEX', 'IVASIM', 'IVASOP', 'IVASUE', 'IVAREX', 'IVAREP', 'IVARUE', 'IVARRE'];

            case self::SPECIAL_GROUP_TAX_INPUT:
                return ['IVASEX', 'IVASIM', 'IVASOP', 'IVASUE'];

            case self::SPECIAL_GROUP_TAX_OUTPUT:
                return ['IVAREX', 'IVAREP', 'IVARUE', 'IVARRE'];
        }

        return [];
    }

    /**
     * Get the where filter with the list of special account codes
     * for the indicated group.
     *
     * @param string $field
     * @param int    $group
     *
     * @return DataBaseWhere
     */
    public function whereForSpecialAccounts(string $field, int $group)
    {
        $specialAccounts = implode(',', $this->specialAccountsForGroup($group));
        return new DataBaseWhere($field, $specialAccounts, 'IN');
    }
}
