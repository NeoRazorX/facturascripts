<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\AccEntryRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Divisa as DinDivisa;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * The line of an accounting entry.
 * It is related to an accounting entry and account.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Partida extends ModelClass
{
    use ModelTrait;
    use AccEntryRelationTrait;

    /** Importe de la base imponible asociada a la partida. @var float|int */
    public $baseimponible;

    /** Identificador fiscal relacionado con la partida. @var string */
    public $cifnif;

    /** Código de la subcuenta de contrapartida. @var string */
    public $codcontrapartida;

    /** Código de la divisa utilizada en la partida. @var string */
    public $coddivisa;

    /** Código de la serie documental asociada. @var string */
    public $codserie;

    /** Código de la subcuenta contable asociada. @var string */
    public $codsubcuenta;

    /** Concepto de la partida contable. @var string */
    public $concepto;

    /** Importe anotado en el debe. @var float|int */
    public $debe;

    /** Indica si se omiten las comprobaciones adicionales del modelo. @var bool */
    private $disable_additional_test = false;

    /** Documento relacionado con la partida. @var string */
    public $documento;

    /** Número de factura relacionado con la partida. @var string */
    public $factura;

    /** Importe anotado en el haber. @var float|int */
    public $haber;

    /** Identificador de la subcuenta de contrapartida. @var int */
    public $idcontrapartida;

    /** Identificador único de la partida contable. @var int */
    public $idpartida;

    /** Identificador de la subcuenta contable asociada. @var int */
    public $idsubcuenta;

    /** Porcentaje de IVA aplicado. @var float|int */
    public $iva;

    /** Posición visual de la partida dentro del asiento. @var int */
    public $orden;

    /** Indica si la partida ha sido punteada o conciliada. @var bool */
    public $punteada;

    /** Porcentaje de recargo de equivalencia aplicado. @var float|int */
    public $recargo;

    /** Saldo acumulado de la subcuenta tras la partida. @var float */
    public $saldo;

    /** Tasa de conversión de la divisa utilizada. @var float|int */
    public $tasaconv;

    public function clear(): void
    {
        parent::clear();
        $this->baseimponible = 0.0;
        $this->coddivisa = Tools::settings('default', 'coddivisa');
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->orden = 0;
        $this->punteada = false;
        $this->recargo = 0.0;
        $this->saldo = 0.0;
        $this->tasaconv = 1.0;
    }

    public function delete(): bool
    {
        $entry = $this->getAccountingEntry();
        if (false === $entry->editable) {
            return false;
        }

        $exercise = $entry->getExercise();
        if (false === $exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::delete();
    }

    public function disableAdditionalTest(bool $value): void
    {
        $this->disable_additional_test = $value;
    }

    /**
     * @param string $codsubcuenta
     *
     * @return DinSubcuenta
     */
    public function getSubcuenta(string $codsubcuenta = ''): Subcuenta
    {
        $accEntry = $this->getAccountingEntry();
        $subCta = new DinSubcuenta();

        // get by parameter
        if (!empty($codsubcuenta)) {
            $where = [
                Where::eq('codejercicio', $accEntry->codejercicio),
                Where::eq('codsubcuenta', $codsubcuenta)
            ];
            $subCta->loadWhere($where);
            return $subCta;
        }

        // get by id
        if (
            !empty($this->idsubcuenta) &&
            $subCta->load($this->idsubcuenta) &&
            $subCta->codsubcuenta === $this->codsubcuenta &&
            $subCta->codejercicio === $accEntry->codejercicio
        ) {
            return $subCta;
        }

        // get by code and exercise
        $where2 = [
            Where::eq('codejercicio', $accEntry->codejercicio),
            Where::eq('codsubcuenta', $this->codsubcuenta)
        ];
        $subCta->loadWhere($where2);
        return $subCta;
    }

    public function install(): string
    {
        new DinDivisa();
        new DinAsiento();
        new DinSubcuenta();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpartida';
    }

    public function save(): bool
    {
        $entry = $this->getAccountingEntry();
        if (false === $this->disable_additional_test && false === $entry->editable) {
            return false;
        }

        $exercise = $entry->getExercise();
        if (false === $this->disable_additional_test && false === $exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        return parent::save();
    }

    public function setAccount(Subcuenta $subAccount): Partida
    {
        $this->codsubcuenta = $subAccount->codsubcuenta;
        $this->idsubcuenta = $subAccount->idsubcuenta;

        return $this;
    }

    public function setCounterpart(Subcuenta $subAccount): Partida
    {
        $this->codcontrapartida = $subAccount->codsubcuenta;
        $this->idcontrapartida = $subAccount->idsubcuenta;

        return $this;
    }

    public function setDottedStatus(bool $value): Partida
    {
        $sql = 'UPDATE ' . self::tableName() . ' SET punteada = ' . self::db()->var2str($value)
            . ' WHERE ' . self::primaryColumn() . ' = ' . self::db()->var2str($this->id());

        if ($value !== $this->punteada && self::db()->exec($sql)) {
            $this->punteada = $value;
        }

        return $this;
    }

    public static function tableName(): string
    {
        return 'partidas';
    }

    public function test(): bool
    {
        $this->cifnif = Tools::noHtml($this->cifnif);
        $this->codsubcuenta = Tools::noHtml($this->codsubcuenta);
        $this->codcontrapartida = Tools::noHtml($this->codcontrapartida);
        $this->concepto = Tools::noHtml($this->concepto);
        $this->documento = Tools::noHtml($this->documento);

        if (strlen($this->concepto ?? '') < 1) {
            Tools::log()->warning('field-required', ['%field%' => 'concepto']);
            return false;
        }

        // set missing account id
        if (empty($this->idsubcuenta)) {
            $this->idsubcuenta = $this->getSubcuenta()->idsubcuenta;
        }

        // set contrapartida id
        if (empty($this->codcontrapartida)) {
            $this->codcontrapartida = null;
            $this->idcontrapartida = null;
        } elseif (empty($this->idcontrapartida)) {
            $this->idcontrapartida = $this->getSubcuenta($this->codcontrapartida)->idsubcuenta;
            if (empty($this->idcontrapartida)) {
                $this->codcontrapartida = null;
            }
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getAccountingEntry()->url($type, $list);
    }

    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'codcontrapartida':
                if (!empty($this->codcontrapartida)) {
                    $this->idcontrapartida = $this->getSubcuenta($this->codcontrapartida)->idsubcuenta;
                }
                if (empty($this->idcontrapartida)) {
                    $this->codcontrapartida = null;
                }
                break;

            case 'codsubcuenta':
                $this->idsubcuenta = $this->getSubcuenta($this->codsubcuenta)->idsubcuenta;
                break;
        }

        return parent::onChange($field);
    }
}
