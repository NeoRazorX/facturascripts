<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\Empresa;

/**
 * Controller to list the items in the Agentes model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListAgente extends ListController
{

    /**
     * Company list used by filters
     *
     * @var array
     */
    protected $companyList;

    /**
     * Initializes all the objects and properties.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     * @param string          $uri
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->companyList = $this->codeModel->all(Empresa::tableName(), Empresa::primaryColumn(), 'nombre');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'agents';
        $data['icon'] = 'fas fa-id-badge';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addAgentView();
        $this->addCommissionView();
        $this->addSettlementView();
    }

    /**
     * Add Agent View
     *
     * @param string $viewName
     */
    private function addAgentView($viewName = 'ListAgente')
    {
        /// View
        $this->addView($viewName, 'Agente', 'agents', 'fas fa-id-badge');
        $this->addSearchFields($viewName, ['nombre', 'codagente', 'email']);

        /// Order by
        $this->addOrderBy($viewName, ['codagente'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
        $this->addOrderBy($viewName, ['provincia'], 'province');

        /// Filters
        $selectValues = $this->codeModel->all('agentes', 'cargo', 'cargo');
        $this->addFilterSelect($viewName, 'cargo', 'position', 'cargo', $selectValues);

        $cityValues = $this->codeModel->all('agentes', 'ciudad', 'ciudad');
        $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cityValues);

        $values = [
            ['label' => $this->i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $this->i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $this->i18n->trans('all'), 'where' => []]
        ];
        $this->addFilterSelectWhere($viewName, 'status', $values);
    }

    /**
     * Add Commission View
     *
     * @param string $viewName
     */
    private function addCommissionView($viewName = 'ListComision')
    {
        /// View
        $this->addView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
        $this->addSearchFields($viewName, ['codagente', 'codcliente', 'CAST(porcentaje AS VARCHAR)', 'codfamilia', 'idproducto']);

        /// Order By
        $this->addOrderBy($viewName, ['idempresa', 'codagente', 'porcentaje'], 'company');
        $this->addOrderBy($viewName, ['codagente', 'codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'agent', 1);
        $this->addOrderBy($viewName, ['codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'customer');
        $this->addOrderBy($viewName, ['codfamilia', 'idproducto', 'porcentaje'], 'family');

        /// Filters
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $this->companyList);

        $this->addFilterAutocomplete($viewName, 'agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre');
        $this->addFilterAutocomplete($viewName, 'customer', 'customer', 'codcliente', 'Cliente', 'codcliente');
        $this->addFilterAutocomplete($viewName, 'family', 'family', 'codfamilia', 'Familia', 'codfamilia');
        $this->addFilterAutocomplete($viewName, 'product', 'product', 'referencia', 'Producto', 'referencia', 'descripcion');
    }

    /**
     * Add Settled Commission View
     *
     * @param string $viewName
     */
    private function addSettlementView($viewName = 'ListLiquidacionComision')
    {
        /// View
        $this->addView($viewName, 'ModelView\LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
        $this->addSearchFields($viewName, ['agentes.nombre', 'facturasprov.codigo']);

        /// Order By
        $this->addOrderBy($viewName, ['fecha', 'idliquidacion'], 'date', 2);
        $this->addOrderBy($viewName, ['codagente', 'fecha'], 'agent');

        /// Filters
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'ejercicios.idempresa', $this->companyList);
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'liquidacioncomision.fecha');
        $this->addFilterAutocomplete($viewName, 'agent', 'agent', 'liquidacioncomision.codagente', 'agentes', 'codagente', 'nombre');
    }
}
