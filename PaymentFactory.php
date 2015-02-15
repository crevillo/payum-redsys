<?php
namespace Crevillo\Payum\Redsys;

use Crevillo\Payum\Redsys\Action\NotifyAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\PaymentFactory as CorePaymentFactory;
use Crevillo\Payum\Redsys\Action\CaptureAction;
use Crevillo\Payum\Redsys\Action\FillOrderDetailsAction;
use Crevillo\Payum\Redsys\Action\StatusAction;
use Payum\Core\PaymentFactoryInterface;

class PaymentFactory implements PaymentFactoryInterface
{
    /**
     * @var PaymentFactoryInterface
     */
    protected $corePaymentFactory;

    /**
     * @var array
     */
    private $defaultConfig;

    /**
     * @param array $defaultConfig
     * @param PaymentFactoryInterface $corePaymentFactory
     */
    public function __construct(array $defaultConfig = array(), PaymentFactoryInterface $corePaymentFactory = null)
    {
        $this->corePaymentFactory = $corePaymentFactory ?: new CorePaymentFactory();
        $this->defaultConfig = $defaultConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $config = array())
    {
        return $this->corePaymentFactory->create($this->createConfig($config));
    }

    /**
     * {@inheritDoc}
     */
    public function createConfig(array $config = array())
    {
        $config = ArrayObject::ensureArrayObject($config);
        $config->defaults($this->defaultConfig);
        $config->defaults($this->corePaymentFactory->createConfig());

        $config->defaults(array(
            'payum.factory_name' => 'redsys',
            'payum.factory_title' => 'Redsys',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.fill_order_details' => new FillOrderDetailsAction(),
            'payum.action.status' => new StatusAction(),
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'merchant_code' => '',
                'terminal' => '',
                'secret_key' => '',
                'sandbox' => true,
            );

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array('merchant_code', 'terminal', 'secret_key');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $redsysConfig = array(
                    'merchant_code' => $config['merchant_code'],
                    'terminal' => $config['terminal'],
                    'secret_key' => $config['secret_key'],
                    'sandbox' => $config['sandbox'],
                );

                return new Api($redsysConfig, $config['buzz.client']);
            };
        }

        return (array) $config;
    }
}
