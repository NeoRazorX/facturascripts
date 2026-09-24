<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\UIComponents\UIEditController;
use FacturaScripts\Core\Where;

/**
 * Formulario de edición de atributos de artículo construido sobre UIEditController.
 *
 * Replica EditAtributo mostrando el formulario del atributo y una lista inline
 * de sus valores (AtributoValor) filtrada por codatributo.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewEditAtributo extends UIEditController
{
    public function getModelClassName(): string
    {
        return 'Atributo';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'attribute';
        $data['icon'] = 'fa-solid fa-tshirt';
        return $data;
    }

    public function listUrl(): string
    {
        return 'NewListAtributo';
    }

    protected function getViewName(): string
    {
        return 'EditAtributo';
    }

    protected function buildForm(): void
    {
        $this->loadModel();

        $this->startGroup('data');

        $this->addComponent(
            ComponentText::make('nombre')
                ->setLabel('name')
                ->setRequired()
                ->setMaxLength(100)
        );

        $this->addComponent(
            ComponentText::make('codatributo')
                ->setLabel('code')
                ->setDescription('optional')
                ->setIcon('fa-solid fa-hashtag')
                ->setMaxLength(20)
                ->setReadOnlyDynamic()
                ->setCols(2)
        );

        $this->addComponent(
            ComponentNumber::make('num_selector')
                ->setLabel('selector-number')
                ->setIcon('fa-solid fa-folder-tree')
                ->setMin(0)
                ->setDecimals(0)
                ->setCols(2)
        );

        $this->addEditListView('EditAtributoValor', 'AtributoValor', 'attribute-values')
            ->setInLine(true);
    }

    protected function modifyUI(): void
    {
        parent::modifyUI();

        $model = $this->editModel;
        if ($model === null || !$model->exists()) {
            return;
        }

        $list = $this->listView('EditAtributoValor');
        if ($list === null) {
            return;
        }

        $list->processFormData($this->request, 'load');

        $code = $model->codatributo ?? '';
        if (empty($code)) {
            return;
        }

        $where = [Where::eq('codatributo', $code)];
        $list->loadData('', $where, ['orden' => 'ASC', 'id' => 'DESC']);
        $list->disableColumn('attribute');
    }
}
