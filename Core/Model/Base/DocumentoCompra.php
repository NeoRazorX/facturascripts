<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Proveedor;

/**
 * Description of DocumentoCompra
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait DocumentoCompra
{

    use ModelTrait {
        clear as traitClear;
    }

    /**
     * VAT number of the supplier.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Employee who created this document.
     *
     * @var string
     */
    public $codagente;

    /**
     * Warehouse in which the merchandise enters.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Currency of the document.
     *
     * @var string
     */
    public $coddivisa;

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
     * Payment method associated.
     *
     * @var string
     */
    public $codpago;

    /**
     * Supplier code for this document.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Related serie.
     *
     * @var string
     */
    public $codserie;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Document time.
     *
     * @var string
     */
    public $hora;

    /**
     * Company id. of the document.
     *
     * @var int
     */
    public $idempresa;

    /**
     * % IRPF retention of the document. It is obtained from the series.
           * Each line can have a different%.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Provider's name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Number of the document.
           * Unique within the series + exercise.
     *
     * @var string
     */
    public $numero;

    /**
     * Supplier's document number, if any.
           * May contain letters.
     *
     * @var string
     */
    public $numproveedor;

    /**
     * Sum of the pvptotal of lines. Total of the document before taxes.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Rate of conversion to Euros of the selected currency.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Total sum of the document, with taxes.
     *
     * @var float|int
     */
    public $total;

    /**
     * Sum of the VAT of the lines.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * Total expressed in euros, if it were not the currency of the document.
           * totaleuros = total / tasaconv
           * It is not necessary to fill it, when doing save () the value is calculated.
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
     * Notes of the document.
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
    private function clearDocumentoCompra()
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
     * Assign the supplier to the document.
     *
     * @param Proveedor $proveedor
     */
    public function setProveedor($proveedor)
    {
        $this->codproveedor = $proveedor->codproveedor;
        $this->nombre = $proveedor->razonsocial;
        $this->cifnif = $proveedor->cifnif;
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
        $this->nombre = static::noHtml($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }
        $this->numproveedor = static::noHtml($this->numproveedor);
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
