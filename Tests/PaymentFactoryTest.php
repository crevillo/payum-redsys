<?php
namespace Crevillo\Payum\Redsys\Tests;

use Crevillo\Payum\Redsys\Api;
use Crevillo\Payum\Redsys\PaymentFactory;

class PaymentFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function mustNotBeInstantiated()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\PaymentFactory');

        $this->assertFalse($rc->isInstantiable());
    }

    /**
     * @test
     */
    public function shouldAllowCreatePaymentWithStandardActionsAdded()
    {
        $apiMock = $this->createApiMock();

        $payment = PaymentFactory::create($apiMock);

        $this->assertInstanceOf('Payum\Core\Payment', $payment);

        $actions = $this->readAttribute($payment, 'actions');
        $this->assertInternalType('array', $actions);
        $this->assertNotEmpty($actions);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->getMock('Crevillo\Payum\Redsys\Api', array(), array(), '', false);
    }
}
