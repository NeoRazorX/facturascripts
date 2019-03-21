<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;

/**
 * The head of transfer.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class TransferenciaStock extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Warehouse of destination. Varchar (4).
     *
     * @var string
     */
    public $codalmacendestino;

    /**
     * Warehouse of origin. Varchar (4).
     *
     * @var string
     */
    public $codalmacenorigen;

    /**
     * Primary key autoincremental.
     *
     * @var int
     */
    public $idtrans;

    /**
     * Date of transfer.
     *
     * @var string
     */
    public $fecha;

    /**
     * User of transfer action. Varchar (50).
     *
     * @var string
     */
    public $nick;

    /**
     *
     * @var string
     */
    public $observaciones;

    public function clear()
    {
        parent::clear();
        $this->fecha = date('d-m-Y H:i:s');
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        /// remove lines to force update stock
        foreach ($this->getLines() as $line) {
            $line->delete();
        }

        return parent::delete();
    }

    /**
     * 
     * @return LineaTransferenciaStock[]
     */
    public function getLines()
    {
        $line = new LineaTransferenciaStock();
        $where = [new DataBaseWhere('idtrans', $this->primaryColumnValue())];
        return $line->all($where, [], 0, 0);
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idtrans';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'transferenciasstock';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->observaciones = Utils::noHtml($this->observaciones);

        if ($this->codalmacenorigen == $this->codalmacendestino) {
            self::$miniLog->alert(self::$i18n->trans('warehouse-cant-be-same'));
            return false;
        }

        if ($this->getIdempresa($this->codalmacendestino) !== $this->getIdempresa($this->codalmacenorigen)) {
            self::$miniLog->alert(self::$i18n->trans('warehouse-must-be-same-business'));
            return false;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     * 
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return parent::url($type, 'ListAlmacen?activetab=List');
    }

    /**
     * 
     * @param string $codalmacen
     *
     * @return int
     */
    protected function getIdempresa($codalmacen)
    {
        $warehouse = new Almacen;
        $warehouse->loadFromCode($codalmacen);
        return $warehouse->idempresa;
    }
}
