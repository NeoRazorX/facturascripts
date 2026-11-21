<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Lib\Block\FormBlock;
use FacturaScripts\Core\Tools;

/**
 * Controlador de bloques para editar un registro: el primer bloque es su formulario y el
 * resto de bloques, que reciben ese registro para filtrar sus datos, solo se muestran
 * cuando el registro ya existe.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class EditBlockController extends BlockController
{
    /**
     * Devuelve el nombre de la clase del modelo del registro principal.
     */
    abstract public function getModelClassName(): string;

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function hasRecord(): bool
    {
        return $this->hasData;
    }

    public function mainBlock(): FormBlock
    {
        /** @var FormBlock $block */
        $block = $this->viewBlock($this->mainBlockName());

        return $block;
    }

    public function mainBlockName(): string
    {
        return 'Edit' . $this->getModelClassName();
    }

    /**
     * @return ModelClass
     */
    public function record()
    {
        return $this->mainBlock()->model();
    }

    /**
     * Comprueba que el usuario puede acceder al registro principal antes de ejecutar la acción.
     * Las acciones se ejecutan antes de cargar los datos y algunas cargan el registro por su
     * cuenta, así que no basta con la comprobación de loadRecord().
     */
    protected function assertActionOwnerData(string $action): void
    {
        if ('' === $action || false === $this->permissions->onlyOwnerData) {
            return;
        }

        // el registro principal se identifica por code en la url y, si la petición va dirigida
        // a su bloque, también por code o por su clave primaria en el formulario
        $model = $this->record();
        $codes = [$this->request()->query('code')];
        if ($this->target() === $this->mainBlockName()) {
            $codes[] = $this->request()->input('code');
            $codes[] = $this->request()->input($model->primaryColumn());
        }

        foreach (array_unique(array_filter($codes, fn($code) => null !== $code && '' !== $code)) as $code) {
            $record = clone $model;
            if ($record->loadFromCode($code)) {
                $this->assertOwnerData($record);
            }
        }
    }

    protected function createBlocks(): void
    {
        $pageData = $this->getPageData();
        $this->page()->add(
            FormBlock::make($this->mainBlockName(), $this->getModelClassName(), $pageData['title'], $pageData['icon'])
                ->setSettings('btnPrint', true)
                ->showWithoutRecord()
        );
    }

    protected function loadRecord(): void
    {
        $block = $this->mainBlock();
        $view = $block->view();
        $view->processFormData($this->request(), $this->target() === $block->name() ? 'load' : 'preload');

        // necesitamos el identificador para cargar el registro: casi siempre es code, pero no siempre
        $primaryKey = $this->request()->input($view->model->primaryColumn());
        $code = $this->request()->query('code', $primaryKey);
        $view->loadData($code);
        $this->markLoaded($block);
        $this->pipe('loadBlock', $block);

        // ¿puede el usuario acceder a este registro? si no, se interrumpe la petición
        $this->assertOwnerData($view->model);

        $this->hasData = $view->model->exists();
        if ($this->hasData) {
            $this->title .= ' ' . $view->model->primaryDescription();
            return;
        }

        $action = (string)$this->request()->input('action', '');
        if ('' === $action && false === empty($code)) {
            Tools::log()->warning('record-not-found');
        }
    }
}
