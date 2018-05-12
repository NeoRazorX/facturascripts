<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Core\Lib\BusinessDocumentGenerator;

/**
 * Description of BusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocument extends ModelClass
{

    /**
     * VAT number of the supplier.
     *
     * @var string
     */
    public $cifnif;

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
     * Related serie.
     *
     * @var string
     */
    public $codserie;

    /**
     * indicates whether the document can be modified
     *
     * @var bool
     */
    public $editable;

    /**
     *
     * @var EstadoDocumento[]
     */
    private static $estados;

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
     * Document state, from EstadoDocumento model.
     *
     * @var int
     */
    public $idestado;

    /**
     *
     * @var int
     */
    private $idestadoAnt;

    /**
     * % IRPF retention of the document. It is obtained from the series.
     * Each line can have a different%.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Sum of the pvptotal of lines. Total of the document before taxes.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Number of the document.
     * Unique within the series + exercise.
     *
     * @var string
     */
    public $numero;

    /**
     * Notes of the document.
     *
     * @var string
     */
    public $observaciones;

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
     * Returns the lines associated with the document.
     *
     * @return mixed
     */
    abstract public function getLines();

    /**
     * Returns a new line for this business document.
     * 
     * @param array $data
     * 
     * @return BusinessDocumentLine[]
     */
    abstract public function getNewLine(array $data = []);

    /**
     * Returns an array with the column for identify the subject(s),
     * 
     * @return BusinessDocumentLine
     */
    abstract public function getSubjectColumns();

    /**
     * Sets subjects for this document.
     */
    abstract public function setSubject($subjects);

    /**
     * Updates subjects data in this document.
     */
    abstract public function updateSubject();

    /**
     * 
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->idestadoAnt = $this->idestado;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
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

        if (!isset(self::$estados)) {
            $estadoDocModel = new EstadoDocumento();
            self::$estados = $estadoDocModel->all([], [], 0, 0);
        }

        /// select default status
        foreach (self::$estados as $estado) {
            if ($estado->tipodoc === $this->modelClassName() && $estado->predeterminado) {
                $this->idestado = $estado->idestado;
                $this->editable = $estado->editable;
                break;
            }
        }
    }

    public function delete()
    {
        $lines = $this->getLines();
        if (parent::delete()) {
            foreach ($lines as $line) {
                $line->cantidad = 0;
                $line->updateStock($this->codalmacen);
            }

            return true;
        }

        return false;
    }

    /**
     * 
     * @return EstadoDocumento
     */
    public function getState()
    {
        foreach (self::$estados as $state) {
            if ($state->idestado === $this->idestado) {
                return $state;
            }
        }

        return new EstadoDocumento();
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new Serie();
        new Ejercicio();

        return '';
    }

    /**
     * 
     * @param string $cod
     * @param array  $where
     * @param array  $orderby
     * 
     * @return boolean
     */
    public function loadFromCode($cod, array $where = [], array $orderby = [])
    {
        if (parent::loadFromCode($cod, $where, $orderby)) {
            $this->idestadoAnt = $this->idestado;
            return true;
        }

        return false;
    }

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
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        if (is_null($this->codigo)) {
            $this->newCodigo();
        }

        if ($this->test()) {
            if ($this->exists()) {
                return $this->checkState() ? $this->saveUpdate() : false;
            }

            return $this->saveInsert();
        }

        return false;
    }

    /**
     * Assign the date and find an accounting exercise.
     * 
     * @param string $date
     * @param string $hour
     * 
     * @return bool
     */
    public function setDate(string $date, string $hour): bool
    {
        $ejercicioModel = new Ejercicio();
        $ejercicio = $ejercicioModel->getByFecha($date);
        if ($ejercicio) {
            $this->codejercicio = $ejercicio->codejercicio;
            $this->fecha = $date;
            $this->hora = $hour;
            return true;
        }

        return false;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->observaciones = Utils::noHtml($this->observaciones);

        /**
         * We use the euro as a bridge currency when adding, compare
         * or convert amounts in several currencies. For this reason we need
         * many decimals.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);
        if (!Utils::floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('bad-total-error'));
            return false;
        }

        $estadoDoc = new EstadoDocumento();
        if ($estadoDoc->loadFromCode($this->idestado)) {
            $this->editable = $estadoDoc->editable;
        }

        return parent::test();
    }

    private function checkState()
    {
        if ($this->idestado == $this->idestadoAnt) {
            return true;
        }

        $state = $this->getState();
        foreach ($this->getLines() as $line) {
            $line->actualizastock = $state->actualizastock;
            $line->save();
            $line->updateStock($this->codalmacen);
        }

        if (!empty($state->generadoc)) {
            $docGenerator = new BusinessDocumentGenerator();
            if (!$docGenerator->generate($this, $state->generadoc)) {
                return false;
            }
        }

        $this->idestadoAnt = $this->idestado;
        return true;
    }

    /**
     * Generates a new code.
     */
    private function newCodigo()
    {
        $this->numero = '1';

        $sql = "SELECT MAX(" . self::$dataBase->sql2Int('numero') . ") as num FROM " . static::tableName()
            . " WHERE codejercicio = " . self::$dataBase->var2str($this->codejercicio)
            . " AND codserie = " . self::$dataBase->var2str($this->codserie) . ";";

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            $this->numero = (string) (1 + (int) $data[0]['num']);
        }

        $this->codigo = $this->codejercicio . $this->codserie . $this->numero;
    }
}
