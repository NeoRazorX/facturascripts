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

namespace FacturaScripts\Test\Core\Controller;

use Closure;
use FacturaScripts\Core\Controller\MegaSearch as CoreMegaSearch;
use FacturaScripts\Dinamic\Controller\MegaSearch;
use PHPUnit\Framework\TestCase;

final class MegaSearchTest extends TestCase
{
    public function testDocsSearchResults(): void
    {
        $controller = new MegaSearch('MegaSearch', '/MegaSearch');

        $data = [
            'results' => [
                ['title' => 'Factura <b>1</b>', 'description' => '<p>Texto</p>', 'url' => 'https://facturascripts.com/a'],
                ['title' => 'Sin https', 'url' => 'http://facturascripts.com/b'],
                ['title' => 'Javascript', 'url' => 'javascript:alert(1)'],
                ['title' => 'Sin url'],
                'no es un array',
            ]
        ];
        $section = $controller->docsSearchResults($data);
        $this->assertArrayHasKey('docs', $section);
        $this->assertCount(1, $section['docs']['results']);

        $item = $section['docs']['results'][0];
        $this->assertSame('https://facturascripts.com/a', $item['url']);
        $this->assertStringNotContainsString('<', $item['title']);
        $this->assertSame('Texto', $item['description']);

        // respuestas no válidas o vacías no generan sección
        $this->assertSame([], $controller->docsSearchResults(null));
        $this->assertSame([], $controller->docsSearchResults(['results' => 'x']));
        $this->assertSame([], $controller->docsSearchResults(['results' => []]));
    }

    public function testDocsSearchResultsLimit(): void
    {
        $results = [];
        for ($num = 1; $num <= CoreMegaSearch::DOCS_SEARCH_LIMIT + 5; $num++) {
            $results[] = ['title' => 'Doc ' . $num, 'url' => 'https://facturascripts.com/doc-' . $num];
        }

        $controller = new MegaSearch('MegaSearch', '/MegaSearch');
        $section = $controller->docsSearchResults(['results' => $results]);
        $this->assertCount(CoreMegaSearch::DOCS_SEARCH_LIMIT, $section['docs']['results']);
    }

    public function testDocsSearchUrl(): void
    {
        $controller = new MegaSearch('MegaSearch', '/MegaSearch');
        $this->assertSame(CoreMegaSearch::DOCS_SEARCH_URL, $controller->docsSearchUrl());

        // un plugin puede cambiar la URL con una extensión
        MegaSearch::addExtension(new class {
            public function docsSearchUrl(): Closure
            {
                return function () {
                    return 'https://factura.city/MegaSearchDocs';
                };
            }
        });

        try {
            $this->assertSame('https://factura.city/MegaSearchDocs', $controller->docsSearchUrl());
        } finally {
            MegaSearch::removeExtension('docsSearchUrl');
        }

        $this->assertSame(CoreMegaSearch::DOCS_SEARCH_URL, $controller->docsSearchUrl());
    }
}
