<?php

namespace Crevillo\Payum\Redsys\Tests;

use Crevillo\Payum\Redsys\Api;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function constructSetOptionsCorrectly()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertAttributeEquals($options, 'options', $api);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The merchant_code option must be set.
     */
    public function throwIfMerchantCodeOptionNotSetInConstructor()
    {
        new Api(array());
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The merchant_code option must be set.
     */
    public function throwIfMerchantCodeOptionIsEmpty()
    {
        new Api(array(
            'merchant_code' => ''
        ));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The terminal option must be set.
     */
    public function throwIfTerminalOptionNotSetInConstructor()
    {
        new Api(array(
            'merchant_code' => 'a_merchant_code'
        ));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The terminal option must be set.
     */
    public function throwIfTerminalOptionIsEmpty()
    {
        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => ''
        ));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The secret_key option must be set.
     */
    public function throwIfSecretKeyOptionNotSetInConstructor()
    {
        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal'
        ));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The secret_key option must be set.
     */
    public function throwIfSecretKeyOptionIsEmpty()
    {
        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => ''
        ));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The boolean sandbox option must be set.
     */
    public function throwIfSandboxOptionIsNotBoolean()
    {
        new Api(array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => '*****',
            'sandbox' => 'string'
        ));
    }

    /**
     * @test
     */
    public function shouldReturnSandboxUrlIfInSandboxMode()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('https://sis-t.sermepa.es:25443/sis/realizarPago', $api->getRedsysUrl());
    }

    /**
     * @test
     */
    public function shouldReturnProductionEnvIfNotInSandboxMode()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => false,
        );

        $api = new Api($options);

        $this->assertEquals('https://sis.sermepa.es/sis/realizarPago', $api->getRedsysUrl() );
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Currency not allowed by the payment gateway.
     */
    public function throwIsCurrencyIsNotSupported()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $currencyCode = $api->getISO4127( 'XXX' );
    }

    /**
     * @test
     */
    public function ISO4127Test()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('978', $api->getISO4127('EUR'));
        $this->assertEquals('840', $api->getISO4127('USD'));
    }

    /**
     * @test
     *
     * @dataProvider orderNumberProvider
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The payment gateway doesn't allow order numbers with this format.
     */
    public function shouldThrowIfOrderNumberHasNotValidFormat($orderNumber)
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $api->ensureCorrectOrderNumber($orderNumber);
    }

    public function orderNumberProvider()
    {
        return array(
            array('a'),
            array('abcd'),
            array('111a111'),
            array('1234abcd#efg'),
            array('1234ñ')
        );
    }

    /**
     * @test
     *
     * @dataProvider longOrderNumberProvider
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The payment gateway can't have more than 12 characters.
     */
    public function shouldThrowIfOrderNumberHasMoreThan12Characters($orderNumber)
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $api->ensureCorrectOrderNumber($orderNumber);
    }

    public function longOrderNumberProvider()
    {
        return array(
            array('1234567890123'),
            array('1234abcdefghi')
        );
    }

    /**
     * @test
     *
     * @dataProvider shortOrderNumberProvider
     */
    public function showBuildAOrderNumberWithAtLeast4Characters($orderNumber, $correctedOrderNumber)
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals($correctedOrderNumber, $api->ensureCorrectOrderNumber($orderNumber));
    }

    public function shortOrderNumberProvider()
    {
        return array(
            array('1', '0001'),
            array('12', '0012'),
            array('123', '0123'),
            array('1234', '1234'),
            array('1234a', '1234a')
        );
    }

    /**
     * @test
     */
    public function showReturnOptionsfromGetters()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals('a_merchant_code', $api->getMerchantCode());
        $this->assertEquals('a_terminal', $api->getMerchantTerminalCode());
    }

    /**
     * @test
     */
    public function shouldReturnDefaultTransactionTypeIfNotSetInConstructor()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
        );

        $api = new Api($options);

        $this->assertEquals($api::TRANSACTIONTYPE_DEFAULT, $api->getTransactionType());
    }

    /**
     * @test
     */
    public function shouldReturTransactionTypePassedToTheConstructor()
    {
        $options = array(
            'merchant_code' => 'a_merchant_code',
            'terminal' => 'a_terminal',
            'secret_key' => 'a_secret_key',
            'sandbox' => true,
            'default_transaction_type' => 3
        );

        $api = new Api($options);

        $this->assertEquals(3, $api->getTransactionType());
    }
}
