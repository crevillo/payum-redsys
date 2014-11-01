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
           //@todo make this configurable
        $details['Ds_Merchant_Currency'] = 978;
        $details['Ds_Merchant_Order'] = $this->formatOrderNumber( $order->getNumber() );
        //@todo make this configurable
        $details['Ds_Merchant_TransactionType'] = 0;
        //@todo make this configurable
        $details['Ds_Merchant_MerchantName'] = 'EFL';
        //@todo make this configurable
        $details['Ds_Merchant_ProductDescription'] = 'Compra en EFL';
        //@todo make this configurable
        $details['Ds_Merchant_ConsumerLanguage'] = '001';        
        $details['Ds_Merchant_MerchantURL'] = $token->getTargetUrl();
	      $details['Ds_Merchant_UrlOK'] = $token->getAfterUrl();
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
