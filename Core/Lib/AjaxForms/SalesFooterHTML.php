<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\AjaxForms;

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;

/**
 * Description of SalesFooterHTML
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class SalesFooterHTML
{
    use CommonSalesPurchases;

    /** @var SalesModInterface[] */
    private static $mods = [];

    public static function addMod(SalesModInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function apply(SalesDocument &$model, array $formData): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData);
        }

        self::$columnView = $formData['columnView'] ?? Tools::settings('default', 'columnetosubtotal', 'subtotal');

        $model->dtopor1 = isset($formData['dtopor1']) ? (float)$formData['dtopor1'] : $model->dtopor1;
        $model->dtopor2 = isset($formData['dtopor2']) ? (float)$formData['dtopor2'] : $model->dtopor2;
        $model->observaciones = $formData['observaciones'] ?? $model->observaciones;

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $formData);
        }
    }

    public static function assets(): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    public static function render(SalesDocument $model): string
    {
        if (empty(self::$columnView)) {
            self::$columnView = Tools::settings('default', 'columnetosubtotal', 'subtotal');
        }

        if (empty($model->codcliente)) {
            return '';
        }

        return '<div class="container-fluid mt-3">'
            . '<div class="row g-3">'
            . self::renderField($model, '_productBtn')
            . self::renderField($model, '_newLineBtn')
            . self::renderField($model, '_sortableBtn')
            . self::renderField($model, '_fastLineInput')
            . self::renderField($model, '_subtotalNetoBtn')
            . '</div>'
            . '<div class="row g-3">'
            . self::renderField($model, 'observaciones')
            . self::renderNewFields($model)
            . self::renderField($model, 'netosindto')
            . self::renderField($model, 'dtopor1')
            . self::renderField($model, 'dtopor2')
            . self::renderField($model, 'neto')
            . self::renderField($model, 'totaliva')
            . self::renderField($model, 'totalrecargo')
            . self::renderField($model, 'totalirpf')
            . self::renderField($model, 'totalsuplidos')
            . self::renderField($model, 'totalcoste')
            . self::renderField($model, 'totalbeneficio')
            . self::renderField($model, 'total')
            . '</div>'
            . '<div class="row g-3">'
            . '<div class="col-auto">'
            . self::renderField($model, '_deleteBtn')
            . '</div>'
            . '<div class="col text-end">'
            . self::renderNewBtnFields($model)
            . self::renderField($model, '_modalFooter')
            . self::renderField($model, '_undoBtn')
            . self::renderField($model, '_saveBtn')
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function modalFooter(SalesDocument $model): string
    {
        $htmlModal = self::renderNewModalFields($model);

        if (empty($htmlModal)) {
            return '';
        }

        return '<button class="btn btn-outline-secondary me-2" type="button" data-bs-toggle="modal" data-bs-target="#footerModal">'
            . '<i class="fa-solid fa-plus fa-fw" aria-hidden="true"></i></button>'
            . self::modalFooterHtml($htmlModal);
    }

    private static function modalFooterHtml(string $htmlModal): string
    {
        return '<div class="modal fade" id="footerModal" tabindex="-1" aria-labelledby="footerModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered modal-lg">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . Tools::lang()->trans('detail') . ' ' . Tools::lang()->trans('footer') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . $htmlModal
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Tools::lang()->trans('close') . '</button>'
            . '<button type="button" class="btn btn-primary" data-bs-dismiss="modal">' . Tools::lang()->trans('accept') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function renderField(SalesDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_deleteBtn':
                return self::deleteBtn($model, 'salesFormSave');

            case '_fastLineInput':
                return self::fastLineInput($model, 'salesFastLine');

            case '_modalFooter':
                return self::modalFooter($model);

            case '_newLineBtn':
                return self::newLineBtn($model, 'salesFormAction');

            case '_productBtn':
                return self::productBtn($model);

            case '_saveBtn':
                return self::saveBtn($model, 'salesFormSave');

            case '_sortableBtn':
                return self::sortableBtn($model);

            case '_subtotalNetoBtn':
                return self::subtotalNetoBtn();

            case '_undoBtn':
                return self::undoBtn($model);

            case 'dtopor1':
                return self::dtopor1($model, 'salesFormActionWait');

            case 'dtopor2':
                return self::dtopor2($model, 'salesFormActionWait');

            case 'neto':
                return self::column($model, 'neto', 'net', true);

            case 'netosindto':
                return self::netosindto($model);

            case 'observaciones':
                return self::observaciones($model);

            case 'total':
                return self::column($model, 'total', 'total', true);

            case 'totalbeneficio':
                return self::column($model, 'totalbeneficio', 'profits', true, Tools::settings('default', 'levelbenefitsales', 0));

            case 'totalcoste':
                return self::column($model, 'totalcoste', 'total-cost', true, Tools::settings('default', 'levelcostsales', 0));

            case 'totalirpf':
                return self::column($model, 'totalirpf', 'irpf', true);

            case 'totaliva':
                return self::column($model, 'totaliva', 'taxes', true);

            case 'totalrecargo':
                return self::column($model, 'totalrecargo', 're', true);

            case 'totalsuplidos':
                return self::column($model, 'totalsuplidos', 'supplied-amount', true);
        }

        return null;
    }

    private static function renderNewBtnFields(SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newBtnFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewFields(SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewModalFields(SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newModalFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }

        return $html;
    }
}
