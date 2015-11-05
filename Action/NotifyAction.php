<?php
namespace Crevillo\Payum\Redsys\Action;

use Crevillo\Payum\Redsys\Api;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;

class NotifyAction extends GatewayAwareAction implements ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (null === $httpRequest->request['Ds_Signature']) {
            throw new HttpResponse('The notification is invalid', 400);
        }

        if (null === $httpRequest->request['Ds_MerchantParameters']) {
            throw new HttpResponse('The notification is invalid', 400);
        }

        if (false == $this->api->validateNotificationSignature($httpRequest->request)) {
            throw new HttpResponse('The notification is invalid', 400);
        }

        // After migrating to sha256, DS_Response param is not present in the
        // post request sent by the bank. Instead, bank sends an encoded string
        //  our gateway needs to decode.
        // Once this is decoded we need to add this info to the details among
        // with the $httpRequest->request part
        $details->replace(
            ArrayObject::ensureArrayObject(
                json_decode(base64_decode(strtr($httpRequest->request['Ds_MerchantParameters'], '-_', '+/')))
            )->toUnsafeArray() +
            $httpRequest->request
        );

        throw new HttpResponse('', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
