<?php declare(strict_types=1);


namespace FacturaScripts\Core\Lib;


use Carbon\Carbon;
use Carbon\CarbonImmutable;
use FacturaScripts\Core\Html;

/**
 * Adapted from https://tighten.com/insights/building-a-calendar-with-carbon/.
 */
class Calendar
{
    /**
     * @param int $year
     * @return mixed[]
     */
    public static function buildYear($year)
    {
        return [
            'year' => $year,
            'months' => array_map(
                function ($month) use ($year):void {
                    static::buildMonth($year, $month);
                },
                range(1, 12)
            ),
        ];
    }

    /**
     * @param int $year
     * @param int $month
     * @param int|null $day
     * @param CalendarEvent[] $eventos
     * @return mixed[]|null
     */
    public static function buildMonth($year, $month, $day = null, $eventos = [])
    {
        $selectedDate = CarbonImmutable::create($year, $month, $day ?? 1);
        if (false === $selectedDate) {
            return null;
        }

        $startOfMonth = $selectedDate->startOfMonth();

        $endOfMonth = $selectedDate->endOfMonth();

        $startOfWeek = $startOfMonth->startOfWeek(Carbon::MONDAY);

        $endOfWeek = $endOfMonth->endOfWeek(Carbon::SUNDAY);

        $weeks = array_map(
            function ($date) use ($day, $startOfMonth, $endOfMonth, $selectedDate, $eventos) {
                $eventosDelDia = array_filter(
                    $eventos,
                    function ($evento) use ($date) {
                        return $evento->vencimiento === $date->format('d-m-Y');
                    },
                    ARRAY_FILTER_USE_BOTH
                );

                return [
                    'day' => $date->day,
                    'withinMonth' => $date->between($startOfMonth, $endOfMonth),
                    'selected' => $day && $date->is($selectedDate->format('d-m-Y')),
                    'events' => $eventosDelDia,
                ];
            },
            $startOfWeek->toPeriod($endOfWeek)->toArray()
        );

        $weeks = array_chunk($weeks, 7, true);


        return [
            'year' => $selectedDate->year,
            'month' => $selectedDate->format('F'),
            'weeks' => $weeks,
        ];
    }

    /**
     * @param int $year
     * @param int $month
     * @param int|null $day
     * @param CalendarEvent[] $eventos
     * @return string|null
     */
    public static function renderMonth($year, $month, $day = null, $eventos = []):?string
    {
        $month = static::buildMonth($year, $month, $day, $eventos);
        $templatePath = 'Components' . DIRECTORY_SEPARATOR . 'calendar.html.twig';

        return Html::render($templatePath, compact('month'));
    }
}
