<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaProveedor;

class ApiPagarFacturaProveedor extends ApiController
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
            $facturasprov = new FacturaProveedor();
            $facturasprov->loadFromCode($id);
            foreach ($facturasprov->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }
            $facturasprov->loadFromCode($id);

            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $facturasprov->toArray());
        }
        else if ($pagada === 'false') {
            $facturasprov = new FacturaProveedor();
            $facturasprov->loadFromCode($id);
            foreach ($facturasprov->getReceipts() as $receipt) {
                $receipt->pagado = false;
                $receipt->save();
            }
            $facturasprov->loadFromCode($id);

            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $facturasprov->toArray());
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