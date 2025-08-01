<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ApiPagarFacturaCliente extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es POST o PUT, devolvemos un error
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        $pagada = $this->request->get('pagada');
        $id = $this->getUriParam(3);

        if (empty($pagada)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
        }
        else if($pagada === 'true') {
            $facturascli = new FacturaCliente();
            $facturascli->loadFromCode($id);
            foreach ($facturascli->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }
            $facturascli->loadFromCode($id);

            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $facturascli->toArray());
        }
        else if ($pagada === 'false') {
            $facturascli = new FacturaCliente();
            $facturascli->loadFromCode($id);
            foreach ($facturascli->getReceipts() as $receipt) {
                $receipt->pagado = false;
                $receipt->save();
            }
            $facturascli->loadFromCode($id);

            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $facturascli->toArray());
        }
    }

    protected function setOk(string $message, ?array $data = null)
    {
        Tools::log('api')->notice($message);

        $res = ['ok' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setHttpCode(Response::HTTP_OK);
    }

    protected function setError(string $message, ?array $data = null, int $status = Response::HTTP_BAD_REQUEST)
    {
        Tools::log('api')->error($message);

        $res = ['error' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setHttpCode($status);
    }
}