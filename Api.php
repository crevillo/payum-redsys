<?php
namespace Crevillo\Payum\Redsys;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;

class Api
{
    const TRANSACTIONTYPE_AUTHORIZATION = 0;

    const CONSUMERLANGUAGE_SPANISH = '001';

    const DS_RESPONSE_CANCELED = '0184';

    const DS_RESPONSE_USER_CANCELED = '9915';

    const ORDER_NUMBER_MINIMUM_LENGTH = 4;

    const ORDER_NUMBER_MAXIMUM_LENGHT = 12;

    const SIGNATURE_VERSION = 'HMAC_SHA256_V1';

    protected $options = array(
        'merchant_code' => null,
        'terminal' => null,
        'secret_key' => null,
        'sandbox' => true,
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
        'TRL' => '949',
    );

    private $payment_vars = array();

    public function setParameter($key, $value)
    {
        $this->payment_vars[$key] = $value;
    }


    public function __construct(array $options)
    {
        $this->options = array_replace($this->options, $options);

        if (true == empty($this->options['merchant_code'])) {
            throw new InvalidArgumentException('The merchant_code option must be set.');
        }
        if (true == empty($this->options['terminal'])) {
            throw new InvalidArgumentException('The terminal option must be set.');
        }
        if (true == empty($this->options['secret_key'])) {
            throw new InvalidArgumentException('The secret_key option must be set.');
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
            'https://sis-t.redsys.es:25443/sis/realizarPago' :
            'https://sis.redsys.es/sis/realizarPago';
    }

    /**
     * @param $currency
     *
     * @return mixed
     */
    public function getISO4127($currency)
    {
        if (!isset($this->currencies[$currency])) {
            throw new LogicException('Currency not allowed by the gateway.');
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
        if (strlen($orderNumber) > self::ORDER_NUMBER_MAXIMUM_LENGHT) {
            throw new LogicException('Payment number can\'t have more than 12 characters');
        }

        // add 0 to the left in case length of the order number is less than 4
        $orderNumber = str_pad($orderNumber, self::ORDER_NUMBER_MINIMUM_LENGTH,
            '0', STR_PAD_LEFT);

        if (!preg_match('/^[0-9]{4}[a-z0-9]{0,12}$/i', $orderNumber)) {
            throw new LogicException('The payment gateway doesn\'t allow order numbers with this format.');
        }

        return $orderNumber;
    }

    /**
     * 3DES Function provided by Redsys
     *
     * @param string $merchantOrder
     * @param string $key
     *
     * @return string
     */
    private function encrypt_3DES($merchantOrder, $key)
    {
        // default IV
        $bytes = array(0, 0, 0, 0, 0, 0, 0, 0);
        $iv = implode(array_map("chr", $bytes));

        // sign
        $ciphertext = mcrypt_encrypt(MCRYPT_3DES, $key, $merchantOrder,
            MCRYPT_MODE_CBC, $iv);

        return $ciphertext;
    }

    /**
     * base64_url_encode function provided by Redsys
     *
     * @param $input
     * @return string
     */
    function base64_url_encode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    /**
     * Decode function provided by Redsys
     *
     * @param $input
     * @return string
     */
    private function base64_url_decode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Mac256 function provided by Redsys
     *
     * @param $ent
     * @param $key
     * @return string
     */
    private function mac256($ent, $key)
    {
        $res = hash_hmac('sha256', $ent, $key, true);

        return $res;
    }

    /**
     * encodeBase64 function provided by Redsys
     *
     * @param $data
     * @return string
     */
    private function encodeBase64($data)
    {
        $data = base64_encode($data);

        return $data;
    }

    /**
     * decodeBase64 function provided by Redsys
     *
     * @param $data
     * @return string
     */
    private function decodeBase64($data)
    {
        $data = base64_decode($data);

        return $data;
    }

    /**
     * builds signature from the data sent by Redsys in the reply
     *
     * @param $key
     * @param $data
     * @return string
     */
    function createMerchantSignatureNotif($key, $data)
    {
        $key = $this->decodeBase64($key);
        $decodec = $this->base64_url_decode($data);
        $orderData = json_decode($decodec, true);
        $key = $this->encrypt_3DES($orderData['Ds_Order'], $key);
        $res = $this->mac256($data, $key);

        return $this->base64_url_encode($res);
    }

    /**
     * Validates notification sent by Redsys when it receives the payment
     *
     * @param array $notification
     *
     * @return bool
     */
    public function validateNotificationSignature(array $notification)
    {
        $notification = ArrayObject::ensureArrayObject($notification);
        $notification->validateNotEmpty('Ds_Signature');
        $notification->validateNotEmpty('Ds_MerchantParameters');
        $data = $notification["Ds_MerchantParameters"];

        $key = $this->options['secret_key'];
        $signedResponse = $this->createMerchantSignatureNotif($key, $data);

        return $signedResponse == $notification['Ds_Signature'];
    }

    /**
     * Builds Merchant Parameters encoded string.
     * Bank will take care of decode this
     *
     * @param array $params
     * @return string
     */
    function createMerchantParameters(array $params)
    {
        $json = json_encode($params);

        return $this->encodeBase64($json);
    }

    /**
     * Sing request sent to Gateway
     *
     * @param array $params
     *
     * @return string
     */
    public function sign(array $params)
    {
        $base64DecodedKey = base64_decode($this->options['secret_key']);
        $key = $this->encrypt_3DES($params['Ds_Merchant_Order'],
            $base64DecodedKey);

        $res = $this->mac256(
            $this->createMerchantParameters($params),
            $key
        );

        return base64_encode($res);
    }
}
