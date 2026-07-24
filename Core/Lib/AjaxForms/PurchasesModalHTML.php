<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Contract\PurchasesModalInterface;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Fabricantes;
use FacturaScripts\Core\DataSrc\Familias;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchasesModalHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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

    /** @var int */
    protected static $idempresa;

    /** @var string */
    protected static $orden;

    /** @var string */
    protected static $query;

    /** @var PurchasesModalInterface[] */
    private static $mods = [];

    public static function addMod(PurchasesModalInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function apply(PurchaseDocument &$model, array $formData): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->applyBefore($model, $formData);
        }

        self::$codalmacen = $model->codalmacen;
        self::$coddivisa = $model->coddivisa;
        self::$codfabricante = $formData['fp_codfabricante'] ?? '';
        self::$codfamilia = $formData['fp_codfamilia'] ?? '';
        self::$codproveedor = $model->codproveedor;
        self::$idempresa = $model->idempresa;
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$comprado = (bool)($formData['fp_comprado'] ?? false);
        self::$query = isset($formData['fp_query']) ?
            Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';

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

    public static function render(PurchaseDocument $model, string $url = ''): string
    {
        self::$codalmacen = $model->codalmacen;
        self::$coddivisa = $model->coddivisa;
        self::$codproveedor = $model->codproveedor;
        self::$idempresa = $model->idempresa;

        if (empty($model->id()) && !$model->editable) {
            return '<div class="alert alert-warning mt-4">'
                . '<i class="fa-solid fa-triangle-exclamation fa-fw"></i> ' . Tools::trans('default-status-non-editable')
                . '</div>';
        }

        return $model->editable ? static::modalProveedores($url) . static::modalProductos() : '';
    }

    public static function renderProductList(): string
    {
        $tbody = '';
        foreach (static::getProducts() as $row) {
            $cssClass = $row['nostock'] ? 'table-info clickableRow' : ($row['disponible'] > 0 ? 'clickableRow' : 'table-warning clickableRow');
            $cost = $row['neto'] ?? $row['coste'];
            $reference = static::html($row['referencia']);
            $providerReference = static::html($row['refproveedor']);
            $label = empty($row['refproveedor']) || $row['refproveedor'] === $row['referencia'] ?
                '<b>' . $reference . '</b>' :
                '<b>' . $reference . '</b> <span class="badge bg-light">' . $providerReference . '</span>';
            $description = Tools::textBreak($row['descripcion'], 120)
                . static::idatributovalor($row['idatributovalor1'])
                . static::idatributovalor($row['idatributovalor2'])
                . static::idatributovalor($row['idatributovalor3'])
                . static::idatributovalor($row['idatributovalor4']);
            $tbody .= '<tr class="' . $cssClass . '" data-reference="' . $reference
                . '" onclick="$(\'#findProductModal\').modal(\'hide\');'
                . ' return purchasesFormAction(\'add-product\', this.dataset.reference);">'
                . '<td>' . $label . ' ' . $description . '</td>'
                . '<td class="text-end">' . str_replace(' ', '&nbsp;', Tools::money($cost)) . '</td>'
                . '<td class="text-end">' . str_replace(' ', '&nbsp;', Tools::money($row['precio'])) . '</td>'
                . self::renderNewProduct($row)
                . '<td class="text-end">' . $row['disponible'] . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="4">' . Tools::trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . Tools::trans('product') . '</th>'
            . '<th class="text-end">' . Tools::trans('cost-price') . '</th>'
            . '<th class="text-end">' . Tools::trans('price') . '</th>'
            . self::renderNewProductHeads()
            . '<th class="text-end">' . Tools::trans('stock') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }

    protected static function fabricantes(): string
    {
        $options = '<option value="">' . Tools::trans('manufacturer') . '</option>'
            . '<option value="">------</option>';
        foreach (Fabricantes::all() as $man) {
            $options .= '<option value="' . $man->codfabricante . '">' . $man->nombre . '</option>';
        }

        return '<select name="fp_codfabricante" class="form-select" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function familias(): string
    {
        $options = '<option value="">' . Tools::trans('family') . '</option>'
            . '<option value="">------</option>';

        foreach (Familias::children() as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">' . $fam->descripcion . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam);
        }

        return '<select name="fp_codfamilia" class="form-select" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function html(?string $text): string
    {
        $decoded = html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return htmlspecialchars($decoded, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected static function getProducts(): array
    {
        $dataBase = new DataBase();
        $dataBase->connect();

        // Los mods pueden añadir columnas con cualquier campo de variantes o productos.
        // Listamos primero p.* y después v.*, de forma que en los campos comunes
        // (referencia, precio, idproducto, stockfis) prevalezca el valor de la variante.
        $sql = 'SELECT p.*, v.*, pp.refproveedor, pp.neto, COALESCE(s.disponible, 0) as disponible'
            . ' FROM variantes v'
            . ' LEFT JOIN productos p ON v.idproducto = p.idproducto'
            . ' LEFT JOIN stocks s ON v.referencia = s.referencia'
            . ' AND s.codalmacen = ' . $dataBase->var2str(self::$codalmacen)
            . ' LEFT JOIN productosprov pp ON pp.referencia = p.referencia'
            . ' AND pp.codproveedor = ' . $dataBase->var2str(self::$codproveedor)
            . ' AND pp.coddivisa = ' . $dataBase->var2str(self::$coddivisa)
            . ' WHERE p.secompra = true AND p.bloqueado = false';

        if (self::$codfabricante) {
            $sql .= ' AND p.codfabricante = ' . $dataBase->var2str(self::$codfabricante);
        }

        if (self::$codfamilia) {
            $codFamilias = [$dataBase->var2str(self::$codfamilia)];

            // buscamos las subfamilias
            $familia = new Familia();
            if ($familia->load(self::$codfamilia)) {
                foreach ($familia->getSubfamilias() as $fam) {
                    $codFamilias[] = $dataBase->var2str($fam->codfamilia);
                }
            }

            $sql .= ' AND p.codfamilia IN (' . implode(',', $codFamilias) . ')';
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
                $sql .= " ORDER BY p.descripcion ASC";
                break;

            case 'price_desc':
                $sql .= " ORDER BY v.precio DESC";
                break;

            case 'ref_asc':
                $sql .= " ORDER BY v.referencia ASC";
                break;

            case 'stock_desc':
                $sql .= " ORDER BY disponible DESC";
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
            $attValor->load($id);
            self::$idatributovalores[$id] = $attValor->descripcion;
        }

        return ', ' . self::$idatributovalores[$id];
    }

    protected static function modalProductos(): string
    {
        return '<div class="modal" id="findProductModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-cubes fa-fw"></i> ' . Tools::trans('products') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-2">'
            . '<div class="col-sm-6 col-md-12 col-lg mb-2">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="productModalInput" placeholder="' . Tools::trans('search')
            . '" onkeyup="return purchasesFormActionWait(\'find-product\', \'0\', event);"/>'
            . ''
            . '<button class="btn btn-primary btn-spin-action" type="button" onclick="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<i class="fa-solid fa-search"></i></button>'
            . ''
            . '</div>'
            . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::fabricantes() . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::familias() . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::orden() . '</div>'
            . '</div>'
            . '<div class="row g-2">'
            . '<div class="col-sm">'
            . '<div class="form-check">'
            . '<input type="checkbox" name="fp_comprado" value="1" class="form-check-input" id="comprado" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<label class="form-check-label" for="comprado">' . Tools::trans('previously-purchased-from-supplier') . '</label>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findProductList">' . static::renderProductList() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function modalProveedores(string $url): string
    {
        $trs = '';
        $where = [Where::isNull('fechabaja')];
        foreach (Proveedor::all($where, ['fechaalta' => 'DESC', 'LOWER(nombre)' => 'ASC'], 0, 50) as $pro) {
            $name = ($pro->nombre === $pro->razonsocial) ? $pro->nombre : $pro->nombre . ' <small>(' . $pro->razonsocial . ')</span>';
            $trs .= '<tr class="clickableRow" onclick="document.forms[\'purchasesForm\'][\'codproveedor\'].value = \''
                . $pro->codproveedor . '\'; $(\'#findSupplierModal\').modal(\'hide\'); purchasesFormAction(\'set-supplier\', \'0\'); return false;">'
                . '<td><i class="fa-solid fa-user fa-fw"></i> ' . $name . '</td>'
                . self::renderNewSupplier($pro)
                . '</tr>';
        }

        $newSupplierButton = '';
        $newSupplierModal = '';
        if (static::canCreateSupplier(Session::user())) {
            $newSupplierButton = '<button type="button" class="btn w-100 btn-success" onclick="return showNewSupplierModal();">'
                . '<i class="fa-solid fa-plus fa-fw"></i> ' . Tools::trans('new')
                . '</button>';
            $newSupplierModal = static::modalNewSupplier();
        }

        return '<div class="modal" id="findSupplierModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-scrollable">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-users fa-fw me-1"></i> ' . Tools::trans('suppliers') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . '</button>'
            . '</div>'
            . '<div class="modal-body p-0">'
            . '<div class="p-3">'
            . '<div class="input-group">'
            . '<input type="text" id="findSupplierInput" class="form-control" placeholder="' . Tools::trans('search') . '" />'
            . '<button type="button" class="btn btn-secondary"><i class="fa-solid fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '<table class="table table-hover mb-0">' . $trs . '</table></div>'
            . '<div class="modal-footer bg-light">'
            . $newSupplierButton
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . $newSupplierModal;
    }

    protected static function modalNewSupplier(): string
    {
        $company = Empresas::get(self::$idempresa);
        $countryOptions = '';
        $defaultCountry = Tools::settings('default', 'codpais');
        foreach (Paises::all() as $country) {
            $selected = $country->codpais === $defaultCountry ? ' selected' : '';
            $countryOptions .= '<option value="' . static::html($country->codpais) . '"' . $selected . '>'
                . static::html($country->nombre) . '</option>';
        }

        return '<div class="modal" id="newSupplierModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-lg modal-dialog-scrollable">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-user-plus fa-fw me-1"></i> '
            . Tools::trans('new-supplier') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-2">'
            . '<div class="col-sm-8"><label for="newSupplierName" class="form-label">' . Tools::trans('name') . '</label>'
            . '<input type="text" name="newsupplier_nombre" id="newSupplierName" class="form-control" maxlength="100" required></div>'
            . '<div class="col-sm-4"><label for="newSupplierCifnif" class="form-label">' . Tools::trans('cifnif') . '</label>'
            . '<input type="text" name="newsupplier_cifnif" id="newSupplierCifnif" class="form-control" maxlength="30"></div>'
            . '<div class="col-sm-4"><label for="newSupplierPhone" class="form-label">' . Tools::trans('phone') . '</label>'
            . '<input type="tel" name="newsupplier_telefono" id="newSupplierPhone" class="form-control" maxlength="30"></div>'
            . '<div class="col-sm-8"><label for="newSupplierEmail" class="form-label">' . Tools::trans('email') . '</label>'
            . '<input type="email" name="newsupplier_email" id="newSupplierEmail" class="form-control" maxlength="100"></div>'
            . '<div class="col-sm-9"><label for="newSupplierAddress" class="form-label">' . Tools::trans('address') . '</label>'
            . '<input type="text" name="newsupplier_direccion" id="newSupplierAddress" class="form-control" maxlength="200" required></div>'
            . '<div class="col-sm-3"><label for="newSupplierPostalCode" class="form-label">' . Tools::trans('zip-code') . '</label>'
            . '<input type="text" name="newsupplier_codpostal" id="newSupplierPostalCode" class="form-control" maxlength="10" value="'
            . static::html($company->codpostal) . '"></div>'
            . '<div class="col-sm-4"><label for="newSupplierCity" class="form-label">' . Tools::trans('city') . '</label>'
            . '<input type="text" name="newsupplier_ciudad" id="newSupplierCity" class="form-control" maxlength="100" value="'
            . static::html($company->ciudad) . '" required></div>'
            . '<div class="col-sm-4"><label for="newSupplierProvince" class="form-label">' . Tools::trans('province') . '</label>'
            . '<input type="text" name="newsupplier_provincia" id="newSupplierProvince" class="form-control" maxlength="100" value="'
            . static::html($company->provincia) . '" required></div>'
            . '<div class="col-sm-4"><label for="newSupplierCountry" class="form-label">' . Tools::trans('country') . '</label>'
            . '<select name="newsupplier_codpais" id="newSupplierCountry" class="form-select" required>' . $countryOptions . '</select></div>'
            . '</div>'
            . '</div>'
            . '<div class="modal-footer bg-light">'
            . '<button type="button" class="btn btn-secondary" onclick="return showSupplierModal();">'
            . '<i class="fa-solid fa-arrow-left fa-fw"></i> ' . Tools::trans('back') . '</button>'
            . '<button type="button" class="btn btn-success btn-spin-action" onclick="return createSupplierFromPurchases();">'
            . '<i class="fa-solid fa-save fa-fw"></i> ' . Tools::trans('save') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function canCreateSupplier(User $user): bool
    {
        return $user->can('EditProveedor', 'update');
    }

    protected static function orden(): string
    {
        return '<div class="input-group">'
            . '<span class="input-group-text"><i class="fa-solid fa-sort-amount-down-alt"></i></span>'
            . '<select name="fp_orden" class="form-select" onchange="return purchasesFormAction(\'find-product\', \'0\');">'
            . '<option value="">' . Tools::trans('sort') . '</option>'
            . '<option value="">------</option>'
            . '<option value="ref_asc">' . Tools::trans('reference') . '</option>'
            . '<option value="desc_asc">' . Tools::trans('description') . '</option>'
            . '<option value="price_desc">' . Tools::trans('price') . '</option>'
            . '<option value="stock_desc">' . Tools::trans('stock') . '</option>'
            . '</select>'
            . '</div>';
    }

    /**
     * Lista unificada (sin duplicados) de las columnas que los mods añaden al modal de productos.
     *
     * @return string[]
     */
    private static function newProductFields(): array
    {
        $fields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newProductFields() as $field) {
                if (false === in_array($field, $fields, true)) {
                    $fields[] = $field;
                }
            }
        }
        return $fields;
    }

    /**
     * Lista unificada (sin duplicados) de las columnas que los mods añaden al modal de proveedores.
     *
     * @return string[]
     */
    private static function newSupplierFields(): array
    {
        $fields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newSupplierFields() as $field) {
                if (false === in_array($field, $fields, true)) {
                    $fields[] = $field;
                }
            }
        }
        return $fields;
    }

    /**
     * Renderiza las celdas añadidas por los mods a una fila del modal de productos.
     * Recorre la misma lista de campos para todas las filas y emite una celda vacía
     * cuando ningún mod resuelve el campo, de forma que todas las filas tengan el mismo
     * número de columnas.
     *
     * @param array $row
     * @return string
     */
    private static function renderNewProduct(array $row): string
    {
        $html = '';
        foreach (self::newProductFields() as $field) {
            $cell = null;
            foreach (self::$mods as $mod) {
                $cell = $mod->renderField($row, $field);
                if ($cell !== null) {
                    break;
                }
            }
            $html .= $cell ?? '<td></td>';
        }
        return $html;
    }

    /**
     * Renderiza las cabeceras de las columnas añadidas por los mods al modal de productos.
     * Usa la misma lista de campos que renderNewProduct() para mantener alineadas cabecera y celdas.
     *
     * @return string
     */
    private static function renderNewProductHeads(): string
    {
        $html = '';
        foreach (self::newProductFields() as $field) {
            $cell = null;
            foreach (self::$mods as $mod) {
                $cell = $mod->renderFieldHead($field);
                if ($cell !== null) {
                    break;
                }
            }
            $html .= $cell ?? '<th></th>';
        }
        return $html;
    }

    /**
     * Renderiza las celdas añadidas por los mods a una fila del modal de proveedores.
     *
     * @param Proveedor $pro
     * @return string
     */
    private static function renderNewSupplier(Proveedor $pro): string
    {
        $html = '';
        foreach (self::newSupplierFields() as $field) {
            $cell = null;
            foreach (self::$mods as $mod) {
                $cell = $mod->renderField($pro, $field);
                if ($cell !== null) {
                    break;
                }
            }
            $html .= $cell ?? '<td></td>';
        }
        return $html;
    }

    private static function subfamilias(Familia $family, int $level = 1, array $visited = []): string
    {
        $visited[$family->codfamilia] = true;
        $options = '';
        foreach (Familias::children($family->codfamilia) as $fam) {
            if (isset($visited[$fam->codfamilia])) {
                continue;
            }

            $options .= '<option value="' . $fam->codfamilia . '">'
                . str_repeat('-', $level) . ' ' . $fam->descripcion
                . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam, $level + 1, $visited);
        }

        return $options;
    }
}
