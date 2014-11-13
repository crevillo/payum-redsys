<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\FillOrderDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
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
        $details = ArrayObject::ensureArrayObject($order->getDetails());

        $details['Ds_Merchant_Amount'] = $order->getTotalAmount();
        $details['Ds_Merchant_Currency'] = $this->api->getISO4127($order->getCurrencyCode());
        $details['Ds_Merchant_Order'] = $this->api->ensureCorrectOrderNumber($order->getNumber());
        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        // notification url where the bank will post the response
        $details['Ds_Merchant_MerchantURL'] = $token->getTargetUrl();

        // return url in case of payment done
        $details['Ds_Merchant_UrlOK'] = $token->getAfterUrl();

        // return url in case of payment cancel. same as above
        $details['Ds_Merchant_UrlKO'] = $token->getAfterUrl();

        if (!$details['Ds_Merchant_TransactionType']) {
            $details['Ds_Merchant_TransactionType'] = $this->api->getTransactionType();
        }

        // set customer language to spanish in case not provided
        if (!$details['Ds_Merchant_ConsumerLanguage']) {
            $details['Ds_Merchant_ConsumerLanguage'] = $this->api->getDefaultLanguageCode();
        }

        // these following to are not mandatory. only filled if present in the
        // order details or in the options
        $merchantName = $this->api->getMerchantName();
        if (!$details['Ds_Merchant_MerchantName'] && !empty($merchantName)) {
            $details['Ds_Merchant_MerchantName'] = $merchantName;
        }

        $merchantProductDescription = $this->api->getMerchantProductDescription();
        if (!$details['Ds_Merchant_ProductDescription'] && !empty($merchantProductDescription)) {
            $details['Ds_Merchant_ProductDescription'] = $merchantProductDescription;
        }

        // finally build the signature
        $details['Ds_Merchant_MerchantSignature'] = $this->api->sign($details);

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
