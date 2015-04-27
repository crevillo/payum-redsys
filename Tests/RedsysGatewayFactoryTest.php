<?php
namespace Crevillo\Payum\Redsys\Tests;

use Crevillo\Payum\Redsys\RedsysGatewayFactory;

class RedsysGatewayFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldImplementGatewayFactoryInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\RedsysGatewayFactory');
        $this->assertTrue($rc->implementsInterface('Payum\Core\GatewayFactoryInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new RedsysGatewayFactory();
    }
    /**
     * @test
     */
    public function shouldCreateCoreGatewayFactoryIfNotPassed()
    {
        $factory = new RedsysGatewayFactory();
        $this->assertAttributeInstanceOf('Payum\Core\GatewayFactory', 'coreGatewayFactory', $factory);
    }

    /**
     * @test
     */
    public function shouldUseCoreGatewayFactoryPassedAsSecondArgument()
    {
        $coreGatewayFactory = $this->getMock('Payum\Core\GatewayFactoryInterface');
        $factory = new RedsysGatewayFactory(array(), $coreGatewayFactory);
        $this->assertAttributeSame($coreGatewayFactory, 'coreGatewayFactory', $factory);
    }
    /**
     * @test
     */
    public function shouldAllowCreateGateway()
    {
        $factory = new RedsysGatewayFactory();
        $gateway = $factory->create(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key'
        ));
        $this->assertInstanceOf('Payum\Core\Gateway', $gateway);
        $this->assertAttributeNotEmpty('apis', $gateway);
        $this->assertAttributeNotEmpty('actions', $gateway);
        $extensions = $this->readAttribute($gateway, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayWithCustomApi()
    {
        $factory = new RedsysGatewayFactory();
        $gateway = $factory->create(array('payum.api' => new \stdClass()));
        $this->assertInstanceOf('Payum\Core\Gateway', $gateway);
        $this->assertAttributeNotEmpty('apis', $gateway);
        $this->assertAttributeNotEmpty('actions', $gateway);
        $extensions = $this->readAttribute($gateway, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayConfig()
    {
        $factory = new RedsysGatewayFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);
    }

    /**
     * @test
     */
    public function shouldAddDefaultConfigPassedInConstructorWhileCreatingGatewayConfig()
    {
        $factory = new RedsysGatewayFactory(array(
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
        $factory = new RedsysGatewayFactory();
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
        $factory = new RedsysGatewayFactory();
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
        $factory = new RedsysGatewayFactory();
        $factory->create();
    }
}

