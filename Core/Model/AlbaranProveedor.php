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
 * Albarán de proveedor o albarán de compra. Representa la recepción
 * de un material que se ha comprado. Implica la entrada de ese material
 * al almacén.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranProveedor
{

    use Base\DocumentoCompra;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria. Integer
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
        return 'albaranesprov';
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
     * Devuelve la url donde ver/modificar los datos de la factura
     * @return string
     */
    public function facturaUrl()
    {
        if ($this->idfactura === null) {
            return '#';
        }
        return 'index.php?page=ComprasFactura&id=' . $this->idfactura;
    }

    /**
     * Devuelve la url donde ver/modificar los datos de agentes
     * @return string
     */
    public function agenteUrl()
    {
        if ($this->codagente === null) {
            return 'index.php?page=AdminAgentes';
        }
        return 'index.php?page=AdminAgente&cod=' . $this->codagente;
    }

    /**
     * Devuelve la url donde ver/modificar los datos de proveedores
     * @return string
     */
    public function proveedorUrl()
    {
        if ($this->codproveedor === null) {
            return 'index.php?page=ComprasProveedores';
        }
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * Devuelve las líneas asociadas al albarán
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranProveedor();
        return $lineaModel->all(new DataBaseWhere('idalbaran', $this->idalbaran));
    }

    /**
     * Genera un nuevo código y número para el albarán
     */
    public function newCodigo()
    {
        $this->numero = fsDocumentoNewNumero(
            $this->dataBase, $this->tableName(), $this->codejercicio, $this->codserie, 'nalbaranprov'
        );
        $this->codigo = fsDocumentoNewCodigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si está correcto
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);
        if ($this->nombre === '') {
            $this->nombre = '-';
        }

        $this->numproveedor = self::noHtml($this->numproveedor);
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
     * Comprobaciones extra para el albarán. Devuelve TRUE si está correcto
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
            $lineaFactura = new LineaFacturaProveedor();
            $facturas = $lineaFactura->facturasFromAlbaran($this->idalbaran);
            if (!empty($facturas)) {
                if (count($facturas) > 1) {
                    $msg = 'Este ' . FS_ALBARAN . ' esta asociado a las siguientes facturas (y no debería):';
                    foreach ($facturas as $f) {
                        if ($f instanceof FacturaProveedor) {
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
                    . "'>factura</a> que ya no existe. <b>Corregido</b>.");
                $this->idfactura = null;
                $this->save();

                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha)
                . ' AND codproveedor = ' . $this->var2str($this->codproveedor)
                . ' AND total = ' . $this->var2str($this->total)
                . ' AND codagente = ' . $this->var2str($this->codagente)
                . ' AND numproveedor = ' . $this->var2str($this->numproveedor)
                . ' AND observaciones = ' . $this->var2str($this->observaciones)
                . ' AND idalbaran != ' . $this->var2str($this->idalbaran) . ';';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasalbaranesprov WHERE
                  idalbaran = ' . $this->var2str($this->idalbaran) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranesprov
                  WHERE idalbaran = ' . $this->var2str($alb['idalbaran']) . ');';
                    $aux = $this->dataBase->select($sql);
                    if (!empty($aux)) {
                        $this->miniLog->alert('Este ' . FS_ALBARAN . " es un posible duplicado de
                     <a href='index.php?page=ComprasAlbaran&id=" . $alb['idalbaran'] . "'>este otro</a>.
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
     * Elimina el albarán de la base de datos
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idalbaran = ' . $this->var2str($this->idalbaran) . ';';
        if ($this->dataBase->exec($sql)) {
            if ($this->idfactura) {
                /**
                 * Delegamos la eliminación de la factura en la clase correspondiente,
                 * que tendrá que hacer más cosas.
                 */
                $factura = new FacturaProveedor();
                $factura0 = $factura->get($this->idfactura);
                if ($factura0) {
                    $factura0->delete();
                }
            }

            $this->miniLog->info(ucfirst(FS_ALBARAN) . ' de compra ' . $this->codigo . ' eliminado correctamente.');
            return true;
        }
        return false;
    }

    /**
     * Devuelve un array con los albaranes que coinciden con $query
     *
     * @param string $query
     * @param integer $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alblist = [];
        $query = self::noHtml(mb_strtolower($query, 'UTF8'));

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranProveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del proveedor $codproveedor
     * que coincidan con los filtros.
     *
     * @param string $codproveedor
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $coddivisa
     *
     * @return array
     */
    public function searchFromProveedor($codproveedor, $desde, $hasta, $codserie = '', $coddivisa = '')
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = '
            . $this->var2str($codproveedor) . ' AND ptefactura AND fecha BETWEEN '
            . $this->var2str($desde) . ' AND ' . $this->var2str($hasta);

        if ($codserie) {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }

        if ($coddivisa) {
            $sql .= ' AND coddivisa = ' . $this->var2str($coddivisa);
        }

        $sql .= ' ORDER BY fecha ASC, codigo ASC';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranProveedor($a);
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
         * Ponemos a NULL todos los idfactura que no están en facturasprov
         */
        $sql = 'UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturasprov);';
        $this->dataBase->exec($sql);
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    public function install()
    {
        /// nos aseguramos de que se comprueban las tablas de facturas y series antes
        // new Serie();
        // new FacturaProveedor();

        return '';
    }
}
