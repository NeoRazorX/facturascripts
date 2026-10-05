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

namespace FacturaScripts\Test\Core\Lib\Widget;

use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\PageOption;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class VisualItemLoadEngineTest extends TestCase
{
    public function testMergeCustomizationIgnoresColumnsMissingFromXml(): void
    {
        $xmlColumns = [
            'new-column' => [
                'tag' => 'column',
                'name' => 'new-column',
                'display' => 'center',
                'children' => [],
            ],
        ];
        $base = $this->newPageOption($xmlColumns);
        $custom = $this->newPageOption([
            'removed-column' => [
                'tag' => 'column',
                'name' => 'removed-column',
                'display' => 'none',
                'children' => [],
            ],
        ]);

        VisualItemLoadEngine::mergeCustomization($base, $custom);

        $this->assertSame($xmlColumns, $base->columns);
    }

    public function testMergeCustomization(): void
    {
        $base = $this->newPageOption([
            'main' => [
                'tag' => 'group',
                'name' => 'main',
                'children' => [
                    'existing' => [
                        'tag' => 'column',
                        'name' => 'existing',
                        'description' => 'description-from-xml',
                        'display' => 'start',
                        'level' => '1',
                        'numcolumns' => '6',
                        'order' => '100',
                        'title' => 'title-from-xml',
                        'children' => [[
                            'tag' => 'widget',
                            'type' => 'number',
                            'decimal' => '2',
                            'readonly' => 'false',
                            'required' => 'true',
                        ]],
                    ],
                    'new-column' => [
                        'tag' => 'column',
                        'name' => 'new-column',
                        'display' => 'center',
                        'children' => [[
                            'tag' => 'widget',
                            'type' => 'text',
                        ]],
                    ],
                ],
            ],
            'loose' => [
                'tag' => 'column',
                'name' => 'loose',
                'display' => 'start',
                'children' => [[
                    'tag' => 'widget',
                    'type' => 'text',
                    'readonly' => 'false',
                ]],
            ],
        ]);

        $custom = $this->newPageOption([
            'old-group' => [
                'tag' => 'group',
                'name' => 'old-group',
                'children' => [
                    'existing' => [
                        'tag' => 'column',
                        'name' => 'existing',
                        'description' => 'old-description',
                        'display' => 'none',
                        'level' => '',
                        'numcolumns' => '',
                        'order' => '',
                        'title' => 'custom-title',
                        'children' => [[
                            'tag' => 'widget',
                            'type' => 'text',
                            'decimal' => '',
                            'readonly' => '',
                            'required' => 'false',
                        ]],
                    ],
                    'removed-column' => [
                        'tag' => 'column',
                        'name' => 'removed-column',
                        'display' => 'end',
                        'children' => [],
                    ],
                ],
            ],
            'loose' => [
                'tag' => 'column',
                'name' => 'loose',
                'display' => 'end',
                'children' => [[
                    'tag' => 'widget',
                    'type' => 'text',
                    'readonly' => 'true',
                ]],
            ],
        ]);

        VisualItemLoadEngine::mergeCustomization($base, $custom);

        $existing = $base->columns['main']['children']['existing'];
        $this->assertSame('none', $existing['display']);
        $this->assertSame('', $existing['level']);
        $this->assertSame('', $existing['numcolumns']);
        $this->assertSame('', $existing['order']);
        $this->assertSame('custom-title', $existing['title']);
        $this->assertSame('', $existing['children'][0]['decimal']);
        $this->assertSame('', $existing['children'][0]['readonly']);

        // Attributes that cannot be customized must continue coming from the XML.
        $this->assertSame('description-from-xml', $existing['description']);
        $this->assertSame('number', $existing['children'][0]['type']);
        $this->assertSame('true', $existing['children'][0]['required']);

        // New XML columns remain, removed XML columns are not restored, and loose columns are supported.
        $this->assertArrayHasKey('new-column', $base->columns['main']['children']);
        $this->assertArrayNotHasKey('removed-column', $base->columns['main']['children']);
        $this->assertSame('end', $base->columns['loose']['display']);
        $this->assertSame('true', $base->columns['loose']['children'][0]['readonly']);
    }

    private function newPageOption(array $columns): PageOption
    {
        $pageOption = (new ReflectionClass(PageOption::class))->newInstanceWithoutConstructor();
        $pageOption->columns = $columns;

        return $pageOption;
    }
}
