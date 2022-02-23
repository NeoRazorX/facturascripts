<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Defines the status and attributes of a purchase or sale document.
 *
 * @author Francesc Pineda Segarra <francesc.pìneda.segarra@gmail.com>
 * @author Carlos García Gómez     <carlos@facturascripts.com>
 */
class EstadoDocumento extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     * Mode in which the stock of purchased or sold products is updated.
     *
     * @var int
     */
    public $actualizastock;

    /**
     * True to prevent modification of this state.
     *
     * @var bool
     */
    public $bloquear;

    /**
     * Indicates whether the document will be editable or not.
     *
     * @var bool
     */
    public $editable;

    /**
     * Name of the document to generate when this state is selected.
     *
     * @var string
     */
    public $generadoc;

    /**
     * Icon of EstadoDocumento.
     *
     * @var string
     */
    public $icon;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idestado;

    /**
     * Name of the state to show the user.
     *
     * @var string
     */
    public $nombre;

    /**
     * Sets this state as default for tipodoc.
     *
     * @var bool
     */
    public $predeterminado;

    /**
     * Document type: customer invoice, supplier order, etc...
     *
     * @var string
     */
    public $tipodoc;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->actualizastock = 0;
        $this->bloquear = false;
        $this->editable = true;
        $this->predeterminado = false;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if ($this->bloquear) {
            $this->toolBox()->i18nLog()->warning('locked');
            return false;
        }

        return parent::delete();
    }

    /**
     * @return string
     */
    public function icon(): string
    {
        if (!empty($this->icon)) {
            return $this->icon;
        } elseif (!empty($this->generadoc)) {
            return 'fas fa-check';
        }

        return $this->editable ? 'fas fa-pen' : 'fas fa-lock';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idestado';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'estados_documentos';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        if (empty($this->nombre) || empty($this->tipodoc)) {
            return false;
        }

        if (!empty($this->generadoc)) {
            $this->editable = false;

            if (in_array($this->tipodoc, ['FacturaCliente', 'FacturaProveedor'])) {
                self::toolBox()::i18nLog()->warning('invoices-cant-generate-new-docs');
                return false;
            }
        }

        return parent::test();
    }

    /**
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if ($this->bloquear && $this->previousData['bloquear']) {
            $this->toolBox()->i18nLog()->warning('locked');
            return false;
        }

        if ($field === 'predeterminado') {
            return $this->onChangePredeterminado();
        }

        return parent::onChange($field);
    }

    /**
     * @return bool
     */
    protected function onChangePredeterminado(): bool
    {
        if ($this->predeterminado) {
            $sql = "UPDATE " . static::tableName() . " SET predeterminado = false"
                . " WHERE predeterminado = true"
                . " AND tipodoc = " . self::$dataBase->var2str($this->tipodoc)
                . " AND idestado != " . self::$dataBase->var2str($this->idestado) . ";";
            return self::$dataBase->exec($sql);
        }

        // set other status as default
        $where = [
            new DataBaseWhere('editable', true),
            new DataBaseWhere('tipodoc', $this->tipodoc)
        ];
        foreach ($this->all($where) as $item) {
            $sql = "UPDATE " . static::tableName() . " SET predeterminado = true"
                . " WHERE idestado = " . self::$dataBase->var2str($item->idestado) . ";";
            return self::$dataBase->exec($sql);
        }

        return false;
    }

    protected function onDelete()
    {
        if ($this->predeterminado) {
            $where = [
                new DataBaseWhere('editable', true),
                new DataBaseWhere('tipodoc', $this->tipodoc)
            ];
            foreach ($this->all($where) as $item) {
                $sql = "UPDATE " . static::tableName() . " SET predeterminado = true"
                    . " WHERE idestado = " . self::$dataBase->var2str($item->idestado) . ";";
                self::$dataBase->exec($sql);
                break;
            }
        }
    }

    protected function onInsert()
    {
        if ($this->predeterminado) {
            $sql = "UPDATE " . static::tableName() . " SET predeterminado = false"
                . " WHERE predeterminado = true"
                . " AND tipodoc = " . self::$dataBase->var2str($this->tipodoc)
                . " AND idestado != " . self::$dataBase->var2str($this->idestado) . ";";
            self::$dataBase->exec($sql);
        }
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->idestado)) {
            /**
             * postgresql does not correctly update the serial when inserting the values from a csv.
             * So we use this to get the new id manually.
             */
            $this->idestado = $this->newCode();
        }

        return parent::saveInsert($values);
    }

    /**
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['actualizastock', 'bloquear', 'editable', 'generadoc', 'predeterminado', 'tipodoc'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
