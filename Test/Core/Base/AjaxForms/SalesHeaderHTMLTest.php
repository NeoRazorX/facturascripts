<?php

namespace Base\AjaxForms;

use FacturaScripts\Core\Base\AjaxForms\SalesHeaderHTML;
use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
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

        //file_put_contents(__DIR__ . '/sales/testRenderWithoutMod.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__.'/sales/testRenderWithoutMod.html');
        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithoutNewFields()
    {
        SalesHeaderHTML::addMod(new SalesModMock());
        $purchasesHeaderHTML = SalesHeaderHTML::render(new SalesDocumentMock());

        //file_put_contents(__DIR__ . '/sales/testRenderWithModWithoutNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__.'/sales/testRenderWithModWithoutNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }

    public function testRenderWithModWithNewFields()
    {
        SalesHeaderHTML::addMod(new SalesModWithNewFieldsMock());
        $purchasesHeaderHTML = SalesHeaderHTML::render(new SalesDocumentMock());

        //file_put_contents(__DIR__ . '/sales/testRenderWithModWithNewFields.html', $purchasesHeaderHTML);
        $expectedHtml = file_get_contents(__DIR__.'/sales/testRenderWithModWithNewFields.html');

        $this->assertEquals($expectedHtml, $purchasesHeaderHTML);
    }
}

class SalesDocumentMock extends SalesDocument
{
    protected $test;

    public function getLines(): array
    {
        return [];
    }

    public function getNewLine($data = [], $exclude = [])
    {
    }

    public static function addExtension($extension)
    {
    }

    public function getModelFields(): array
    {
        return [];
    }

    protected function loadModelFields(&$dataBase, $tableName)
    {
    }

    public function modelClassName(): string
    {
        return '';
    }

    protected function modelName(): string
    {
        return '';
    }

    public function pipe($name, ...$arguments)
    {
    }

    public function pipeFalse($name, ...$arguments): bool
    {
        return false;
    }

    public static function primaryColumn(): string
    {
        return 'test';
    }

    public static function tableName(): string
    {
        return '';
    }
}

class SalesModMock implements SalesModInterface
{
    public function apply(&$model, $formData, $user)
    {
        //
    }

    public function applyBefore(&$model, $formData, $user)
    {
        //
    }

    public function assets(): void
    {
        //
    }

    public function newFields(): array
    {
        return [];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        return null;
    }
}

class SalesModWithNewFieldsMock implements SalesModInterface
{
    public function apply(&$model, $formData, $user)
    {
        //
    }

    public function applyBefore(&$model, $formData, $user)
    {
        //
    }

    public function assets(): void
    {
        //
    }

    public function newFields(): array
    {
        return [
            'test1',
            'test2_header' => SalesModInterface::HEADER_POSITION,
            'test3_modal' => SalesModInterface::MODAL_POSITION,
        ];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'test1') {
            return self::test1();
        }

        if ($field === 'test2_header') {
            return self::test2Header();
        }

        if ($field === 'test3_modal') {
            return self::test3Modal();
        }

        return null;
    }

    private function test1()
    {
        return '<h1>Test1</h1>';
    }

    private function test2Header()
    {
        return '<h1>Test2</h1>';
    }

    private function test3Modal()
    {
        return '<h1>Test3</h1>';
    }
}
