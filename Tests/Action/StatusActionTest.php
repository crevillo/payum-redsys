<?php


namespace Tests\Action;

use Crevillo\Payum\Redsys\Action\StatusAction;
use Crevillo\Payum\Redsys\Api;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetStatusInterface;
use PHPUnit\Framework\TestCase;

class StatusActionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Crevillo\Payum\Redsys\Action\StatusAction');

        $this->assertTrue($rc->implementsInterface('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new StatusAction();
    }

    /**
     * @test
     */
    public function shouldSupportStatusRequestWithArrayAccessAsModel()
    {
        $action = new StatusAction();

        $request = $this->createGetStatusStub($this->createMock('ArrayAccess'));

        $this->assertTrue($action->supports($request));
    }

    /**
     * @test
     */
    public function shouldNotSupportNotStatusRequest()
    {
        $action = new StatusAction();

        $request = new \stdClass();

        $this->assertFalse($action->supports($request));
    }

    /**
     * @test
     */
    public function shouldNotSupportStatusRequestWithNotArrayAccessAsModel()
    {
        $action = new StatusAction();

        $request = $this->createGetStatusStub(new \stdClass());

        $this->assertFalse($action->supports($request));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\RequestNotSupportedException
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $action = new StatusAction();

        $action->execute(new \stdClass());
    }

    /**
     * @test
     */
    public function shouldMarkNewIfDetailsEmpty()
    {
        $request = new GetBinaryStatus(array());
        $request->markUnknown();

        $action = new StatusAction();

        $action->execute($request);

        $this->assertTrue($request->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkAsNewIfResponseIsNull()
    {
        $request = new GetBinaryStatus(
            new ArrayObject(['Ds_Response' => null])
        );
        $request->markUnknown();

        $action = new StatusAction();

        $action->execute($request);

        $this->assertTrue($request->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkAsCanceledWhenNeeded()
    {
        $request = new GetBinaryStatus(
            new ArrayObject(['Ds_Response' => Api::DS_RESPONSE_CANCELED])
        );
        $request->markUnknown();

        $action = new StatusAction();

        $action->execute($request);

        $this->assertTrue($request->isCanceled());
    }

    /**
     * @test
     */
    public function shouldMarkAsCapturedWhenGoodResponse()
    {
        $request = new GetBinaryStatus(
            new ArrayObject(['Ds_Response' => rand(0, 99)])
        );
        $request->markUnknown();

        $action = new StatusAction();

        $action->execute($request);

        $this->assertTrue($request->isCaptured());
    }

    /**
     * @test
     */
    public function shouldMarkAsUnknownForBadResponses()
    {
        $request = new GetBinaryStatus(
            new ArrayObject(['Ds_Response' => -1])
        );
        $request->markUnknown();

        $action = new StatusAction();

        $action->execute($request);

        $this->assertTrue($request->isUnknown());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GetStatusInterface
     */
    protected function createGetStatusStub($model)
    {
        $status = $this->createMock('Payum\Core\Request\GetStatusInterface');

        $status
            ->expects($this->any())
            ->method('getModel')
            ->will($this->returnValue($model))
        ;

        return $status;
    }
}
