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
 * Factura de un proveedor.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaProveedor
{

    use Base\DocumentoCompra;
    use Base\Factura;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'facturasprov';
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
                                $this->miniLog->alert($this->i18n->trans('invoice-regularized-cant-change-date', [FS_IVA]));
                            } elseif ($regiva0->getFechaInside($fecha)) {
                                $this->miniLog->alert($this->i18n->trans('cant-assign-date-already-regularized', [$fecha, FS_IVA]));
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
                            $this->miniLog->alert($this->i18n->trans('closed-exercise-cant-change-date', [$eje2->nombre]));
                        }
                    }
                } else {
                    $this->miniLog->alert($this->i18n->trans('closed-exercise-cant-change-date', [$ejercicio->nombre]));
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
     * @return LineaFacturaProveedor[]
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
     * @return LineaIvaFacturaProveedor[]
     */
    public function getLineasIva()
    {
        return $this->getLineasIvaTrait('FacturaProveedor');
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

    /**
     * Ejecuta un test completo de pruebas
     *
     * @return bool
     */
    public function fullTest()
    {
        return $this->fullTestTrait('invoice');
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

            $this->miniLog->info($this->i18n->trans('supplier-invoice-deleted-successfully', [$this->codigo]));

            return true;
        }

        return false;
    }
}
