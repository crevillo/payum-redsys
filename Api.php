<?php
namespace Crevillo\Payum\Redsys;

use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Payum\Core\Bridge\Buzz\ClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;

class Api
{
    const TRANSACTIONTYPE_AUTHORIZATION = 0;

    const CONSUMERLANGUAGE_SPANISH = '001';

    const DS_RESPONSE_CANCELED = '0184';

    const ORDER_NUMBER_MINIMUM_LENGTH = 4;

    const ORDER_NUMBER_MAXIMUM_LENGHT = 12;

    protected $options = array(
        'merchant_code' => null,
        'terminal' => null,
        'secret_key' => null,
        'sandbox' => true
    );

    /**
     * Currency codes to the values the bank
     * understand. Remember you can only work
     * with one of them per commerce
     */
    protected $currencies = array(
        'EUR' => '978',
        'USD' => '840',
        'GBP' => '826',
        'JPY' => '392',
        'ARA' => '32',
        'CAD' => '124',
        'CLP' => '152',
        'COP' => '170',
        'INR' => '356',
        'MXN' => '484',
        'PEN' => '604',
        'CHF' => '756',
        'BRL' => '986',
        'VEF' => '937',
        'TRL' => '949'
    );

    public function __construct(array $options, ClientInterface $client = null)
    {
        $this->client = $client ?: ClientFactory::createCurl();

        $this->options = array_replace( $this->options, $options );

        if (true == empty( $this->options['merchant_code'] )) {
            throw new InvalidArgumentException( 'The merchant_code option must be set.' );
        }
        if (true == empty( $this->options['terminal'] )) {
            throw new InvalidArgumentException( 'The terminal option must be set.' );
        }
        if (true == empty( $this->options['secret_key'] )) {
            throw new InvalidArgumentException( 'The secret_key option must be set.' );
        }
        if (false == is_bool($this->options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }
    }

    /**
     * @return string
     */
    public function getRedsysUrl()
    {
        return $this->options['sandbox'] ?
            'https://sis-t.sermepa.es:25443/sis/realizarPago' :
            'https://sis.sermepa.es/sis/realizarPago'
        ;
    }

    /**
     * @param $currency
     *
     * @return mixed
     */
    public function getISO4127( $currency )
    {
        if (!isset($this->currencies[$currency])) {
            throw new LogicException( 'Currency not allowed by the gateway.');
        }

        return $this->currencies[$currency];
    }

    /**
     * @return string
     */
    public function getMerchantCode()
    {
        return $this->options['merchant_code'];
    }

    /**
     * @return string
     */
    public function getMerchantTerminalCode()
    {
        return $this->options['terminal'];
    }

    /**
     * Validate the order number passed to the bank. it needs to pass the
     * following test
     *
     * - Must be between 4 and 12 characters
     *     - We complete with 0 to the left in case length or the number is lower
     *       than 4 in order to make the integration easier
     * - Four first characters must be digits
     * - Following eight can be digits or characters which ASCII numbers are:
     *    - between 65 and 90 ( A - Z)
     *    - between 97 and 122 ( a - z )
     *
     * If the test pass, orderNumber will be returned. if not, a Exception will be thrown
     *
     * @param string $orderNumber
     *
     * @return string
     */
    public function ensureCorrectOrderNumber($orderNumber)
    {
        if (strlen($orderNumber) > self::ORDER_NUMBER_MAXIMUM_LENGHT ) {
            throw new LogicException('Payment number can\'t have more than 12 characters');
        }

        // add 0 to the left in case length of the order number is less than 4
        $orderNumber = str_pad($orderNumber, self::ORDER_NUMBER_MINIMUM_LENGTH, '0', STR_PAD_LEFT);

        if (!preg_match('/^[0-9]{4}[a-z0-9]{0,12}$/i', $orderNumber)) {
            throw new LogicException('The payment gateway doesn\'t allow order numbers with this format.');
        }

        return $orderNumber;
    }

    /**
     * @param array $notification
     *
     * @return bool
     */
    public function validateNotificationSignature(array $notification)
    {
        $notification = ArrayObject::ensureArrayObject($notification);
        $notification->validateNotEmpty('Ds_Signature');

        return $notification['Ds_Signature'] === strtoupper(sha1(
            $notification['Ds_Amount'].
            $notification['Ds_Order'].
            $this->options['merchant_code'].
            $notification['Ds_Currency'].
            $notification['Ds_Response'].
            $this->options['secret_key']
        ));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function sign(array $params)
    {
        return strtoupper(sha1(
            $params['Ds_Merchant_Amount'].
            $params['Ds_Merchant_Order'].
            $this->options['merchant_code'].
            $params['Ds_Merchant_Currency'].
            $params['Ds_Merchant_TransactionType'].
            $params['Ds_Merchant_MerchantURL'].
            $this->options['secret_key']
        ));
    }
}
