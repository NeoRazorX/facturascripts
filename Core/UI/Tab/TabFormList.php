<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI\Tab;

use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Core\Tools;

class TabFormList extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fas fa-edit';

        // añadimos datos de prueba
        foreach (range(1, rand(2, 20)) as $i) {
            $this->data[] = [
                'id' => $i,
                'name' => 'Name ' . $i,
                'surname' => 'Surname ' . $i,
                'observation' => 'Observation ' . $i,
            ];

            $this->counter++;
        }
    }

    public function jsInitFunction(): string
    {
        return '';
    }

    public function jsRedrawFunction(): string
    {
        return '';
    }

    public function render(string $context = ''): string
    {
        $forms = [];
        foreach ($this->data as $row) {
            $forms[] = $this->renderForm($row);
        }

        return '<div class="container-fluid pt-2 pb-2">'
            . '<div class="row">'
            . '<div class="col-12">'
            . $this->renderFormInsert()
            . implode("\n", $forms)
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function renderForm(array $form): string
    {
        return '<form>'
            . '<div class="card shadow mt-2 mb-2">'
            . '<div class="card-body pb-0">'
            . '<div class="form-row">'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="name">Name</label>'
            . '<input type="text" class="form-control" id="name" placeholder="Name" value="' . $form['name'] . '">'
            . '</div>'
            . '</div>'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="surname">Surname</label>'
            . '<input type="text" class="form-control" id="surname" placeholder="Surname" value="' . $form['surname'] . '">'
            . '</div>'
            . '</div>'
            . '<div class="col-12">'
            . '<div class="form-group">'
            . '<label for="observation">Observation</label>'
            . '<textarea class="form-control" id="observation" rows="3">' . $form['observation'] . '</textarea>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="card-footer p-2 text-right">'
            . '<button type="button" class="btn btn-sm btn-danger float-left">'
            . '<i class="fas fa-trash-alt mr-1"></i> ' . Tools::lang()->trans('delete')
            . '</button>'
            . '<button type="reset" class="btn btn-sm btn-secondary">'
            . '<i class="fas fa-undo mr-1"></i> ' . Tools::lang()->trans('undo')
            . '</button>'
            . '<button type="submit" class="btn btn-sm btn-primary ml-1">'
            . '<i class="fas fa-save mr-1"></i> ' . Tools::lang()->trans('save')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</form>';
    }

    protected function renderFormInsert(): string
    {
        return '<form>'
            . '<div class="card shadow border-success mt-2 mb-2">'
            . '<div class="card-body pb-0">'
            . '<div class="form-row">'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="name">Name</label>'
            . '<input type="text" class="form-control" id="name" placeholder="Name">'
            . '</div>'
            . '</div>'
            . '<div class="col">'
            . '<div class="form-group">'
            . '<label for="surname">Surname</label>'
            . '<input type="text" class="form-control" id="surname" placeholder="Surname">'
            . '</div>'
            . '</div>'
            . '<div class="col-12">'
            . '<div class="form-group">'
            . '<label for="observation">Observation</label>'
            . '<textarea class="form-control" id="observation" rows="3"></textarea>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="card-footer p-2 text-right">'
            . '<button type="submit" class="btn btn-sm btn-success">'
            . '<i class="fa fa-save mr-1"></i> ' . Tools::lang()->trans('new')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</form>';
    }
}