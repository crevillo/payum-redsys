<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\FillOrderDetails;
use Payum\Core\Bridge\Spl\ArrayObject;
use Crevillo\Payum\Redsys\Api;

class FillOrderDetailsAction implements ActionInterface, ApiAwareInterface
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

        $details->defaults(array(
            'Ds_Merchant_TransactionType' => Api::TRANSACTIONTYPE_AUTHORIZATION,
            'Ds_Merchant_ConsumerLanguage' => Api::CONSUMERLANGUAGE_SPANISH,
        ));

        $details['Ds_Merchant_Amount'] = $order->getTotalAmount();
        $details['Ds_Merchant_Currency'] = $this->api->getISO4127($order->getCurrencyCode());
        $details['Ds_Merchant_Order'] = $this->api->ensureCorrectOrderNumber($order->getNumber());
        $details['Ds_Merchant_MerchantCode'] = $this->api->getMerchantCode();
        $details['Ds_Merchant_Terminal'] = $this->api->getMerchantTerminalCode();

        $order->setDetails($details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof FillOrderDetails;
    }
}
