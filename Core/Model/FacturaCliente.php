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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Invoice of a client.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaCliente extends Base\SalesDocument
{

    use Base\ModelTrait;
    use Base\InvoiceTrait;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'facturascli';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idfactura';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->anulada = false;
        $this->pagada = false;
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
        parent::install();
        new Asiento();

        return '';
    }

    /**
     * Set the date and time, but respecting the numbering, the exercise
           * and VAT regularizations.
           * Returns True if a different date is assigned to those requested.
     *
     * @param string $fecha
     * @param string $hora
     *
     * @return bool
     */
    public function setFechaHora($fecha, $hora)
    {
        $cambio = false;

        if ($this->numero === null) { /// new invoice
            /// we look for the last date used in an invoice in this series and exercise
            $sql = 'SELECT MAX(fecha) AS fecha FROM ' . static::tableName()
                . ' WHERE codserie = ' . self::$dataBase->var2str($this->codserie)
                . ' AND codejercicio = ' . self::$dataBase->var2str($this->codejercicio) . ';';

            $data = self::$dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['fecha']) > strtotime($fecha)) {
                    $fechaOld = $fecha;
                    $fecha = date('d-m-Y', strtotime($data[0]['fecha']));

                    self::$miniLog->alert(self::$i18n->trans('invoice-new-assigned-date', ['%oldDate%' => $fechaOld, '%newDate%' => $fecha]));
                    $cambio = true;
                }
            }

            /// now we look for the last hour used for that date, series and exercise
            $sql = 'SELECT MAX(hora) AS hora FROM ' . static::tableName()
                . ' WHERE codserie = ' . self::$dataBase->var2str($this->codserie)
                . ' AND codejercicio = ' . self::$dataBase->var2str($this->codejercicio)
                . ' AND fecha = ' . self::$dataBase->var2str($fecha) . ';';

            $data = self::$dataBase->select($sql);
            if (!empty($data)) {
                if (strtotime($data[0]['hora']) > strtotime($hora) || $cambio) {
                    $hora = date('H:i:s', strtotime($data[0]['hora']));
                    $cambio = true;
                }
            }

            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// existing invoice and change date
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                if (!$ejercicio->abierto()) {
                    self::$miniLog->alert(self::$i18n->trans('closed-exercise-cant-change-date', ['%exerciseName%' => $ejercicio->nombre]));
                } elseif ($fecha === $ejercicio->get_best_fecha($fecha)) {
                    $regiva0 = new RegularizacionIva();
                    if ($regiva0->getFechaInside($fecha)) {
                        self::$miniLog->alert(self::$i18n->trans('cant-assign-date-already-regularized', ['%date%' => $fecha, '%tax%' => FS_IVA]));
                    } elseif ($regiva0->getFechaInside($this->fecha)) {
                        self::$miniLog->alert(self::$i18n->trans('invoice-regularized-cant-change-date', ['%tax%' => FS_IVA]));
                    } else {
                        $this->fecha = $fecha;
                        $this->hora = $hora;
                        $cambio = false;
                    }
                } else {
                    self::$miniLog->alert(self::$i18n->trans('date-out-of-exercise-range', ['%exerciseName%' => $ejercicio->nombre]));
                }
            } else {
                self::$miniLog->alert(self::$i18n->trans('exercise-not-found'));
            }
        } elseif ($hora !== $this->hora) { /// existing invoice and we change hour
            $this->hora = $hora;
        }

        return $cambio;
    }

    /**
     * Returns the lines associated with the invoice.
     *
     * @return LineaFacturaCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaFacturaCliente();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Remove an invoice and update the records related to it.
     *
     * @return bool
     */
    public function delete()
    {
        $bloquear = false;

        $eje0 = new Ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    self::$miniLog->alert(self::$i18n->trans('invoice-regularized-cant-delete', ['%tax%' => FS_IVA]));
                    $bloquear = true;
                } else {
                    foreach ($this->getRectificativas() as $rect) {
                        self::$miniLog->alert(self::$i18n->trans('invoice-have-rectifying-cant-delete'));
                        $bloquear = true;
                        break;
                    }
                }
            } else {
                self::$miniLog->alert(self::$i18n->trans('closed-exercise', ['%exerciseName%' => $ejercicio->nombre]));
                $bloquear = true;
            }
        }

        /// unlink associated delivery notes and eliminate invoice
        $sql = 'UPDATE albaranescli'
            . ' SET idfactura = NULL, ptefactura = TRUE WHERE idfactura = ' . self::$dataBase->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . static::tableName() . ' WHERE idfactura = ' . self::$dataBase->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if (self::$dataBase->exec($sql)) {
            if ($this->idasiento) {
                /**
                 * We delegate the elimination of the accounting entries in the corresponding class.
                 */
                $asiento = new Asiento();
                $asi0 = $asiento->get($this->idasiento);
                if ($asi0) {
                    $asi0->delete();
                }

                $asi1 = $asiento->get($this->idasientop);
                if ($asi1) {
                    $asi1->delete();
                }
            }

            self::$miniLog->info(self::$i18n->trans('customer-invoice-deleted-successfully', ['%docCode%' => $this->codigo]));

            return true;
        }

        return false;
    }
}
