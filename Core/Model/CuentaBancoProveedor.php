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

/**
 * A bank account of a provider.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBancoProveedor
{

    use Base\ModelTrait {
        save as private traitSave;
    }

    use Base\BankAccount;

    /**
     * Primary key. Varchar(6).
     *
     * @var int
     */
    public $codcuenta;

    /**
     * Supplier code.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * True if it is the main account, but False.
     *
     * @var bool
     */
    public $principal;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentasbcopro';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcuenta';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->principal = true;
        $this->clearBankAccount();
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                $allOK = $this->saveUpdate();
            } else {
                $this->codcuenta = $this->newCode();
                $allOK = $this->saveInsert();
            }

            if ($allOK) {
                /// If this account is the main one, we demarcate the others
                $sql = 'UPDATE ' . static::tableName()
                    . ' SET principal = false'
                    . ' WHERE codproveedor = ' . self::$dataBase->var2str($this->codproveedor)
                    . ' AND codcuenta <> ' . self::$dataBase->var2str($this->codcuenta) . ';';
                $allOK = self::$dataBase->exec($sql);
            }

            return $allOK;
        }

        return false;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return boolean
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);
        if (!$this->testBankAccount()) {
            self::$miniLog->alert(self::$i18n->trans('error-incorrect-bank-details'));

            return false;
        }

        return true;
    }
}
