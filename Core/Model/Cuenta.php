<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * Element of the third level of the accounting plan.
 * It is related to a single fiscal year and epigraph,
 * but it can be related to many subaccounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Cuenta extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Code of the exercise of this account.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Identifier of the parent account
     *
     * @var integer
     */
    public $parent_idcuenta;

    /**
     * Parent account code
     *
     * @var string
     */
    public $parent_codcuenta;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_cuentas';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcuenta';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// force the parents tables
        new Ejercicio();
        new CuentaEspecial();

        return '';
    }

    /**
     * Returns all sub-accounts in the account.
     *
     * @return Subcuenta[]
     */
    public function getSubAccounts()
    {
        $subcuenta = new Subcuenta();
        return $subcuenta->all([new DataBaseWhere('idcuenta', $this->idcuenta)]);
    }

    /**
     * Returns the first account that meets the indicated condition.
     *
     * @param DataBaseWhere[] $where
     * @param array $orderby
     *
     * @return null|Cuenta
     */
    public function getAccountWithCondition($where, $orderby = [])
    {
        if (empty($orderby)) {
            $orderby = ['codejercicio' => 'DESC', 'codcuenta' => 'ASC'];
        }
        $result = $this->all($where, $orderby, 0, 1);
        if (empty($result)) {
            return null;
        }
        return $result[0];
    }

    /**
     * Gets the first selected special account.
     *
     * @param string $idcuentaesp
     * @param string $codejercicio
     *
     * @return null|Cuenta
     */
    public function getSpecialAccount($idcuentaesp, $codejercicio)
    {
        $where = [
            new DataBaseWhere('idcuentaesp', $idcuentaesp),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        return $this->getAccountWithCondition($where);
    }

    /**
     * Check and load the id of the parent account
     *
     * @return bool
     */
    private function testParentAccount()
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('parent_codcuenta', $this->parent_codcuenta)
        ];

        $account = $this->getAccountWithCondition($where);
        if (isset($account)) {
            $this->parent_idcuenta = $account->parent_idcuenta;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = Utils::noHtml($this->descripcion);

        if (strlen($this->codcuenta) < 1 || strlen($this->descripcion) < 1) {
            self::$miniLog->alert(self::$i18n->trans('account-data-missing'));
            return false;
        }

        $this->parent_idcuenta = null;
        if (!empty($this->parent_codcuenta) && !$this->testParentAccount()) {
            self::$miniLog->alert(self::$i18n->trans('account-parent-error'));
            return false;
        }

        return true;
    }
}
