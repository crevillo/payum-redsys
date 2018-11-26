<?php
namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Bridge\Spl\ArrayObject;
use Crevillo\Payum\Redsys\Api;

class ConvertPaymentAction implements ActionInterface, ApiAwareInterface
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
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details->defaults(array(
            'Ds_Merchant_Amount' => $payment->getTotalAmount(),
            'Ds_Merchant_Order' => $this->api->ensureCorrectOrderNumber($payment->getNumber()),
            'Ds_Merchant_MerchantCode' => $this->api->getMerchantCode(),
            'Ds_Merchant_Currency' => $this->api->getISO4127($payment->getCurrencyCode()),
            'Ds_Merchant_TransactionType' => Api::TRANSACTIONTYPE_AUTHORIZATION,
            'Ds_Merchant_Terminal' => $this->api->getMerchantTerminalCode(),
            'Ds_Merchant_UrlOK' => $this->api->getMerchantUrlOk(),
            'Ds_Merchant_UrlKO' => $this->api->getMerchantUrlKo(),
        ));

        $request->setResult((array)$details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            'array' == $request->getTo() &&
            $request->getSource() instanceof PaymentInterface;
    }
}
