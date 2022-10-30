<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

    /** @var int */
    public $actualizastock;

    /** @var bool */
    public $bloquear;

    /** @var string */
    public $color;

    /** @var bool */
    public $editable;

    /** @var string */
    public $generadoc;

    /** @var string */
    public $icon;

    /** @var int */
    public $idestado;

    /** @var string */
    public $nombre;

    /** @var bool */
    public $predeterminado;

    /** @var string */
    public $tipodoc;

    public function clear()
    {
        parent::clear();
        $this->actualizastock = 0;
        $this->bloquear = false;
        $this->editable = true;
        $this->predeterminado = false;
    }

    public function delete(): bool
    {
        if ($this->bloquear) {
            $this->toolBox()->i18nLog()->warning('locked');
            return false;
        }

        return parent::delete();
    }

    public function icon(): string
    {
        if (!empty($this->icon)) {
            return $this->icon;
        } elseif (!empty($this->generadoc)) {
            return 'fas fa-check';
        }

        return $this->editable ? 'fas fa-pen' : 'fas fa-lock';
    }

    public static function primaryColumn(): string
    {
        return 'idestado';
    }

    public static function tableName(): string
    {
        return 'estados_documentos';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->color = self::toolBox()::utils()::noHtml($this->color);
        $this->generadoc = self::toolBox()::utils()::noHtml($this->generadoc);
        $this->icon = self::toolBox()::utils()::noHtml($this->icon);
        $this->nombre = self::toolBox()::utils()::noHtml($this->nombre);
        $this->tipodoc = self::toolBox()::utils()::noHtml($this->tipodoc);

        // Comprobamos que el nombre no esté vacío
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

    protected function saveInsert(array $values = []): bool
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

    protected function setPreviousData(array $fields = [])
    {
        $more = ['actualizastock', 'bloquear', 'editable', 'generadoc', 'predeterminado', 'tipodoc'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
