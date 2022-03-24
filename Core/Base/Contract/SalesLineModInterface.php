<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;

interface SalesLineModInterface
{
    public function apply(SalesDocument &$model, array &$lines, array $formData);

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id);

    public function newModalFields(): array;

    public function newFields(): array;

    public function renderField(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string;
}
