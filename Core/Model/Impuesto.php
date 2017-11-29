<?php
/**
 * This file is part of facturacion_base
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * Un impuesto (IVA) que puede estar asociado a artículos, líneas de albaranes,
 * facturas, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto
{

    use Base\ModelTrait;

    /**
     * Clave primaria. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Código de la subcuenta para ventas.
     *
     * @var string
     */
    public $codsubcuentarep;

    /**
     * Código de la subcuenta para compras.
     *
     * @var string
     */
    public $codsubcuentasop;

    /**
     * Descripción del impuesto.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Valor del IVA
     *
     * @var float|int
     */
    public $iva;

    /**
     * Valor del Recargo
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codimpuesto';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codimpuesto = null;
        $this->codsubcuentarep = null;
        $this->codsubcuentasop = null;
        $this->descripcion = null;
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Devuelve True si el impuesto es el predeterminado del usuario
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codimpuesto === AppSettings::get('default', 'codimpuesto');
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codimpuesto = trim($this->codimpuesto);
        $this->descripcion = self::noHtml($this->descripcion);

        if (empty($this->codimpuesto) || strlen($this->codimpuesto) > 10) {
            $this->miniLog->alert($this->i18n->trans('not-valid-tax-code-length'));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 50) {
            $this->miniLog->alert($this->i18n->trans('not-valid-description-tax'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        return CSVImport::importTableSQL($this->tableName());
    }
}
