<?php

namespace Base;

use FacturaScripts\Core\Model\EstadoDocumento;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class TransformerDocumentTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        self::setDefaultSettings();
    }

    /**
     * Restaurar estado anterior de documentos al desagrupar
     */
    public function testRestoreDocumentState(): void
    {
        // creamos un presupuesto con estado NO PREDETERMINADO
        $doc = $this->getRandomCustomerOrder();

        // nos guardamos el estado original del pedido
        $idEstadoOriginal = $doc->idestado;

        // transformamos en albaran
        $estadoGenerarAlbaran = $this->getEstadoGenerarAlbaran();
        $doc->idestado = $estadoGenerarAlbaran->idestado;
        $this->assertTrue($doc->save());

        // eliminamos albaran
        foreach ($doc->childrenDocuments() as $children_document) {
            $this->assertTrue($children_document->delete());
        }

        // recargamos el pedido
        $doc->reload();

        // comprobamos que el estado del pedido es el de origen y no el predeterminado
        $this->assertEquals($idEstadoOriginal, $doc->idestado);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function getRandomCustomerOrder(): PedidoCliente
    {
        // creamos un estado NO PREDETERMINADO
        $estadoAceptado = $this->getEstadoAceptado();

        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save());

        $doc = new PedidoCliente();
        $doc->setSubject($subject);
        $doc->idestado = $estadoAceptado->idestado;
        $this->assertTrue($doc->save());

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        $lines = $doc->getLines();
        Calculator::calculate($doc, $lines, true);

        return $doc;
    }

    private function getEstadoAceptado(): EstadoDocumento
    {
        $estadoAceptado = new EstadoDocumento();
        $estadoAceptado->nombre = 'Aceptado';
        $estadoAceptado->tipodoc = 'PedidoCliente';
        $estadoAceptado->generadoc = '';
        $this->assertTrue($estadoAceptado->save());

        return $estadoAceptado;
    }

    private function getEstadoGenerarAlbaran(): EstadoDocumento
    {
        foreach (EstadoDocumento::all() as $estado_documento) {
            if ($estado_documento->tipodoc === 'PedidoCliente' && $estado_documento->generadoc === 'AlbaranCliente') {
                return $estado_documento;
            }
        }

        return new EstadoDocumento();
    }
}
