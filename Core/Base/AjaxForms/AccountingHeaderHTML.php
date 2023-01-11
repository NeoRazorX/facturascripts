<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Lib\CodePatterns;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\ConceptoPartida;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;

/**
 * Description of AccountingHeaderHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingHeaderHTML
{

    /**
     * @param Asiento $model
     * @param array $formData
     */
    public static function apply(Asiento &$model, array $formData)
    {
        $model->idempresa = $formData['idempresa'] ?? $model->idempresa;
        $model->setDate($formData['fecha'] ?? $model->fecha);
        $model->canal = $formData['canal'] ?? $model->canal;
        $model->concepto = $formData['concepto'] ?? $model->concepto;
        $model->iddiario = !empty($formData['iddiario']) ? $formData['iddiario'] : null;
        $model->documento = $formData['documento'] ?? $model->documento;
        $model->operacion = !empty($formData['operacion']) ? $formData['operacion'] : null;
    }

    /**
     * Render the view header.
     *
     * @param Asiento $model
     *
     * @return string
     */
    public static function render(Asiento $model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid">'
            . '<div class="form-row">'
            . static::idempresa($i18n, $model)
            . static::fecha($i18n, $model)
            . static::concepto($i18n, $model)
            . static::documento($i18n, $model)
            . static::diario($i18n, $model)
            . static::canal($i18n, $model)
            . static::operacion($i18n, $model)
            . '</div></div><br/>';
    }

    /**
     * Render the canal field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function canal(Translator $i18n, Asiento $model): string
    {
        $attributes = $model->editable ? 'name="canal"' : 'disabled';
        return '<div class="col-sm-2 col-md">'
            . '<div class="form-group">' . $i18n->trans('channel')
            . '<input type="number" ' . $attributes . ' value="' . $model->canal . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the concept field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function concepto(Translator $i18n, Asiento $model): string
    {
        $attributes = $model->editable ? 'name="concepto" autocomplete="off" required' : 'disabled';
        return '<div class="col-sm-6 col-md">'
            . '<div class="form-group">' . $i18n->trans('concept')
            . '<input type="text" list="concept-items" ' . $attributes . ' value="' . $model->concepto . '" class="form-control"/>'
            . '<datalist id="concept-items">' . static::getConceptItems($model) . '</datalist>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the document field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function documento(Translator $i18n, Asiento $model): string
    {
        if (empty($model->documento)) {
            return '';
        }

        $link = '';
        $facturaCliente = new FacturaCliente();
        $where = [
            new DataBaseWhere('codigo', $model->documento),
            new DataBaseWhere('idasiento', $model->idasiento),
        ];
        if ($facturaCliente->loadFromCode('', $where)) {
            $link = $facturaCliente->url();
        } else {
            $facturaProveedor = new FacturaProveedor();
            if ($facturaProveedor->loadFromCode('', $where)) {
                $link = $facturaProveedor->url();
            }
        }

        if ($link) {
            return '<div class="col-sm-3 col-md-2">'
                . '<div class="form-group">' . $i18n->trans('document')
                . '<div class="input-group">'
                . '<div class="input-group-prepend">'
                . '<a class="btn btn-outline-primary" href="' . $link . '"><i class="far fa-eye"></i></a>'
                . '</div>'
                . '<input type="text" value="' . $model->documento . '" class="form-control" readonly/>'
                . '</div>'
                . '</div>'
                . '</div>';
        }

        return '<div class="col-sm-3 col-md-2 mb-2">'
            . '<div class="form-group">' . $i18n->trans('document')
            . '<input type="text" value="' . $model->documento . '" class="form-control" readonly/>'
            . '</div></div>';
    }

    /**
     * Render the diario field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function diario(Translator $i18n, Asiento $model): string
    {
        $options = '<option value="">------</option>';
        $modelDiario = new Diario();
        foreach ($modelDiario->all([], [], 0, 0) as $diario) {
            $check = $diario->iddiario === $model->iddiario ? 'selected' : '';
            $options .= '<option value="' . $diario->iddiario . '" ' . $check . '>' . $diario->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="iddiario"' : 'disabled';
        return '<div class="col-sm-2 col-md">'
            . '<div class="form-group">' . $i18n->trans('daily')
            . '<select ' . $attributes . ' class="form-control">' . $options . '</select>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the date field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function fecha(Translator $i18n, Asiento $model): string
    {
        $attributes = $model->editable ? 'name="fecha" required' : 'disabled';
        return '<div class="col-sm-3 col-md-2">'
            . '<div class="form-group">' . $i18n->trans('date')
            . '<input type="date" ' . $attributes . ' value="' . date('Y-m-d', strtotime($model->fecha)) . '" class="form-control" />'
            . '</div>'
            . '</div>';
    }

    /**
     * Returns the list of predefined concepts.
     *
     * @param Asiento $model
     *
     * @return string
     */
    private static function getConceptItems(Asiento $model): string
    {
        $result = '';
        $conceptModel = new ConceptoPartida();
        foreach ($conceptModel->all([], ['descripcion' => 'ASC']) as $concept) {
            $result .= '<option value="' . CodePatterns::trans($concept->descripcion, $model) . '">';
        }
        return $result;
    }

    /**
     * Returns the list of options.
     *
     * @param array $options
     * @param string $key
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    private static function getItems(array &$options, string $key, string $name, $value): string
    {
        $result = '';
        foreach ($options as $item) {
            $selected = ($item->{$key} == $value) ? ' selected ' : '';
            $result .= '<option value="' . $item->{$key} . '"' . $selected . '>' . $item->{$name} . '</option>';
        }
        return $result;
    }

    /**
     * Render the company id field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function idempresa(Translator $i18n, Asiento $model): string
    {
        $company = new Empresa();
        $companyList = $company->all([], ['nombre' => 'ASC']);
        if (count($companyList) < 2) {
            return '<input type="hidden" name="idempresa" value=' . $model->idempresa . ' />';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<div class="form-group">' . $i18n->trans('company')
            . '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-building fa-fw"></i></span></div>'
            . '<select name="idempresa" class="form-control" required>'
            . static::getItems($companyList, 'idempresa', 'nombre', $model->idempresa)
            . '</select>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the operacion field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function operacion(Translator $i18n, Asiento $model): string
    {
        $attributes = $model->editable ? 'name="operacion"' : 'disabled';
        return '<div class="col-sm-2 col-md">'
            . '<div class="form-group">' . $i18n->trans('operation')
            . '<select ' . $attributes . ' class="form-control">'
            . '<option value="">------</option>'
            . '<option value="A" ' . ($model->operacion === 'A' ? 'selected' : '') . '>' . $i18n->trans('opening-operation') . '</option>'
            . '<option value="C" ' . ($model->operacion === 'C' ? 'selected' : '') . '>' . $i18n->trans('closing-operation') . '</option>'
            . '<option value="R" ' . ($model->operacion === 'R' ? 'selected' : '') . '>' . $i18n->trans('regularization-operation') . '</option>'
            . '</select>'
            . '</div>'
            . '</div>';
    }
}
