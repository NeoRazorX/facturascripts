<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Description of BusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocument extends ModelOnChangeClass
{
    use CompanyRelationTrait;
    use CurrencyRelationTrait;
    use ExerciseRelationTrait;
    use PaymentRelationTrait;
    use SerieRelationTrait;
    use IntracomunitariaTrait;

    /**
     * VAT number of the customer or supplier.
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
     * Unique identifier for humans.
     *
     * @var string
     */
    public $codigo;

    /** @var array */
    protected static $dont_copy_fields = ['codejercicio', 'codigo', 'codigorect', 'fecha', 'femail', 'hora',
        'idasiento', 'idestado', 'idfacturarect', 'neto', 'netosindto', 'numero', 'pagada', 'total', 'totalirpf',
        'totaliva', 'totalrecargo', 'totalsuplidos'];

    /**
     * Percentage of discount.
     *
     * @var float
     */
    public $dtopor1;

    /**
     * Percentage of discount.
     *
     * @var float
     */
    public $dtopor2;

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
     * Default retention for this document. Each line can have a different retention.
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
     * Sum of the pvptotal of lines. Total of the document before taxes and global discounts.
     *
     * @var float|int
     */
    public $netosindto;

    /**
     * User who created this document. User model.
     *
     * @var string
     */
    public $nick;

    /**
     * Number of the document. Unique within the series.
     *
     * @var string
     */
    public $numero;

    /**
     * Number of attached documents.
     *
     * @var int
     */
    public $numdocs;

    /**
     * Notes of the document.
     *
     * @var string
     */
    public $observaciones;

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
     * Total sum of supplied lines.
     *
     * @var float|int
     */
    public $totalsuplidos;

    /**
     * Returns the lines associated with the document.
     */
    abstract public function getLines(): array;

    /**
     * Returns a new line for this business document.
     */
    abstract public function getNewLine(array $data = [], array $exclude = []);

    /**
     * Returns a new line for this business document completed with the product data.
     */
    abstract public function getNewProductLine($reference);

    /**
     * Returns the subject of this document.
     */
    abstract public function getSubject();

    /**
     * Sets the author for this document.
     */
    abstract public function setAuthor($user): bool;

    /**
     * Sets subject for this document.
     */
    abstract public function setSubject($subject): bool;

    /**
     * Returns the name of the column for subject.
     */
    abstract public function subjectColumn();

    /**
     * Updates subjects data in this document.
     */
    abstract public function updateSubject(): bool;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();

        $this->codalmacen = Tools::settings('default', 'codalmacen');
        $this->codpago = Tools::settings('default', 'codpago');
        $this->codserie = Tools::settings('default', 'codserie');
        $this->dtopor1 = 0.0;
        $this->dtopor2 = 0.0;
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->irpf = 0.0;
        $this->neto = 0.0;
        $this->netosindto = 0.0;
        $this->numero = 1;
        $this->total = 0.0;
        $this->totaleuros = 0.0;
        $this->totalirpf = 0.0;
        $this->totaliva = 0.0;
        $this->totalrecargo = 0.0;
        $this->totalsuplidos = 0.0;
        $this->numdocs = 0;
    }

    public static function dontCopyField(string $field): void
    {
        static::$dont_copy_fields[] = $field;
    }

    public static function dontCopyFields(): array
    {
        $more = [static::primaryColumn()];
        return array_merge(static::$dont_copy_fields, $more);
    }

    public function getAttachedFiles(): array
    {
        $relationModel = new AttachedFileRelation();
        $where = [new DataBaseWhere('model', $this->modelClassName())];
        $where[] = is_numeric($this->primaryColumnValue()) ?
            new DataBaseWhere('modelid|modelcode', $this->primaryColumnValue()) :
            new DataBaseWhere('modelcode', $this->primaryColumnValue());
        return $relationModel->all($where, ['creationdate' => 'DESC'], 0, 0);
    }

    /**
     * Returns the Equivalent Unified Discount.
     *
     * @return float
     */
    public function getEUDiscount()
    {
        $eud = 1.0;
        foreach ([$this->dtopor1, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install(): string
    {
        // needed dependencies
        new Serie();
        new Ejercicio();
        new Almacen();
        new Divisa();
        new FormaPago();

        return parent::install();
    }

    public function paid(): bool
    {
        return false;
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        return 'codigo';
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save(): bool
    {
        // check accounting exercise
        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha, $this->hora);
        }

        // empty code?
        if (empty($this->codigo)) {
            BusinessDocumentCode::setNewCode($this);
        }

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
        // force check of warehouse-company relation
        if (false === $this->setWarehouse($this->codalmacen)) {
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

        Tools::log()->warning('accounting-exercise-not-found');
        return false;
    }

    /**
     * Sets warehouse and company for this document.
     *
     * @param string $codalmacen
     *
     * @return bool
     */
    public function setWarehouse(string $codalmacen): bool
    {
        foreach (Almacenes::all() as $almacen) {
            if ($almacen->codalmacen == $codalmacen) {
                $this->codalmacen = $almacen->codalmacen;
                $this->idempresa = $almacen->idempresa ?? $this->idempresa;
                return true;
            }
        }

        Tools::log()->warning('warehouse-not-found');
        return false;
    }

    /**
     * @return string
     */
    public function subjectColumnValue()
    {
        return $this->{$this->subjectColumn()};
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->observaciones = Tools::noHtml($this->observaciones);

        // check number
        if ((int)$this->numero < 1) {
            Tools::log()->error('invalid-number', ['%number%' => $this->numero]);
            return false;
        }

        // check exercise and date
        if (false === $this->hasChanged('fecha') && false === $this->getExercise()->inRange($this->fecha)) {
            Tools::log()->error('date-out-of-exercise-range', ['%exerciseName%' => $this->codejercicio]);
            return false;
        }

        // check total
        $total = $this->neto + $this->totalsuplidos + $this->totaliva - $this->totalirpf + $this->totalrecargo;
        if (false === Utils::floatcmp($this->total, $total, FS_NF0, true)) {
            Tools::log()->error('bad-total-error');
            return false;
        }

        /**
         * We use the euro as a bridge currency when adding, compare
         * or convert amounts in several currencies. For this reason we need
         * many decimals.
         */
        $this->totaleuros = empty($this->tasaconv) ? 0 : round($this->total / $this->tasaconv, 5);

        return parent::test();
    }

    /**
     * Check changed fields before update the database.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'codalmacen':
                foreach ($this->getLines() as $line) {
                    $line->transfer($this->previousData['codalmacen'], $this->codalmacen);
                }
                break;

            case 'codserie':
                BusinessDocumentCode::setNewCode($this);
                break;

            case 'fecha':
                $oldCodejercicio = $this->codejercicio;
                if (false === $this->setDate($this->fecha, $this->hora)) {
                    return false;
                } elseif ($this->codejercicio != $oldCodejercicio) {
                    BusinessDocumentCode::setNewCode($this);
                }
                break;

            case 'idempresa':
                Tools::log()->warning('non-editable-columns', ['%columns%' => 'idempresa']);
                return false;

            case 'numero':
                BusinessDocumentCode::setNewCode($this, false);
                break;
        }

        return parent::onChange($field);
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = []): bool
    {
        if (false === parent::saveUpdate($values)) {
            return false;
        }

        // add audit log
        Tools::log(self::AUDIT_CHANNEL)->info('updated-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->primaryColumnValue(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->primaryColumnValue(),
            'model-data' => $this->toArray()
        ]);
        return true;
    }

    /**
     * Sets fields to be watched.
     *
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = [
            'codalmacen', 'coddivisa', 'codpago', 'codserie', 'fecha', 'hora', 'idempresa', 'numero',
            'operacion', 'total'
        ];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
