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

namespace FacturaScripts\Test\Core\Lib\ListFilter;

use DateInterval;
use DateTimeImmutable;
use FacturaScripts\Core\Lib\ListFilter\PeriodTools;
use FacturaScripts\Core\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Periodos de fechas futuras del filtro de periodos.
 *
 * PeriodTools calcula los periodos a partir de la fecha actual, así que lo esperado se calcula aquí a partir de
 * hoy con aritmética de fechas, no con las mismas expresiones relativas que usa PeriodTools.
 *
 * @covers \FacturaScripts\Core\Lib\ListFilter\PeriodTools
 */
final class PeriodToolsTest extends TestCase
{
    const FUTURE_PERIODS = [
        'next-year',
        'until-end-of-year',
        'next-month',
        'until-end-of-month',
        'next-week',
        'until-end-of-week',
        'tomorrow',
    ];

    const SENTINEL = '01-01-1970';

    public function testFilterOptionsIncludeFuturePeriods(): void
    {
        foreach (['es_ES', 'en_EN'] as $lang) {
            $codes = [];
            foreach (PeriodTools::getFilterOptions(new Translator($lang)) as $option) {
                $codes[$option['code']] = $option['description'];
            }

            foreach (self::FUTURE_PERIODS as $period) {
                $this->assertArrayHasKey($period, $codes, $lang . '-missing-' . $period);
                $this->assertNotEquals($period, $codes[$period], $lang . '-not-translated-' . $period);
            }
        }
    }

    public function testNextMonth(): void
    {
        $today = $this->today();
        $first = $today->setDate((int)$today->format('Y'), (int)$today->format('n') + 1, 1);
        $this->assertPeriod('next-month', $first, $first->setDate((int)$first->format('Y'), (int)$first->format('n'), (int)$first->format('t')));
    }

    public function testNextWeek(): void
    {
        $today = $this->today();
        $monday = $today->add(new DateInterval('P' . (8 - (int)$today->format('N')) . 'D'));
        $this->assertPeriod('next-week', $monday, $monday->add(new DateInterval('P6D')));
    }

    public function testNextYear(): void
    {
        $year = (int)$this->today()->format('Y') + 1;
        $this->assertPeriod('next-year', $this->today()->setDate($year, 1, 1), $this->today()->setDate($year, 12, 31));
    }

    public function testTomorrow(): void
    {
        $tomorrow = $this->today()->add(new DateInterval('P1D'));
        $this->assertPeriod('tomorrow', $tomorrow, $tomorrow);
    }

    public function testUntilEndOfMonth(): void
    {
        $today = $this->today();
        $this->assertPeriod('until-end-of-month', $today, $today->setDate((int)$today->format('Y'), (int)$today->format('n'), (int)$today->format('t')));
    }

    public function testUntilEndOfWeek(): void
    {
        $today = $this->today();
        $this->assertPeriod('until-end-of-week', $today, $today->add(new DateInterval('P' . (7 - (int)$today->format('N')) . 'D')));
    }

    public function testUntilEndOfYear(): void
    {
        $today = $this->today();
        $this->assertPeriod('until-end-of-year', $today, $today->setDate((int)$today->format('Y'), 12, 31));
    }

    private function assertPeriod(string $period, DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $before = date(PeriodTools::DATE_FORMAT);
        $startDate = self::SENTINEL;
        $endDate = self::SENTINEL;
        PeriodTools::applyPeriod($period, $startDate, $endDate);
        if ($before !== date(PeriodTools::DATE_FORMAT)) {
            $this->markTestSkipped('the date changed while running the test');
        }

        $this->assertEquals($start->format(PeriodTools::DATE_FORMAT), $startDate, $period . '-bad-start-date');
        $this->assertEquals($end->format(PeriodTools::DATE_FORMAT), $endDate, $period . '-bad-end-date');
    }

    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today');
    }
}
