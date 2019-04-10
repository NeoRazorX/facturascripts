<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of BusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocument extends ModelOnChangeClass
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
     * Document status, from EstadoDocumento model.
     *
     * @var int
     */
    public $idestado;

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
     * User who created this document. User model.
     *
     * @var string
     */
    public $nick;

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
     * Paid.
     *
     * @var bool
     */
    public $pagado;

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
     */
    abstract public function getLines();

    /**
     * Returns a new line for this business document.
     */
    abstract public function getNewLine(array $data = []);

    /**
     * Sets subject for this document.
     */
    abstract public function setSubject($subject);

    /**
     * Updates subjects data in this document.
     */
    abstract public function updateSubject();

    /**
     *
     * @return BusinessDocument[]
     */
    public function childrenDocuments()
    {
        $children = [];

        $keys = [];
        $docTransformation = new DocTransformation();
        $where = [
            new DataBaseWhere('model1', $this->modelClassName()),
            new DataBaseWhere('iddoc1', $this->primaryColumnValue())
        ];
        foreach ($docTransformation->all($where, [], 0, 0) as $docTrans) {
            $key = $docTrans->model2 . '|' . $docTrans->iddoc2;
            if (in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $docTrans->model2;
            $newModel = new $newModelClass();
            if ($newModel->loadFromCode($docTrans->iddoc2)) {
                $children[] = $newModel;
                $keys[] = $key;
            }
        }

        return $children;
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
        $this->pagado = false;
        $this->tasaconv = 1.0;
        $this->total = 0.0;
        $this->totaleuros = 0.0;
        $this->totalirpf = 0.0;
        $this->totaliva = 0.0;
        $this->totalrecargo = 0.0;

        /// select default status
        foreach ($this->getAvaliableStatus() as $status) {
            if ($status->predeterminado) {
                $this->idestado = $status->idestado;
                $this->editable = $status->editable;
                break;
            }
        }
    }

    /**
     *
     * @return bool
     */
    public function delete()
    {
        $lines = $this->getLines();
        if (!parent::delete()) {
            return false;
        }

        /// update stock
        foreach ($lines as $line) {
            $line->delete();
        }

        /// change parent doc status
        foreach ($this->parentDocuments() as $parent) {
            foreach ($parent->getAvaliableStatus() as $status) {
                if ($status->predeterminado) {
                    $parent->idestado = $status->idestado;
                    $parent->save();
                    break;
                }
            }
        }

        /// remove data from DocTransformation
        $docTransformation = new DocTransformation();
        $docTransformation->deleteFrom($this->modelClassName(), $this->primaryColumnValue());

        return true;
    }

    /**
     *
     * @return EstadoDocumento[]
     */
    public function getAvaliableStatus()
    {
        if (!isset(self::$estados)) {
            $statusModel = new EstadoDocumento();
            self::$estados = $statusModel->all([], [], 0, 0);
        }

        $avaliables = [];
        foreach (self::$estados as $status) {
            if ($status->tipodoc === $this->modelClassName()) {
                $avaliables[] = $status;
            }
        }

        return $avaliables;
    }

    /**
     *
     * @return Empresa
     */
    public function getCompany()
    {
        $empresa = new Empresa();
        $empresa->loadFromCode($this->idempresa);
        return $empresa;
    }

    /**
     *
     * @param string $reference
     *
     * @return BusinessDocumentLine
     */
    public function getNewProductLine($reference)
    {
        $newLine = $this->getNewLine();

        $variant = new Variante();
        $where = [new DataBaseWhere('referencia', $reference)];
        if ($variant->loadFromCode('', $where)) {
            $product = $variant->getProducto();
            $impuesto = $product->getImpuesto();

            $newLine->cantidad = 1;
            $newLine->codimpuesto = $impuesto->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $impuesto->iva;
            $newLine->pvpunitario = $variant->precio;
            $newLine->recargo = $impuesto->recargo;
            $newLine->referencia = $variant->referencia;
        }

        return $newLine;
    }

    /**
     *
     * @return EstadoDocumento
     */
    public function getStatus()
    {
        foreach ($this->getAvaliableStatus() as $status) {
            /// don't use ===
            if ($status->idestado == $this->idestado) {
                return $status;
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
        /// needed dependencies
        new Serie();
        new Ejercicio();
        new Almacen();

        return parent::install();
    }

    /**
     *
     * @return BusinessDocument[]
     */
    public function parentDocuments()
    {
        $parents = [];

        $keys = [];
        $docTransformation = new DocTransformation();
        $where = [
            new DataBaseWhere('model2', $this->modelClassName()),
            new DataBaseWhere('iddoc2', $this->primaryColumnValue())
        ];
        foreach ($docTransformation->all($where, [], 0, 0) as $docTrans) {
            $key = $docTrans->model1 . '|' . $docTrans->iddoc1;
            if (in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $docTrans->model1;
            $newModel = new $newModelClass();
            if ($newModel->loadFromCode($docTrans->iddoc1)) {
                $parents[] = $newModel;
                $keys[] = $key;
            }
        }

        return $parents;
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
        /// check accounting exercise
        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha, $this->hora);
        }

        /// empty code?
        if (empty($this->codigo)) {
            BusinessDocumentCode::getNewCode($this);
        }

        /// match editable with status
        $status = $this->getStatus();
        $this->editable = $status->editable;

        return parent::save();
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
        /// force check of warehouse-company relation
        if (!$this->setWarehouse($this->codalmacen)) {
            return false;
        }

        $ejercicio = new Ejercicio();
        $ejercicio->idempresa = $this->idempresa;
        if ($ejercicio->loadFromDate($date)) {
            $this->codejercicio = $ejercicio->codejercicio;
            $this->fecha = $date;
            $this->hora = $hour;
            return true;
        }

        self::$miniLog->warning(self::$i18n->trans('accounting-exercise-not-found'));
        return false;
    }

    /**
     * 
     * @param string $codalmacen
     *
     * @return bool
     */
    public function setWarehouse($codalmacen)
    {
        $almacen = new Almacen();
        if ($almacen->loadFromCode($codalmacen)) {
            $this->codalmacen = $almacen->codalmacen;
            $this->idempresa = $almacen->idempresa ?? $this->idempresa;
            return true;
        }

        self::$miniLog->warning(self::$i18n->trans('warehouse-not-found'));
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

        /// check ammount
        if (!Utils::floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('bad-total-error'));
            return false;
        }

        return parent::test();
    }

    /**
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (!$this->editable && !$this->previousData['editable'] && $field != 'idestado') {
            self::$miniLog->warning(self::$i18n->trans('non-editable-document'));
            return false;
        }

        switch ($field) {
            case 'codalmacen':
            case 'idempresa':
                self::$miniLog->warning(self::$i18n->trans('non-editable-columns', ['%columns%' => 'codalmacen,idempresa']));
                return false;

            case 'codejercicio':
            case 'codserie':
                BusinessDocumentCode::getNewCode($this);
                break;

            case 'idestado':
                $status = $this->getStatus();
                foreach ($this->getLines() as $line) {
                    $line->actualizastock = $status->actualizastock;
                    $line->save();
                }
                $docGenerator = new BusinessDocumentGenerator();
                if (!empty($status->generadoc) && !$docGenerator->generate($this, $status->generadoc)) {
                    return false;
                }
                break;
        }

        return parent::onChange($field);
    }
}
