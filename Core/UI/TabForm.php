<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;

class TabForm extends SectionTab
{
    public function __construct()
    {
        $this->icon = 'fas fa-edit';
    }

    public function render(): string
    {
        return '<form>'
            . '<div class="container-fluid mt-4 mb-4">'
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
            . '<div class="col-12 text-right">'
            . '<button type="button" class="btn btn-danger float-left">Delete</button>'
            . '<button type="reset" class="btn btn-secondary">Reset</button>'
            . '<button type="submit" class="btn btn-primary ml-1">Save</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</form>';
    }
}