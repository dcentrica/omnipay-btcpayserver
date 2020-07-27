<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\Omnipay\BTCPayServer\Message;

use \Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;

abstract class AbstractRequest extends CommonAbstractRequest
{
    /**
     * @var string
     */
    protected $liveEndpoint = '';

    /**
     * @var string
     */
    protected $testEndpoint = '';

    /**
     * @return void
     */
    public function __construct()
    {
        $this->liveEndpoint = getenv('BTCPAYSERVER_ENDPOINT_LIVE');
        $this->testEndpoint = getenv('BTCPAYSERVER_ENDPOINT_TEST');
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->getParameter('apiKey');
    }

    /**
     * @param  string
     * @return AbstractRequest
     */
    public function setApiKey(string $value)
    {
        return $this->setParameter('apiKey', $value);
    }

    /**
     * @return string
     */
    protected function getHttpMethod(): string
    {
        return 'POST';
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->getTestMode() ?
            $this->testEndpoint :
            $this->liveEndpoint;
    }

    /**
     * @param  array $data
     * @return AbstractResponse
     * @todo This is the method that needs to contain all BitPay/BTCPayServer-specific stuff.
     *       Look at: https://github.com/thephpleague/omnipay-common/search?q=sendData&unscoped_q=sendData
     *       for an idea of how it's used.
     * @todo Wrap this call in a BTCPayServer-specific class
     */
    public function sendData($data): AbstractResponse
    {
        $response = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            array('Authorization' => sprintf('Basic %s:', base64_encode($this->getApiKey()))),
            $data
        );

        return $this->response = $this->createResponse(
            $response,
            json_decode($response->getBody()->getContents(),
            true
        ));
    }

    /**
     * @todo Finish this off / refactor as necessary
     * 
     * @return array
     */
    public function generateInvoice(): array
    {
        return [];
    }

    /**
     * Internal response object factory.
     * 
     * @param  AbstractResponse $response
     * @param  array            $data
     * @return PurchaseResponse
     */
    protected function createResponse($response, array $data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($response, $data);
    }

}
