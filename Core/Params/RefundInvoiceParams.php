<?php

namespace FacturaScripts\Core\Params;

use FacturaScripts\Core\Model\LineaFacturaCliente;

class RefundInvoiceParams
{
    /** @var LineaFacturaCliente[] */
    public array $lines = [];

    public string $codserie = '';

    public string $fecha = '';

    public ?string $hora = null;

    public string $observaciones = '';

    public string $idestado = '';

    public string $nick = '';

    public bool $includeAllLinesIfEmpty = false;

    public function __construct(
        array $lines = [],
        string $codserie = '',
        string $fecha = '',
        ?string $hora = null,
        string $observaciones = '',
        string $idestado = '',
        string $nick = '',
        bool $includeAllLinesIfEmpty = false
    ) {
        $this->lines = $lines;
        $this->codserie = $codserie;
        $this->fecha = $fecha;
        $this->hora = $hora;
        $this->observaciones = $observaciones;
        $this->idestado = $idestado;
        $this->nick = $nick;
        $this->includeAllLinesIfEmpty = $includeAllLinesIfEmpty;
    }
}
