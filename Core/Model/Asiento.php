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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Asiento extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Seat number. It will be modified when renumbering.
     *
     * @var string
     */
    public $numero;

    /**
     * Identificacion de la empresa
     *
     * @var int
     */
    public $idempresa;

    /**
     * Identifier of the concept.
     *
     * @var int
     */
    public $idconcepto;

    /**
     * Seat concept.
     *
     * @var string
     */
    public $concepto;

    /**
     * Date of the seat.
     *
     * @var string
     */
    public $fecha;

    /**
     * Exercise code of the seat.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Seat plan code.
     *
     * @var string
     */
    public $codplanasiento;

    /**
     * True if it is editable, but false.
     *
     * @var bool
     */
    public $editable;

    /**
     * Document associated with the seat.
     *
     * @var string
     */
    public $documento;

    /**
     * Text that identifies the type of document
           * 'Customer invoice' or 'Vendor invoice'.
     *
     * @var string
     */
    public $tipodocumento;

    /**
     * Amount of the seat.
     *
     * @var float|int
     */
    public $importe;

    /**
     * Seat currency code.
     *
     * @var string
     */
    private $coddivisa;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_asientos';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idasiento';
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
        new Ejercicio();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fecha = date('d-m-Y');
        $this->editable = true;
        $this->importe = 0.0;
    }

    /**
     * Returns the invoice associated with the seat.
     *
     * @return bool|FacturaCliente|FacturaProveedor
     */
    public function getFactura()
    {
        if ($this->tipodocumento === 'Factura de cliente') {
            $fac = new FacturaCliente();

            return $fac->loadFromCode(null, [new DataBaseWhere('codigo', $this->documento)]);
        }
        if ($this->tipodocumento === 'Factura de proveedor') {
            $fac = new FacturaProveedor();

            return $fac->loadFromCode(null, [new DataBaseWhere('codigo', $this->documento)]);
        }

        return false;
    }

    /**
     * Returns the code of the currency.
           * What happens is that this data is stored in the games, that's why
           * you have to use this function.
     *
     * @return string|null
     */
    public function codDivisa()
    {
        if ($this->coddivisa === null) {
            $this->coddivisa = AppSettings::get('default', 'coddivisa');

            foreach ($this->getPartidas() as $par) {
                if ($par->coddivisa) {
                    $this->coddivisa = $par->coddivisa;
                    break;
                }
            }
        }

        return $this->coddivisa;
    }

    /**
     * Returns all the items of the seat.
     *
     * @return Partida[]
     */
    public function getPartidas()
    {
        $partida = new Partida();

        return $partida->all([new DataBaseWhere('idasiento', $this->idasiento)]);
    }

    /**
     * We assign a number to the seat.
     */
    public function newNumero()
    {
        $this->numero = '1';
        $sql = 'SELECT MAX(' . self::$dataBase->sql2Int('numero') . ') as num FROM ' . static::tableName()
            . ' WHERE codejercicio = ' . self::$dataBase->var2str($this->codejercicio) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            $this->numero = (string) (1 + (int) $data[0]['num']);
        }
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = Utils::noHtml($this->concepto);
        $this->documento = Utils::noHtml($this->documento);

        if (strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('concept-seat-too-large'));

            return false;
        }

        return true;
    }

    /**
     * Remove the database entry.
     *
     * @return bool
     */
    public function delete()
    {
        $bloquear = false;

        $eje0 = new Ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($this->idasiento === $ejercicio->idasientoapertura) {
                /// we allow to eliminate the opening seat
            } elseif ($this->idasiento === $ejercicio->idasientocierre) {
                /// we allow to eliminate the closing seat
            } elseif ($this->idasiento === $ejercicio->idasientopyg) {
                /// we allow to eliminate the profit and loss statement
            } elseif ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    self::$miniLog->alert(self::$i18n->trans('seat-within-regularization', ['%tax%' => FS_IVA]));
                    $bloquear = true;
                }
            } else {
                self::$miniLog->alert(self::$i18n->trans('closed-exercise', ['%exerciseName%' => $ejercicio->nombre]));
                $bloquear = true;
            }
        }

        if ($bloquear) {
            return false;
        }

        /// we unlink the invoice
        $fac = $this->getFactura();
        if ($fac) {
            if ($fac->idasiento === $this->idasiento) {
                $fac->idasiento = null;
                $fac->save();
            }
        }

        /// we eliminate the items one by one to force the updating of the associated subaccounts
        foreach ($this->getPartidas() as $p) {
            $p->delete();
        }

        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE idasiento = ' . self::$dataBase->var2str($this->idasiento) . ';';

        return self::$dataBase->exec($sql);
    }

    /**
     * Returns a list of unbalanced entries.
     *
     * @return array
     */
    public function descuadrados()
    {
        /// we started games to make sure that the table exists
        new Partida();

        $alist = [];
        $sql = 'SELECT p.idasiento,SUM(p.debe) AS sdebe,SUM(p.haber) AS shaber
         FROM co_partidas p, ' . static::tableName() . ' a
          WHERE p.idasiento = a.idasiento
           GROUP BY p.idasiento
            HAVING ABS(SUM(p.haber) - SUM(p.debe)) > 0.01
             ORDER BY p.idasiento DESC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alist[] = $this->get($a['idasiento']);
            }
        }

        return $alist;
    }

    /**
     * Re-number the seats of the open exercises
     *
     * @return bool
     */
    public function renumber()
    {
        $continuar = false;
        $ejercicio = new Ejercicio();
        foreach ($ejercicio->all([new DataBaseWhere('estado', 'ABIERTO')]) as $eje) {
            $posicion = 0;
            $numero = 1;
            $sql = '';
            $continuar = true;
            $consulta = 'SELECT idasiento,numero,fecha FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($eje->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
            while (!empty($asientos) && $continuar) {
                foreach ($asientos as $col) {
                    if ($col['numero'] !== $numero) {
                        $sql .= 'UPDATE ' . static::tableName() . ' SET numero = ' . self::$dataBase->var2str($numero)
                            . ' WHERE idasiento = ' . self::$dataBase->var2str($col['idasiento']) . ';';
                    }

                    ++$numero;
                }
                $posicion += 1000;

                if ($sql !== '') {
                    if (!self::$dataBase->exec($sql)) {
                        self::$miniLog->alert(self::$i18n->trans('renumber-accounting-error', ['%exerciseCode%' => $eje->codejercicio]));
                        $continuar = false;
                    }
                    $sql = '';
                }

                $asientos = self::$dataBase->selectLimit($consulta, 1000, $posicion);
            }
        }

        return $continuar;
    }

    /**
     * Execute a task with cron
     */
    public function cronJob()
    {
        /**
         * We block closed exercise seats or within regularizations.
         */
        $eje0 = new Ejercicio();
        $regiva0 = new RegularizacionIva();
        foreach ($eje0->all() as $ej) {
            if ($ej instanceof Ejercicio && $ej->abierto()) {
                foreach ($regiva0->all([new DataBaseWhere('codejercicio', $ej->codejercicio)]) as $reg) {
                    $sql = 'UPDATE ' . static::tableName() . ' SET editable = false WHERE editable = true'
                        . ' AND codejercicio = ' . self::$dataBase->var2str($ej->codejercicio)
                        . ' AND fecha >= ' . self::$dataBase->var2str($reg->fechainicio)
                        . ' AND fecha <= ' . self::$dataBase->var2str($reg->fechafin) . ';';
                    self::$dataBase->exec($sql);
                }
            } else {
                $sql = 'UPDATE ' . static::tableName() . ' SET editable = false WHERE editable = true'
                    . ' AND codejercicio = ' . self::$dataBase->var2str($ej->codejercicio) . ';';
                self::$dataBase->exec($sql);
            }
        }

        echo self::$i18n->trans('renumber-accounting');
        $this->renumber();
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert($values = [])
    {
        $this->newNumero();

        return parent::saveInsert();
    }
}
