<?php
/**
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Lib\CodePatterns;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\ConceptoPartida;
use FacturaScripts\Dinamic\Model\Empresa;

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
    public static function apply(&$model, array $formData)
    {
        $model->setDate($formData['fecha'] ?? $model->fecha);
        $model->concepto = $formData['concepto'] ?? $model->concepto;
        $model->documento = $formData['documento'] ?? $model->documento;
    }

    /**
     * Render the view header.
     *
     * @param Asiento $model
     *
     * @return string
     */
    public static function render($model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid"><div class="form-row">'
            . static::idempresa($i18n, $model)
            . static::fecha($i18n, $model)
            . static::concepto($i18n, $model)
            . static::documento($i18n, $model)
            . static::importe($i18n, $model)
            . static::descuadre($i18n, $model)
            . '</div></div><br/>';
    }

    /**
     * Render the concept field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function concepto(Translator $i18n, $model): string
    {
        $attributes = $model->editable ? 'name="concepto" autocomplete="off" autofocus required' : 'disabled';
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
    protected static function documento(Translator $i18n, $model): string
    {
        return empty($model->documento) ? '' : '<div class="col-sm-3 col-md-2">'
            . '<div class="form-group">' . $i18n->trans('document')
            . '<input type="text" value="' . $model->documento . '" class="form-control" readonly/>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the unbalance value
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function descuadre(Translator $i18n, $model): string
    {
        $unbalance = isset($model->debe, $model->haber) ? $model->debe - $model->haber : 0.00;
        if (empty($unbalance)) {
            return '';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<div class="form-group text-danger">' . $i18n->trans('unbalance')
            . '<input type="number" value="' . $unbalance . '" class="form-control" step="any" readonly>'
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
    protected static function fecha(Translator $i18n, $model): string
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
    private static function getConceptItems($model): string
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
    protected static function idempresa(Translator $i18n, $model): string
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
     * Render the amount field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function importe(Translator $i18n, $model): string
    {
        return '<div class="col-sm-3 col-md-2">'
            . '<div class="form-group">' . $i18n->trans('amount')
            . '<input type="number" value="' . $model->importe . '" class="form-control" step="any" tabindex="-1" readonly>'
            . '</div>'
            . '</div>';
    }
}
