<?php

namespace Crevillo\Payum\Redsys\Tests\Action;

use Crevillo\Payum\Redsys\Action\CaptureAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;

class CaptureActionTest extends GenericActionTest
{
    protected $requestClass = 'Payum\Core\Request\Capture';

    protected $actionClass = 'Crevillo\Payum\Redsys\Action\CaptureAction';

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\Action\CaptureAction');

        $this->assertTrue($rc->implementsInterface('Payum\Core\ApiAwareInterface'));
    }

    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\Action\CaptureAction');

        $this->assertTrue($rc->implementsInterface('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $action = new CaptureAction();
        $action->setApi($expectedApi);

        $this->assertAttributeSame($expectedApi, 'api', $action);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven()
    {
        $action = new CaptureAction();

        $action->setApi(new \stdClass);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The Ds_Merchant_Amount fields is not set.
     */
    public function shouldThrowIfAmountIsNotSet()
    {
        $model = array();

        $details = ArrayObject::ensureArrayObject($model);

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->with($details)
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The Ds_Merchant_Order fields is not set.
     */
    public function shouldThrowIfOrderNumberIsNotSet()
    {
        $model = array(
            'Ds_Merchant_Amount' => 1000
        );

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The Ds_Merchant_Currency fields is not set.
     */
    public function shouldThrowIfCurrencyIsNotSet()
    {
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234'
        );

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The Ds_Merchant_TransactionType fields is not set.
     */
    public function shouldThrowIfTransactionTypeIsNotSet()
    {
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978'
        );

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The Ds_Merchant_MerchantURL fields is not set.
     */
    public function shouldThrowIfMerchantURLIsNotSet()
    {
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978',
            'Ds_Merchant_TransactionType' => 0
        );

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpPostRedirect
     */
    public function shouldRedirectToRedsysSite()
    {
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978',
            'Ds_Merchant_TransactionType' => 0,
            'Ds_Merchant_MerchantURL' => 'https://sis-t.sermepa.es:25443/sis/realizarPago'
        );

        $apiMock = $this->createApiMock();

        $paymentMock = $this->createPaymentMock();
        $paymentMock
            ->expects($this->never())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->getMock( 'Crevillo\Payum\Redsys\Api', array(), array(), '', false );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentInterface
     */
    protected function createPaymentMock()
    {
        return $this->getMock('Payum\Core\PaymentInterface');
    }
}
