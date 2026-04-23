<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Contract\SalesModalInterface;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Description of SalesModalHTML
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /** @var SalesModalInterface[] */
    private static $mods = [];

    public static function addMod(SalesModalInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function apply(SalesDocument &$model, array $formData): void
    {
        // mods
        foreach (self::$mods as $mod) {
            if (false === $mod->applyBefore($model, $formData)) {
                return;
            }
        }

        self::$codalmacen = $model->codalmacen;
        self::$codcliente = $model->codcliente;
        self::$codfabricante = $formData['fp_codfabricante'] ?? '';
        self::$codfamilia = $formData['fp_codfamilia'] ?? '';
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$vendido = (bool)($formData['fp_vendido'] ?? false);
        self::$query = isset($formData['fp_query']) ?
            Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';

        // mods
        foreach (self::$mods as $mod) {
            if (false === $mod->apply($model, $formData)) {
                return;
            }
        }
    }

    public static function assets(): void
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    /**
     * @throws KernelException
     */
    public static function render(SalesDocument $model, string $url): string
    {
        self::$codalmacen = $model->codalmacen;

        if (empty($model->id()) && !$model->editable) {
            return '<div class="alert alert-warning mt-4">'
                . '<i class="fa-solid fa-triangle-exclamation fa-fw"></i> ' . Tools::trans('default-status-non-editable')
                . '</div>';
        }

        return $model->editable ? static::modalClientes($url) . static::modalProductos() : '';
    }

    /**
     * @throws KernelException
     */
    public static function renderProductList(): string
    {
        $tbody = '';
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
                . '<td class="text-end">' . str_replace(' ', '&nbsp;', Tools::money($row['precio'])) . '</td>';

            $tbody .= self::renderNewProduct($row);

            if (self::$vendido) {
                $tbody .= '<td class="text-end">' . str_replace(' ', '&nbsp;', Tools::money($row['ultimo_precio'])) . '</td>';
            }

            $tbody .= '<td class="text-end">' . $row['disponible'] . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="4">' . Tools::trans('no-data') . '</td></tr>';
        }

        $extraTh = self::$vendido ?
            '<th class="text-end">' . Tools::trans('last-price-sale') . '</th>' :
            '';
        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . Tools::trans('product') . '</th>'
            . '<th class="text-end">' . Tools::trans('price') . '</th>'
            . self::renderNewProductHeads()
            . $extraTh
            . '<th class="text-end">' . Tools::trans('stock') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }

    public static function fabricantes(): string
    {
        $options = '<option value="">' . Tools::trans('manufacturer') . '</option>'
            . '<option value="">------</option>';
        foreach (Fabricante::all([], ['nombre' => 'ASC']) as $man) {
            $options .= '<option value="' . $man->codfabricante . '">' . $man->nombre . '</option>';
        }

        return '<select name="fp_codfabricante" class="form-select" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function familias(): string
    {
        $options = '<option value="">' . Tools::trans('family') . '</option>'
            . '<option value="">------</option>';

        $where = [Where::isNull('madre')];
        $orderBy = ['descripcion' => 'ASC'];
        foreach (Familia::all($where, $orderBy) as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">' . $fam->descripcion . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam);
        }

        return '<select name="fp_codfamilia" class="form-select" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . $options . '</select>';
    }

    protected static function getClientes(User $user, ControllerPermissions $permissions): array
    {
        $cacheKey = 'model-Cliente-sales-modal-' . $user->nick;

        return Cache::remember($cacheKey, function () use ($user, $permissions) {
            // ¿El usuario tiene permiso para ver todos los clientes?
            $showAll = false;
            foreach (RoleAccess::allFromUser($user->nick, 'EditCliente') as $access) {
                if (false === $access->onlyownerdata) {
                    $showAll = true;
                }
            }

            // consultamos la base de datos
            $where = [Where::isNull('fechabaja')];
            if ($permissions->onlyOwnerData && !$showAll) {
                $where[] = Where::eq('codagente', $user->codagente);
                $where[] = Where::isNotNull('codagente');
            }
            return Cliente::all($where, ['LOWER(nombre)' => 'ASC'], 0, 50);
        });
    }

    /**
     * @throws KernelException
     */
    protected static function getProducts(): array
    {
        $dataBase = new DataBase();
        $dataBase->connect();

        // Los mods pueden añadir cualquier campo de variantes o productos.
        $sql = 'SELECT v.*, p.*, COALESCE(s.disponible, 0) as disponible, p.nostock'
            . ' FROM variantes v'
            . ' LEFT JOIN productos p ON v.idproducto = p.idproducto'
            . ' LEFT JOIN stocks s ON v.referencia = s.referencia AND s.codalmacen = ' . $dataBase->var2str(self::$codalmacen)
            . ' WHERE p.sevende = true AND p.bloqueado = false';

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
                $sql .= " ORDER BY p.descripcion ASC";
                break;

            case 'price_desc':
                $sql .= " ORDER BY v.precio DESC";
                break;

            case 'ref_asc':
                $sql .= " ORDER BY v.referencia ASC";
                break;

            case 'stock_desc':
                $sql .= " ORDER BY COALESCE(s.disponible, 0) DESC";
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
            $attValor->load($id);
            self::$idatributovalores[$id] = $attValor->descripcion;
        }

        return ', ' . self::$idatributovalores[$id];
    }

    protected static function modalClientes(string $url): string
    {
        $trs = '';
        $user = Session::user();
        $permissions = Session::permissions();

        foreach (static::getClientes($user, $permissions) as $cli) {
            $name = ($cli->nombre === $cli->razonsocial) ? $cli->nombre : $cli->nombre . ' <small>(' . $cli->razonsocial . ')</span>';
            $trs .= '<tr class="clickableRow" onclick="document.forms[\'salesForm\'][\'codcliente\'].value = \''
                . $cli->codcliente . '\'; $(\'#findCustomerModal\').modal(\'hide\'); salesFormAction(\'set-customer\', \'0\'); return false;">'
                . '<td><i class="fa-solid fa-user fa-fw"></i> ' . $name . '</td>'
                . self::renderNewCustomer($cli)
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
            . '<h5 class="modal-title"><i class="fa-solid fa-users fa-fw"></i> ' . Tools::trans('customers') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" />'
            . '</div>'
            . '<div class="modal-body p-0">'
            . '<div class="p-3">'
            . '<div class="input-group">'
            . '<input type="text" id="findCustomerInput" class="form-control" placeholder="' . Tools::trans('search') . '" />'
            . '<div class="input-group-apend">'
            . '<button type="button" class="btn btn-primary"><i class="fa-solid fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<table class="table table-hover mb-0">' . $trs . '</table></div>'
            . '<div class="modal-footer bg-light">'
            . '<a href="EditCliente?return=' . urlencode($url) . $linkAgent . '" class="btn w-100 btn-success">'
            . '<i class="fa-solid fa-plus fa-fw"></i> ' . Tools::trans('new')
            . '</a>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * @throws KernelException
     */
    protected static function modalProductos(): string
    {
        return '<div class="modal" id="findProductModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-cubes fa-fw"></i> ' . Tools::trans('products') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" />'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-2">'
            . '<div class="col-sm-6 col-md-12 col-lg mb-2">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="productModalInput" placeholder="' . Tools::trans('search')
            . '" onkeyup="return salesFormActionWait(\'find-product\', \'0\', event);"/>'
            . '<button class="btn btn-primary btn-spin-action" type="button" onclick="return salesFormAction(\'find-product\', \'0\');">'
            . '<i class="fa-solid fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::fabricantes() . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::familias() . '</div>'
            . '<div class="col-sm-6 col-md-4 col-lg mb-2">' . static::orden() . '</div>'
            . '</div>'
            . '<div class="row g-2">'
            . '<div class="col-sm">'
            . '<div class="form-check">'
            . '<input type="checkbox" name="fp_vendido" value="1" class="form-check-input" id="vendido" onchange="return salesFormAction(\'find-product\', \'0\');">'
            . '<label class="form-check-label" for="vendido">' . Tools::trans('previously-sold-to-customer') . '</label>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findProductList">' . static::renderProductList() . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function orden(): string
    {
        return '<div class="input-group">'
            . '<span class="input-group-text"><i class="fa-solid fa-sort-amount-down-alt"></i></span>'
            . '<select name="fp_orden" class="form-select" onchange="return salesFormAction(\'find-product\', \'0\');">'
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
     * @throws KernelException
     */
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

    /**
     * Renderiza las columnas añadidas por los mods al modal de clientes.
     *   - Primero unifica las columnas para evitar duplicados.
     *   - Los mods deben retornar el HTML completo de la columna incluyendo las etiquetas '<td>'.
     *     Esto permite al mod aplicar clases e ids personalizables.
     *     Ejemplos:
     *        '<td> xxxxx </td>'
     *        '<td class="myColumn"> xxxxx </td>'
     *        '<td id="myColumnXXX"> xxxxx </td>'
     *
     * @param Cliente $cli
     * @return string
     */
    private static function renderNewCustomer(Cliente $cli): string
    {
        // cargamos las nuevas columnas
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newCustomerFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos las columnas
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($cli, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    /**
     * Renderiza las columnas añadidas por los mods al modal de productos.
     *   - Primero unifica las columnas para evitar duplicados.
     *   - Los mods deben retornar el HTML completo de la columna. <td> xxxxx </td>
     *
     * @param array $row
     * @return string
     */
    private static function renderNewProduct(array $row): string
    {
        // cargamos las nuevas columnas
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newProductFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos las columnas
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($row, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    /**
     * Renderiza los titulos de las columnas añadidas por los mods.
     * *   - Primero unifica las columnas para evitar duplicados.
     * *   - Los mods deben retornar el HTML completo de la columna. <th> xxxxx </th>
     *
     * @return string
     */
    private static function renderNewProductHeads(): string
    {
        // cargamos las nuevas columnas
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newProductFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos las columnas
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderFieldHead($field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function subfamilias(Familia $family, int $level = 1): string
    {
        $options = '';
        foreach ($family->getSubfamilias() as $fam) {
            $options .= '<option value="' . $fam->codfamilia . '">'
                . str_repeat('-', $level) . ' ' . $fam->descripcion
                . '</option>';

            // añadimos las subfamilias de forma recursiva
            $options .= static::subfamilias($fam, $level + 1);
        }

        return $options;
    }
}
