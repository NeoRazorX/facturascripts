<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\Model;

/**
 * El asiento contable. Se relaciona con un ejercicio y se compone de partidas.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Asiento
{
    use Model {
        saveInsert as private saveInsertTrait;
    }

    /**
     * Clave primaria.
     * @var
     */
    public $idasiento;

    /**
     * Número de asiento. Se modificará al renumerar.
     * @var
     */
    public $numero;
    /**
     * TODO
     * @var
     */
    public $idconcepto;
    /**
     * TODO
     * @var
     */
    public $concepto;
    /**
     * TODO
     * @var
     */
    public $fecha;
    /**
     * TODO
     * @var
     */
    public $codejercicio;
    /**
     * TODO
     * @var
     */
    public $codplanasiento;
    /**
     * TODO
     * @var
     */
    public $editable;
    /**
     * TODO
     * @var
     */
    public $documento;
    /**
     * TODO
     * @var
     */
    public $tipodocumento;
    /**
     * TODO
     * @var
     */
    public $importe;
    /**
     * TODO
     * @var
     */
    private $coddivisa;

    /**
     * Asiento constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'co_asientos', 'idasiento');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
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
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->idasiento === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasiento;
    }

    /**
     * TODO
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
     * TODO
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
     * TODO
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
     * @return Divisa|null
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
     * TODO
     * @return array
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
        $sql = 'SELECT MAX(' . $this->database->sql2Int('numero') . ') as num FROM ' . $this->tableName()
            . ' WHERE codejercicio = ' . $this->var2str($this->codejercicio) . ';';

        $data = $this->database->select($sql);
        if ($data) {
            $this->numero = 1 + (int)$data[0]['num'];
        }

        /// Nos guardamos la secuencia para dar compatibilidad con eneboo
        $secc = new SecuenciaContabilidad();
        $secc0 = $secc->getByParams2($this->codejercicio, 'nasiento');
        if ($secc0) {
            if ($this->numero >= $secc0->valorout) {
                $secc0->valorout = 1 + $this->numero;
                $secc0->save();
            }
        }
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $this->concepto = static::noHtml($this->concepto);
        $this->documento = static::noHtml($this->documento);

        if (strlen($this->concepto) > 255) {
            $this->miniLog->alert('Concepto del asiento demasiado largo.');
            return false;
        }
        return true;
    }

    /**
     * TODO
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
        if ($partidas) {
            foreach ($partidas as $p) {
                $debe += $p->debe;
                $haber += $p->haber;

                $sc = $p->getSubcuenta();
                if ($sc) {
                    if ($sc->codejercicio !== $this->codejercicio) {
                        $this->miniLog->alert('La subcuenta ' . $sc->codsubcuenta . ' pertenece a otro ejercicio.');
                        $status = false;
                    }
                } else {
                    $this->miniLog->alert('Subcuenta ' . $p->codsubcuenta . ' no encontrada.');
                    $status = false;
                }
            }
        }

        if (!$this->floatcmp($debe, $haber, FS_NF0, true)) {
            $this->miniLog->alert('Asiento descuadrado. Descuadre: ' . round($debe - $haber, FS_NF0 + 1));
            $status = false;
        } elseif (!$this->floatcmp($this->importe, max([abs($debe), abs($haber)]), FS_NF0, true)) {
            $this->miniLog->alert('Importe del asiento incorrecto.');
            $status = false;
        }

        /// comprobamos que la fecha sea correcta
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) || strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                $this->miniLog->alert("La fecha de este asiento está fuera del rango del <a target='_blank' href='" . $eje0->url() . "'>ejercicio</a>.");
                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha) . '
            AND concepto = ' . $this->var2str($this->concepto) . ' AND importe = ' . $this->var2str($this->importe) . '
            AND idasiento != ' . $this->var2str($this->idasiento) . ';';
            $asientos = $this->database->select($sql);
            if ($asientos) {
                foreach ($asientos as $as) {
                    /// comprobamos las líneas
                    if (strtolower(FS_DB_TYPE) === 'mysql') {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . $this->var2str($this->idasiento) . '
                     AND NOT EXISTS(SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . $this->var2str($as['idasiento']) . ');';
                        $aux = $this->database->select($sql);
                    } else {
                        $sql = 'SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = ' . $this->var2str($this->idasiento) . '
                     EXCEPT SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = ' . $this->var2str($as['idasiento']) . ';';
                        $aux = $this->database->select($sql);
                    }

                    if (!$aux) {
                        $this->miniLog->alert("Este asiento es un posible duplicado de
                     <a href='index.php?page=ContabilidadAsiento&id=" . $as['idasiento'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica el concepto.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * TODO
     * @return bool
     */
    public function fix()
    {
        $importe_old = $this->importe;
        $debe = $haber = 0;
        foreach ($this->getPartidas() as $p) {
            $debe += $p->debe;
            $haber += $p->haber;
        }
        $total = $debe - $haber;
        $this->importe = max([abs($debe), abs($haber)]);

        /// corregimos descuadres de menos de 0.01
        if ($this->floatcmp($debe, $haber, 2)) {
            $debe = $haber = 0;
            $partidas = $this->getPartidas();
            foreach ($partidas as $p) {
                $p->debe = bround($p->debe, 2);
                $debe += $p->debe;
                $p->haber = bround($p->haber, 2);
                $haber += $p->haber;
            }

            /// si con el redondeo se soluciona el problema, pues genial!
            if ($this->floatcmp($debe, $haber)) {
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
                    $partidas[0]->debe -=  $total;
                } elseif ($partidas[0]->haber !== 0) {
                    $partidas[0]->haber += $total;
                }

                $debe = $haber = 0;
                foreach ($partidas as $p) {
                    $debe += $p->debe;
                    $haber += $p->haber;
                }

                /// si hemos resuelto el problema grabamos
                if ($this->floatcmp($debe, $haber)) {
                    $this->importe = max([abs($debe), abs($haber)]);
                    foreach ($partidas as $p) {
                        $p->save();
                    }
                }
            }
        }

        /// si el importe ha cambiado, lo guardamos
        if (!$this->floatcmp($this->importe, $importe_old)) {
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
     * Inserta los datos del modelo en la base de datos.
     * @return bool
     */
    private function saveInsert()
    {
        $this->newNumero();
        return $this->saveInsertTrait();
    }

    /**
     * TODO
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
                    $this->miniLog->alert('El asiento se encuentra dentro de una regularización de '
                        . FS_IVA . '. No se puede eliminar.');
                    $bloquear = true;
                }
            } else {
                $this->miniLog->alert('El ejercicio ' . $ejercicio->nombre . ' está cerrado.');
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
        return $this->database->exec($sql);
    }

    /**
     * TODO
     *
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alist = [];
        $query = static::noHtml(mb_strtolower($query, 'UTF8'));

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $aux_sql = '';
            if (strtolower(FS_DB_TYPE) === 'postgresql') {
                $aux_sql = '::TEXT';
            }

            $consulta .= 'numero' . $aux_sql . " LIKE '%" . $query . "%' OR concepto LIKE '%" . $query
                . "%' OR importe BETWEEN " . ($query - .01) . ' AND ' . ($query + .01);
        } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/i', $query)) {
            $consulta .= 'fecha = ' . $this->var2str($query) . " OR concepto LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(concepto) LIKE '%" . $buscar = str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC';

        $data = $this->database->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alist[] = new Asiento($a);
            }
        }

        return $alist;
    }

    /**
     * TODO
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

        $data = $this->database->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $alist[] = $this->get($a['idasiento']);
            }
        }

        return $alist;
    }

    /**
     * TODO
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

            $asientos = $this->database->selectLimit($consulta, 1000, $posicion);
            while ($asientos && $continuar) {
                foreach ($asientos as $col) {
                    if ($col['numero'] !== $numero) {
                        $sql .= 'UPDATE ' . $this->tableName() . ' SET numero = ' . $this->var2str($numero)
                            . ' WHERE idasiento = ' . $this->var2str($col['idasiento']) . ';';
                    }

                    $numero++;
                }
                $posicion += 1000;

                if ($sql !== '') {
                    if (!$this->database->exec($sql)) {
                        $this->miniLog->alert(
                            'Se ha producido un error mientras se renumeraban los asientos del ejercicio '
                            . $eje->codejercicio
                        );
                        $continuar = false;
                    }
                    $sql = '';
                }

                $asientos = $this->database->selectLimit($consulta, 1000, $posicion);
            }
        }

        return $continuar;
    }

    /// renumera todos los asientos. Devuelve FALSE en caso de error

    /**
     * TODO
     */
    public function cronJob()
    {
        /**
         * Bloqueamos asientos de ejercicios cerrados o dentro de regularizaciones.
         */
        $eje0 = new Ejercicio();
        $regiva0 = new RegularizacionIva();
        foreach ($eje0->all() as $ej) {
            if ($ej->abierto()) {
                foreach ($regiva0->allFromEjercicio($ej->codejercicio) as $reg) {
                    $sql = 'UPDATE ' . $this->tableName() . ' SET editable = false WHERE editable = true'
                        . ' AND codejercicio = ' . $this->var2str($ej->codejercicio)
                        . ' AND fecha >= ' . $this->var2str($reg->fechainicio)
                        . ' AND fecha <= ' . $this->var2str($reg->fechafin) . ';';
                    $this->database->exec($sql);
                }
            } else {
                $sql = 'UPDATE ' . $this->tableName() . ' SET editable = false WHERE editable = true'
                    . ' AND codejercicio = ' . $this->var2str($ej->codejercicio) . ';';
                $this->database->exec($sql);
            }
        }

        echo "\nRenumerando asientos...";
        $this->renumerar();
    }
}
