<?php
namespace Crevillo\Payum\Redsys;

use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Model\OrderInterface;
use Payum\Core\Security\TokenInterface;

class Api
{
    const TRANSACTIONTYPE_DEFAULT = 0;

    const CONSUMERLANGUAGE_SPANISH = '001';

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
        $this->client = $client ?: new Curl();

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
     * Returns the url where we need to send the post request
     *
     * @return string
     */
    public function getRedsysUrl()
    {
        return $this->options['sandbox']
            ? 'https://sis-t.sermepa.es:25443/sis/realizarPago'
            : 'https://sis.sermepa.es/sis/realizarPago';
    }

    /**
     * Get currency code as needed for the bank
     *
     * @param $currency
     *
     * @return mixed
     */
    public function getISO4127( $currency )
    {
        return $this->currencies[$currency];
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
        // add 0 to the left in case length of the order number is less than 4
        $orderNumber = str_pad( $orderNumber, 4, '0', STR_PAD_LEFT );

        $firstPartOfTheOrderNumber = substr( $orderNumber, 0, 4 );
        $secondPartOfTheOrderNumber = substr( $orderNumber, 4, strlen( $orderNumber ) );

        if (!ctype_digit( $firstPartOfTheOrderNumber ) ||
            !ctype_alnum( $secondPartOfTheOrderNumber )
        ) {
            throw new InvalidArgumentException( 'The order number is not correct.' );
        }

        return $orderNumber;
    }

    /**
     * Calculate the signature depending on some other values
     * sent in the payment.
     *
     * @param array $params
     *
     * @return string
     */
    public function buildSignature($params)
    {
        $msgToSign = $params['Ds_Merchant_Amount']
            . $params['Ds_Merchant_Order']
            . $this->options['merchant_code']
            . $params['Ds_Merchant_Currency']
            . $params['Ds_Merchant_TransactionType']
            . $params['Ds_Merchant_MerchantURL']
            . $this->options['secret_key'];

        return strtoupper(sha1($msgToSign));
    }

    /**
     * Validate the response to be sure the bank is sending it
     *
     * @param array $response
     *
     * @return bool
     */
    public function validateGatewayResponse($response)
    {
        $msgToSign = $response['Ds_Amount']
            . $response['Ds_Order']
            . $this->options['merchant_code']
            . $response['Ds_Currency']
            . $response['Ds_Response']
            . $this->options['secret_key'];

        return strtoupper(sha1($msgToSign)) == $response['Ds_Signature'];
    }

    /**
     * Getter for merchant_code option
     *
     * @return string
     */
    public function getMerchantCode()
    {
        return $this->options['merchant_code'];
    }

    /**
     * Getter for terminal code
     *
     * @return string
     */
    public function getMerchantTerminalCode()
    {
        return $this->options['terminal'];
    }

    /**
     * Getter for Transaction Type.
     *
     * If not set in the options if will return default value (0)
     *
     * @return int
     */
    public function getTransactionType()
    {
        return isset($this->options['default_transaction_type'])
            ? $this->options['default_transaction_type'] : self::TRANSACTIONTYPE_DEFAULT;
    }

    /**
     * Returns merchant name if provided in options
     * or empty if not provided
     *
     * @return string
     */
    public function getMerchantName()
    {
        return !empty( $this->options['merchant_name'] )
            ? $this->options['merchant_name'] : '';
    }

    /**
     * Returns merchant product description if provided in options
     * or empty if not provided
     *
     * @return string
     */
    public function getMerchantProductDescription()
    {
        return !empty( $this->options['product_description'] )
            ? $this->options['product_description'] : '';
    }

    /**
     * Getter for default language code
     *
     * @return string
     */
    public function getDefaultLanguageCode()
    {
        return self::CONSUMERLANGUAGE_SPANISH;
    }
}
