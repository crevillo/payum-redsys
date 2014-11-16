<?php
namespace Crevillo\Payum\Redsys;

use Crevillo\Payum\Redsys\Action\NotifyAction;
use Payum\Core\Action\ExecuteSameRequestWithModelDetailsAction;
use Payum\Core\Action\GetHttpRequestAction;
use Payum\Core\Extension\EndlessCycleDetectorExtension;
use Payum\Core\Payment;
use Crevillo\Payum\Redsys\Action\CaptureAction;
use Crevillo\Payum\Redsys\Action\FillOrderDetailsAction;
use Crevillo\Payum\Redsys\Action\StatusAction;

abstract class PaymentFactory
{
    /**
     * @return \Payum\Core\Payment
     */
    public static function create(Api $api)
    {
        $payment = new Payment();

        $payment->addApi($api);
        $payment->addExtension(new EndlessCycleDetectorExtension());
        $payment->addAction(new CaptureAction());
        $payment->addAction(new NotifyAction());
        $payment->addAction(new FillOrderDetailsAction());
        $payment->addAction(new StatusAction());
        $payment->addAction(new ExecuteSameRequestWithModelDetailsAction());
        $payment->addAction(new GetHttpRequestAction());

        return $payment;
    }
}
