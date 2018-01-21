<?php
/**
 * This file is part of FacturaScripts
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\NewCodigoDoc;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Ejercicio;

/**
 * Description of DocumentoVenta
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait DocumentoVenta
{

    use ModelTrait {
        clear as traitClear;
    }

    /**
     * Mail box of the client.
     *
     * @var string
     */
    public $apartado;

    /**
     * CIF / NIF of the client.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Customer's city
     *
     * @var string
     */
    public $ciudad;

    /**
     * Employee who created this document. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Warehouse from which the goods leave.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Customer of this document.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Currency of this document.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * ID of the customer's address. Customer_address model.
     *
     * @var int
     */
    public $coddir;

    /**
     * Related exercise. The one that corresponds to the date.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Unique identifier for humans.
     *
     * @var string
     */
    public $codigo;

    /**
     * Form of payment of this document.
     *
     * @var string
     */
    public $codpago;

    /**
     * Customer's country.
     *
     * @var string
     */
    public $codpais;

    /**
     * Customer's postal code.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Related serie.
     *
     * @var string
     */
    public $codserie;

    /**
     * Customer's address
     *
     * @var string
     */
    public $direccion;

    /**
     * Shipping code for the shipment.
     *
     * @var string
     */
    public $codtrans;

    /**
     * Shipping tracking code.
     *
     * @var string
     */
    public $codigoenv;

    /**
     * Name of the shipping address.
     *
     * @var string
     */
    public $nombreenv;

    /**
     * Last name of the shipping address.
     *
     * @var string
     */
    public $apellidosenv;

    /**
     * Post box of the shipping address.
     *
     * @var string
     */
    public $apartadoenv;

    /**
     * Address of the shipping address.
     *
     * @var string
     */
    public $direccionenv;

    /**
     * Postal code of the shipping address.
     *
     * @var string
     */
    public $codpostalenv;

    /**
     * City of the shipping address.
     *
     * @var string
     */
    public $ciudadenv;

    /**
     * Province of the shipping address.
     *
     * @var string
     */
    public $provinciaenv;

    /**
     * Country code of the shipping address.
     *
     * @var string
     */
    public $codpaisenv;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Date on which the document was sent by email.
     *
     * @var string
     */
    public $femail;

    /**
     * Time of the document.
     *
     * @var string
     */
    public $hora;

    /**
     * Company ID of the document.
     *
     * @var int
     */
    public $idempresa;

    /**
     * % IRPF retention of the delivery note. It is obtained from the series.
           * Each line can have a different %.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Sum of the pvptotal of lines. Total of the invoice before taxes.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Customer name.
     *
     * @var string
     */
    public $nombrecliente;

    /**
     * Delivery note number.
           * It is unique within the serie + exercise.
     *
     * @var string
     */
    public $numero;

    /**
     * Optional number available to the user.
     *
     * @var string
     */
    public $numero2;

    /**
     * % commission of the employee.
     *
     * @var float|int
     */
    public $porcomision;

    /**
     * Customer's province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Rate of conversion to Euros of the selected currency.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Total amount of the delivery note, with taxes.
     *
     * @var float|int
     */
    public $total;

    /**
     * Total sum of the VAT of the lines.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * Total expressed in euros, if it were not the currency of the delivery note.
           * totaleuros = total / tasaconv
           * It is not necessary to fill it, when doing save() the value is calculated.
     *
     * @var float|int
     */
    public $totaleuros;

    /**
     * Total sum of the IRPF withholdings of the lines.
     *
     * @var float|int
     */
    public $totalirpf;

    /**
     * Total sum of the equivalence surcharge of the lines.
     *
     * @var float|int
     */
    public $totalrecargo;

    /**
     * Observations of the document.
     *
     * @var string
     */
    public $observaciones;

    /**
     * indicates whether the document can be modified
     *
     * @var bool
     */
    public $editable;

    /**
     * Document state, from EstadoDocumento model.
     *
     * @var int
     */
    public $idestado;

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'codigo';
    }

    /**
     * Initializes document values.
     */
    private function clearDocumentoVenta()
    {
        $this->traitClear();
        $this->codalmacen = AppSettings::get('default', 'codalmacen');
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->codpago = AppSettings::get('default', 'codpago');
        $this->codserie = AppSettings::get('default', 'codserie');
        $this->editable = true;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->idempresa = AppSettings::get('default', 'idempresa');
        $this->irpf = 0.0;
        $this->neto = 0.0;
        $this->tasaconv = 1.0;
        $this->total = 0.0;
        $this->totaleuros = 0.0;
        $this->totalirpf = 0.0;
        $this->totaliva = 0.0;
        $this->totalrecargo = 0.0;
    }

    /**
     * Assign the customer to the document.
     *
     * @param Cliente $cliente
     */
    public function setCliente($cliente)
    {
        $this->codcliente = $cliente->codcliente;
        $this->nombrecliente = $cliente->razonsocial;
        $this->cifnif = $cliente->cifnif;
        foreach ($cliente->getDirecciones() as $dir) {
            $this->coddir = $dir->id;
            $this->codpais = $dir->codpais;
            $this->provincia = $dir->provincia;
            $this->ciudad = $dir->ciudad;
            $this->direccion = $dir->direccion;
            $this->codpostal = $dir->codpostal;
            $this->apartado = $dir->apartado;
            if ($dir->domfacturacion) {
                break;
            }
        }
    }

    /**
     * Assign the date and find an accounting exercise.
     *
     * @param string $fecha
     */
    public function setFecha($fecha)
    {
        $ejercicioModel = new Ejercicio();
        $ejercicio = $ejercicioModel->getByFecha($fecha);
        if ($ejercicio) {
            $this->codejercicio = $ejercicio->codejercicio;
        }
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();

            return $this->saveInsert();
        }

        return false;
    }

    /**
     * Generates a new code.
     */
    private function newCodigo()
    {
        $newCodigoDoc = new NewCodigoDoc();
        $this->numero = (string) $newCodigoDoc->getNumero(static::tableName(), $this->codejercicio, $this->codserie);
        $this->codigo = $newCodigoDoc->getCodigo(static::tableName(), $this->numero, $this->codserie, $this->codejercicio);
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     *
     * @return bool
     */
    private function testTrait()
    {
        $this->nombrecliente = static::noHtml($this->nombrecliente);
        if ($this->nombrecliente == '') {
            $this->nombrecliente = '-';
        }
        $this->direccion = static::noHtml($this->direccion);
        $this->ciudad = static::noHtml($this->ciudad);
        $this->provincia = static::noHtml($this->provincia);
        $this->nombreenv = static::noHtml($this->nombreenv);
        $this->apellidosenv = static::noHtml($this->apellidosenv);
        $this->direccionenv = static::noHtml($this->direccionenv);
        $this->ciudadenv = static::noHtml($this->ciudadenv);
        $this->provinciaenv = static::noHtml($this->provinciaenv);
        $this->numero2 = static::noHtml($this->numero2);
        $this->observaciones = static::noHtml($this->observaciones);

        /**
         * We use the euro as a bridge currency when adding, compare
                   * or convert amounts in several currencies. For this reason we need
                   * many decimals.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);
        if (static::floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true)) {
            return true;
        }

        self::$miniLog->alert(self::$i18n->trans('bad-total-error'));

        return false;
    }

    /**
     * Returns the lines associated with the document.
     *
     * @return array
     */
    abstract public function getLineas();
}
