<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the FormatoDocumento model
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class ListFormatoDocumento extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'document-configuration';
        $data['icon'] = 'fas fa-copy';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createFormatView($viewName = 'ListFormatoDocumento')
    {
        $this->addView($viewName, 'FormatoDocumento', 'printing-formats', 'fas fa-print');
        $this->addSearchFields($viewName, ['titulo', 'texto']);
        $this->addOrderBy($viewName, ['id'], 'id');
        $this->addOrderBy($viewName, ['titulo'], 'title');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSequenceView($viewName = 'ListSecuenciaDocumento')
    {
        $this->addView($viewName, 'SecuenciaDocumento', 'sequences', 'fas fa-code');
        $this->addSearchFields($viewName, ['patron', 'tipodoc']);
        $this->addOrderBy($viewName, ['codejercicio'], 'exercise');
        $this->addOrderBy($viewName, ['codserie'], 'serie');
        $this->addOrderBy($viewName, ['numero'], 'number');

        /// Filters
        $warehouses = $this->codeModel->all('empresas', 'idempresa', 'nombre');
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $warehouses);

        $exercises = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');
        $this->addFilterSelect($viewName, 'codejercicio', 'exercise', 'codejercicio', $exercises);

        $series = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect($viewName, 'codserie', 'serie', 'codserie', $series);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createStateView($viewName = 'ListEstadoDocumento')
    {
        $this->addView($viewName, 'EstadoDocumento', 'states', 'fas fa-tags');
        $this->addSearchFields($viewName, ['nombre']);
        $this->addOrderBy($viewName, ['idestado'], 'id');
        $this->addOrderBy($viewName, ['nombre'], 'name');

        $types = $this->codeModel->all('estados_documentos', 'tipodoc', 'tipodoc');
        $this->addFilterSelect($viewName, 'tipodoc', 'doc-type', 'tipodoc', $types);

        $generateTypes = $this->codeModel->all('estados_documentos', 'generadoc', 'generadoc');
        $this->addFilterSelect($viewName, 'generadoc', 'generate-doc-type', 'generadoc', $generateTypes);

        $this->addFilterSelect($viewName, 'actualizastock', 'update-stock', 'actualizastock', $this->getActualizastockValues());
        $this->addFilterCheckbox($viewName, 'predeterminado', 'default', 'predeterminado');
        $this->addFilterCheckbox($viewName, 'editable', 'editable', 'editable');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createFormatView();
        $this->createStateView();
        $this->createSequenceView();
    }

    /**
     * 
     * @return array
     */
    protected function getActualizastockValues()
    {
        return [
            ['code' => null, 'description' => '------'],
            ['code' => -2, 'description' => $this->i18n->trans('book')],
            ['code' => -1, 'description' => $this->i18n->trans('subtract')],
            ['code' => 0, 'description' => $this->i18n->trans('do-nothing')],
            ['code' => 1, 'description' => $this->i18n->trans('add')],
            ['code' => 2, 'description' => $this->i18n->trans('foresee')],
        ];
    }
}
