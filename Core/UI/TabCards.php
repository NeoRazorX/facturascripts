<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Core\Tools;

class TabCards extends SectionTab
{
    /** @var array */
    public $cursor = [];

    public function __construct()
    {
        $this->icon = 'fa-regular fa-images';

        // creamos algunos datos de ejemplo
        foreach (range(1, 12) as $i) {
            $this->cursor[] = [
                'image' => 'https://picsum.photos/200?random=' . $i,
                'title' => 'Card title ' . $i,
                'text' => 'This is a longer card with supporting text below as a natural lead-in to additional content.'
                    . ' This content is a little bit longer.',
                'footer' => 'Last updated ' . rand(1, 30) . ' mins ago',
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
        $cards = [];
        foreach ($this->cursor as $num => $card) {
            $cards[] = '<div class="col mb-3">'
                . '<div class="card shadow-sm">'
                . '<img src="' . $card['image'] . '" class="card-img-top" alt="' . Tools::slug($card['title']) . '" loading="lazy">'
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