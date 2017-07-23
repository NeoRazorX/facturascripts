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

use FacturaScripts\Core\Base\Model;

/**
 * Albarán de proveedor o albarán de compra. Representa la recepción
 * de un material que se ha comprado. Implica la entrada de ese material
 * al almacén.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranProveedor
{
    use Model;

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
     * Identificador único de cara a humanos.
     * @var string
     */
    public $codigo;

    /**
     * Número del albarán.
     * Único dentro de la serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número de albarán de proveedor, si lo hay.
     * Puede contener letras.
     * @var string
     */
    public $numproveedor;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Serie relacionada.
     * @var string
     */
    public $codserie;

    /**
     * Divisa del albarán.
     * @var string
     */
    public $coddivisa;

    /**
     * Forma de pago asociada.
     * @var string
     */
    public $codpago;

    /**
     * Empleado que ha creado este albarán.
     * @var string
     */
    public $codagente;

    /**
     * Almacén en el que entra la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * Fecha del albarán
     * @var \DateTime('d-m-Y')
     */
    public $fecha;

    /**
     * Hora del albarán
     * @var \DateTime('H:i:s')
     */
    public $hora;

    /**
     * Código del proveedor de este albarán.
     * @var string
     */
    public $codproveedor;

    /**
     * Nombre del proveedor
     * @var string
     */
    public $nombre;

    /**
     * CIF/NIF del proveedor
     * @var string
     */
    public $cifnif;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     * @var float
     */
    public $neto;

    /**
     * Suma total del albarán, con impuestos.
     * @var float
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     * @var float
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var float
     */
    public $totaleuros;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var float
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     * @var float
     */
    public $totalirpf;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     * @var float
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     * @var float
     */
    public $totalrecargo;

    /**
     * Observaciones del albarán
     * @var string
     */
    public $observaciones;

    /**
     * TRUE => está pendiente de factura.
     * @var bool
     */
    public $ptefactura;

    /**
     * Número de documentos adjuntos.
     * @var int
     */
    public $numdocs;

    /**
     * AlbaranProveedor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'albaranesprov', 'idalbaran');
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
        $this->idalbaran = null;
        $this->idfactura = null;
        $this->codigo = '';
        $this->numero = '';
        $this->numproveedor = '';
        $this->codejercicio = null;
        $this->codserie = $this->defaultItems->codSerie();
        $this->coddivisa = null;
        $this->codpago = $this->defaultItems->codPago();
        $this->codagente = null;
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->codproveedor = null;
        $this->nombre = '';
        $this->cifnif = '';
        $this->neto = 0;
        $this->total = 0;
        $this->totaliva = 0;
        $this->totaleuros = 0;
        $this->irpf = 0;
        $this->totalirpf = 0;
        $this->tasaconv = 1;
        $this->totalrecargo = 0;
        $this->observaciones = '';
        $this->ptefactura = true;

        $this->numdocs = 0;
    }

    /**
     * Acorta el texto de observaciones
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones === '') {
            return '-';
        }
        if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }
        return substr($this->observaciones, 0, 50) . '...';
    }

    /**
     * Devuelve la url donde ver/modificar los datos de albaranes
     * @return string
     */
    public function url()
    {
        if ($this->idalbaran === null) {
            return 'index.php?page=ComprasAlbaranes';
        }
        return 'index.php?page=ComprasAlbaran&id=' . $this->idalbaran;
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
        $linea = new LineaAlbaranProveedor();
        return $linea->allFromAlbaran($this->idalbaran);
    }

    /**
     * Genera un nuevo código y número para el albarán
     */
    public function newCodigo()
    {
        $this->numero = fsDocumentoNewNumero(
            $this->database,
            $this->tableName(),
            $this->codejercicio,
            $this->codserie,
            'nalbaranprov'
        );
        $this->codigo = fsDocumentoNewCodigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si está correcto
     * @return bool
     */
    public function test()
    {
        $this->nombre = static::noHtml($this->nombre);
        if ($this->nombre === '') {
            $this->nombre = '-';
        }

        $this->numproveedor = static::noHtml($this->numproveedor);
        $this->observaciones = static::noHtml($this->observaciones);

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
            $this->total,
            $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo,
            FS_NF0,
            true
        )) {
            return true;
        }

        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Avisa al informático!');
        return false;
    }

    /**
     * Comprobaciones extra para el albarán. Devuelve TRUE si está correcto
     *
     * @param $duplicados
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
            $linea_factura = new LineaFacturaProveedor();
            $facturas = $linea_factura->facturasFromAlbaran($this->idalbaran);
            if ($facturas) {
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
            $data = $this->database->select($sql);
            if (!empty($data)) {
                foreach ($data as $alb) {
                    /// comprobamos las líneas
                    $aux = $this->database->select('SELECT referencia FROM lineasalbaranesprov WHERE
                  idalbaran = ' . $this->var2str($this->idalbaran) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasalbaranesprov
                  WHERE idalbaran = ' . $this->var2str($alb['idalbaran']) . ');');
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

    /**
     * Inserta los datos del modelo en la base de datos.
     * @return bool
     */
    public function saveInsert()
    {
        $this->newCodigo();
        $sql = 'INSERT INTO ' . $this->tableName() . ' (codigo,numero,numproveedor,
               codejercicio,codserie,coddivisa,codpago,codagente,codalmacen,fecha,codproveedor,
               nombre,cifnif,neto,total,totaliva,totaleuros,irpf,totalirpf,tasaconv,
               totalrecargo,observaciones,ptefactura,hora,numdocs) VALUES
                      (' . $this->var2str($this->codigo)
            . ', ' . $this->var2str($this->numero)
            . ', ' . $this->var2str($this->numproveedor)
            . ', ' . $this->var2str($this->codejercicio)
            . ', ' . $this->var2str($this->codserie)
            . ', ' . $this->var2str($this->coddivisa)
            . ', ' . $this->var2str($this->codpago)
            . ', ' . $this->var2str($this->codagente)
            . ', ' . $this->var2str($this->codalmacen)
            . ', ' . $this->var2str($this->fecha)
            . ', ' . $this->var2str($this->codproveedor)
            . ', ' . $this->var2str($this->nombre)
            . ', ' . $this->var2str($this->cifnif)
            . ', ' . $this->var2str($this->neto)
            . ', ' . $this->var2str($this->total)
            . ', ' . $this->var2str($this->totaliva)
            . ', ' . $this->var2str($this->totaleuros)
            . ', ' . $this->var2str($this->irpf)
            . ', ' . $this->var2str($this->totalirpf)
            . ', ' . $this->var2str($this->tasaconv)
            . ', ' . $this->var2str($this->totalrecargo)
            . ', ' . $this->var2str($this->observaciones)
            . ', ' . $this->var2str($this->ptefactura)
            . ', ' . $this->var2str($this->hora)
            . ', ' . $this->var2str($this->numdocs) . ');';

        if ($this->database->exec($sql)) {
            $this->idalbaran = $this->database->lastval();
            return true;
        }
        return false;
    }

    /**
     * Elimina el albarán de la base de datos
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idalbaran = ' . $this->var2str($this->idalbaran) . ';';
        if ($this->database->exec($sql)) {
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
     * Devuelve un array con los albaranes pendientes
     *
     * @param int $offset
     * @param string $order
     * @param int $limit
     *
     * @return array
     */
    public function allPtefactura($offset = 0, $order = 'fecha ASC, codigo ASC', $limit = FS_ITEM_LIMIT)
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE ptefactura = TRUE ORDER BY ' . $order;

        $data = $this->database->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new AlbaranProveedor($a);
            }
        }

        return $albalist;
    }

    /**
     * Devuelve un array con los albaranes del proveedor
     *
     * @param string $codproveedor
     * @param int $offset
     *
     * @return array
     */
    public function allFromProveedor($codproveedor, $offset = 0)
    {
        $alblist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = '
            . $this->var2str($codproveedor) . ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranProveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del agente/empleado
     *
     * @param string $codagente
     * @param int $offset
     *
     * @return array
     */
    public function allFromAgente($codagente, $offset = 0)
    {
        $alblist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codagente = '
            . $this->var2str($codagente) . ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranProveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes relacionados con la factura $id
     *
     * @param int $id
     *
     * @return array
     */
    public function allFromFactura($id)
    {
        $alblist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idfactura = '
            . $this->var2str($id) . ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranProveedor($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes comprendidos entre $desde y $hasta
     *
     * @param string $desde
     * @param string $hasta
     *
     * @return array
     */
    public function allDesde($desde, $hasta)
    {
        $alblist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha >= '
            . $this->var2str($desde) . ' AND fecha <= ' . $this->var2str($hasta)
            . ' ORDER BY codigo ASC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new AlbaranProveedor($a);
            }
        }

        return $alblist;
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
        $query = static::noHtml(mb_strtolower($query, 'UTF8'));

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->database->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
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

        $data = $this->database->select($sql);
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
        $this->database->exec($sql);
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        /// nos aseguramos de que se comprueban las tablas de facturas y series antes
        // new Serie();
        // new FacturaProveedor();

        return '';
    }
}
