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

namespace FacturaScripts\Core\Lib\PDF\Dynamic;

use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\PageBreakBlock;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Tools;

/**
 * Builds a dynamic PDF document: an A4 HTML container where blocks (titles,
 * texts, images, tables...) are added with a fluent interface. The resulting
 * HTML embeds html2pdf.js so the browser can print it or convert it to PDF.
 *
 * $html = PDFBuilder::create()
 *     ->setTitle('my-doc')
 *     ->addTitle('Invoice F-2026-001')
 *     ->addTable($rows, $titles)
 *     ->getHtml();
 */
class PDFBuilder
{
    const ORIENTATION_PORTRAIT = 'portrait';
    const ORIENTATION_LANDSCAPE = 'landscape';

    /** @var BlockInterface[] */
    protected $blocks = [];

    /** @var string[] */
    protected $cssExtra = [];

    /** @var int */
    protected $margin = 20;

    /** @var string */
    protected $orientation = self::ORIENTATION_PORTRAIT;

    /** @var array */
    protected $styleOptions = [];

    /** @var string */
    protected $title = 'document';

    /** @var array name => className */
    protected static $customBlocks = [];

    public static function create(): self
    {
        $dynClass = '\\FacturaScripts\\Dinamic\\Lib\\PDF\\Dynamic\\PDFBuilder';
        if (class_exists($dynClass) && static::class === self::class) {
            return new $dynClass();
        }

        return new static();
    }

    public static function addCustomBlock(string $name, string $className): void
    {
        static::$customBlocks[$name] = $className;
    }

    public function add(string $name, ...$args): self
    {
        if (isset(static::$customBlocks[$name])) {
            $className = static::$customBlocks[$name];
            return $this->addBlock(new $className(...$args));
        }

        return $this->addBlock($this->newBlock($name, ...$args));
    }

    public function addBlock(BlockInterface $block): self
    {
        $this->blocks[] = $block;
        return $this;
    }

    public function addColumns(array $columnsOfBlocks, array $widths = []): self
    {
        return $this->add('Columns', $columnsOfBlocks, $widths);
    }

    public function addCompanyHeader(Empresa $empresa, string $logoAlign = 'left'): self
    {
        return $this->add('CompanyHeader', $empresa, $logoAlign);
    }

    public function addCss(string $css): self
    {
        $this->cssExtra[] = $css;
        return $this;
    }

    public function addDocumentHeader(Empresa $empresa, ?string $logoSrc = null, bool $logoLeft = true): self
    {
        return $this->add('DocumentHeader', $empresa, $logoSrc, $logoLeft);
    }

    public function addDualColumnTable(array $data): self
    {
        return $this->add('DualColumnTable', $data);
    }

    public function addHtml(string $html): self
    {
        return $this->add('RawHtml', $html);
    }

    public function addImage(string $src, ?int $widthMm = null, string $align = 'left'): self
    {
        return $this->add('Image', $src, $widthMm, $align);
    }

    /**
     * Adds a table with the model list data, using the XMLView columns to
     * resolve titles, alignments and cell values (like the core exporters).
     *
     * @param array $cursor ModelClass[]
     * @param array $columns GroupItem[] from BaseView::getColumns()
     */
    public function addModelTable(array $cursor, array $columns, string $cssClass = 'table-list'): self
    {
        $titles = ModelTableHelper::titles($columns);
        $rows = [];
        foreach (ModelTableHelper::rows($cursor, $columns) as $row) {
            $rows[] = array_values($row);
        }

        return $this->addTable(
            $rows,
            array_values($titles),
            array_values(ModelTableHelper::alignments($columns)),
            $cssClass
        );
    }

    public function addPageBreak(): self
    {
        return $this->add('PageBreak');
    }

    public function addPageFooter(string $left = '', string $right = ''): self
    {
        return $this->add('PageFooter', $left, $right);
    }

    public function addParallelTable(array $data): self
    {
        return $this->add('ParallelTable', $data);
    }

    public function addSpacer(int $mm = 5): self
    {
        return $this->add('Spacer', $mm);
    }

    public function addTable(array $rows, array $titles = [], array $alignments = [], string $cssClass = 'table-list'): self
    {
        return $this->add('Table', $rows, $titles, $alignments, $cssClass);
    }

    public function addText(string $text, string $cssClass = ''): self
    {
        return $this->add('Text', $text, $cssClass);
    }

    public function addTitle(string $text, int $level = 1, string $align = 'left'): self
    {
        return $this->add('Title', $text, $level, $align);
    }

    public function addWatermarkText(string $text, string $color = '#C80000'): self
    {
        return $this->add('WatermarkText', $text, $color);
    }

    public function getBodyHtml(): string
    {
        $pages = [''];
        foreach ($this->blocks as $block) {
            if ($block instanceof PageBreakBlock) {
                $pages[] = '';
                continue;
            }

            $pages[count($pages) - 1] .= $block->render();
        }

        $html = '';
        foreach ($pages as $page) {
            $html .= '<div class="page">' . $page . '</div>';
        }

        return $html;
    }

    public function getHtml(): string
    {
        $route = Tools::config('route', '');

        return '<!DOCTYPE html>'
            . '<html lang="' . substr(Tools::lang()->getLang(), 0, 2) . '">'
            . '<head>'
            . '<meta charset="utf-8"/>'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"/>'
            . '<title>' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>' . PDFStyles::get($this->orientation, $this->margin, $this->styleOptions)
            . implode(' ', $this->cssExtra) . '</style>'
            . '<script src="' . $route . '/node_modules/html2pdf.js/dist/html2pdf.bundle.min.js"></script>'
            . '</head>'
            . '<body>'
            . '<div id="FORPRINT">' . $this->getBodyHtml() . '</div>'
            . '<script>' . $this->exportScript() . '</script>'
            . '</body>'
            . '</html>';
    }

    public function setMargin(int $mm): self
    {
        $this->margin = max($mm, 0);
        return $this;
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation === self::ORIENTATION_LANDSCAPE ?
            self::ORIENTATION_LANDSCAPE :
            self::ORIENTATION_PORTRAIT;
        return $this;
    }

    public function setStyleOptions(array $options): self
    {
        $this->styleOptions = $options;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    protected function exportScript(): string
    {
        $fileName = json_encode($this->sanitizeFileName($this->title) . '.pdf');

        // the page margin is already the .page padding (wysiwyg), so html2pdf margin is 0.
        // onclone removes the preview-only decoration (shadows, gaps) before the capture.
        return 'function fsPrint() { window.print(); }'
            . 'function fsPdfOptions(filename) {'
            . 'return {'
            . 'margin: 0,'
            . 'filename: filename || ' . $fileName . ','
            . 'image: {type: "jpeg", quality: 0.98},'
            . 'html2canvas: {scale: 3, letterRendering: true, useCORS: true, backgroundColor: "#ffffff",'
            . ' onclone: function (doc) {'
            . 'doc.body.style.padding = "0";'
            . 'doc.body.style.background = "#fff";'
            . 'doc.querySelectorAll(".page").forEach(function (page) {'
            . 'page.style.boxShadow = "none";'
            . 'page.style.margin = "0 auto";'
            . '});'
            . '}},'
            . 'jsPDF: {unit: "mm", format: "a4", orientation: ' . json_encode($this->orientation) . '},'
            . 'pagebreak: {mode: ["css", "legacy"]}'
            . '};'
            . '}'
            . 'function fsDownloadPdf(filename) {'
            . 'return html2pdf().from(document.getElementById("FORPRINT")).set(fsPdfOptions(filename)).save();'
            . '}';
    }

    protected function newBlock(string $name, ...$args): BlockInterface
    {
        $dynClass = '\\FacturaScripts\\Dinamic\\Lib\\PDF\\Dynamic\\Blocks\\' . $name . 'Block';
        if (class_exists($dynClass)) {
            return new $dynClass(...$args);
        }

        $coreClass = '\\FacturaScripts\\Core\\Lib\\PDF\\Dynamic\\Blocks\\' . $name . 'Block';
        return new $coreClass(...$args);
    }

    protected function sanitizeFileName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $name);
        return trim($clean, '-') ?: 'document';
    }
}
