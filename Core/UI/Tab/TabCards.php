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

class TabCards extends SectionTab
{
    /** @var array */
    public $data = [];

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->icon = 'fa-regular fa-images';

        // creamos algunos datos de ejemplo
        foreach (range(1, rand(4, 16)) as $i) {
            $this->data[] = [
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
        foreach ($this->data as $card) {
            $cards[] = $this->renderCard($card);
        }

        return '<div class="container-fluid p-2">'
            . '<div class="form-row row-cols-1 row-cols-md-4">' . implode('', $cards) . '</div>'
            . '</div>';
    }

    protected function renderCard(array $card): string
    {
        return '<div class="col mb-3">'
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
}