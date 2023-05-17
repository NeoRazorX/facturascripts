<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\AjaxForms\SalesHeaderHTML;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\SalesDocumentMock;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\SalesModMock;
use FacturaScripts\Test\Core\Base\AjaxForms\Mocks\SalesModWithNewFieldsMock;
use PHPUnit\Framework\TestCase;

class SalesHeaderHTMLTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DataBase();
        $db->connect();
    }

    public function testRenderWithoutMod()
    {
        $purchasesHeaderHTML = SalesHeaderHTML::render(new SalesDocumentMock());
        // Eliminamos los select que como se generan automaticamente
        // no siempre tienen el mismo orden y esto hace que falle el test erroneamente.
        $purchasesHeaderHTML = preg_replace('/<select[\s\S]*?select>/m', '', $purchasesHeaderHTML);

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/sales/testRenderWithoutMod.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/sales/testRenderWithoutMod.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithoutNewFields()
    {
        SalesHeaderHTML::addMod(new SalesModMock());

        $purchasesHeaderHTML = SalesHeaderHTML::render(new SalesDocumentMock());
        // Eliminamos los select que como se generan automaticamente
        // no siempre tienen el mismo orden y esto hace que falle el test erroneamente.
        $purchasesHeaderHTML = preg_replace('/<select[\s\S]*?select>/m', '', $purchasesHeaderHTML);

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/sales/testRenderWithModWithoutNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/sales/testRenderWithModWithoutNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithNewFields()
    {
        SalesHeaderHTML::addMod(new SalesModWithNewFieldsMock());

        $purchasesHeaderHTML = SalesHeaderHTML::render(new SalesDocumentMock());
        // Eliminamos los select que como se generan automaticamente
        // no siempre tienen el mismo orden y esto hace que falle el test erroneamente.
        $purchasesHeaderHTML = preg_replace('/<select[\s\S]*?select>/m', '', $purchasesHeaderHTML);

        // Descomentar para guardar el html esperado a comparar con el html obtenido.
        //file_put_contents(__DIR__ . '/sales/testRenderWithModWithNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__ . '/sales/testRenderWithModWithNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }
}
