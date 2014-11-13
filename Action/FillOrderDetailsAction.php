<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\FillOrderDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Crevillo\Payum\Redsys\Api;

class FillOrderDetailsAction implements ActionInterface, ApiAwareInterface
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
     * @param GenericTokenFactoryInterface $tokenFactory
     */
    public function __construct(GenericTokenFactoryInterface $tokenFactory = null)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException( 'Not supported.' );
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param FillOrderDetails $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $order = $request->getOrder();
        $token = $request->getToken();
        $details = $order->getDetails();

        $details['Ds_Merchant_Amount'] = $order->getTotalAmount();

        $details['Ds_Merchant_Currency'] = $this->api->getISO4127($order->getCurrencyCode());

        $details['Ds_Merchant_Order'] = $this->api->ensureCorrectOrderNumber($order->getNumber());

        $details = $this->api->completePaymentDetails($details);

        // notification url where the bank will post the response
        $details['Ds_Merchant_MerchantURL'] = $token->getTargetUrl();

        // return url in case of payment done
        $details['Ds_Merchant_UrlOK'] = $token->getAfterUrl();

        // return url in case of payment cancel. same as above
        $details['Ds_Merchant_UrlKO'] = $token->getAfterUrl();
        $details['Ds_Merchant_MerchantSignature'] = $this->api->buildSignature( $details );


        $order->setDetails( $details );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof FillOrderDetails;
    }
}
