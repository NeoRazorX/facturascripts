<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

/**
 * Description of SalesFooterHTML
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SalesFooterHTML
{
    use CommonSalesPurchases;

    /** @var SalesModInterface[] */
    private static $mods = [];

    public static function addMod(SalesModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    public static function apply(SalesDocument &$model, array $formData, User $user)
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData, $user);
        }

        self::$columnView = $formData['columnView'] ?? AppSettings::get('default', 'columnetosubtotal', 'subtotal');

        $model->dtopor1 = isset($formData['dtopor1']) ? (float)$formData['dtopor1'] : $model->dtopor1;
        $model->dtopor2 = isset($formData['dtopor2']) ? (float)$formData['dtopor2'] : $model->dtopor2;
        $model->observaciones = $formData['observaciones'] ?? $model->observaciones;

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $formData, $user);
        }
    }

    public static function assets()
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    public static function render(SalesDocument $model): string
    {
        if (empty(self::$columnView)) {
            self::$columnView = AppSettings::get('default', 'columnetosubtotal', 'subtotal');
        }

        if (empty($model->codcliente)) {
            return '';
        }

        $i18n = new Translator();
        return '<div class="container-fluid mt-3">'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, '_productBtn')
            . self::renderField($i18n, $model, '_newLineBtn')
            . self::renderField($i18n, $model, '_sortableBtn')
            . self::renderField($i18n, $model, '_fastLineInput')
            . self::renderField($i18n, $model, '_subtotalNetoBtn')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'observaciones')
            . self::renderNewFields($i18n, $model)
            . self::renderField($i18n, $model, 'netosindto')
            . self::renderField($i18n, $model, 'dtopor1')
            . self::renderField($i18n, $model, 'dtopor2')
            . self::renderField($i18n, $model, 'neto')
            . self::renderField($i18n, $model, 'totaliva')
            . self::renderField($i18n, $model, 'totalrecargo')
            . self::renderField($i18n, $model, 'totalirpf')
            . self::renderField($i18n, $model, 'totalsuplidos')
            . self::renderField($i18n, $model, 'total')
            . '</div>'
            . '<div class="form-row">'
            . '<div class="col">' . self::renderField($i18n, $model, '_deleteBtn') . '</div>'
            . '<div class="col-auto">' . self::renderField($i18n, $model, '_undoBtn') . '</div>'
            . '<div class="col-auto">' . self::renderField($i18n, $model, '_saveBtn') . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($i18n, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_deleteBtn':
                return self::deleteBtn($i18n, $model, 'salesFormSave');

            case '_fastLineInput':
                return self::fastLineInput($i18n, $model, 'salesFastLine');

            case '_newLineBtn':
                return self::newLineBtn($i18n, $model, 'salesFormAction');

            case '_productBtn':
                return self::productBtn($i18n, $model);

            case '_saveBtn':
                return self::saveBtn($i18n, $model, 'salesFormSave');

            case '_sortableBtn':
                return self::sortableBtn($i18n, $model);

            case '_subtotalNetoBtn':
                return self::subtotalNetoBtn($i18n);

            case '_undoBtn':
                return self::undoBtn($i18n, $model);

            case 'dtopor1':
                return self::dtopor1($i18n, $model, 'salesFormActionWait');

            case 'dtopor2':
                return self::dtopor2($i18n, $model, 'salesFormActionWait');

            case 'neto':
                return self::column($i18n, $model, 'neto', 'net', true);

            case 'netosindto':
                return self::netosindto($i18n, $model);

            case 'observaciones':
                return self::observaciones($i18n, $model);

            case 'total':
                return self::column($i18n, $model, 'total', 'total', true);

            case 'totalirpf':
                return self::column($i18n, $model, 'totalirpf', 'irpf', true);

            case 'totaliva':
                return self::column($i18n, $model, 'totaliva', 'taxes', true);

            case 'totalrecargo':
                return self::column($i18n, $model, 'totalrecargo', 're', true);

            case 'totalsuplidos':
                return self::column($i18n, $model, 'totalsuplidos', 'supplied-amount', true);
        }

        return null;
    }

    private static function renderNewFields(Translator $i18n, SalesDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }
}
