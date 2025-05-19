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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Description of SalesModalHTML
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/SalesModalHTML
 */
class SalesModalHTML
{
    /** @var string */
    protected static $codalmacen;

    /** @var string */
    protected static $codcliente;

    /** @var string */
    protected static $codfabricante;

    /** @var string */
    protected static $codfamilia;

    /** @var array */
    protected static $idatributovalores = [];

    /** @var string */
    protected static $orden;

    /** @var string */
    protected static $query;

    /** @var bool */
    protected static $vendido;

    public static function apply(SalesDocument &$model, array $formData)
    {
        self::$codalmacen = $model->codalmacen;
        self::$codcliente = $model->codcliente;
        self::$codfabricante = $formData['fp_codfabricante'] ?? '';
        self::$codfamilia = $formData['fp_codfamilia'] ?? '';
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$vendido = (bool)($formData['fp_vendido'] ?? false);
        self::$query = isset($formData['fp_query']) ?
            Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';
    }

    public static function render(SalesDocument $model, string $url, User $user, ControllerPermissions $permissions): string
    {
        self::$codalmacen = $model->codalmacen;

        $i18n = new Translator();
        return $model->editable ? static::modalClientes($i18n, $url, $user, $permissions) . static::modalProductos($i18n) : '';
    }

    public static function renderProductList(): string
    {
        $tbody = '';
        $i18n = new Translator();
        foreach (static::getProducts() as $row) {
            $cssClass = $row['nostock'] ? 'table-info clickableRow' : ($row['disponible'] > 0 ? 'clickableRow' : 'table-warning clickableRow');
            $description = Tools::textBreak($row['descripcion'], 120)
                . static::idatributovalor($row['idatributovalor1'])
                . static::idatributovalor($row['idatributovalor2'])
                . static::idatributovalor($row['idatributovalor3'])
                . static::idatributovalor($row['idatributovalor4']);
            $tbody .= '<tr class="' . $cssClass . '" onclick="$(\'#findProductModal\').modal(\'hide\');'
                . ' return salesFormAction(\'add-product\', \'' . $row['referencia'] . '\');">'
                . '<td><b>' . $row['referencia'] . '</b> ' . $description . '</td>'
                . '<td class="text-right">' . str_replace(' ', '&nbsp;', Tools::money($row['precio'])) . '</td>';

            if (self::$vendido) {
                $tbody .= '<td class="text-right">' . str_replace(' ', '&nbsp;', Tools::money($row['ultimo_precio'])) . '</td>';
            }

            $tbody .= '<td class="text-right">' . $row['disponible'] . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="4">' . $i18n->trans('no-data') . '</td></tr>';
        }

        $extraTh = self::$vendido ?
            '<th class="text-right">' . $i18n->trans('last-price-sale') . '</th>' :
            '';
        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('product') . '</th>'
            . '<th class="text-right">' . $i18n->trans('price') . '</th>'
            . $extraTh
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

        return '<select name="fp_codfamilia" class="form-control" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function getClientes(User $user, ControllerPermissions $permissions): array
    {
        // buscamos en caché
        $cacheKey = 'model-Cliente-sales-modal-' . $user->nick;
        $clientes = Cache::get($cacheKey);
        if (is_array($clientes)) {
            return $clientes;
        }

        // ¿El usuario tiene permiso para ver todos los clientes?
        $showAll = false;
        foreach (RoleAccess::allFromUser($user->nick, 'EditCliente') as $access) {
            if (false === $access->onlyownerdata) {
                $showAll = true;
            }
        }

        // consultamos la base de datos
        $cliente = new Cliente();
        $where = [new DataBaseWhere('fechabaja', null, 'IS')];
        if ($permissions->onlyOwnerData && !$showAll) {
            $where[] = new DataBaseWhere('codagente', $user->codagente);
            $where[] = new DataBaseWhere('codagente', null, 'IS NOT');
        }
        $clientes = $cliente->all($where, ['LOWER(nombre)' => 'ASC']);

        // guardamos en caché
        Cache::set($cacheKey, $clientes);

        return $clientes;
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

        if (self::$vendido) {
            $sql .= ' AND v.referencia IN (SELECT referencia FROM lineasfacturascli'
                . ' LEFT JOIN facturascli ON lineasfacturascli.idfactura = facturascli.idfactura'
                . ' WHERE codcliente = ' . $dataBase->var2str(self::$codcliente) . ')';
        }

        if (self::$query) {
            $words = explode(' ', self::$query);
            if (count($words) === 1) {
                $sql .= " AND (LOWER(v.codbarras) = " . $dataBase->var2str(self::$query)
                    . " OR LOWER(v.referencia) LIKE '%" . self::$query . "%'"
                    . " OR LOWER(p.descripcion) LIKE '%" . self::$query . "%')";
            } elseif (count($words) > 1) {
                $sql .= " AND (LOWER(v.referencia) LIKE '%" . self::$query . "%' OR (";
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

        $results = $dataBase->selectLimit($sql);
        if (self::$vendido) {
            static::setProductsLastPrice($dataBase, $results);
        }

        return $results;
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

    protected static function modalClientes(Translator $i18n, string $url, User $user, ControllerPermissions $permissions): string
    {
        $trs = '';


        foreach (static::getClientes($user, $permissions) as $cli) {
            $name = ($cli->nombre === $cli->razonsocial) ? $cli->nombre : $cli->nombre . ' <small>(' . $cli->razonsocial . ')</span>';
            $trs .= '<tr class="clickableRow" onclick="document.forms[\'salesForm\'][\'codcliente\'].value = \''
                . $cli->codcliente . '\'; $(\'#findCustomerModal\').modal(\'hide\'); salesFormAction(\'set-customer\', \'0\'); return false;">'
                . '<td><i class="fas fa-user fa-fw"></i> ' . $name . '</td>'
                . '</tr>';
        }

        $linkAgent = '';
        if ($user->codagente) {
            $linkAgent = '&codagente=' . $user->codagente;
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
            . '<a href="EditCliente?return=' . urlencode($url) . $linkAgent . '" class="btn btn-block btn-success">'
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
            . '<div class="col-sm mb-2">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="productModalInput" placeholder="' . $i18n->trans('search')
            . '" onkeyup="return salesFormActionWait(\'find-product\', \'0\', event);"/>'
            . '<div class="input-group-append">'
            . '<button class="btn btn-primary btn-spin-action" type="button" onclick="return salesFormAction(\'find-product\', \'0\');">'
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
            . '<input type="checkbox" name="fp_vendido" value="1" class="form-check-input" id="vendido" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . '<label class="form-check-label" for="vendido">' . $i18n->trans('previously-sold-to-customer') . '</label>'
            . '</div>'
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

    protected static function setProductsLastPrice(DataBase $db, array &$items): void
    {
        foreach ($items as $key => $item) {
            // obtenemos el último precio en facturas de este cliente
            $sql = 'SELECT pvpunitario FROM lineasfacturascli l'
                . ' LEFT JOIN facturascli f ON f.idfactura = l.idfactura'
                . ' WHERE f.codcliente = ' . $db->var2str(self::$codcliente)
                . ' AND l.referencia = ' . $db->var2str($item['referencia'])
                . ' ORDER BY f.fecha DESC';
            foreach ($db->selectLimit($sql, 1) as $row) {
                $items[$key]['ultimo_precio'] = $row['pvpunitario'];
                continue 2;
            }

            // no hay facturas, asignamos el último precio de venta
            $items[$key]['ultimo_precio'] = $item['precio'];
        }
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
