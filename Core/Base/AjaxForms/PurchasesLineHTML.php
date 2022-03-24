<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Contract\PurchasesLineModInterface;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of PurchasesLineHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class PurchasesLineHTML
{
    use CommonLineHTML;

    /** @var array */
    protected static $deletedLines = [];

    /** @var PurchasesLineModInterface[] */
    private static $mods = [];

    /** @var int */
    protected static $num = 0;

    public static function addMod(PurchasesLineModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    /**
     * @param PurchaseDocument $model
     * @param PurchaseDocumentLine[] $lines
     * @param array $formData
     */
    public static function apply(PurchaseDocument &$model, array &$lines, array $formData)
    {
        // update or remove lines
        $rmLineId = $formData['action'] === 'rm-line' ? $formData['selectedLine'] : 0;
        foreach ($lines as $key => $value) {
            if ($value->idlinea === (int)$rmLineId || false === isset($formData['cantidad_' . $value->idlinea])) {
                self::$deletedLines[] = $value->idlinea;
                unset($lines[$key]);
                continue;
            }

            self::applyToLine($formData, $value, $value->idlinea);
        }

        // new lines
        for ($num = 1; $num < 1000; $num++) {
            if (isset($formData['cantidad_n' . $num]) && $rmLineId !== 'n' . $num) {
                $newLine = isset($formData['referencia_n' . $num]) ? $model->getNewProductLine($formData['referencia_n' . $num]) : $model->getNewLine();
                $idNewLine = 'n' . $num;
                self::applyToLine($formData, $newLine, $idNewLine);
                $lines[] = $newLine;
            }
        }

        // add new line
        if ($formData['action'] === 'add-product') {
            $lines[] = $model->getNewProductLine($formData['selectedLine']);
        } elseif ($formData['action'] === 'fast-line') {
            $newLine = static::getFastLine($model, $formData);
            if ($newLine) {
                $lines[] = $newLine;
            }
        } elseif ($formData['action'] === 'new-line') {
            $lines[] = $model->getNewLine();
        }

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $lines, $formData);
        }
    }

    public static function getDeletedLines(): array
    {
        return self::$deletedLines;
    }

    /**
     * @param PurchaseDocumentLine[] $lines
     * @param PurchaseDocument $model
     *
     * @return array
     */
    public static function map(array $lines, PurchaseDocument $model): array
    {
        $map = [];
        foreach ($lines as $line) {
            self::$num++;
            $idlinea = $line->idlinea ?? 'n' . self::$num;

            // codimpuesto
            $map['iva_' . $idlinea] = $line->iva;

            // total
            $map['linetotal_' . $idlinea] = $line->pvptotal * (100 + $line->iva + $line->recargo - $line->irpf) / 100;
        }

        return $map;
    }

    /**
     * @param PurchaseDocumentLine[] $lines
     * @param PurchaseDocument $model
     *
     * @return string
     */
    public static function render(array $lines, PurchaseDocument $model): string
    {
        $html = '';
        foreach ($lines as $line) {
            $html .= self::renderLine($line, $model);
        }

        return empty($html) && $model->codproveedor ?
            '<div class="alert alert-warning border-top mb-0">' . ToolBox::i18n()->trans('new-invoice-line-p') . '</div>' :
            $html;
    }

    public static function renderLine(PurchaseDocumentLine $line, PurchaseDocument $model): string
    {
        self::$num++;
        $i18n = new Translator();
        $idlinea = $line->idlinea ?? 'n' . self::$num;
        $cssClass = static::$num % 2 == 0 ? 'bg-white border-top' : 'bg-light border-top';
        return '<div class="' . $cssClass . ' line pl-2 pr-2">'
            . '<div class="form-row align-items-end">'
            . self::renderField($i18n, $idlinea, $line, $model, 'descripcion')
            . self::renderField($i18n, $idlinea, $line, $model, 'cantidad')
            . self::renderNewFields($i18n, $idlinea, $line, $model)
            . self::renderField($i18n, $idlinea, $line, $model, 'pvpunitario')
            . self::renderField($i18n, $idlinea, $line, $model, 'dtopor')
            . self::renderField($i18n, $idlinea, $line, $model, 'codimpuesto')
            . self::renderField($i18n, $idlinea, $line, $model, '_total')
            . self::renderCalculatorBtn($i18n, $idlinea, $model, 'purchasesLineTotalWithTaxes')
            . self::renderExpandButton($i18n, $idlinea, $model, 'purchasesFormAction')
            . '</div>'
            . self::renderLineModal($i18n, $line, $idlinea, $model)
            . '</div>';
    }

    private static function renderLineModal(Translator $i18n, PurchaseDocumentLine $line, string $idlinea, PurchaseDocument $model): string
    {
        return '<div class="modal fade" id="lineModal-' . $idlinea . '" tabindex="-1" aria-labelledby="lineModal-' . $idlinea . 'Label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">'
            . $line->referencia
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . self::renderField($i18n, $idlinea, $line, $model, 'recargo')
            . self::renderField($i18n, $idlinea, $line, $model, 'irpf')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $idlinea, $line, $model, 'suplido')
            . self::renderField($i18n, $idlinea, $line, $model, 'dtopor2')
            . '</div>'
            . '<div class="form-row">'
            . self::renderNewModalFields($i18n, $idlinea, $line, $model)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
            . $i18n->trans('close')
            . '</button>'
            . '<button type="button" class="btn btn-primary" data-dismiss="modal">'
            . $i18n->trans('accept')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function applyToLine(array $formData, PurchaseDocumentLine &$line, string $id)
    {
        $line->orden = (int)$formData['orden_' . $id];
        $line->cantidad = (float)$formData['cantidad_' . $id];
        $line->dtopor = (float)$formData['dtopor_' . $id];
        $line->dtopor2 = (float)$formData['dtopor2_' . $id];
        $line->descripcion = $formData['descripcion_' . $id];
        $line->irpf = (float)($formData['irpf_' . $id] ?? '0');
        $line->iva = (float)($formData['iva_' . $id] ?? '0');
        $line->recargo = (float)($formData['recargo_' . $id] ?? '0');
        $line->suplido = (bool)($formData['suplido_' . $id] ?? '0');
        $line->pvpunitario = (float)$formData['pvpunitario_' . $id];

        // mods
        foreach (self::$mods as $mod) {
            $mod->applyToLine($formData, $line, $id);
        }
    }

    protected static function cantidad(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $jsFunc): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm-2 col-xl-1 small order-2">' . $i18n->trans('quantity')
                . '<input type="number" class="form-control mb-1" value="' . $line->cantidad . '" disabled=""/>'
                . '</div>';
        }

        return '<div class="col-sm-2 col-xl-1 small order-2">' . $i18n->trans('quantity')
            . '<input type="number" name="cantidad_' . $idlinea . '" value="' . $line->cantidad
            . '" class="form-control mb-1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"/>'
            . '</div>';
    }

    protected static function getFastLine(PurchaseDocument $model, array $formData): ?PurchaseDocumentLine
    {
        if (empty($formData['fastli'])) {
            return $model->getNewLine();
        }

        $variantModel = new Variante();
        $whereBarcode = [new DataBaseWhere('codbarras', $formData['fastli'])];
        foreach ($variantModel->all($whereBarcode) as $variante) {
            return $model->getNewProductLine($variante->referencia);
        }

        $whereRef = [new DataBaseWhere('referencia', $formData['fastli'])];
        foreach ($variantModel->all($whereRef) as $variante) {
            return $model->getNewProductLine($variante->referencia);
        }

        ToolBox::i18nLog()->warning('product-not-found', ['%ref%' => $formData['fastli']]);
        return null;
    }

    protected static function precio(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $jsFunc): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm-2 col-xl-1 order-3">'
                . '<div class="mb-1 small">' . $i18n->trans('price')
                . '<input type="number" value="' . $line->pvpunitario . '" class="form-control" disabled=""/>'
                . '</div>'
                . '</div>';
        }

        $attributes = 'name="pvpunitario_' . $idlinea . '" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"';
        return '<div class="col-sm-2 col-xl-1 order-3">'
            . '<div class="mb-1 small">' . $i18n->trans('price')
            . '<input type="number" ' . $attributes . ' value="' . $line->pvpunitario . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($i18n, $idlinea, $line, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_total':
                return self::lineTotal($i18n, $idlinea, $line, $model);

            case 'cantidad':
                return self::cantidad($i18n, $idlinea, $line, $model, 'purchasesFormActionWait');

            case 'codimpuesto':
                return self::codimpuesto($i18n, $idlinea, $line, $model, 'purchasesFormAction');

            case 'descripcion':
                return self::descripcion($i18n, $idlinea, $line, $model);

            case 'dtopor':
                return self::dtopor($i18n, $idlinea, $line, $model, 'purchasesFormActionWait');

            case 'dtopor2':
                return self::dtopor2($i18n, $idlinea, $line, $model, 'dtopor2', 'purchasesFormActionWait');

            case 'irpf':
                return self::irpf($i18n, $idlinea, $line, $model, 'purchasesFormAction');

            case 'pvpunitario':
                return self::precio($i18n, $idlinea, $line, $model, 'purchasesFormActionWait');

            case 'recargo':
                return self::recargo($i18n, $idlinea, $line, $model, 'purchasesFormActionWait');

            case 'suplido':
                return self::suplido($i18n, $idlinea, $line, $model, 'purchasesFormAction');
        }

        return null;
    }

    private static function renderNewModalFields(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $idlinea, $line, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewFields(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model): string
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
                $fieldHtml = $mod->renderField($i18n, $idlinea, $line, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }
}
