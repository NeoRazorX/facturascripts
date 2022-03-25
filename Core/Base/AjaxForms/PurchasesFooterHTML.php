<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Plugins\BetaForms\Contract\PurchasesModInterface;

/**
 * Description of PurchasesFooterHTML
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class PurchasesFooterHTML
{
    use CommonSalesPurchases;

    /** @var PurchasesModInterface[] */
    private static $mods = [];

    public static function addMod(PurchasesModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    public static function apply(PurchaseDocument &$model, array $formData, User $user)
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData, $user);
        }

        $model->dtopor1 = isset($formData['dtopor1']) ? (float)$formData['dtopor1'] : $model->dtopor1;
        $model->dtopor2 = isset($formData['dtopor2']) ? (float)$formData['dtopor2'] : $model->dtopor2;
        $model->observaciones = $formData['observaciones'] ?? $model->observaciones;

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $formData, $user);
        }
    }

    public static function render(PurchaseDocument $model): string
    {
        if (empty($model->codproveedor)) {
            return '';
        }

        $i18n = new Translator();
        return '<div class="container-fluid mt-3">'
            . '<div class="form-row">'
            . self::renderField($i18n, $model, 'newLineBtn')
            . self::renderField($i18n, $model, 'productBtn')
            . self::renderField($i18n, $model, 'fastLineInput')
            . self::renderField($i18n, $model, 'sortableBtn')
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
            . self::renderField($i18n, $model, 'total')
            . '</div>'
            . '<div class="form-row">'
            . '<div class="col-sm">'
            . self::renderField($i18n, $model, 'deleteBtn')
            . '</div>'
            . '<div class="col-sm text-right">'
            . self::renderField($i18n, $model, 'saveBtn')
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($i18n, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case 'deleteBtn':
                return self::deleteBtn($i18n, $model, 'purchasesFormSave');

            case 'dtopor1':
                return self::dtopor1($i18n, $model, 'purchasesFormActionWait');

            case 'dtopor2':
                return self::dtopor2($i18n, $model, 'purchasesFormActionWait');

            case 'fastLineInput':
                return self::fastLineInput($i18n, $model, 'purchasesFastLine');

            case 'neto':
                return self::column($i18n, $model, 'neto', 'net', true);

            case 'netosindto':
                return self::netosindto($i18n, $model);

            case 'newLineBtn':
                return self::newLineBtn($i18n, $model, 'purchasesFormAction');

            case 'observaciones':
                return self::observaciones($i18n, $model);

            case 'productBtn':
                return self::productBtn($i18n, $model);

            case 'saveBtn':
                return self::saveBtn($i18n, $model, 'purchasesFormSave');

            case 'sortableBtn':
                return self::sortableBtn($i18n, $model);

            case 'total':
                return self::column($i18n, $model, 'total', 'total', true);

            case 'totalirpf':
                return self::column($i18n, $model, 'totalirpf', 'irpf', true);

            case 'totaliva':
                return self::column($i18n, $model, 'totaliva', 'taxes', true);

            case 'totalrecargo':
                return self::column($i18n, $model, 'totalrecargo', 're', true);
        }

        return null;
    }

    private static function renderNewFields(Translator $i18n, PurchaseDocument $model): string
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