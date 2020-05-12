<?php
namespace Crevillo\Payum\Redsys\Action;

use Crevillo\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request GetStatusInterface */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (null == $model['Ds_Response']) {
            $request->markNew();

            return;
        }

        if (in_array($model['Ds_Response'],
            array(Api::DS_RESPONSE_CANCELED, Api::DS_RESPONSE_USER_CANCELED, Api::DS_AUTHENTICATION_ERROR))) {
            $request->markCanceled();

            return;
        }

        if (0 <= $model['Ds_Response'] && 99 >= $model['Ds_Response']) {
            $request->markCaptured();

            return;
        }

        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
