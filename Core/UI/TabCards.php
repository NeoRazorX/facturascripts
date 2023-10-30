<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;

class TabCards extends SectionTab
{
    /** @var array */
    public $cursor = [];

    public function render(): string
    {
        // creamos algunos datos de ejemplo
        foreach (range(1, 12) as $i) {
            $this->cursor[] = [
                'title' => 'Card title ' . $i,
                'text' => 'This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.',
                'footer' => 'Last updated ' . rand(1, 30) . ' mins ago',
            ];
        }

        $cards = [];
        foreach ($this->cursor as $card) {
            $cards[] = '<div class="col mb-3">'
                . '<div class="card shadow-sm">'
                . '<img src="..." class="card-img-top" alt="...">'
                . '<div class="card-body">'
                . '<h5 class="card-title">' . $card['title'] . '</h5>'
                . '<p class="card-text">' . $card['text'] . '</p>'
                . '<p class="card-text"><small class="text-muted">' . $card['footer'] . '</small></p>'
                . '</div>'
                . '</div>'
                . '</div>';
        }

        return '<div class="container-fluid p-2">'
            . '<div class="form-row row-cols-1 row-cols-md-4">' . implode('', $cards) . '</div>'
            . '</div>';
    }
}