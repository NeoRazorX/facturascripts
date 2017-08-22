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
 * Description of DocumentoVenta
 *
 * @author Carlos García Gómez
 */
trait DocumentoVenta
{

    /**
     * Identificador único de cara a humanos.
     * @var string
     */
    public $codigo;

    /**
     * Serie relacionada.
     * @var string
     */
    public $codserie;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Cliente del albarán.
     * @var string
     */
    public $codcliente;

    /**
     * Empleado que ha creado este albarán. Modelo agente.
     * @var string
     */
    public $codagente;

    /**
     * Forma de pago de este albarán.
     * @var string
     */
    public $codpago;

    /**
     * Divisa de este albarán.
     * @var string
     */
    public $coddivisa;

    /**
     * Almacén del que sale la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * País del cliente.
     * @var string
     */
    public $codpais;

    /**
     * ID de la dirección del cliente. Modelo direccion_cliente.
     * @var int
     */
    public $coddir;

    /**
     * Código postal del cliente.
     * @var string
     */
    public $codpostal;

    /**
     * Número de albarán.
     * Es único dentro de la serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número opcional a disposición del usuario.
     * @var string
     */
    public $numero2;

    /**
     * Nombre del cliente
     * @var string
     */
    public $nombrecliente;

    /**
     * CIF/NIF del cliente
     * @var string
     */
    public $cifnif;

    /**
     * Dirección del cliente
     * @var string
     */
    public $direccion;

    /**
     * Ciudad del cliente
     * @var string
     */
    public $ciudad;

    /**
     * Provincia del cliente
     * @var string
     */
    public $provincia;

    /**
     * Apartado de correos del cliente
     * @var string
     */
    public $apartado;

    /**
     * Fecha del albarán
     * @var string
     */
    public $fecha;

    /**
     * Hora del albarán
     * @var |DateTime('H:i:s')
     */
    public $hora;
    /// datos de transporte

    /**
     * Código de transportista para el envío
     * @var string
     */
    public $envio_codtrans;

    /**
     * Código de seguimiento del envío
     * @var string
     */
    public $envio_codigo;

    /**
     * Nombre de la dirección de envío
     * @var string
     */
    public $envio_nombre;

    /**
     * Apellidos de la dirección de envío
     * @var string
     */
    public $envio_apellidos;

    /**
     * Apartado de correos de la dirección de envío
     * @var string
     */
    public $envio_apartado;

    /**
     * Dirección de la dirección de envío
     * @var string
     */
    public $envio_direccion;

    /**
     * Código postal de la dirección de envío
     * @var string
     */
    public $envio_codpostal;

    /**
     * Ciudad de la dirección de envío
     * @var string
     */
    public $envio_ciudad;

    /**
     * Provincia de la dirección de envío
     * @var string
     */
    public $envio_provincia;

    /**
     * Código de país de la dirección de envío
     * @var string
     */
    public $envio_codpais;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     * @var float
     */
    public $neto;

    /**
     * Importe total del albarán, con impuestos.
     * @var float
     */
    public $total;

    /**
     * Suma total del IVA de las líneas.
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
     * % de comisión del empleado.
     * @var float
     */
    public $porcomision;

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
     * Fecha en la que se envió el albarán por email.
     * @var string
     */
    public $femail;

    /**
     * Número de documentos adjuntos.
     * @var int
     */
    public $numdocs;

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
    
    private function newCodigo()
    {
        $newCodigoDoc = new NewCodigoDoc();
        $this->numero = $newCodigoDoc->getNumero($this->tableName(), $this->codejercicio, $this->codserie);
        $this->codigo = $newCodigoDoc->getCodigo($this->tableName(), $this->numero);
    }
}
