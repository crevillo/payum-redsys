<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Crevillo\Payum\Redsys\Api;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;

class CaptureAction implements ActionInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param GenericTokenFactoryInterface $genericTokenFactory
     *
     * @return void
     */
    public function setGenericTokenFactory(GenericTokenFactoryInterface $genericTokenFactory = null)
    {
        $this->tokenFactory = $genericTokenFactory;
    }

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
     */
    public function execute($request)
    {
        /** @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($details['Ds_Merchant_MerchantURL']) && $request->getToken() && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $details['Ds_Merchant_MerchantURL'] = $notifyToken->getTargetUrl();
        }

        $details->validatedKeysSet(array(
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantURL',
        ));

        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        if (false == $details['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $details['Ds_Merchant_UrlOK'] = $request->getToken()->getTargetUrl();
        }

        if (false == $details['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $details['Ds_Merchant_UrlKO'] = $request->getToken()->getTargetUrl();
        }

        if (false == $details['Ds_Merchant_MerchantSignature']) {
            $details['Ds_Merchant_MerchantSignature'] = $this->api->sign($details->toUnsafeArray());

            throw new HttpPostRedirect($this->api->getRedsysUrl(), $details->toUnsafeArray());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
