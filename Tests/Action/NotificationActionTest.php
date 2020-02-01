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

        $model = array(
            'Ds_Signature' => null,
        );

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

}
