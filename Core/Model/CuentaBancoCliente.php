<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Una cuenta bancaria de un cliente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaBancoCliente
{

    use Base\ModelTrait {
        save as private saveTrait;
    }

    use Base\BankAccount;

    /**
     * Clave primaria. Varchar(6).
     *
     * @var int
     */
    public $codcuenta;

    /**
     * Código del cliente.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Identificación descriptiva para humanos
     *
     * @var string
     */
    public $descripcion;

    /**
     * ¿Es la cuenta principal del cliente?
     *
     * @var boolean
     */
    public $principal;

    /**
     * Fecha en la que se firmó el mandato para autorizar la domiciliación de recibos.
     *
     * @var string
     */
    public $fmandato;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentasbcocli';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcuenta';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codcuenta = null;
        $this->codcliente = null;
        $this->descripcion = null;
        $this->principal = true;
        $this->fmandato = null;
        $this->clearBankAccount();
    }

    /**
     * Almacena los datos del modelo en la base de datos.
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
                /// si esta cuenta es la principal, desmarcamos las demás
                $sql = 'UPDATE ' . $this->tableName()
                    . ' SET principal = false'
                    . ' WHERE codcliente = ' . $this->var2str($this->codcliente)
                    . ' AND codcuenta <> ' . $this->var2str($this->codcuenta) . ';';
                $allOK = $this->dataBase->exec($sql);
            }

            return $allOK;
        }

        return false;
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return boolean
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);
        if (!$this->testBankAccount()) {
            $this->miniLog->alert($this->i18n->trans('error-incorrect-bank-details'));

            return false;
        }

        return true;
    }
}
