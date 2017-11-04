<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Factura de un cliente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaCliente
{

    use Base\DocumentoVenta;
    use Base\Factura;

    /**
     * Identificador opcional para la impresión. Todavía sin uso.
     * Se puede usar para identificar una forma de impresión y usar siempre
     * esa en esta factura.
     *
     * @var int
     */
    public $idimprenta;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'facturascli';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idfactura';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearDocumentoVenta();
        $this->pagada = false;
        $this->anulada = false;
        $this->vencimiento = date('d-m-Y', strtotime('+1 day'));
    }

    /**
     * Devuelve true su está vencida, sino false
     *
     * @return bool
     */
    public function vencida()
    {
        return ($this->pagada) ? false : strtotime($this->vencimiento) < strtotime(date('d-m-Y'));
    }

    /**
     * Establece la fecha y la hora, pero respetando la numeración, el ejercicio
     * y las regularizaciones de IVA.
     * Devuelve True si se asigna una fecha distinta a los solicitados.
     *
     * @param string $fecha
     * @param string $hora
     *
     * @return bool
     */
    public function setFechaHora($fecha, $hora)
    {
        $cambio = false;

        if ($this->numero === null) { /// nueva factura
            /// buscamos la última fecha usada en una factura en esta serie y ejercicio
            $sql = 'SELECT MAX(fecha) AS fecha FROM ' . $this->tableName()
                . ' WHERE codserie = ' . $this->var2str($this->codserie)
                . ' AND codejercicio = ' . $this->var2str($this->codejercicio) . ';';

            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['fecha']) > strtotime($fecha)) {
                    $fechaOld = $fecha;
                    $fecha = date('d-m-Y', strtotime($data[0]['fecha']));

                    $this->miniLog->alert($this->i18n->trans('invoice-new-assigned-date', [$fechaOld, $fecha]));
                    $cambio = true;
                }
            }

            /// ahora buscamos la última hora usada para esa fecha, serie y ejercicio
            $sql = 'SELECT MAX(hora) AS hora FROM ' . $this->tableName()
                . ' WHERE codserie = ' . $this->var2str($this->codserie)
                . ' AND codejercicio = ' . $this->var2str($this->codejercicio)
                . ' AND fecha = ' . $this->var2str($fecha) . ';';

            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['hora']) > strtotime($hora) || $cambio) {
                    $hora = date('H:i:s', strtotime($data[0]['hora']));
                    $cambio = true;
                }
            }

            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// factura existente y cambiamos fecha
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                if (!$ejercicio->abierto()) {
                    $this->miniLog->alert($this->i18n->trans('closed-exercise-cant-change-date', [$ejercicio->nombre]));
                } elseif ($fecha === $ejercicio->get_best_fecha($fecha)) {
                    $regiva0 = new RegularizacionIva();
                    if ($regiva0->getFechaInside($fecha)) {
                        $this->miniLog->alert($this->i18n->trans('cant-assign-date-already-regularized', [$fecha, FS_IVA]));
                    } elseif ($regiva0->getFechaInside($this->fecha)) {
                        $this->miniLog->alert($this->i18n->trans('invoice-regularized-cant-change-date', [FS_IVA]));
                    } else {
                        $this->fecha = $fecha;
                        $this->hora = $hora;
                        $cambio = false;
                    }
                } else {
                    $this->miniLog->alert($this->i18n->trans('date-out-of-exercise-range', [$ejercicio->nombre]));
                }
            } else {
                $this->miniLog->alert($this->i18n->trans('exercise-not-found'));
            }
        } elseif ($hora !== $this->hora) { /// factura existente y cambiamos hora
            $this->hora = $hora;
        }

        return $cambio;
    }

    /**
     * Devuelve las líneas asociadas a la factura.
     *
     * @return LineaFacturaCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaFacturaCliente();
        return $lineaModel->all(new DataBaseWhere('idfactura', $this->idfactura));
    }

    /**
     * Devuelve las líneas de IVA de la factura.
     * Si no hay, las crea.
     *
     * @return LineaIvaFacturaCliente[]
     */
    public function getLineasIva()
    {
        return $this->getLineasIvaTrait('FacturaCliente');
    }

    /**
     * Comprueba los datos de la factura, devuelve True si está correcto
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }

    /**
     * Elimina una factura y actualiza los registros relacionados con ella.
     *
     * @return bool
     */
    public function delete()
    {
        $bloquear = false;

        $eje0 = new Ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    $this->miniLog->alert($this->i18n->trans('invoice-regularized-cant-delete', [FS_IVA]));
                    $bloquear = true;
                } else {
                    foreach ($this->getRectificativas() as $rect) {
                        $this->miniLog->alert($this->i18n->trans('invoice-have-rectifying-cant-delete'));
                        $bloquear = true;
                        break;
                    }
                }
            } else {
                $this->miniLog->alert($this->i18n->trans('closed-exercise', [$ejercicio->nombre]));
                $bloquear = true;
            }
        }

        /// desvincular albaranes asociados y eliminar factura
        $sql = 'UPDATE albaranescli'
            . ' SET idfactura = NULL, ptefactura = TRUE WHERE idfactura = ' . $this->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if ($this->dataBase->exec($sql)) {
            $this->cleanCache();

            if ($this->idasiento) {
                /**
                 * Delegamos la eliminación de los asientos en la clase correspondiente.
                 */
                $asiento = new Asiento();
                $asi0 = $asiento->get($this->idasiento);
                if ($asi0) {
                    $asi0->delete();
                }

                $asi1 = $asiento->get($this->idasientop);
                if ($asi1) {
                    $asi1->delete();
                }
            }

            $this->miniLog->info($this->i18n->trans('customer-invoice-deleted-successfully', [$this->codigo]));

            return true;
        }

        return false;
    }

    /**
     * Devuelve un array con los huecos en la numeración.
     *
     * @return mixed
     */
    public function huecos()
    {
        return [];
    }
}
