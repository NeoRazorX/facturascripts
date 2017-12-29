<?php
/**
 * This file is part of FacturaScripts
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

use FacturaScripts\Core\App\AppSettings;

/**
 * The accounting entry. It is related to an exercise and consists of games.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Asiento
{

    use Base\ModelTrait {
        saveInsert as private saveInsertTrait;
    }

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
     *Identificacion de la empresa
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
    public function primaryColumn()
    {
        return 'idasiento';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->idasiento = null;
        $this->numero = null;
        $this->idconcepto = null;
        $this->concepto = null;
        $this->fecha = date('d-m-Y');
        $this->codejercicio = null;
        $this->codplanasiento = null;
        $this->editable = true;
        $this->documento = null;
        $this->tipodocumento = null;
        $this->importe = 0;
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

            return $fac->getByCodigo($this->documento);
        }
        if ($this->tipodocumento === 'Factura de proveedor') {
            $fac = new FacturaProveedor();

            return $fac->getByCodigo($this->documento);
        }

        return false;
    }

    /**
     * Returns the url of the invoice associated with the seat.
     *
     * @return string
     */
    public function facturaUrl()
    {
        $fac = $this->getFactura();
        if ($fac) {
            return $fac->url();
        }

        return '#';
    }

    /**
     * Returns the url to the exercise associated with the seat.
     *
     * @return string
     */
    public function ejercicioUrl()
    {
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            return $eje0->url();
        }

        return '#';
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

        return $partida->allFromAsiento($this->idasiento);
    }

    /**
     * We assign a number to the seat.
     */
    public function newNumero()
    {
        $this->numero = 1;
        $sql = 'SELECT MAX(' . self::$dataBase->sql2Int('numero') . ') as num FROM ' . static::tableName()
            . ' WHERE codejercicio = ' . self::$dataBase->var2str($this->codejercicio) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            $this->numero = 1 + (int) $data[0]['num'];
        }
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = self::noHtml($this->concepto);
        $this->documento = self::noHtml($this->documento);

        if (strlen($this->concepto) > 255) {
            self::$miniLog->alert(self::$i18n->trans('concept-seat-too-large'));

            return false;
        }

        return true;
    }

    /**
     * Run a complete test of tests.
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

        /*
         * We check that the seat is not empty or unbalanced.
         * We also check that the subaccounts belong to the same fiscal year.
         */
        $debe = $haber = 0;
        $partidas = $this->getPartidas();
        if (!empty($partidas)) {
            foreach ($partidas as $p) {
                $debe += $p->debe;
                $haber += $p->haber;

                $sc = $p->getSubcuenta();
                if ($sc) {
                    if ($sc->codejercicio !== $this->codejercicio) {
                        self::$miniLog->alert(self::$i18n->trans('subaccount-belongs-other-year', ['%subAccountCode%' => $sc->codsubcuenta]));
                        $status = false;
                    }
                } else {
                    self::$miniLog->alert(self::$i18n->trans('subaccount-not-found', ['%subAccountCode%' => $p->codsubcuenta]));
                    $status = false;
                }
            }
        }

        if (!static::floatcmp($debe, $haber, FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('notchead-seat', [round($debe - $haber, FS_NF0 + 1)]));
            $status = false;
        } elseif (!static::floatcmp($this->importe, max([abs($debe), abs($haber)]), FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('incorrect-seat-amount'));
            $status = false;
        }

        /// check that the date is correct
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) || strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                self::$miniLog->alert(self::$i18n->trans('seat-date-not-in-exercise-range', ['%link%' => $eje0->url()]));
                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// check if it is a duplicate
            $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE fecha = ' . self::$dataBase->var2str($this->fecha) . '
            AND concepto = ' . self::$dataBase->var2str($this->concepto) . ' AND importe = ' . self::$dataBase->var2str($this->importe) . '
            AND idasiento != ' . self::$dataBase->var2str($this->idasiento) . ';';
            $asientos = self::$dataBase->select($sql);
            if (!empty($asientos)) {
                foreach ($asientos as $as) {
                    /// check the lines
                    if (strtolower(FS_DB_TYPE) === 'mysql') {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . self::$dataBase->var2str($this->idasiento) . '
                     AND NOT EXISTS(SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . self::$dataBase->var2str($as['idasiento']) . ');';
                        $aux = self::$dataBase->select($sql);
                    } else {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . self::$dataBase->var2str($this->idasiento) . '
                     EXCEPT SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . self::$dataBase->var2str($as['idasiento']) . ';';
                        $aux = self::$dataBase->select($sql);
                    }

                    if (empty($aux)) {
                        self::$miniLog->alert(self::$i18n->trans('seat-possible-duplicated', ['%seatId%' => $as['idasiento']]));
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Apply corrections to the seat
     *
     * @return bool
     */
    public function fix()
    {
        $importeOld = $this->importe;
        $debe = $haber = 0;
        foreach ($this->getPartidas() as $p) {
            $debe += $p->debe;
            $haber += $p->haber;
        }
        $total = $debe - $haber;
        $this->importe = max([abs($debe), abs($haber)]);

        /// we correct unbalances of less than 0.01
        if (static::floatcmp($debe, $haber, 2)) {
            $debe = $haber = 0;
            $partidas = $this->getPartidas();
            foreach ($partidas as $p) {
                $p->debe = bround($p->debe, 2);
                $debe += $p->debe;
                $p->haber = bround($p->haber, 2);
                $haber += $p->haber;
            }

            /// if with rounding the problem is solved, then great!
            if (static::floatcmp($debe, $haber)) {
                $this->importe = max([abs($debe), abs($haber)]);
                foreach ($partidas as $p) {
                    $p->save();
                }
            } else {
                /// If it has not worked, we try to fix it
                $total = 0;
                $partidas = $this->getPartidas();
                foreach ($partidas as $p) {
                    $total += ($p->debe - $p->haber);
                }

                if ($partidas[0]->debe !== 0) {
                    $partidas[0]->debe -= $total;
                } elseif ($partidas[0]->haber !== 0) {
                    $partidas[0]->haber += $total;
                }

                $debe = $haber = 0;
                foreach ($partidas as $p) {
                    $debe += $p->debe;
                    $haber += $p->haber;
                }

                /// if we have solved the problem we recorded
                if (static::floatcmp($debe, $haber)) {
                    $this->importe = max([abs($debe), abs($haber)]);
                    foreach ($partidas as $p) {
                        $p->save();
                    }
                }
            }
        }

        /// If the amount has changed, we keep it
        if (!static::floatcmp($this->importe, $importeOld)) {
            $this->save();
        }

        /// we check the associated invoice
        $status = true;
        $fac = $this->getFactura();
        if ($fac) {
            if ($fac->idasiento === null) {
                $fac->idasiento = $this->idasiento;
                $status = $fac->save();
            }
        }

        if ($status) {
            return $this->fullTest();
        }

        return false;
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
     * Returns an array with combinations containing $query in its number
     * or concept or amount.
     *
     * @param string $query
     * @param int    $offset
     *
     * @return self[]
     */
    public function search($query, $offset = 0)
    {
        $alist = [];
        $query = self::noHtml(mb_strtolower($query, 'UTF8'));

        $consulta = 'SELECT * FROM ' . static::tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $auxSql = '';
            if (strtolower(FS_DB_TYPE) === 'postgresql') {
                $auxSql = '::TEXT';
            }

            $consulta .= 'numero' . $auxSql . " LIKE '%" . $query . "%' OR concepto LIKE '%" . $query
                . "%' OR importe BETWEEN " . ($query - .01) . ' AND ' . ($query + .01);
        } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/i', $query)) {
            $consulta .= 'fecha = ' . self::$dataBase->var2str($query) . " OR concepto LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(concepto) LIKE '%" . $buscar = str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC';

        $data = self::$dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alist[] = new self($a);
            }
        }

        return $alist;
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
    public function renumerar()
    {
        $continuar = false;
        $ejercicio = new Ejercicio();
        foreach ($ejercicio->allAbiertos() as $eje) {
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
                        self::$miniLog->alert(self::$i18n->trans('error-while-renumbering-seats', ['%exerciseCode%' => $eje->codejercicio]));
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
                foreach ($regiva0->allFromEjercicio($ej->codejercicio) as $reg) {
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

        echo self::$i18n->trans('renumbering-seats');
        $this->renumerar();
    }
    /// Renumber all seats. Returns False in case of error

    /**
     * Insert the model data in the database.
     *
     * @return bool
     */
    private function saveInsert()
    {
        $this->newNumero();

        return $this->saveInsertTrait();
    }
}
