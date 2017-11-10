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
 * El asiento contable. Se relaciona con un ejercicio y se compone de partidas.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Asiento
{

    use Base\ModelTrait {
        saveInsert as private saveInsertTrait;
    }

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Número de asiento. Se modificará al renumerar.
     *
     * @var string
     */
    public $numero;

    /**
     * Identificador del concepto
     *
     * @var int
     */
    public $idconcepto;

    /**
     * Concepto del asiento
     *
     * @var string
     */
    public $concepto;

    /**
     * Fecha del asiento
     *
     * @var string
     */
    public $fecha;

    /**
     * Código de ejercicio del asiento
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Código del plan de asiento
     *
     * @var string
     */
    public $codplanasiento;

    /**
     * True si es editable, sino false
     *
     * @var bool
     */
    public $editable;

    /**
     * Documento asociado al asiento
     *
     * @var string
     */
    public $documento;

    /**
     * Texto que identifica el tipo de documento
     * 'Factura de cliente' o 'Factura de proveedor'
     *
     * @var string
     */
    public $tipodocumento;

    /**
     * Importe del asiento
     *
     * @var float|int
     */
    public $importe;

    /**
     * Código de divisa del asiento
     *
     * @var string
     */
    private $coddivisa;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_asientos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idasiento';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
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
     * Devuelve la factura asociada al asiento
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
     * Devuelve la url de la factura asociada al asiento
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
     * Devuelve la url al ejercicio asociado al asiento
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
     * Devuelve el código de la divisa.
     * Lo que pasa es que ese dato se almacena en las partidas, por eso
     * hay que usar esta función.
     *
     * @return string|null
     */
    public function codDivisa()
    {
        if ($this->coddivisa === null) {
            $this->coddivisa = $this->defaultItems->codDivisa();

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
     * Devuelve todas las partidas del asiento
     *
     * @return Partida[]
     */
    public function getPartidas()
    {
        $partida = new Partida();

        return $partida->allFromAsiento($this->idasiento);
    }

    /**
     * Asignamos un número al asiento.
     */
    public function newNumero()
    {
        $this->numero = 1;
        $sql = 'SELECT MAX(' . $this->dataBase->sql2Int('numero') . ') as num FROM ' . $this->tableName()
            . ' WHERE codejercicio = ' . $this->var2str($this->codejercicio) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $this->numero = 1 + (int) $data[0]['num'];
        }
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = self::noHtml($this->concepto);
        $this->documento = self::noHtml($this->documento);

        if (strlen($this->concepto) > 255) {
            $this->miniLog->alert($this->i18n->trans('concept-seat-too-large'));

            return false;
        }

        return true;
    }

    /**
     * Ejecuta un test completo de pruebas
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

        /*
         * Comprobamos que el asiento no esté vacío o descuadrado.
         * También comprobamos que las subcuentas pertenezcan al mismo ejercicio.
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
                        $this->miniLog->alert($this->i18n->trans('subaccount-belongs-other-year', [$sc->codsubcuenta]));
                        $status = false;
                    }
                } else {
                    $this->miniLog->alert($this->i18n->trans('subaccount-not-found', [$p->codsubcuenta]));
                    $status = false;
                }
            }
        }

        if (!static::floatcmp($debe, $haber, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('notchead-seat', [round($debe - $haber, FS_NF0 + 1)]));
            $status = false;
        } elseif (!static::floatcmp($this->importe, max([abs($debe), abs($haber)]), FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('incorrect-seat-amount'));
            $status = false;
        }

        /// comprobamos que la fecha sea correcta
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) || strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                $this->miniLog->alert($this->i18n->trans('seat-date-not-in-exercise-range', [$eje0->url()]));
                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha) . '
            AND concepto = ' . $this->var2str($this->concepto) . ' AND importe = ' . $this->var2str($this->importe) . '
            AND idasiento != ' . $this->var2str($this->idasiento) . ';';
            $asientos = $this->dataBase->select($sql);
            if (!empty($asientos)) {
                foreach ($asientos as $as) {
                    /// comprobamos las líneas
                    if (strtolower(FS_DB_TYPE) === 'mysql') {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . $this->var2str($this->idasiento) . '
                     AND NOT EXISTS(SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . $this->var2str($as['idasiento']) . ');';
                        $aux = $this->dataBase->select($sql);
                    } else {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . $this->var2str($this->idasiento) . '
                     EXCEPT SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . $this->var2str($as['idasiento']) . ';';
                        $aux = $this->dataBase->select($sql);
                    }

                    if (empty($aux)) {
                        $this->miniLog->alert($this->i18n->trans('seat-possible duplicated', [$as['idasiento']]));
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Aplica correcciones al asiento
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

        /// corregimos descuadres de menos de 0.01
        if (static::floatcmp($debe, $haber, 2)) {
            $debe = $haber = 0;
            $partidas = $this->getPartidas();
            foreach ($partidas as $p) {
                $p->debe = bround($p->debe, 2);
                $debe += $p->debe;
                $p->haber = bround($p->haber, 2);
                $haber += $p->haber;
            }

            /// si con el redondeo se soluciona el problema, pues genial!
            if (static::floatcmp($debe, $haber)) {
                $this->importe = max([abs($debe), abs($haber)]);
                foreach ($partidas as $p) {
                    $p->save();
                }
            } else {
                /// si no ha funcionado, intentamos arreglarlo
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

                /// si hemos resuelto el problema grabamos
                if (static::floatcmp($debe, $haber)) {
                    $this->importe = max([abs($debe), abs($haber)]);
                    foreach ($partidas as $p) {
                        $p->save();
                    }
                }
            }
        }

        /// si el importe ha cambiado, lo guardamos
        if (!static::floatcmp($this->importe, $importeOld)) {
            $this->save();
        }

        /// comprobamos la factura asociada
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
     * Elimina el asiento de la base de datos.
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
                /// permitimos eliminar el asiento de apertura
            } elseif ($this->idasiento === $ejercicio->idasientocierre) {
                /// permitimos eliminar el asiento de cierre
            } elseif ($this->idasiento === $ejercicio->idasientopyg) {
                /// permitimos eliminar el asiento de pérdidas y ganancias
            } elseif ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    $this->miniLog->alert($this->i18n->trans('seat-within-regularization', [FS_IVA]));
                    $bloquear = true;
                }
            } else {
                $this->miniLog->alert($this->i18n->trans('closed-exercise', [$ejercicio->nombre]));
                $bloquear = true;
            }
        }

        if ($bloquear) {
            return false;
        }

        /// desvinculamos la factura
        $fac = $this->getFactura();
        if ($fac) {
            if ($fac->idasiento === $this->idasiento) {
                $fac->idasiento = null;
                $fac->save();
            }
        }

        /// eliminamos las partidas una a una para forzar la actualización de las subcuentas asociadas
        foreach ($this->getPartidas() as $p) {
            $p->delete();
        }

        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idasiento = ' . $this->var2str($this->idasiento) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Devuelve un array con las combinaciones que contienen $query en su numero
     * o concepto o importe.
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

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $auxSql = '';
            if (strtolower(FS_DB_TYPE) === 'postgresql') {
                $auxSql = '::TEXT';
            }

            $consulta .= 'numero' . $auxSql . " LIKE '%" . $query . "%' OR concepto LIKE '%" . $query
                . "%' OR importe BETWEEN " . ($query - .01) . ' AND ' . ($query + .01);
        } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/i', $query)) {
            $consulta .= 'fecha = ' . $this->var2str($query) . " OR concepto LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(concepto) LIKE '%" . $buscar = str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alist[] = new self($a);
            }
        }

        return $alist;
    }

    /**
     * Devuelve un listado de asientos descuadrados
     *
     * @return array
     */
    public function descuadrados()
    {
        /// iniciamos partidas para asegurarnos que existe la tabla
        new Partida();

        $alist = [];
        $sql = 'SELECT p.idasiento,SUM(p.debe) AS sdebe,SUM(p.haber) AS shaber
         FROM co_partidas p, ' . $this->tableName() . ' a
          WHERE p.idasiento = a.idasiento
           GROUP BY p.idasiento
            HAVING ABS(SUM(p.haber) - SUM(p.debe)) > 0.01
             ORDER BY p.idasiento DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alist[] = $this->get($a['idasiento']);
            }
        }

        return $alist;
    }

    /**
     * Reenumera los asientos de los ejercicios abiertos
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
            $consulta = 'SELECT idasiento,numero,fecha FROM ' . $this->tableName()
                . ' WHERE codejercicio = ' . $this->var2str($eje->codejercicio)
                . ' ORDER BY codejercicio ASC, fecha ASC, idasiento ASC';

            $asientos = $this->dataBase->selectLimit($consulta, 1000, $posicion);
            while (!empty($asientos) && $continuar) {
                foreach ($asientos as $col) {
                    if ($col['numero'] !== $numero) {
                        $sql .= 'UPDATE ' . $this->tableName() . ' SET numero = ' . $this->var2str($numero)
                            . ' WHERE idasiento = ' . $this->var2str($col['idasiento']) . ';';
                    }

                    ++$numero;
                }
                $posicion += 1000;

                if ($sql !== '') {
                    if (!$this->dataBase->exec($sql)) {
                        $this->miniLog->alert($this->i18n->trans('error-while-renumbering-seats', [$eje->codejercicio]));
                        $continuar = false;
                    }
                    $sql = '';
                }

                $asientos = $this->dataBase->selectLimit($consulta, 1000, $posicion);
            }
        }

        return $continuar;
    }

    /**
     * Ejecuta una tarea con cron
     */
    public function cronJob()
    {
        /**
         * Bloqueamos asientos de ejercicios cerrados o dentro de regularizaciones.
         */
        $eje0 = new Ejercicio();
        $regiva0 = new RegularizacionIva();
        foreach ($eje0->all() as $ej) {
            if ($ej instanceof Ejercicio && $ej->abierto()) {
                foreach ($regiva0->allFromEjercicio($ej->codejercicio) as $reg) {
                    $sql = 'UPDATE ' . $this->tableName() . ' SET editable = false WHERE editable = true'
                        . ' AND codejercicio = ' . $this->var2str($ej->codejercicio)
                        . ' AND fecha >= ' . $this->var2str($reg->fechainicio)
                        . ' AND fecha <= ' . $this->var2str($reg->fechafin) . ';';
                    $this->dataBase->exec($sql);
                }
            } else {
                $sql = 'UPDATE ' . $this->tableName() . ' SET editable = false WHERE editable = true'
                    . ' AND codejercicio = ' . $this->var2str($ej->codejercicio) . ';';
                $this->dataBase->exec($sql);
            }
        }

        echo $this->i18n->trans('renumbering-seats');
        $this->renumerar();
    }
    /// renumera todos los asientos. Devuelve False en caso de error

    /**
     * Inserta los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveInsert()
    {
        $this->newNumero();

        return $this->saveInsertTrait();
    }
}
