<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Crevillo\Payum\Redsys\Api;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\GenericTokenFactoryInterface;

class CaptureAction implements ActionInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $postData = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($postData['Ds_Merchant_MerchantURL']) && $request->getToken() && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $postData['Ds_Merchant_MerchantURL'] = $notifyToken->getTargetUrl();
        }

        $postData->validatedKeysSet(array(
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantURL',
        ));

        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        if (false == $postData['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlOK'] = $request->getToken()
                ->getTargetUrl();
        }
        if (false == $postData['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlKO'] = $request->getToken()
                ->getTargetUrl();
        }

        $details['Ds_SignatureVersion'] = Api::SIGNATURE_VERSION;

        if (false == $postData['Ds_MerchantParameters'] && $request->getToken()) {
            $details['Ds_MerchantParameters'] = $this->api->createMerchantParameters($postData->toUnsafeArray());
        }

        if (false == $postData['Ds_Signature']) {
            $details['Ds_Signature'] = $this->api->sign($postData->toUnsafeArray());

            throw new HttpPostRedirect($this->api->getRedsysUrl(), $details);
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