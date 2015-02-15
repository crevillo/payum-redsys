<?php

namespace Crevillo\Payum\Redsys\Tests\Action;

use Crevillo\Payum\Redsys\Action\FillOrderDetailsAction;
use Payum\Core\Model\Order;
use Payum\Core\Request\FillOrderDetails;
use Payum\Core\Tests\GenericActionTest;

class FillOrderDetailsActionTest extends GenericActionTest
{
    protected $actionClass = 'Crevillo\Payum\Redsys\Action\FillOrderDetailsAction';

    protected $requestClass = 'Payum\Core\Request\FillOrderDetails';

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new Order())),
            array(new $this->requestClass($this->getMock('Payum\Core\Model\OrderInterface'))),
            array(new $this->requestClass(new Order(), $this->getMock('Payum\Core\Security\TokenInterface'))),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array($this->getMockForAbstractClass('Payum\Core\Request\Generic', array(array()))),
        );
    }

    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\Action\FillOrderDetailsAction');

        $this->assertTrue($rc->implementsInterface('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $action = new FillOrderDetailsAction();
        $action->setApi($expectedApi);

        $this->assertAttributeSame($expectedApi, 'api', $action);
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack()
    {
        $order = new Order;
        $order->setNumber('1234');
        $order->setCurrencyCode('USD');
        $order->setTotalAmount(123);
        $order->setDescription('the description');
        $order->setClientId('theClientId');
        $order->setClientEmail('theClientEmail');
        $order->setDetails(array(
            'Ds_Merchant_MerchantURL' => 'a_merchant_url'
            )
        );

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('ensureCorrectOrderNumber')
            ->with($order->getNumber())
            ->willReturn($order->getNumber())
        ;

        $apiMock
            ->expects($this->once())
            ->method('getISO4127')
            ->with($order->getCurrencyCode())
            ->willReturn(840)
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantCode')
            ->willReturn('a_merchant_code')
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantTerminalCode')
            ->willReturn('001')
        ;

        $tokenMock = $this->getMock('Payum\Core\Security\TokenInterface');

        $action = new FillOrderDetailsAction();
        $action->setApi($apiMock);
        $action->execute(new FillOrderDetails($order, $tokenMock ));
        $details = $order->getDetails();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('Ds_Merchant_Amount', $details);
        $this->assertEquals(123, $details['Ds_Merchant_Amount']);

        $this->assertArrayHasKey('Ds_Merchant_Order', $details);
        $this->assertEquals('1234', $details['Ds_Merchant_Order']);

        $this->assertArrayHasKey('Ds_Merchant_Currency', $details);
        $this->assertEquals(840, $details['Ds_Merchant_Currency']);

        $this->assertArrayHasKey('Ds_Merchant_MerchantCode', $details);
        $this->assertEquals('a_merchant_code', $details['Ds_Merchant_MerchantCode']);

        $this->assertArrayHasKey('Ds_Merchant_Terminal', $details);
        $this->assertEquals('001', $details['Ds_Merchant_Terminal']);

        $this->assertArrayHasKey('Ds_Merchant_MerchantURL', $details);
        $this->assertEquals('a_merchant_url', $details['Ds_Merchant_MerchantURL']);
    }

    /**
     * @test
     */
    public function shouldNotOverrideProvidesValue()
    {
        $order = new Order;
        $order->setNumber('1234');
        $order->setCurrencyCode('USD');
        $order->setTotalAmount(123);
        $order->setDescription('the description');
        $order->setClientId('theClientId');
        $order->setClientEmail('theClientEmail');
        $order->setDetails(array(
                'Ds_Merchant_MerchantURL' => 'a_merchant_url',
                'Ds_Merchant_TransactionType' => 1,
                'Ds_Merchant_ConsumerLanguage' => '002'
            )
        );

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('ensureCorrectOrderNumber')
            ->with($order->getNumber())
            ->willReturn($order->getNumber())
        ;

        $apiMock
            ->expects($this->once())
            ->method('getISO4127')
            ->with($order->getCurrencyCode())
            ->willReturn(840)
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantCode')
            ->willReturn('a_merchant_code')
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantTerminalCode')
            ->willReturn('001')
        ;

        $tokenMock = $this->getMock('Payum\Core\Security\TokenInterface');

        $action = new FillOrderDetailsAction();
        $action->setApi($apiMock);
        $action->execute(new FillOrderDetails($order, $tokenMock ));
        $details = $order->getDetails();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('Ds_Merchant_MerchantURL', $details);
        $this->assertEquals('a_merchant_url', $details['Ds_Merchant_MerchantURL']);

        $this->assertArrayHasKey('Ds_Merchant_TransactionType', $details);
        $this->assertEquals(1, $details['Ds_Merchant_TransactionType']);

        $this->assertArrayHasKey('Ds_Merchant_ConsumerLanguage', $details);
        $this->assertEquals('002', $details['Ds_Merchant_ConsumerLanguage']);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven()
    {
        $action = new FillOrderDetailsAction();

        $action->setApi(new \stdClass);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->getMock( 'Crevillo\Payum\Redsys\Api', array(), array(), '', false );
    }
}
