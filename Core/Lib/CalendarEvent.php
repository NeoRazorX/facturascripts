<?php declare(strict_types=1);


namespace FacturaScripts\Core\Lib;


class CalendarEvent
{
    /**
     * @var string
     */
    public $vencimiento;

    /**
     * @var string
     */

    public $url;
    /**
     * @var string
     */
    public $titulo;

    /**
     * @var string
     */
    public $descripcion;

    /**
     * @param string $vencimiento
     * @param string $url
     * @param string $titulo
     * @param string $descripcion
     */
    public function __construct($vencimiento, $url, $titulo, $descripcion = '')
    {
        $this->vencimiento = $vencimiento;
        $this->url = $url;
        $this->titulo = $titulo;
        $this->descripcion = $descripcion;
    }
}
