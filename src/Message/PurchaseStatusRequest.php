<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\Omnipay\BTCPayServer\Message;

/**
 * BTCPayServer Purchase Status Request
 */
class PurchaseStatusRequest extends PurchaseRequest
{
    /**
     * @return array
     */
    public function getData(): array
    {
        $this->validate('transactionReference');
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return parent::getEndpoint() . '/' . $this->getTransactionReference();
    }

    /**
     * @return string
     */
    protected function getHttpMethod(): string
    {
        return 'GET';
    }

    /**
     * @param  PurchaseStatusResponse $reponse
     * @param  array                  $data
     * @return PurchaseStatusResponse
     */
    protected function createResponse($response, $data): PurchaseStatusResponse
    {
        return $this->response = new PurchaseStatusResponse($response, $data);
    }

}

