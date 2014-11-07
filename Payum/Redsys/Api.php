<?php
/**
 * Created by PhpStorm.
 * User: carlos
 * Date: 1/11/14
 * Time: 13:55
 */

namespace Payum\Redsys;

use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Payum\Core\Exception\InvalidArgumentException;
use Buzz\Message\Form\FormRequest;

class Api
{
    protected $options = array(
        'merchant_code' => null,
        'terminal' => null,
        'secret_key' => null,
        'url' => null
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
        $this->client = $client ?: new Curl;

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
        if (true == empty($this->options['url'])) {
            throw new InvalidArgumentException('The url option must be set.');
        }
    }

    public function getOnsiteUrl()
    {
        return $this->options['url'];
    }

    public function preparePayment( array $params )
    {
        $params['Ds_Merchant_MerchantCode'] = $this->options['merchant_code'];
        $params['Ds_Merchant_Terminal'] = 1;
        $params['Ds_Merchant_MerchantSignature'] = $this->signature( $params );
        $params['Ds_Merchant_SumTotal'] = $params['Ds_Merchant_Amount'];
        return $params;
    }

    public function signature( $params )
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
    
    public function buildOrderDetails( $order, $token )
    {
        $details = $order->getDetails();

        $details['Ds_Merchant_Amount'] = $order->getTotalAmount();
      
        $details['Ds_Merchant_Currency'] = $this->currencies[$order->getCurrencyCode()];

        $details['Ds_Merchant_Order'] = $this->formatOrderNumber( $order->getNumber() );

        // following values can be addded to the details 
        // order when building it. If they are not passed, values
        // will be taken from the default options if present
        // in case of Ds_Merchant_TransactionType, as its mandatory
        // 0 will be asigned in case value is not present in the 
        // order details or in the options. 
        if ( !isset( $details['Ds_Merchant_TransactionType'] ) )
        {
            $details['Ds_Merchant_TransactionType'] = isset( $this->options['default_transaction_type'] )
              ? $this->options['default_transaction_type'] : 0 ;
        }
        
        // set customer language to spanish in case not provided
        if ( !isset( $details['Ds_Merchant_ConsumerLanguage'] ) )
        {
          $details['Ds_Merchant_ConsumerLanguage'] = '001';
        }
        
        // these following to are not mandatory. only filled if present in the 
        // order details or in the options
        if ( !isset( $details['Ds_Merchant_MerchantName'] ) && isset( $this->options['merchant_name'] ) )
        {
           $details['Ds_Merchant_MerchantName'] = $this->options['merchant_name'];
        }
        if ( !isset( $details['Ds_Merchant_ProductDescription'] ) && isset( $this->options['product_description'] ) )
        {
           $details['Ds_Merchant_ProductDescription'] = $this->options['product_description'];
        }
        
        // notification url where the bank will post the response        
        $details['Ds_Merchant_MerchantURL'] = $token->getTargetUrl();
        
        // return url in case of payment done
	      $details['Ds_Merchant_UrlOK'] = $token->getAfterUrl();
	      
	      // return url in case of payment cancel. same as above
        $details['Ds_Merchant_UrlKO'] = $token->getAfterUrl();
        $details['Ds_Merchant_MerchantSignature'] = $this->signature( $details );
        
        return $details;
        
    }
    
    public function validateGatewayResponse( $response )
    {
         $msgToSign = $response['Ds_Amount']
            . $response['Ds_Order']
            . $this->options['merchant_code']
            . $response['Ds_Currency']
            . $response['Ds_Response']
            . $this->options['secret_key'];
         
         return strtoupper(sha1($msgToSign)) == $response['Ds_Signature'];
    }
    
    private function formatOrderNumber( $orderNumber )
    {
        //Falta comprobar que empieza por 4 numericos y que como mucho tiene 12 de longitud
        $length = strlen($orderNumber);
        $minLength = 4;
        if ($length < $minLength) {
            $orderNumber = str_pad($orderNumber, $minLength, '0', STR_PAD_LEFT);
        }
        return $orderNumber;
    }
}
