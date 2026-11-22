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

namespace FacturaScripts\Test\Core\Lib\Block;

use FacturaScripts\Core\Lib\Block\Block;
use FacturaScripts\Core\Lib\Block\Header;
use FacturaScripts\Core\Lib\Block\Page;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class HeaderTest extends TestCase
{
    public function testAlertsAreShownWhileTheirConditionIsMet(): void
    {
        $header = new Header();
        $header->alert('locked', fn($record) => $record->locked ? 'Locked record' : null, 'danger')
            ->alert('risk', fn($record) => $record->risk > 100 ? 'Risk exceeded' : null);

        $this->assertSame([], $header->alerts($this->record(false, 50)));
        $this->assertSame(
            [['name' => 'risk', 'color' => 'warning', 'message' => 'Risk exceeded']],
            $header->alerts($this->record(false, 500))
        );
        $this->assertCount(2, $header->alerts($this->record(true, 500)));
    }

    public function testBuiltInButtonsCanBeHidden(): void
    {
        $header = new Header();

        // todos se muestran salvo duplicar, que solo sirve para algunos modelos
        $this->assertTrue($header->shows('new'));
        $this->assertTrue($header->shows('print'));
        $this->assertFalse($header->shows('clone'));
        $this->assertFalse($header->shows('unknown'));

        $header->hide('new', 'print')->show('clone');
        $this->assertFalse($header->shows('new'));
        $this->assertFalse($header->shows('print'));
        $this->assertTrue($header->shows('clone'));
        $this->assertTrue($header->shows('list'));
    }

    public function testButtonsKeepTheRequestedPosition(): void
    {
        $header = new Header();
        $header->button('first')
            ->button('last')
            ->button('before-last', ['before' => 'last'])
            ->button('after-first', ['after' => 'first'])
            ->button('unknown-reference', ['after' => 'missing']);

        $this->assertSame(
            ['first', 'after-first', 'before-last', 'last', 'unknown-reference'],
            $this->names($header->buttons(null))
        );

        $this->assertTrue($header->remove('after-first'));
        $this->assertFalse($header->remove('after-first'));
        $this->assertNull($header->find('after-first'));
    }

    public function testButtonsUseTheRecord(): void
    {
        $header = new Header();
        $header->button('unlock', ['visible' => fn($record) => $record->locked])
            ->button('pay', ['enabled' => fn($record) => false === $record->locked])
            ->button('open', ['type' => 'link', 'url' => fn($record) => 'Risk?amount=' . $record->risk]);

        $locked = $this->record(true, 10);
        $this->assertSame(['unlock', 'pay', 'open'], $this->names($header->buttons($locked)));
        $this->assertFalse($header->find('pay')->isEnabled($locked));
        $this->assertSame('Risk?amount=10', $header->find('open')->href($locked));

        $unlocked = $this->record(false, 10);
        $this->assertSame(['pay', 'open'], $this->names($header->buttons($unlocked)));
        $this->assertTrue($header->find('pay')->isEnabled($unlocked));

        // las condiciones también se pueden indicar después, por ejemplo desde un plugin
        $header->find('open')->visibleIf(fn($record) => $record->risk > 100);
        $this->assertSame(['pay'], $this->names($header->buttons($unlocked)));
    }

    public function testDropdownGroupsButtons(): void
    {
        $header = new Header();
        $header->dropdown('actions', ['label' => 'actions'])
            ->button('approve', ['dropdown' => 'actions'])
            ->button('reject', ['dropdown' => 'actions', 'visible' => fn($record) => $record->locked])
            ->button('review', ['dropdown' => 'actions', 'before' => 'approve']);

        $dropdown = $header->find('actions');
        $this->assertTrue($dropdown->isDropdown());
        $this->assertSame(['review', 'approve'], $this->names($dropdown->items($this->record(false, 0))));
        $this->assertSame(['review', 'approve', 'reject'], $this->names($dropdown->items($this->record(true, 0))));
        $this->assertSame('reject', $header->find('reject')->name());

        // un desplegable sin botones que mostrar no se muestra
        $header->dropdown('empty')->button('hidden', ['dropdown' => 'empty', 'visible' => fn() => false]);
        $this->assertSame(['actions'], $this->names($header->buttons($this->record(false, 0))));

        $this->assertTrue($header->remove('approve'));
        $this->assertNull($header->find('approve'));
    }

    public function testDuplicatedOrReservedNamesAreRejected(): void
    {
        $header = new Header();
        $header->button('send');

        foreach (['send', 'print'] as $name) {
            try {
                $header->button($name);
                $this->fail('Button ' . $name . ' should be rejected');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString($name, $exception->getMessage());
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $header->button('orphan', ['dropdown' => 'missing']);
    }

    public function testFindActionLooksInsideDropdowns(): void
    {
        $header = new Header();
        $header->button('send', ['action' => 'send-mail'])
            ->button('docs', ['type' => 'link', 'action' => 'not-an-action'])
            ->dropdown('actions')
            ->button('approve', ['dropdown' => 'actions']);

        $this->assertSame('send', $header->findAction('send-mail')->name());
        $this->assertSame('approve', $header->findAction('approve')->name());
        $this->assertNull($header->findAction('not-an-action'));
        $this->assertNull($header->findAction('actions'));
    }

    public function testStatsTakeTheValueFromTheBlock(): void
    {
        $page = new Page();
        $page->add($this->block('invoices', 12))->add($this->block('orders', 3));
        $page->find('orders')->defer();

        $header = new Header();
        $header->stat('invoices', ['block' => 'invoices'])
            ->stat('orders', ['block' => 'orders'])
            ->stat('risk', ['label' => 'risk', 'value' => fn($record) => $record->risk . ' EUR', 'url' => 'Risk']);

        // un bloque aplazado todavía no sabe cuántos registros tiene, así que su estadística se omite
        $stats = $header->stats($this->record(false, 80), $page);
        $this->assertSame(['invoices', 'risk'], array_column($stats, 'name'));
        $this->assertSame(12, $stats[0]['value']);
        $this->assertSame('invoices', $stats[0]['block']);
        $this->assertSame('Invoices title', $stats[0]['title']);
        $this->assertSame('80 EUR', $stats[1]['value']);
        $this->assertSame('Risk', $stats[1]['url']);
    }

    public function testStatusComesFromTheRecord(): void
    {
        $header = new Header();
        $this->assertNull($header->statusFor($this->record(true, 0)));

        $header->status(fn($record) => $record->locked ? ['Locked', 'danger'] : null);
        $this->assertSame(['label' => 'Locked', 'color' => 'danger'], $header->statusFor($this->record(true, 0)));
        $this->assertNull($header->statusFor($this->record(false, 0)));
    }

    private function block(string $name, int $count): Block
    {
        return new class ($name, $count) extends Block {
            /** @var int */
            private $count;

            public function __construct(string $name, int $count)
            {
                parent::__construct($name);
                $this->count = $count;
                $this->setTitle(ucfirst($name) . ' title');
            }

            public function count(): int
            {
                return $this->count;
            }

            public function template(): string
            {
                return '';
            }
        };
    }

    private function names(array $buttons): array
    {
        return array_map(fn($button) => $button->name(), $buttons);
    }

    private function record(bool $locked, int $risk): stdClass
    {
        $record = new stdClass();
        $record->locked = $locked;
        $record->risk = $risk;

        return $record;
    }
}
