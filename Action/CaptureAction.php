<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Crevillo\Payum\Redsys\Api;

class CaptureAction extends PaymentAwareAction implements ApiAwareInterface
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
            throw new UnsupportedApiException( 'Not supported.' );
        }
        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $details->validatedKeysSet(array(
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantURL'
        ));

        // return url in case of payment done
        if (false == $details['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $details['Ds_Merchant_UrlOK'] = $request->getToken()->getTargetUrl();
        }

        // return url in case of payment cancel. same as above
        if (false == $details['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $details['Ds_Merchant_UrlKO'] = $request->getToken()->getTargetUrl();
        }

        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        if (false == $details['Ds_Merchant_MerchantSignature']) {
            $details['Ds_Merchant_MerchantSignature'] = $this->api->sign($details->toUnsafeArray());

            throw new HttpPostRedirect($this->api->getRedsysUrl(), $details->toUnsafeArray());
        }

        $httpRequest = new GetHttpRequest();
        $this->payment->execute($httpRequest);

//        we are back from redsys site so we have to just update model.
//        if (!empty( $httpRequest->request ) &&
//            $this->api->validateGatewayResponse( $httpRequest->request )
//        ) {
//            $details->replace( $httpRequest->request );
//             throw empty response so bank receive a response with code 200
//            throw new HttpResponse('', 200);
//        }

        if (false == $details['Ds_Response']) {
//            die;

        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
