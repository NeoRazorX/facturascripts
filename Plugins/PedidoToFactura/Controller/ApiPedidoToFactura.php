<?php
/**
 * Plugin PedidoToFactura para FacturaScripts
 *
 * Converts a PedidoCliente to FacturaCliente via the standard
 * BusinessDocumentGenerator, preserving anticipos transfer.
 *
 * POST /api/3/pedidoToFactura
 * Body (form-urlencoded):
 *   idpedido  (required) — PedidoCliente ID
 *   codserie  (optional) — Override series on the new invoice
 *   fecha     (optional) — Override date DD-MM-YYYY
 *   numero2   (optional) — External reference (set before commit)
 *
 * @author CDTCOM
 * @license MIT
 */

namespace FacturaScripts\Plugins\PedidoToFactura\Controller;

use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PedidoCliente;

class ApiPedidoToFactura extends ApiController
{
    protected function runResource(): void
    {
        // Only POST allowed.
        if ($this->request->method() !== 'POST') {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        // Get idpedido from request body.
        $idpedido = (int) $this->request->request->get('idpedido', 0);
        if ($idpedido <= 0) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'missing-or-invalid-idpedido',
                ]);
            return;
        }

        // Load PedidoCliente.
        $pedido = new PedidoCliente();
        if (!$pedido->load($idpedido)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('record-not-found'),
                    'idpedido' => $idpedido,
                ]);
            return;
        }

        // Check if already converted: query DocTransformation.
        $docTrans = new DocTransformation();
        $where = [
            Where::eq('model1', 'PedidoCliente'),
            Where::eq('iddoc1', $idpedido),
            Where::eq('model2', 'FacturaCliente'),
        ];
        $existing = $docTrans->all($where, [], 0, 1);
        if (!empty($existing)) {
            $first = reset($existing);
            $factura = new FacturaCliente();
            $factura->load($first->iddoc2);
            $this->response
                ->setHttpCode(Response::HTTP_CONFLICT)
                ->json([
                    'status' => 'error',
                    'message' => 'already-invoiced',
                    'idfactura' => $first->iddoc2,
                    'codigo' => $factura->codigo ?? '',
                ]);
            return;
        }

        // Build properties overrides.
        $properties = [];
        $codserie = $this->request->request->get('codserie', '');
        if (!empty($codserie)) {
            $properties['codserie'] = $codserie;
        }
        $fecha = $this->request->request->get('fecha', '');
        if (!empty($fecha)) {
            $properties['fecha'] = $fecha;
        }
        $numero2 = $this->request->request->get('numero2', '');
        if (!empty($numero2)) {
            $properties['numero2'] = $numero2;
        }

        // Generate FacturaCliente from PedidoCliente.
        $generator = new BusinessDocumentGenerator();
        $success = $generator->generate($pedido, 'FacturaCliente', [], [], $properties);

        if (!$success) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json([
                    'status' => 'error',
                    'message' => 'conversion-failed',
                    'idpedido' => $idpedido,
                ]);
            return;
        }

        // Get the newly created FacturaCliente.
        $lastDocs = $generator->getLastDocs();
        $newFactura = reset($lastDocs);

        // Set status to Emitida (idestado=11) instead of default Boceto.
        $newFactura->idestado = 11;
        if (!$newFactura->save()) {
            Tools::log()->warning('Could not set factura to Emitida state');
        }

        // Count transferred anticipos (if Anticipos plugin active).
        $anticipos_transferred = 0;
        if (class_exists('\\FacturaScripts\\Dinamic\\Model\\Anticipo')) {
            $anticipoModel = new \FacturaScripts\Dinamic\Model\Anticipo();
            $anticipoWhere = [
                Where::eq('idfactura', $newFactura->primaryColumnValue()),
            ];
            $anticipos_transferred = $anticipoModel->count($anticipoWhere);
        }

        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'status' => 'ok',
                'idfactura' => $newFactura->idfactura,
                'codigo' => $newFactura->codigo,
                'codserie' => $newFactura->codserie,
                'total' => $newFactura->total,
                'neto' => $newFactura->neto,
                'totaliva' => $newFactura->totaliva,
                'idpedido' => $idpedido,
                'anticipos_transferred' => $anticipos_transferred,
                'idestado' => $newFactura->idestado,
            ]);
    }
}
