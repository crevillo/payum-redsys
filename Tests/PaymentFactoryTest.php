<?php
namespace Crevillo\Payum\Redsys\Tests;

use Crevillo\Payum\Redsys\Api;
use Crevillo\Payum\Redsys\PaymentFactory;

class PaymentFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldImplementPaymentFactoryInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\PaymentFactory');
        $this->assertTrue($rc->implementsInterface('Payum\Core\PaymentFactoryInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new PaymentFactory();
    }
    /**
     * @test
     */
    public function shouldCreateCorePaymentFactoryIfNotPassed()
    {
        $factory = new PaymentFactory();
        $this->assertAttributeInstanceOf('Payum\Core\PaymentFactory', 'corePaymentFactory', $factory);
    }

    /**
     * @test
     */
    public function shouldUseCorePaymentFactoryPassedAsSecondArgument()
    {
        $corePaymentFactory = $this->getMock('Payum\Core\PaymentFactoryInterface');
        $factory = new PaymentFactory(array(), $corePaymentFactory);
        $this->assertAttributeSame($corePaymentFactory, 'corePaymentFactory', $factory);
    }
    /**
     * @test
     */
    public function shouldAllowCreatePayment()
    {
        $factory = new PaymentFactory();
        $payment = $factory->create(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key'
        ));
        $this->assertInstanceOf('Payum\Core\Payment', $payment);
        $this->assertAttributeNotEmpty('apis', $payment);
        $this->assertAttributeNotEmpty('actions', $payment);
        $extensions = $this->readAttribute($payment, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAllowCreatePaymentWithCustomApi()
    {
        $factory = new PaymentFactory();
        $payment = $factory->create(array('payum.api' => new \stdClass()));
        $this->assertInstanceOf('Payum\Core\Payment', $payment);
        $this->assertAttributeNotEmpty('apis', $payment);
        $this->assertAttributeNotEmpty('actions', $payment);
        $extensions = $this->readAttribute($payment, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAllowCreatePaymentConfig()
    {
        $factory = new PaymentFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);
    }

    /**
     * @test
     */
    public function shouldAddDefaultConfigPassedInConstructorWhileCreatingPaymentConfig()
    {
        $factory = new PaymentFactory(array(
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ));
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('foo', $config);
        $this->assertEquals('fooVal', $config['foo']);
        $this->assertArrayHasKey('bar', $config);
        $this->assertEquals('barVal', $config['bar']);
    }

    /**
     * @test
     */
    public function shouldConfigContainDefaultOptions()
    {
        $factory = new PaymentFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('payum.default_options', $config);
        $this->assertEquals(
            array('merchant_code' => '', 'terminal' => '', 'secret_key' => '', 'sandbox' => true),
            $config['payum.default_options']
        );
    }

    /**
     * @test
     */
    public function shouldConfigContainFactoryNameAndTitle()
    {
        $factory = new PaymentFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('payum.factory_name', $config);
        $this->assertEquals('redsys', $config['payum.factory_name']);
        $this->assertArrayHasKey('payum.factory_title', $config);
        $this->assertEquals('Redsys', $config['payum.factory_title']);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The merchant_code, terminal, secret_key fields are required.
     */
    public function shouldThrowIfRequiredOptionsNotPassed()
    {
        $factory = new PaymentFactory();
        $factory->create();
    }
}

