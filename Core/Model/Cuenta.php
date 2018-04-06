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
        return 'cuentas';
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

        return '';
    }

    /**
     * Check and load the id of the parent account
     *
     * @return bool
     */
    private function testErrorInParentAccount(): bool
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->parent_codcuenta)
        ];

        $account = $this->all($where, ['codcuenta' => 'ASC'], 0, 1);
        if (empty($account)) {
            return true;
        }

        $this->parent_idcuenta = $account[0]->parent_idcuenta;
        return false;
    }

    /**
     * TODO: Uncomplete documentation
     *
     * @return bool
     */
    private function testErrorInAccount(): bool
    {
        return empty($this->codcuenta) || empty($this->descripcion) || empty($this->codejercicio);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->descripcion = Utils::noHtml($this->descripcion);

        if ($this->testErrorInAccount())  {
            self::$miniLog->alert(self::$i18n->trans('account-data-missing'));
            return false;
        }

        /// Check and load correct id parent account
        $this->parent_idcuenta = null;
        if (!empty($this->parent_codcuenta) && $this->testErrorInParentAccount()) {
            self::$miniLog->alert(self::$i18n->trans('account-parent-error'));
            return false;
        }

        return true;
    }
}
