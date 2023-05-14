<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\AjaxForms\PurchasesHeaderHTML;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\PurchaseDocumentMock;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\PurchasesModMock;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\PurchasesModWithNewFieldsMock;
use PHPUnit\Framework\TestCase;

class PurchasesHeaderHTMLTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DataBase();
        $db->connect();
    }

    public function testRenderWithoutMod()
    {
        $purchasesHeaderHTML = PurchasesHeaderHTML::render(new PurchaseDocumentMock());

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/purchases/testRenderWithoutMod.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/purchases/testRenderWithoutMod.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithoutNewFields()
    {
        PurchasesHeaderHTML::addMod(new PurchasesModMock());
        $purchasesHeaderHTML = PurchasesHeaderHTML::render(new PurchaseDocumentMock());

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/purchases/testRenderWithModWithoutNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/purchases/testRenderWithModWithoutNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithNewFields()
    {
        PurchasesHeaderHTML::addMod(new PurchasesModWithNewFieldsMock());
        $purchasesHeaderHTML = PurchasesHeaderHTML::render(new PurchaseDocumentMock());

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/purchases/testRenderWithModWithNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/purchases/testRenderWithModWithNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }
}
