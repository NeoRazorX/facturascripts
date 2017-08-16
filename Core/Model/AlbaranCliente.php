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
 * Albarán de cliente o albarán de venta. Representa la entrega a un cliente
 * de un material que se le ha vendido. Implica la salida de ese material
 * del almacén de la empresa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranCliente
{

    use Base\DocumentoVenta;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria. Integer.
     * @var int
     */
    public $idalbaran;

    /**
     * ID de la factura relacionada, si la hay.
     * @var int
     */
    public $idfactura;

    /**
     * TRUE => está pendiente de factura.
     * @var bool
     */
    public $ptefactura;

    public function tableName()
    {
        return 'albaranescli';
    }

    public function primaryColumn()
    {
        return 'idalbaran';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->codserie = $this->defaultItems->codSerie();
        $this->codpago = $this->defaultItems->codPago();
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->tasaconv = 1.0;
        $this->ptefactura = true;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar los datos de los albaranes
     * @return string
     */
    public function url()
    {
        if ($this->idalbaran === null) {
            return 'index.php?page=VentasAlbaranes';
        }
        return 'index.php?page=VentasAlbaran&id=' . $this->idalbaran;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar los datos de la factura
     * @return string
     */
    public function facturaUrl()
    {
        if ($this->idfactura === null) {
            return '#';
        }
        return 'index.php?page=VentasFactura&id=' . $this->idfactura;
    }

    /**
     * Devuelve las líneas del albarán.
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranCliente();
        return $lineaModel->all(new DataBaseWhere('idalbaran', $this->idalbaran));
    }

    /**
     * Devuelve un albarán por su código si existe,
     * sino devuelve false
     *
     * @param string $cod
     *
     * @return AlbaranCliente|bool
     */
    public function getByCodigo($cod)
    {
        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE upper(codigo) = ' . strtoupper($this->var2str($cod)) . ';';
        $albaran = $this->dataBase->select($sql);
        if (!empty($albaran)) {
            return new AlbaranCliente($albaran[0]);
        }
        return false;
    }

    /**
     * Genera un nuevo código y número para este albarán
     */
    public function newCodigo()
    {
        $this->numero = fsDocumentoNewNumero(
            $this->dataBase, $this->tableName(), $this->codejercicio, $this->codserie, 'nalbarancli'
        );
        $this->codigo = fsDocumentoNewCodigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si son correctos
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

        if ($this->idfactura) {
            $this->ptefactura = false;
        }

        if ($this->floatcmp(
                $this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true
            )) {
            return true;
        }

        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Avisa al informático!');
        return false;
    }

    /**
     * Comprobaciones extra del albarán, devuelve TRUE si está correcto
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->getLineas() as $l) {
            if (!$l->test()) {
                $status = false;
            }

            $neto += $l->pvptotal;
            $iva += $l->pvptotal * $l->iva / 100;
            $irpf += $l->pvptotal * $l->irpf / 100;
            $recargo += $l->pvptotal * $l->recargo / 100;
        }

        $neto = round($neto, FS_NF0);
        $iva = round($iva, FS_NF0);
        $irpf = round($irpf, FS_NF0);
        $recargo = round($recargo, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor neto del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $neto
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totaliva del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $iva
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalirpf del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $irpf
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalrecargo del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $recargo
            );
            $status = false;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor total del ' . FS_ALBARAN . ' ' . $this->codigo . ' incorrecto. Valor correcto: ' . $total
            );
            $status = false;
        }

        if ($this->total !== 0) {
            /// comprobamos las facturas asociadas
            $lineaFactura = new LineaFacturaCliente();
            $facturas = $lineaFactura->facturasFromAlbaran($this->idalbaran);
            if (!empty($facturas)) {
                if (count($facturas) > 1) {
                    $msg = 'Este ' . FS_ALBARAN . ' esta asociado a las siguientes facturas (y no debería):';
                    foreach ($facturas as $f) {
                        if ($f instanceof FacturaCliente) {
                            $msg .= " <a href='" . $f->url() . "'>" . $f->codigo . '</a>';
                        }
                    }
                    $this->miniLog->alert($msg);
                    $status = false;
                } elseif ($facturas[0]->idfactura !== $this->idfactura) {
                    $this->miniLog->alert('Este ' . FS_ALBARAN . " esta asociado a una <a href='"
                        . $this->facturaUrl() . "'>factura</a> incorrecta. La correcta es <a href='"
                        . $facturas[0]->url() . "'>esta</a>.");
                    $status = false;
                }
            } elseif ($this->idfactura !== null) {
                $this->miniLog->alert('Este ' . FS_ALBARAN . " esta asociado a una <a href='" . $this->facturaUrl()
                    . "'>factura</a> que ya no existe.");
                $this->idfactura = null;
                $this->save();

                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha)
                . ' AND codcliente = ' . $this->var2str($this->codcliente)
                . ' AND total = ' . $this->var2str($this->total)
                . ' AND codagente = ' . $this->var2str($this->codagente)
                . ' AND numero2 = ' . $this->var2str($this->numero2)
                . ' AND observaciones = ' . $this->var2str($this->observaciones)
                . ' AND idalbaran != ' . $this->var2str($this->idalbaran) . ';';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasalbaranescli WHERE
                  idalbaran = ' . $this->var2str($this->idalbaran) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranescli
                  WHERE idalbaran = ' . $this->var2str($alb['idalbaran']) . ');';
                    $aux = $this->dataBase->select($sql);
                    if (!empty($aux)) {
                        $this->miniLog->alert('Este ' . FS_ALBARAN . " es un posible duplicado de
                     <a href='index.php?page=VentasAlbaran&id=" . $alb['idalbaran'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
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
     * Devuelve un array con todos los albaranes que coinciden con $query
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alblist = [];
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
            foreach ($data as $a) {
                $alblist[] = new AlbaranCliente($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del cliente $codcliente que coincidan
     * con los filtros.
     *
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $obs
     * @param string $coddivisa
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $desde, $hasta, $codserie = '', $obs = '', $coddivisa = '')
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente)
            . ' AND ptefactura AND fecha BETWEEN ' . $this->var2str($desde) . ' AND ' . $this->var2str($hasta);

        if ($codserie !== '') {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }

        if ($obs !== '') {
            $sql .= ' AND lower(observaciones) = ' . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        if ($coddivisa !== '') {
            $sql .= ' AND coddivisa = ' . $this->var2str($coddivisa);
        }

        $sql .= ' ORDER BY fecha ASC, codigo ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranCliente($a);
            }
        }

        return $albalist;
    }

    /**
     * TODO
     */
    public function cronJob()
    {
        /**
         * Ponemos a NULL todos los idfactura que no están en facturascli.
         * ¿Por qué? Porque muchos usuarios se dedican a tocar la base de datos.
         */
        $this->dataBase->exec('UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturascli);');
    }
}
