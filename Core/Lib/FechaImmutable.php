<?php declare(strict_types=1);

namespace FacturaScripts\Core\Lib;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class FechaImmutable
{
    /** @var DateTimeImmutable */
    public $date;

    /** @var string */
    public $year;

    /** @var string */
    public $month;

    public function __construct(string $year, string $month, string $day = "1")
    {
        $this->year = $year;
        $this->month = $month;

        $this->date = new DateTimeImmutable("$year-$month-$day 00:00:00");
    }

    /**
     * Devuelve el primer día del mes.
     *
     * @return DateTimeImmutable
     */
    public function startOfMonth(): DateTimeImmutable
    {
        return $this->date->setDate((int)$this->year, (int)$this->month, 1);
    }

    /**
     * Devuelve el último día del mes.
     *
     * @return DateTimeImmutable
     */
    public function endOfMonth(): DateTimeImmutable
    {
        return $this->date
            ->setDate((int)$this->year, (int)$this->month, (int)$this->date->format('t'))
            ->setTime(24 - 1, 60 - 1, 60 - 1, 1000000 - 1);
    }

    /**
     *
     * Devuelve el primer lunes de la semana donde
     * se encuentre el día 1 del mes(aunque sea el del mes anterior).
     * ejemplo: para el mes de diciembre de 2023 seria el 27/11/2023
     * 27 28 29 30 01 02 03
     *
     * @return DateTimeImmutable|false
     */
    public function startOfWeek()
    {
        $days = (7 + (int)$this->startOfMonth()->format("w") - 1) % 7;
        return $this->startOfMonth()->sub(DateInterval::createFromDateString("$days days"));
    }

    /**
     *
     * Devuelve el último domingo de la semana donde
     * se encuentre el último día del mes(aunque sea el del mes posterior).
     * ejemplo: para el mes de noviembre de 2023 seria el 03/12/2023
     * 27 28 29 30 01 02 03
     *
     * @return DateTimeImmutable|false
     */
    public function endOfWeek()
    {
        $days = (7 - (int)$this->endOfMonth()->format("w") + 0) % 7;
        return $this->endOfMonth()->add(DateInterval::createFromDateString("$days days"));
    }

    /**
     * Devuelve la fecha con el formato dado.
     *
     * @param string $format
     * @return string
     */
    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    /**
     * Devuelve el array completo del mes(incluyendo los días desde el primer lunes hasta el último domingo)
     *
     * @return array<DateTimeImmutable>
     */
    public function toPeriod(): array
    {
        $interval = DateInterval::createFromDateString('1 day');

        $daterange = new DatePeriod($this->startOfWeek(), $interval, $this->endOfWeek());

        return iterator_to_array($daterange);
    }

    /**
     * Devuelve verdadero o falso si la fecha pasada por parametro
     * se encuentra entre las fechas de inicio y fin.
     *
     * @param DateTimeImmutable $fecha
     * @return bool
     */
    public function between(DateTimeImmutable $fecha): bool
    {
        $fechaInicio = $this->startOfMonth();
        $fechaFin = $this->endOfMonth();

        return ($fecha >= $fechaInicio && $fecha <= $fechaFin);
    }

    public function is($fecha)
    {
        return ($fecha->format('Y-m-d') === $this->date->format('Y-m-d'));
    }
}
