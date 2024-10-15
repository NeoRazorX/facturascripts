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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchasesModalHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/PurchasesModalHTML
 */
class PurchasesModalHTML
{
    /** @var string */
    protected static $codalmacen;

    /** @var string */
    protected static $coddivisa;

    /** @var string */
    protected static $codfabricante;

    /** @var string */
    protected static $codfamilia;

    /** @var string */
    protected static $codproveedor;

    /** @var bool */
    protected static $comprado;

    /** @var array */
    protected static $idatributovalores = [];

    /** @var string */
    protected static $orden;

    /** @var string */
    protected static $query;

    public static function apply(PurchaseDocument &$model, array $formData): void
    {
        self::$codalmacen = $model->codalmacen;
        self::$coddivisa = $model->coddivisa;
        self::$codfabricante = $formData['fp_codfabricante'] ?? '';
        self::$codfamilia = $formData['fp_codfamilia'] ?? '';
        self::$codproveedor = $model->codproveedor;
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$comprado = (bool)($formData['fp_comprado'] ?? false);
        self::$query = isset($formData['fp_query']) ?
            Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';
    }

    public static function render(PurchaseDocument $model, string $url = ''): string
    {
        self::$codalmacen = $model->codalmacen;
        self::$coddivisa = $model->coddivisa;
        self::$codproveedor = $model->codproveedor;

        $i18n = new Translator();
        return $model->editable ? static::modalProveedores($i18n, $url) . static::modalProductos($i18n) : '';
    }

    public static function renderProductList(): string
    {
        $tbody = '';
        $i18n = new Translator();
        foreach (static::getProducts() as $row) {
            $cssClass = $row['nostock'] ? 'table-info clickableRow' : ($row['disponible'] > 0 ? 'clickableRow' : 'table-warning clickableRow');
            $cost = $row['neto'] ?? $row['coste'];
            $label = empty($row['refproveedor']) || $row['refproveedor'] === $row['referencia'] ?
                '<b>' . $row['referencia'] . '</b>' :
                '<b>' . $row['referencia'] . '</b> <span class="badge badge-light">' . $row['refproveedor'] . '</span>';
            $description = Tools::textBreak($row['descripcion'], 120)
                . static::idatributovalor($row['idatributovalor1'])
                . static::idatributovalor($row['idatributovalor2'])
                . static::idatributovalor($row['idatributovalor3'])
                . static::idatributovalor($row['idatributovalor4']);
            $tbody .= '<tr class="' . $cssClass . '" onclick="$(\'#findProductModal\').modal(\'hide\');'
                . ' return purchasesFormAction(\'add-product\', \'' . $row['referencia'] . '\');">'
                . '<td>' . $label . ' ' . $description . '</td>'
                . '<td class="text-right">' . str_replace(' ', '&nbsp;', Tools::money($cost)) . '</td>'
                . '<td class="text-right">' . str_replace(' ', '&nbsp;', Tools::money($row['precio'])) . '</td>'
                . '<td class="text-right">' . $row['disponible'] . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="4">' . $i18n->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('product') . '</th>'
            . '<th class="text-right">' . $i18n->trans('cost-price') . '</th>'
            . '<th class="text-right">' . $i18n->trans('price') . '</th>'
            . '<th class="text-right">' . $i18n->trans('stock') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }

    protected static function fabricantes(Translator $i18n): string
    {
        $fabricante = new Fabricante();
        $options = '<option value="">' . $i18n->trans('manufacturer') . '</option>'
            . '<option value="">------</option>';
        foreach ($fabricante->all([], ['nombre' => 'ASC'], 0, 0) as $man) {
            $options .= '<option value="' . $man->codfabricante . '">' . $man->nombre . '</option>';
        }

        return '<select name="fp_codfabricante" class="form-control" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function familias(Translator $i18n): string
    {
        $options = '<option value="">' . $i18n->trans('family') . '</option>'
            . '<option value="">------</option>';

        $familia = new Familia();
        $where = [new DataBaseWhere('madre', null, 'IS')];
        $orderBy = ['descripcion' => 'ASC'];
        foreach ($familia->all($where, $orderBy, 0, 0) as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">' . $fam->descripcion . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam, $i18n);
        }

        return '<select name="fp_codfamilia" class="form-control" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function getProducts(): array
    {
        $dataBase = new DataBase();
        $sql = 'SELECT v.referencia, pp.refproveedor, p.descripcion, v.idatributovalor1, v.idatributovalor2, v.idatributovalor3,'
            . ' v.idatributovalor4, v.coste, v.precio, pp.neto, COALESCE(s.disponible, 0) as disponible, p.nostock'
            . ' FROM variantes v'
            . ' LEFT JOIN productos p ON v.idproducto = p.idproducto'
            . ' LEFT JOIN stocks s ON v.referencia = s.referencia'
            . ' AND s.codalmacen = ' . $dataBase->var2str(self::$codalmacen)
            . ' LEFT JOIN productosprov pp ON pp.referencia = p.referencia'
            . ' AND pp.codproveedor = ' . $dataBase->var2str(self::$codproveedor)
            . ' AND pp.coddivisa = ' . $dataBase->var2str(self::$coddivisa)
            . ' WHERE p.secompra = true AND p.bloqueado = false';

        if (self::$codfabricante) {
            $sql .= ' AND codfabricante = ' . $dataBase->var2str(self::$codfabricante);
        }

        if (self::$codfamilia) {
            $codFamilias = [$dataBase->var2str(self::$codfamilia)];

            // buscamos las subfamilias
            $familia = new Familia();
            if ($familia->loadFromCode(self::$codfamilia)) {
                foreach ($familia->getSubfamilias() as $fam) {
                    $codFamilias[] = $dataBase->var2str($fam->codfamilia);
                }
            }

            $sql .= ' AND codfamilia IN (' . implode(',', $codFamilias) . ')';
        }

        if (self::$comprado) {
            $sql .= ' AND pp.codproveedor = ' . $dataBase->var2str(self::$codproveedor);
        }

        if (self::$query) {
            $words = explode(' ', self::$query);
            if (count($words) === 1) {
                $sql .= " AND (LOWER(v.codbarras) = " . $dataBase->var2str(self::$query)
                    . " OR LOWER(v.referencia) LIKE '%" . self::$query . "%'"
                    . " OR LOWER(pp.refproveedor) LIKE '%" . self::$query . "%'"
                    . " OR LOWER(p.descripcion) LIKE '%" . self::$query . "%')";
            } elseif (count($words) > 1) {
                $sql .= " AND (LOWER(v.referencia) LIKE '%" . self::$query . "%'"
                    . " OR LOWER(pp.refproveedor) LIKE '%" . self::$query . "%' OR (";
                foreach ($words as $wc => $word) {
                    $sql .= $wc > 0 ?
                        " AND LOWER(p.descripcion) LIKE '%" . $word . "%'" :
                        "LOWER(p.descripcion) LIKE '%" . $word . "%'";
                }
                $sql .= "))";
            }
        }

        switch (self::$orden) {
            case 'desc_asc':
                $sql .= " ORDER BY 3 ASC";
                break;

            case 'price_desc':
                $sql .= " ORDER BY 9 DESC";
                break;

            case 'ref_asc':
                $sql .= " ORDER BY 1 ASC";
                break;

            case 'stock_desc':
                $sql .= " ORDER BY 11 DESC";
                break;
        }

        return $dataBase->selectLimit($sql);
    }

    protected static function idatributovalor(?int $id): string
    {
        if (empty($id)) {
            return '';
        }

        if (!isset(self::$idatributovalores[$id])) {
            $attValor = new AtributoValor();
            $attValor->loadFromCode($id);
            self::$idatributovalores[$id] = $attValor->descripcion;
        }

        return ', ' . self::$idatributovalores[$id];
    }

    protected static function modalProductos(Translator $i18n): string
    {
        return '<div class="modal" id="findProductModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fas fa-cubes fa-fw"></i> ' . $i18n->trans('products') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . '<div class="col-sm mb-2">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="productModalInput" placeholder="' . $i18n->trans('search')
            . '" onkeyup="return purchasesFormActionWait(\'find-product\', \'0\', event);"/>'
            . '<div class="input-group-append">'
            . '<button class="btn btn-primary btn-spin-action" type="button" onclick="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="col-sm mb-2">' . static::fabricantes($i18n) . '</div>'
            . '<div class="col-sm mb-2">' . static::familias($i18n) . '</div>'
            . '<div class="col-sm mb-2">' . static::orden($i18n) . '</div>'
            . '</div>'
            . '<div class="form-row">'
            . '<div class="col-sm">'
            . '<div class="form-check">'
            . '<input type="checkbox" name="fp_comprado" value="1" class="form-check-input" id="comprado" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<label class="form-check-label" for="comprado">' . $i18n->trans('previously-purchased-from-supplier') . '</label>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findProductList">' . static::renderProductList() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function modalProveedores(Translator $i18n, string $url): string
    {
        $trs = '';
        $proveedor = new Proveedor();
        $where = [new DataBaseWhere('fechabaja', null, 'IS')];
        foreach ($proveedor->all($where, ['LOWER(nombre)' => 'ASC']) as $pro) {
            $name = ($pro->nombre === $pro->razonsocial) ? $pro->nombre : $pro->nombre . ' <small>(' . $pro->razonsocial . ')</span>';
            $trs .= '<tr class="clickableRow" onclick="document.forms[\'purchasesForm\'][\'codproveedor\'].value = \''
                . $pro->codproveedor . '\'; $(\'#findSupplierModal\').modal(\'hide\'); purchasesFormAction(\'set-supplier\', \'0\'); return false;">'
                . '<td><i class="fas fa-user fa-fw"></i> ' . $name . '</td>'
                . '</tr>';
        }

        return '<div class="modal" id="findSupplierModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-scrollable">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fas fa-users fa-fw"></i> ' . $i18n->trans('suppliers') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body p-0">'
            . '<div class="p-3">'
            . '<div class="input-group">'
            . '<input type="text" id="findSupplierInput" class="form-control" placeholder="' . $i18n->trans('search') . '" />'
            . '<div class="input-group-apend">'
            . '<button type="button" class="btn btn-primary"><i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<table class="table table-hover mb-0">' . $trs . '</table></div>'
            . '<div class="modal-footer bg-light">'
            . '<a href="EditProveedor?return=' . urlencode($url) . '" class="btn btn-block btn-success">'
            . '<i class="fas fa-plus fa-fw"></i> ' . $i18n->trans('new')
            . '</a>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function orden(Translator $i18n): string
    {
        return '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-sort-amount-down-alt"></i></span></div>'
            . '<select name="fp_orden" class="form-control" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<option value="">' . $i18n->trans('sort') . '</option>'
            . '<option value="">------</option>'
            . '<option value="ref_asc">' . $i18n->trans('reference') . '</option>'
            . '<option value="desc_asc">' . $i18n->trans('description') . '</option>'
            . '<option value="price_desc">' . $i18n->trans('price') . '</option>'
            . '<option value="stock_desc">' . $i18n->trans('stock') . '</option>'
            . '</select>'
            . '</div>';
    }

    private static function subfamilias(Familia $family, Translator $i18n, int $level = 1): string
    {
        $options = '';
        foreach ($family->getSubfamilias() as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">'
                . str_repeat('-', $level) . ' ' . $fam->descripcion
                . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam, $i18n, $level + 1);
        }

        return $options;
    }
}
