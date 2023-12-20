<?php declare(strict_types=1);


namespace FacturaScripts\Core\Lib;


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
                function ($month) use ($year): void {
                    static::buildMonth($year, $month);
                },
                range(1, 12)
            ),
        ];
    }

    /**
     * @param string $year
     * @param string $month
     * @param string|null $day
     * @param CalendarEvent[] $eventos
     * @return mixed[]|null
     */
    public static function buildMonth($year, $month, $day = null, $eventos = [])
    {
        $selectedDate = new FechaImmutable($year, $month, $day ?? 1);

        if (false === $selectedDate) {
            return null;
        }

        $weeks = array_map(
            function ($date) use ($day, $selectedDate, $eventos) {
                $eventosDelDia = array_filter(
                    $eventos,
                    function ($evento) use ($date) {
                        return $evento->vencimiento === $date->format('d-m-Y');
                    },
                    ARRAY_FILTER_USE_BOTH
                );

                return [
                    'day' => $date->format('d'),
                    'withinMonth' => $selectedDate->between($date),
                    'selected' => $day && $selectedDate->is($date),
                    'events' => $eventosDelDia,
                ];
            },
            $selectedDate->toPeriod()
        );

        $weeks = array_chunk($weeks, 7, true);

        return [
            'year' => $selectedDate->format("Y"),
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
    public static function renderMonth($year, $month, $day = null, $eventos = []): ?string
    {
        $month = static::buildMonth($year, $month, $day, $eventos);
        $templatePath = 'Components' . DIRECTORY_SEPARATOR . 'calendar.html.twig';

        return Html::render($templatePath, compact('month'));
    }
}
