<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;

class TabFormList extends SectionTab
{
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fas fa-edit';

        // añadimos datos de prueba
        foreach (range(1, rand(2, 20)) as $i) {
            $this->cursor[] = [
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

    public function render(): string
    {
        $forms = [];
        foreach ($this->cursor as $row) {
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
            . '<button type="button" class="btn btn-sm btn-danger float-left">Delete</button>'
            . '<button type="reset" class="btn btn-sm btn-secondary">Undo</button>'
            . '<button type="submit" class="btn btn-sm btn-primary ml-1">Save</button>'
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
            . '<button type="submit" class="btn btn-sm btn-success">Submit</button>'
            . '</div>'
            . '</div>'
            . '</form>';
    }
}