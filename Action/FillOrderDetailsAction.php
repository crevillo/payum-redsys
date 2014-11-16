<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\LogicException;
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
        $details = ArrayObject::ensureArrayObject($order->getDetails());

        $details['Ds_Merchant_Amount'] = $order->getTotalAmount();
        $details['Ds_Merchant_Currency'] = $this->api->getISO4127($order->getCurrencyCode());
        $details['Ds_Merchant_Order'] = $this->api->ensureCorrectOrderNumber($order->getNumber());
        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        if (false == $details['Ds_Merchant_MerchantURL']) {
            if (false == $this->tokenFactory) {
                throw new LogicException('The merchant url is not provided. You have explicitly add it to the order details or inject the token factory');
            }

            $details['Ds_Merchant_MerchantURL'] = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getPaymentName(),
                $request->getToken()->getDetails()
            )->getTargetUrl();
        }


        if (false == $details['Ds_Merchant_TransactionType']) {
            $details['Ds_Merchant_TransactionType'] = Api::TRANSACTIONTYPE_AUTHORIZATION;
        }

        if (false == $details['Ds_Merchant_ConsumerLanguage']) {
            $details['Ds_Merchant_ConsumerLanguage'] = Api::CONSUMERLANGUAGE_SPANISH;
        }

        $details['Ds_Merchant_MerchantSignature'] = $this->api->sign($details->toUnsafeArray());

        $order->setDetails($details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof FillOrderDetails &&
            $request->getToken()
        ;
    }
}
