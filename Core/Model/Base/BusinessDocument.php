<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Where;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\Template\ModelClass as NewModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;

/**
 * Documento de negocio base (presupuestos, pedidos, albaranes, facturas).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocument extends NewModelClass
{
    use CompanyRelationTrait;
    use CurrencyRelationTrait;
    use ExerciseRelationTrait;
    use PaymentRelationTrait;
    use SerieRelationTrait;
    use IntracomunitariaTrait;

    /**
     * CIF/NIF del cliente o proveedor.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Almacén del documento.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Identificador único para humanos.
     *
     * @var string
     */
    public $codigo;

    /** @var array */
    protected static $dont_copy_fields = ['codejercicio', 'codigo', 'codigorect', 'fecha', 'femail', 'hora',
        'idasiento', 'idestado', 'idfacturarect', 'neto', 'netosindto', 'numero', 'pagada', 'total', 'totalirpf',
        'totaliva', 'totalrecargo', 'totalsuplidos'];

    /**
     * Porcentaje de descuento.
     *
     * @var float
     */
    public $dtopor1;

    /**
     * Porcentaje de descuento.
     *
     * @var float
     */
    public $dtopor2;

    /**
     * Fecha del documento.
     *
     * @var string
     */
    public $fecha;

    /**
     * Fecha en la que se envió el documento por email.
     *
     * @var string
     */
    public $femail;

    /**
     * Hora del documento.
     *
     * @var string
     */
    public $hora;

    /**
     * Retención por defecto del documento. Cada línea puede tener una retención diferente.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Suma del pvptotal de las líneas. Total del documento antes de impuestos.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Suma del pvptotal de las líneas. Total del documento antes de impuestos y descuentos globales.
     *
     * @var float|int
     */
    public $netosindto;

    /**
     * Usuario que creó este documento. Modelo User.
     *
     * @var string
     */
    public $nick;

    /**
     * Número del documento. Único dentro de la serie.
     *
     * @var string
     */
    public $numero;

    /**
     * Número de documentos adjuntos.
     *
     * @var int
     */
    public $numdocs;

    /**
     * Observaciones del documento.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Suma total del documento, con impuestos.
     *
     * @var float|int
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * Total expresado en euros, si no fuera la divisa del documento.
     * totaleuros = total / tasaconv
     * No es necesario rellenarlo, al hacer save() se calcula el valor.
     *
     * @var float|int
     */
    public $totaleuros;

    /**
     * Suma total de las retenciones de IRPF de las líneas.
     *
     * @var float|int
     */
    public $totalirpf;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     *
     * @var float|int
     */
    public $totalrecargo;

    /**
     * Suma total de las líneas de suplidos.
     *
     * @var float|int
     */
    public $totalsuplidos;

    /**
     * Devuelve las líneas asociadas al documento.
     */
    abstract public function getLines(): array;

    /**
     * Devuelve una nueva línea para este documento.
     */
    abstract public function getNewLine(array $data = [], array $exclude = []);

    /**
     * Devuelve una nueva línea para este documento completada con los datos del producto.
     */
    abstract public function getNewProductLine($reference);

    /**
     * Devuelve el sujeto de este documento.
     */
    abstract public function getSubject();

    /**
     * Establece el autor de este documento.
     */
    abstract public function setAuthor($user): bool;

    /**
     * Establece el sujeto de este documento.
     */
    abstract public function setSubject($subject): bool;

    /**
     * Devuelve el nombre de la columna del sujeto.
     */
    abstract public function subjectColumn();

    /**
     * Actualiza los datos del sujeto en este documento.
     */
    abstract public function updateSubject(): bool;

    /**
     * Restablece los valores de todas las propiedades del modelo.
     */
    public function clear(): void
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
        $where = [Where::eq('model', $this->modelClassName())];
        $where[] = is_numeric($this->id()) ?
            Where::eq('modelid|modelcode', $this->id()) :
            Where::eq('modelcode', $this->id());

        return AttachedFileRelation::all($where, ['creationdate' => 'DESC'], 0, 0);
    }

    public function getAuditChannel(): string
    {
        return LogMessage::DOCS_CHANNEL;
    }

    /**
     * Devuelve el Descuento Unificado Equivalente.
     *
     * @return float
     */
    public function getEUDiscount(): float
    {
        $eud = 1.0;
        foreach ([$this->dtopor1, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    /**
     * Devuelve el descuento total de todo el documento.
     * Este cálculo tiene en cuenta tanto los descuentos globales aplicados al documento
     * como los descuentos individuales aplicados en cada una de las líneas.
     *
     * OJO, sin tener en cuenta impuestos.
     * Se redondea el resultado con los decimales configurados por el sistema para evitar problemas de precisión en coma flotante.
     *
     * @return float Descuento total calculado.
     */
    public function getTotalDiscounts(): float
    {
        $netoNoDto = 0.0;
        foreach ($this->getLines() as $line) {
            // sumar las lineas sin descuentos
            $netoNoDto += $line->pvpsindto;
        }

        // devolver el neto (total con descuento sin impuestos) - sumaLineasSinDto
        return Tools::round($netoNoDto - $this->neto);
    }

    /**
     * Esta función se ejecuta al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará después de la creación de la tabla. Útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install(): string
    {
        // dependencias necesarias
        new Serie();
        new Ejercicio();
        new Almacen();
        new Divisa();
        new FormaPago();
        new User();

        return parent::install();
    }

    public function paid(): bool
    {
        return false;
    }

    /**
     * Devuelve la descripción de la columna que es la clave primaria del modelo.
     *
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        return 'codigo';
    }

    /**
     * Guarda los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save(): bool
    {
        // comprobamos el ejercicio contable
        if (empty($this->codejercicio)) {
            $this->setDate($this->fecha, $this->hora);
        }

        // ¿código vacío?
        if (empty($this->codigo)) {
            BusinessDocumentCode::setNewCode($this);
        }

        return parent::save();
    }

    /**
     * Asigna la fecha y busca un ejercicio contable.
     *
     * @param string $date
     * @param string $hour
     *
     * @return bool
     */
    public function setDate(string $date, string $hour): bool
    {
        // forzamos la comprobación de la relación almacén-empresa
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
     * Establece el almacén y la empresa de este documento.
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

    public function subjectColumnValue(): string
    {
        return $this->{$this->subjectColumn()} ?? '';
    }

    /**
     * Devuelve True si no hay errores en los valores de las propiedades.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->observaciones = Tools::noHtml($this->observaciones);

        // comprobamos el número
        if ((int)$this->numero < 1) {
            Tools::log()->error('invalid-number', ['%number%' => $this->numero]);
            return false;
        }

        // comprobamos el ejercicio y la fecha
        if ((empty($this->id()) || !$this->isDirty('fecha')) && false === $this->getExercise()->inRange($this->fecha)) {
            Tools::log()->error('date-out-of-exercise-range', ['%exerciseName%' => $this->codejercicio]);
            return false;
        }

        // comprobamos el total
        $decimals = Tools::settings('default', 'decimales', 2);
        $total = $this->neto + $this->totalsuplidos + $this->totaliva - $this->totalirpf + $this->totalrecargo;
        if (false === Tools::floatCmp($this->total, $total, $decimals, true)) {
            Tools::log()->error('bad-total-error');
            return false;
        }

        /**
         * Usamos el euro como divisa puente al sumar, comparar
         * o convertir importes en varias divisas. Por eso necesitamos
         * muchos decimales.
         */
        $this->totaleuros = empty($this->tasaconv) ? 0 : round($this->total / $this->tasaconv, 5);

        return parent::test();
    }

    /**
     * Comprueba los campos modificados antes de actualizar la base de datos.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'codalmacen':
                foreach ($this->getLines() as $line) {
                    $line->transfer($this->getOriginal('codalmacen'), $this->codalmacen);
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

    protected function saveUpdate(): bool
    {
        if (false === parent::saveUpdate()) {
            return false;
        }

        if ($this->isDirty()) {
            // añadimos el log de auditoría
            Tools::log($this->getAuditChannel())->info('updated-model', [
                '%model%' => $this->modelClassName(),
                '%key%' => $this->id(),
                '%desc%' => $this->primaryDescription(),
                'model-class' => $this->modelClassName(),
                'model-code' => $this->id(),
                'model-data' => $this->getDirty()
            ]);
        }

        return true;
    }
}
