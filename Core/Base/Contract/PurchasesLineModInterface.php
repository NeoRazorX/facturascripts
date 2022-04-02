<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;

interface PurchasesLineModInterface
{
    public function apply(PurchaseDocument &$model, array &$lines, array $formData);

    public function applyToLine(array $formData, PurchaseDocumentLine &$line, string $id);

    public function newModalFields(): array;

    public function newFields(): array;

    public function newTitles(): array;

    public function renderField(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $field): ?string;

    public function renderTitle(Translator $i18n, PurchaseDocument $model, string $field): ?string;
}
