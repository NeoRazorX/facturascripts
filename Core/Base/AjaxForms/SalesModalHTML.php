<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Familia;

/**
 * Description of SalesModalHTML
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SalesModalHTML
{

    /**
     * @var string
     */
    protected static $codalmacen;

    /**
     * @var string
     */
    protected static $codfabricante;

    /**
     * @var string
     */
    protected static $codfamilia;

    /**
     * @var array
     */
    protected static $idatributovalores = [];

    /**
     * @var string
     */
    protected static $orden;

    /**
     * @var string
     */
    protected static $query;

    public static function apply(SalesDocument &$model, array $formData)
    {
        self::$codalmacen = $model->codalmacen;
        self::$codfabricante = $formData['fp_codfabricante'] ?? '';
        self::$codfamilia = $formData['fp_codfamilia'] ?? '';
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$query = isset($formData['fp_query']) ?
            ToolBox::utils()->noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';
    }

    public static function render(SalesDocument $model, string $url = ''): string
    {
        self::$codalmacen = $model->codalmacen;

        $i18n = new Translator();
        return $model->editable ? static::modalClientes($i18n, $url) . static::modalProductos($i18n) : '';
    }

    public static function renderProductList(): string
    {
        $tbody = '';
        $i18n = new Translator();
        foreach (static::getProducts() as $row) {
            $cssClass = $row['nostock'] ? 'table-info clickableRow' : ($row['disponible'] > 0 ? 'clickableRow' : 'table-warning clickableRow');
            $description = ToolBox::utils()->trueTextBreak($row['descripcion'], 120)
                . static::idatributovalor($row['idatributovalor1'])
                . static::idatributovalor($row['idatributovalor2'])
                . static::idatributovalor($row['idatributovalor3'])
                . static::idatributovalor($row['idatributovalor4']);
            $tbody .= '<tr class="' . $cssClass . '" onclick="$(\'#findProductModal\').modal(\'hide\');'
                . ' return salesFormAction(\'add-product\', \'' . $row['referencia'] . '\');">'
                . '<td><b>' . $row['referencia'] . '</b> ' . $description . '</td>'
                . '<td class="text-right">' . str_replace(' ', '&nbsp;', ToolBox::coins()->format($row['precio'])) . '</td>'
                . '<td class="text-right">' . $row['disponible'] . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="3">' . $i18n->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('product') . '</th>'
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

        return '<select name="fp_codfabricante" class="form-control" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function familias(Translator $i18n): string
    {
        $familia = new Familia();
        $options = '<option value="">' . $i18n->trans('family') . '</option>'
            . '<option value="">------</option>';
        foreach ($familia->all([], ['descripcion' => 'ASC'], 0, 0) as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">' . $fam->descripcion . '</option>';
        }

        return '<select name="fp_codfamilia" class="form-control" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function getProducts(): array
    {
        $dataBase = new DataBase();
        $sql = 'SELECT v.referencia, p.descripcion, v.idatributovalor1, v.idatributovalor2, v.idatributovalor3,'
            . ' v.idatributovalor4, v.precio, COALESCE(s.disponible, 0) as disponible, p.nostock'
            . ' FROM variantes v'
            . ' LEFT JOIN productos p ON v.idproducto = p.idproducto'
            . ' LEFT JOIN stocks s ON v.referencia = s.referencia AND s.codalmacen = ' . $dataBase->var2str(self::$codalmacen)
            . ' WHERE p.sevende = true AND p.bloqueado = false';

        if (self::$codfabricante) {
            $sql .= ' AND codfabricante = ' . $dataBase->var2str(self::$codfabricante);
        }

        if (self::$codfamilia) {
            $sql .= ' AND codfamilia = ' . $dataBase->var2str(self::$codfamilia);
        }

        $words = explode(' ', self::$query);
        if (count($words) === 1) {
            $sql .= " AND (LOWER(v.codbarras) = " . $dataBase->var2str(self::$query)
                . " OR LOWER(v.referencia) LIKE '" . self::$query . "%'"
                . " OR LOWER(p.descripcion) LIKE '%" . self::$query . "%')";
        } elseif (count($words) > 1) {
            $sql .= " AND (LOWER(v.referencia) LIKE '" . self::$query . "%' OR (";
            foreach ($words as $wc => $word) {
                $sql .= $wc > 0 ?
                    " AND LOWER(p.descripcion) LIKE '%" . $word . "%'" :
                    "LOWER(p.descripcion) LIKE '%" . $word . "%'";
            }
            $sql .= "))";
        }

        switch (self::$orden) {
            case 'desc_asc':
                $sql .= " ORDER BY 2 ASC";
                break;

            case 'price_desc':
                $sql .= " ORDER BY 7 DESC";
                break;

            case 'ref_asc':
                $sql .= " ORDER BY 1 ASC";
                break;

            case 'stock_desc':
                $sql .= " ORDER BY 8 DESC";
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

    protected static function modalClientes(Translator $i18n, string $url): string
    {
        $trs = '';
        $cliente = new Cliente();
        $where = [new DataBaseWhere('fechabaja', null, 'IS')];
        foreach ($cliente->all($where, ['nombre' => 'ASC']) as $cli) {
            $name = ($cli->nombre === $cli->razonsocial) ? $cli->nombre : $cli->nombre . ' <small>(' . $cli->razonsocial . ')</span>';
            $trs .= '<tr class="clickableRow" onclick="document.forms[\'salesForm\'][\'codcliente\'].value = \''
                . $cli->codcliente . '\'; $(\'#findCustomerModal\').modal(\'hide\'); salesFormAction(\'set-customer\', \'0\'); return false;">'
                . '<td><i class="fas fa-user fa-fw"></i> ' . $name . '</td>'
                . '</tr>';
        }

        return '<div class="modal" id="findCustomerModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-scrollable">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fas fa-users fa-fw"></i> ' . $i18n->trans('customers') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body p-0">'
            . '<div class="p-3">'
            . '<div class="input-group">'
            . '<input type="text" id="findCustomerInput" class="form-control" placeholder="' . $i18n->trans('search') . '" />'
            . '<div class="input-group-apend">'
            . '<button type="button" class="btn btn-primary"><i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<table class="table table-hover mb-0">' . $trs . '</table></div>'
            . '<div class="modal-footer bg-light">'
            . '<a href="EditCliente?return=' . urlencode($url) . '" class="btn btn-block btn-success">'
            . '<i class="fas fa-plus fa-fw"></i> ' . $i18n->trans('new')
            . '</a>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
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
            . '<div class="col-sm">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="findProductInput" placeholder="' . $i18n->trans('search')
            . '" onkeyup="return salesFormActionWait(\'find-product\', \'0\');"/>'
            . '<div class="input-group-append">'
            . '<button class="btn btn-primary btn-spin-action" type="button" onclick="return salesFormAction(\'find-product\', \'0\');">'
            . '<i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="col-sm">'
            . static::fabricantes($i18n)
            . '</div>'
            . '<div class="col-sm">'
            . static::familias($i18n)
            . '</div>'
            . '<div class="col-sm">'
            . static::orden($i18n)
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findProductList">' . static::renderProductList() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function orden(Translator $i18n): string
    {
        return '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-sort-amount-down-alt"></i></span></div>'
            . '<select name="fp_orden" class="form-control" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . '<option value="">' . $i18n->trans('sort') . '</option>'
            . '<option value="">------</option>'
            . '<option value="ref_asc">' . $i18n->trans('reference') . '</option>'
            . '<option value="desc_asc">' . $i18n->trans('description') . '</option>'
            . '<option value="price_desc">' . $i18n->trans('price') . '</option>'
            . '<option value="stock_desc">' . $i18n->trans('stock') . '</option>'
            . '</select>'
            . '</div>';
    }
}
