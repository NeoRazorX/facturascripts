<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Contract\ExportPDFInterface;
use FacturaScripts\Core\Contract\ExportPDFModInterface;
use FacturaScripts\Core\Lib\Export\PDFExport;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Empresa;
use InvalidArgumentException;
use RuntimeException;

class ExportPDF extends PDFExport implements ExportPDFInterface
{
    /** @var array<int, array{priority:int, mod:ExportPDFModInterface}> */
    protected static $mods = [];

    /** @var array<string, array<int, array{priority:int, handler:callable}>> */
    protected static $modelExtensions = [];

    /** @var array<string, array<int, callable>> */
    protected $queuedSections = [];

    /** @var array<string, mixed> */
    protected $data = [];

    /** @var bool */
    protected $headerEnabled = true;

    /** @var bool */
    protected $footerEnabled = true;

    /** @var null|int */
    protected $companyId;

    /** @var ExportPDFModInterface[] */
    protected $currentMods = [];

    /** @var array<string, mixed> */
    protected $currentContext = [];

    /** @var null|bool */
    protected $contextHeaderEnabled = null;

    /** @var null|bool */
    protected $contextFooterEnabled = null;

    public function __construct()
    {
        parent::__construct();
        $this->resetQueuedSections();
    }

    public static function create(): self
    {
        return new static();
    }

    public static function addMod(ExportPDFModInterface $mod, int $priority = 100): void
    {
        self::$mods[] = [
            'mod' => $mod,
            'priority' => max(0, min(1000, $priority))
        ];
        usort(self::$mods, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    public static function clearMods(): void
    {
        self::$mods = [];
    }

    public static function addModelExtension(string $modelClass, callable $handler, int $priority = 100): void
    {
        $modelClass = ltrim($modelClass, '\\');
        self::$modelExtensions[$modelClass][] = [
            'handler' => $handler,
            'priority' => max(0, min(1000, $priority))
        ];
        usort(self::$modelExtensions[$modelClass], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    public static function clearModelExtensions(?string $modelClass = null): void
    {
        if (null === $modelClass) {
            self::$modelExtensions = [];
            return;
        }

        $modelClass = ltrim($modelClass, '\\');
        unset(self::$modelExtensions[$modelClass]);
    }

    public function addModel($model): ExportPDFInterface
    {
        $context = [
            'model' => $model,
            'format' => $this->discoverFormat($model),
            'options' => [
                'header' => $this->headerEnabled,
                'footer' => $this->footerEnabled,
                'orientation' => null,
                'size' => null,
                'lang' => null
            ],
            'data' => $this->data,
            'sections' => $this->queuedSections
        ];
        $this->resetQueuedSections();

        $mods = $this->filterMods($model, $context);
        $this->currentMods = $mods;
        $this->currentContext =& $context;

        $this->applyFormatMods($mods, $context);
        if (!empty($context['options']['lang'])) {
            $this->setLang($context['options']['lang']);
        }

        $shouldRender = $this->runModsBefore($mods, $context);
        if ($shouldRender) {
            $handled = $this->runModelExtensions($model, $context);
            if (false === $handled) {
                $this->renderModel($model, $context);
            }
        }

        $this->runModsAfter($mods, $context);
        $this->runExtraPages($context);

        $this->currentMods = [];
        $this->currentContext = [];
        $this->contextHeaderEnabled = null;
        $this->contextFooterEnabled = null;

        return $this;
    }

    public function addTable(array $rows, array $headers = [], array $options = []): ExportPDFInterface
    {
        if (empty($rows)) {
            return $this;
        }

        $this->ensureManualContext();

        if (empty($headers)) {
            $firstRow = reset($rows);
            $headers = array_keys($firstRow ?? []);
        }

        if (array_values($headers) === $headers) {
            $headers = array_combine($headers, $headers);
        }

        $title = $options['title'] ?? '';
        unset($options['title']);

        $tableOptions = array_merge([
            'width' => $this->tableWidth,
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95]
        ], $options);

        $this->pdf->ezTable($rows, $headers, $title, $tableOptions);
        return $this;
    }

    public function addText(string $text, array $options = []): ExportPDFInterface
    {
        $this->ensureManualContext();

        $size = $options['size'] ?? self::FONT_SIZE;
        unset($options['size']);

        $this->pdf->ezText($text, $size, $options);
        return $this;
    }

    public function addImage(string $path, array $options = []): ExportPDFInterface
    {
        $this->ensureManualContext();

        $height = $options['height'] ?? 0;
        $width = $options['width'] ?? 0;
        $resize = $options['resize'] ?? 'auto';
        $justification = $options['justification'] ?? 'left';
        $angle = $options['angle'] ?? 0;

        if (!is_file($path)) {
            throw new RuntimeException('Image file not found: ' . $path);
        }

        $this->pdf->ezImage($path, $height, $width, $resize, $justification, $angle);
        return $this;
    }

    public function addSection(string $position, callable $handler): ExportPDFInterface
    {
        $this->assertValidSection($position);
        $this->queuedSections[$position][] = $handler;
        return $this;
    }

    public function newPage(?string $orientation = null, bool $force = false): ExportPDFInterface
    {
        parent::newPage($orientation, $force);
        return $this;
    }

    public function output(): string
    {
        return $this->getDoc();
    }

    public function save(string $fileName, ?string $directory = null): string
    {
        $directory = $directory ?? FS_FOLDER . '/MyFiles/Prints';
        if (false === Tools::folderCheckOrCreate($directory)) {
            throw new RuntimeException('Unable to create directory: ' . $directory);
        }

        $this->setFileName($fileName);
        $fileName = $this->normalizeFileName($fileName);
        $path = rtrim($directory, '/\\') . '/' . $fileName;
        file_put_contents($path, $this->output());
        return $path;
    }

    public function setLang(string $langcode): ExportPDF
    {
        parent::setLang($langcode);
        return $this;
    }

    public function setOrientation(string $orientation): ExportPDFInterface
    {
        parent::setOrientation($orientation);
        return $this;
    }

    public function setSize(string $size): ExportPDFInterface
    {
        parent::setSize($size);
        return $this;
    }

    public function setCompany($company): self
    {
        if ($company instanceof Empresa) {
            $this->companyId = (int)$company->idempresa;
        } else {
            $this->companyId = (int)$company;
        }

        if ($this->pdf !== null && $this->isHeaderEnabled()) {
            $this->insertedHeader = false;
            $this->insertHeader($this->companyId);
        }

        return $this;
    }

    public function setComany($company): self
    {
        return $this->setCompany($company);
    }

    public function setData(string $key, $value): ExportPDFInterface
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function disableHeader(bool $disabled = true): ExportPDFInterface
    {
        $this->headerEnabled = !$disabled;
        return $this;
    }

    public function disableFooter(bool $disabled = true): ExportPDFInterface
    {
        $this->footerEnabled = !$disabled;
        return $this;
    }

    protected function insertHeader($idempresa = null)
    {
        if (!$this->isHeaderEnabled()) {
            $this->insertedHeader = true;
            return;
        }

        parent::insertHeader($idempresa ?? $this->companyId);
    }

    protected function insertFooter()
    {
        if (!$this->isFooterEnabled()) {
            return;
        }

        parent::insertFooter();
    }

    protected function renderModel($model, array &$context): void
    {
        if ($model instanceof BusinessDocument) {
            $this->renderBusinessDocument($model, $context);
            return;
        }

        if ($model instanceof ModelClass) {
            $this->renderGenericModel($model, $context);
            return;
        }

        throw new InvalidArgumentException('Unsupported model type: ' . get_debug_type($model));
    }

    protected function renderBusinessDocument(BusinessDocument $model, array &$context): void
    {
        $this->assignDefaultFileName($model);
        if ($this->companyId === null && isset($model->idempresa)) {
            $this->companyId = (int)$model->idempresa;
        }

        if ($context['format']) {
            $this->format = $context['format'];
        }

        if (!empty($context['options']['size'])) {
            $this->setSize($context['options']['size']);
        }

        $orientation = $context['options']['orientation'] ?? null;

        parent::newPage($orientation, true);

        $this->contextHeaderEnabled = $context['options']['header'];
        $this->contextFooterEnabled = $context['options']['footer'];

        $this->triggerSections(ExportPDFInterface::SECTION_BEFORE_HEADER);
        if ($this->isHeaderEnabled()) {
            $this->insertHeader($this->companyId);
        }
        $this->triggerSections(ExportPDFInterface::SECTION_AFTER_HEADER);

        $this->triggerSections(ExportPDFInterface::SECTION_WATERMARK);
        $this->applyWatermark($context);

        $this->insertBusinessDocHeader($model);
        $this->triggerSections(ExportPDFInterface::SECTION_AFTER_SUBJECT);

        $this->insertBusinessDocBody($model);
        $this->triggerSections(ExportPDFInterface::SECTION_AFTER_LINES);

        $this->insertBusinessDocFooter($model);
        $this->triggerSections(ExportPDFInterface::SECTION_AFTER_TOTALS);

        if ($this->isFooterEnabled()) {
            $this->triggerSections(ExportPDFInterface::SECTION_FOOTER);
            $this->insertFooter();
        }
    }

    protected function renderGenericModel(ModelClass $model, array &$context): void
    {
        $this->assignDefaultFileName($model);

        $this->contextHeaderEnabled = $context['options']['header'];
        $this->contextFooterEnabled = $context['options']['footer'];

        $columns = $this->getModelFields($model);
        parent::addModelPage($model, $columns, $model->modelClassName());
    }

    protected function triggerSections(string $position): void
    {
        $this->assertValidSection($position);

        if (!isset($this->currentContext['sections'][$position])) {
            $this->currentContext['sections'][$position] = [];
        }

        foreach ($this->currentContext['sections'][$position] as $index => $handler) {
            $handler($this, $this->currentContext);
            unset($this->currentContext['sections'][$position][$index]);
        }

        foreach ($this->currentMods as $mod) {
            if ($mod->renderSection($this, $position, $this->currentContext) === false) {
                break;
            }
        }
    }

    protected function applyWatermark(array &$context): void
    {
        foreach ($this->currentMods as $mod) {
            $mod->applyWatermark($this, $context);
        }
    }

    protected function runExtraPages(array &$context): void
    {
        if (!empty($context['sections'][ExportPDFInterface::SECTION_EXTRA_PAGES])) {
            foreach ($context['sections'][ExportPDFInterface::SECTION_EXTRA_PAGES] as $handler) {
                $handler($this, $context);
            }
            $context['sections'][ExportPDFInterface::SECTION_EXTRA_PAGES] = [];
        }

        foreach ($this->currentMods as $mod) {
            $mod->appendExtraPages($this, $context);
        }
    }

    protected function runModelExtensions($model, array &$context): bool
    {
        $class = ltrim(get_class($model), '\\');
        $handlers = [];
        foreach (self::$modelExtensions as $target => $extensions) {
            if (is_a($model, $target)) {
                $handlers = array_merge($handlers, $extensions);
            }
        }

        if (empty($handlers)) {
            return false;
        }

        usort($handlers, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach ($handlers as $extension) {
            $result = $extension['handler']($this, $model, $context);
            if ($result === true) {
                return true;
            }
        }

        return false;
    }

    protected function applyFormatMods(array $mods, array &$context): void
    {
        foreach ($mods as $mod) {
            if ($mod->customizeFormat($this, $context) === false) {
                break;
            }
        }
    }

    protected function runModsBefore(array $mods, array &$context): bool
    {
        foreach ($mods as $mod) {
            if ($mod->beforeAddModel($this, $context) === false) {
                return false;
            }
        }

        return true;
    }

    protected function runModsAfter(array $mods, array &$context): void
    {
        foreach ($mods as $mod) {
            if ($mod->afterAddModel($this, $context) === false) {
                break;
            }
        }
    }

    protected function discoverFormat($model)
    {
        if ($model instanceof BusinessDocument) {
            return $this->getDocumentFormat($model);
        }

        return null;
    }

    protected function filterMods($model, array $context): array
    {
        $mods = [];
        foreach (self::$mods as $item) {
            if ($item['mod']->appliesTo($this, $model, $context)) {
                $mods[] = $item['mod'];
            }
        }
        return $mods;
    }

    protected function ensureManualContext(): void
    {
        if ($this->pdf === null) {
            parent::newPage(null, true);
        }

        if ($this->isHeaderEnabled() && !$this->insertedHeader) {
            $this->insertHeader($this->companyId);
        }
    }

    protected function isHeaderEnabled(): bool
    {
        return $this->contextHeaderEnabled ?? $this->headerEnabled;
    }

    protected function isFooterEnabled(): bool
    {
        return $this->contextFooterEnabled ?? $this->footerEnabled;
    }

    protected function assertValidSection(string $position): void
    {
        $valid = [
            ExportPDFInterface::SECTION_BEFORE_HEADER,
            ExportPDFInterface::SECTION_AFTER_HEADER,
            ExportPDFInterface::SECTION_AFTER_SUBJECT,
            ExportPDFInterface::SECTION_AFTER_LINES,
            ExportPDFInterface::SECTION_AFTER_TOTALS,
            ExportPDFInterface::SECTION_FOOTER,
            ExportPDFInterface::SECTION_WATERMARK,
            ExportPDFInterface::SECTION_EXTRA_PAGES
        ];

        if (!in_array($position, $valid, true)) {
            throw new InvalidArgumentException('Unknown section position: ' . $position);
        }
    }

    protected function resetQueuedSections(): void
    {
        $this->queuedSections = [
            ExportPDFInterface::SECTION_BEFORE_HEADER => [],
            ExportPDFInterface::SECTION_AFTER_HEADER => [],
            ExportPDFInterface::SECTION_AFTER_SUBJECT => [],
            ExportPDFInterface::SECTION_AFTER_LINES => [],
            ExportPDFInterface::SECTION_AFTER_TOTALS => [],
            ExportPDFInterface::SECTION_FOOTER => [],
            ExportPDFInterface::SECTION_WATERMARK => [],
            ExportPDFInterface::SECTION_EXTRA_PAGES => []
        ];
    }

    protected function assignDefaultFileName($model): void
    {
        if (!method_exists($model, 'modelClassName')) {
            return;
        }

        $parts = [$model->modelClassName()];
        if (method_exists($model, 'primaryColumnValue')) {
            $primary = (string)$model->primaryColumnValue();
            if ($primary !== '') {
                $parts[] = $primary;
            }
        }

        $fileName = implode('_', $parts);
        $this->setFileName($fileName);
    }

    protected function normalizeFileName(string $fileName): string
    {
        $fileName = trim($fileName);
        if ('' === $fileName) {
            $fileName = $this->getFileName() ?: 'document';
        }

        if (!str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        return str_replace(['"', "'", '/', '\\'], '_', $fileName);
    }
}
