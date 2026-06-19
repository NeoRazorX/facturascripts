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

namespace FacturaScripts\Core\Component;

use Exception;

/**
 * Trait que añade pestañas de bloques de componentes con nombre a cualquier subclase de BaseController.
 *
 * Incluye este trait en una subclase de PanelController y llama a addComponentBlock()
 * dentro de createViews() para registrar las pestañas. PanelController::privateCore()
 * debe invocar processActiveComponentBlock() tras el bucle de vistas habitual para que
 * el manejo de POST funcione correctamente. En GET puebla los valores de los componentes
 * desde el modelo principal; en POST valida todos los componentes del bloque activo y llama
 * a execAfterComponentBlock() si no hay errores — sobreescribe ese hook para persistir cambios.
 *
 * La capa Twig expone los bloques a través de fsc.componentBlocks() y renderiza cada uno
 * mediante Component/block.html.twig, que ya está integrado en PanelController.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
trait HasComponentBlocks
{
    /** @var ComponentBlock[] keyed by block name */
    private array $componentBlocks = [];

    protected function addComponentBlock(ComponentBlock $block): ComponentBlock
    {
        $block->settings['card'] = $this->tabsPosition !== 'top';
        $this->componentBlocks[$block->name()] = $block;

        // if nothing is active yet, activate this block
        if (empty($this->active)) {
            $this->active = $block->name();
        }

        return $block;
    }

    public function componentBlock(string $name): ComponentBlock
    {
        if (!isset($this->componentBlocks[$name])) {
            throw new Exception("ComponentBlock '{$name}' not found");
        }

        return $this->componentBlocks[$name];
    }

    public function componentBlocks(): array
    {
        return $this->componentBlocks;
    }

    protected function processActiveComponentBlock(): void
    {
        $block = $this->componentBlocks[$this->active] ?? null;
        if ($block === null || !$block->settings['active']) {
            return;
        }

        $model = $this->getMainModelForBlock();

        if ($this->request->isMethod('POST') && $this->validateFormToken()) {
            if ($block->process($this->request, $model)) {
                $this->execAfterComponentBlock($block->name(), $block);
            }
        } else {
            $block->populate($model);
        }
    }

    /**
     * Sobreescribe en la subclase para reaccionar tras procesar correctamente un bloque de componentes.
     */
    protected function execAfterComponentBlock(string $blockName, ComponentBlock $block): void
    {
    }

    private function getMainModelForBlock(): ?object
    {
        try {
            $mainViewName = $this->getMainViewName();
            return $this->views[$mainViewName]->model ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
