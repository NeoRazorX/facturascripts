<?php

namespace FacturaScripts\Test\Core\Base\AjaxForms\Mocks;

use FacturaScripts\Core\Base\Contract\PurchasesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;

class PurchasesModWithNewFieldsMock implements PurchasesModInterface
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
            'test2_header' => PurchasesModInterface::HEADER_POSITION,
            'test3_modal' => PurchasesModInterface::MODAL_POSITION,
        ];
    }

    public function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string
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
