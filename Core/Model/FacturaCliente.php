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
 * Factura de un cliente.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FacturaCliente
{
    use Base\DocumentoVenta;
    use Base\Factura;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Identificador opcional para la impresión. Todavía sin uso.
     * Se puede usar para identificar una forma de impresión y usar siempre
     * esa en esta factura.
     *
     * @var int
     */
    public $idimprenta;

    public function tableName()
    {
        return 'facturascli';
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
        $this->clearTrait();
        $this->codserie = $this->defaultItems->codSerie();
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->codpago = $this->defaultItems->codPago();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->tasaconv = 1.0;
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
        if ($this->pagada) {
            return false;
        }

        return strtotime($this->vencimiento) < strtotime(date('d-m-Y'));
    }

    /**
     * Establece la fecha y la hora, pero respetando la numeración, el ejercicio
     * y las regularizaciones de IVA.
     * Devuelve TRUE si se asigna una fecha distinta a los solicitados.
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

                    $this->miniLog->alert('Ya hay facturas posteriores a la fecha seleccionada (' . $fechaOld . ').'
                        . ' Nueva fecha asignada: ' . $fecha);
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
                    $this->miniLog->alert(
                        'El ejercicio ' . $ejercicio->nombre . ' está cerrado. No se puede modificar la fecha.'
                    );
                } elseif ($fecha === $ejercicio->get_best_fecha($fecha)) {
                    $regiva0 = new RegularizacionIva();
                    if ($regiva0->getFechaInside($fecha)) {
                        $this->miniLog->alert('No se puede asignar la fecha ' . $fecha . ' porque ya hay'
                            . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                    } elseif ($regiva0->getFechaInside($this->fecha)) {
                        $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                            . FS_IVA . '. No se puede modificar la fecha.');
                    } else {
                        $this->fecha = $fecha;
                        $this->hora = $hora;
                        $cambio = false;
                    }
                } else {
                    $this->miniLog->alert('La fecha está fuera del rango del ejercicio ' . $ejercicio->nombre);
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
     * Devulve las líneas de la factura.
     *
     * @return array
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
     * @return array
     */
    public function getLineasIva()
    {
        return $this->getLineasIvaTrait($this->getLineas());
    }

    /**
     * Comprueba los datos de la factura, devuelve TRUE si está correcto
     *
     * @return bool
     */
    public function test()
    {
        $this->nombrecliente = self::noHtml($this->nombrecliente);
        if ($this->nombrecliente === '') {
            $this->nombrecliente = '-';
        }

        $this->direccion = self::noHtml($this->direccion);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->provincia = self::noHtml($this->provincia);
        $this->envio_nombre = self::noHtml($this->envio_nombre);
        $this->envio_apellidos = self::noHtml($this->envio_apellidos);
        $this->envio_direccion = self::noHtml($this->envio_direccion);
        $this->envio_ciudad = self::noHtml($this->envio_ciudad);
        $this->envio_provincia = self::noHtml($this->envio_provincia);
        $this->numero2 = self::noHtml($this->numero2);
        $this->observaciones = self::noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp(
                $this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true
            )) {
            return true;
        }
        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Informa del error!');

        return false;
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

            $this->miniLog->info(ucfirst(FS_FACTURA) . ' de venta ' . $this->codigo . ' eliminada correctamente.');

            return true;
        }

        return false;
    }

    /**
     * Devuelve un array con las facturas que coinciden con $query
     *
     * @param string $query
     * @param int    $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $faclist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new self($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con las facturas del cliente $codcliente que coinciden con $query
     *
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $serie
     * @param string $obs
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $desde, $hasta, $serie, $obs = '')
    {
        $faclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente) .
            ' AND fecha BETWEEN ' . $this->var2str($desde) . ' AND ' . $this->var2str($hasta) .
            ' AND codserie = ' . $this->var2str($serie);

        if ($obs !== '') {
            $sql .= ' AND lower(observaciones) = ' . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        $sql .= ' ORDER BY fecha DESC, codigo DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new self($f);
            }
        }

        return $faclist;
    }

    /**
     * Devuelve un array con los huecos en la numeración.
     *
     * @return mixed
     */
    public function huecos()
    {
        $error = true;
        $huecolist = $this->cache->get('factura_cliente_huecos');
        if ($error) {
            $huecolist = fsHuecosFacturasCliente($this->dataBase, $this->tableName());
            $this->cache->set('factura_cliente_huecos', $huecolist);
        }

        return $huecolist;
    }

    /**
     * TODO
     */
    private function cleanCache()
    {
        $this->cache->delete('factura_cliente_huecos');
    }
}
