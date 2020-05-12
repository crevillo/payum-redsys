<?php

namespace Tests\Action;

use Crevillo\Payum\Redsys\Action\NotifyAction;
use Crevillo\Payum\Redsys\Api;
use GuzzleHttp\Psr7\Request;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use PHPUnit\Framework\TestCase;

class NotificationActionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass(NotifyAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function willThrowExceptionIfWrongApi()
    {
        $api = new \stdClass();

        $notifyAction = (new NotifyAction())->setApi($api);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\RequestNotSupportedException
     */
    public function willThrowExceptionIfNotGoodRequest()
    {
        $api = new Api([
            'merchant_code' => 'a',
            'terminal' => '1',
            'secret_key' => 'a_secret'
        ]);

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);

        $notifyAction->execute(new GetHttpRequest());
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpResponse
     */
    public function willThrowIfNoDsSignature()
    {
        $api = new Api([
            'merchant_code' => 'a',
            'terminal' => '1',
            'secret_key' => 'a_secret'
        ]);

        $model = array();

        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('execute')
            ->with(new GetHttpRequest())
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request = [];
            }))
        ;

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);
        $notifyAction->setGateway($gatewayMock);

        $request = new Notify($model);

        $notifyAction->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpResponse
     */
    public function willThrowIfDsSignatureIsNull()
    {
        $api = new Api([
            'merchant_code' => 'a',
            'terminal' => '1',
            'secret_key' => 'a_secret'
        ]);

        $model = array();

        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('execute')
            ->with(new GetHttpRequest())
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request = ['Ds_Signature' => null];
            }))
        ;

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);
        $notifyAction->setGateway($gatewayMock);

        $request = new Notify($model);

        $notifyAction->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpResponse
     */
    public function willThrowIfDsMerchantParametersIsNull()
    {
        $api = new Api([
            'merchant_code' => 'a',
            'terminal' => '1',
            'secret_key' => 'a_secret'
        ]);

        $model = array();

        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('execute')
            ->with(new GetHttpRequest())
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request = ['Ds_Signature' => 'a', 'Ds_MerchantParameters' => null];
            }))
        ;

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);
        $notifyAction->setGateway($gatewayMock);

        $request = new Notify($model);

        $notifyAction->execute($request);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpResponse
     */
    public function willThrowIfSignatureIsNotValid()
    {
        $api = $this->createMock(Api::class);

        $model = array();

        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('execute')
            ->with(new GetHttpRequest())
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request = ['Ds_Signature' => 'a', 'Ds_MerchantParameters' => 'a'];
            }))
        ;

        $api->expects($this->once())
            ->method('validateNotificationSignature')
            ->with(['Ds_Signature' => 'a', 'Ds_MerchantParameters' => 'a'])
            ->willReturn(false);

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);
        $notifyAction->setGateway($gatewayMock);

        $request = new Notify($model);

        $notifyAction->execute($request);
    }

    /**
     * @test
     *
     * @expectedExceptionMessage
     * @expectedException \Payum\Core\Reply\HttpResponse
     */
    public function willReturn200IfValid()
    {
        $api = $this->createMock(Api::class);

        $model = array();

        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('execute')
            ->with(new GetHttpRequest())
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request = ['Ds_Signature' => 'a', 'Ds_MerchantParameters' => base64_encode(json_encode(['a' => 'b']))];
            }))
        ;

        $api->expects($this->once())
            ->method('validateNotificationSignature')
            ->with(['Ds_Signature' => 'a', 'Ds_MerchantParameters' => base64_encode(
                json_encode(
                    ['a' => 'b']
                ))
            ])
            ->willReturn(true);

        $notifyAction = new NotifyAction();
        $notifyAction->setApi($api);
        $notifyAction->setGateway($gatewayMock);

        $request = new Notify($model);

        $notifyAction->execute($request);
    }
}
