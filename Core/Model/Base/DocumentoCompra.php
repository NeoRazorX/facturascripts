<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

namespace FacturaScripts\Core\Model\Base;

/**
 * Description of DocumentoCompra
 *
 * @author Carlos García Gómez
 */
trait DocumentoCompra
{
    /**
     * Identificador único de cara a humanos.
     *
     * @var string
     */
    public $codigo;

    /**
     * Número del albarán.
     * Único dentro de la serie+ejercicio.
     *
     * @var string
     */
    public $numero;

    /**
     * Número de albarán de proveedor, si lo hay.
     * Puede contener letras.
     *
     * @var string
     */
    public $numproveedor;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Serie relacionada.
     *
     * @var string
     */
    public $codserie;

    /**
     * Divisa del albarán.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Forma de pago asociada.
     *
     * @var string
     */
    public $codpago;

    /**
     * Empleado que ha creado este albarán.
     *
     * @var string
     */
    public $codagente;

    /**
     * Almacén en el que entra la mercancía.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Fecha del albarán
     *
     * @var string
     */
    public $fecha;

    /**
     * Hora del albarán
     *
     * @var string('H:i:s')
     */
    public $hora;

    /**
     * Código del proveedor de este albarán.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Nombre del proveedor
     *
     * @var string
     */
    public $nombre;

    /**
     * CIF/NIF del proveedor
     *
     * @var string
     */
    public $cifnif;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     *
     * @var float
     */
    public $neto;

    /**
     * Suma total del albarán, con impuestos.
     *
     * @var float
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     *
     * @var float
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     *
     * @var float
     */
    public $totaleuros;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     *
     * @var float
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     *
     * @var float
     */
    public $totalirpf;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     *
     * @var float
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     *
     * @var float
     */
    public $totalrecargo;

    /**
     * Observaciones del albarán
     *
     * @var string
     */
    public $observaciones;

    /**
     * Número de documentos adjuntos.
     *
     * @var int
     */
    public $numdocs;

    abstract public function tableName();

    public function observaciones_resume()
    {
        if ($this->observaciones == '') {
            return '-';
        } elseif (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return substr($this->observaciones, 0, 50) . '...';
    }

    private function newCodigo()
    {
        $newCodigoDoc = new NewCodigoDoc();
        $this->numero = $newCodigoDoc->getNumero($this->tableName(), $this->codejercicio, $this->codserie);
        $this->codigo = $newCodigoDoc->getCodigo($this->tableName(), $this->numero);
    }
}
