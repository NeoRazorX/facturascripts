<?php

namespace ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\EditView;
use PHPUnit\Framework\TestCase;

class BaseViewTest extends TestCase
{
    public function testBucleInfinitoLimitCero(): void
    {
        $base_view = new EditView('test', 'test', 'test', 'test');

        $base_view->settings['itemLimit'] = 0;

        $this->assertEmpty($base_view->getPagination());
    }
}
