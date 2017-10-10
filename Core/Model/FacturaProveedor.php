<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Factura de un proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FacturaProveedor
{
    use Base\DocumentoCompra;
    use Base\Factura;

    public function tableName()
    {
        return 'facturasprov';
    }

    public function primaryColumn()
    {
        return 'idfactura';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearDocumentoCompra();
        $this->anulada = false;
    }

    /**
     * Establece la fecha y la hora, pero respetando el ejercicio y las
     * regularizaciones de IVA.
     * Devuelve TRUE si se asigna una fecha u hora distinta a los solicitados.
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
            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// factura existente y cambiamos fecha
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                /// ¿El ejercicio actual está abierto?
                if ($ejercicio->abierto()) {
                    $eje2 = $eje0->getByFecha($fecha);
                    if ($eje2) {
                        if ($eje2->abierto()) {
                            /// ¿La factura está dentro de alguna regularización?
                            $regiva0 = new RegularizacionIva();
                            if ($regiva0->getFechaInside($this->fecha)) {
                                $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                                    . FS_IVA . '. No se puede modificar la fecha.');
                            } elseif ($regiva0->getFechaInside($fecha)) {
                                $this->miniLog->alert('No se puede asignar la fecha ' . $fecha . ' porque ya hay'
                                    . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                            } else {
                                $cambio = false;
                                $this->fecha = $fecha;
                                $this->hora = $hora;

                                /// ¿El ejercicio es distinto?
                                if ($this->codejercicio !== $eje2->codejercicio) {
                                    $this->codejercicio = $eje2->codejercicio;
                                    $this->newCodigo();
                                }
                            }
                        } else {
                            $this->miniLog->alert(
                                'El ejercicio ' . $eje2->nombre . ' está cerrado. No se puede modificar la fecha.'
                            );
                        }
                    }
                } else {
                    $this->miniLog->alert(
                        'El ejercicio ' . $ejercicio->nombre . ' está cerrado. No se puede modificar la fecha.'
                    );
                }
            } else {
                $this->miniLog->alert('Ejercicio no encontrado.');
            }
        } elseif ($hora !== $this->hora) { /// factura existente y cambiamos hora
            $this->hora = $hora;
        }

        return $cambio;
    }

    /**
     * Devuelve las líneas de la factura.
     *
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaFacturaProveedor();
        return $lineaModel->all(new DataBaseWhere('idfactura', $this->idfactura));
    }

    /**
     * Devuelve las líneas de IVA de la factura.
     * Si no hay, las crea.
     *
     * @return array
     */
    public function getLineasIva()
    {
        return $this->getLineasIvaTrait($this->getLineas(), 'LineaIvaFacturaProveedor');
    }

    /**
     * Comprueba los datos de la factura, devuelve TRUE si está correcto
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }
    
    public function fullTest()
    {
        return $this->fullTestTrait('invoice');
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return FALSE;
    }

    /**
     * Elimina la factura de la base de datos.
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
                    $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                        . FS_IVA . '. No se puede eliminar.');
                    $bloquear = true;
                } else {
                    foreach ($this->getRectificativas() as $rect) {
                        $this->miniLog->alert('La factura ya tiene una rectificativa. No se puede eliminar.');
                        $bloquear = true;
                        break;
                    }
                }
            } else {
                $this->miniLog->alert('El ejercicio ' . $ejercicio->nombre . ' está cerrado.');
                $bloquear = true;
            }
        }

        /// desvincular albaranes asociados y eliminar factura
        $sql = 'UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE'
            . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if ($this->dataBase->exec($sql)) {
            if ($this->idasiento) {
                /**
                 * Delegamos la eliminación del asiento en la clase correspondiente.
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

            $this->miniLog->info(ucfirst(FS_FACTURA) . ' de compra ' . $this->codigo . ' eliminada correctamente.');

            return true;
        }

        return false;
    }
}
