<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com> 2020
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com> 2020
 */

namespace Omnipay\BTCPayServer;

use Omnipay\Common\AbstractGateway;
use Omnipay\BTCPayServer\Message\PurchaseStatusRequest;
use Omnipay\BTCPayServer\Message\PurchaseRequest;

/**
 * BtcPayServer Gateway
 *
 */
class Gateway extends AbstractGateway
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'BTCPayServer';
    }

    /**
     *
     * @return array
     */
    public function getDefaultParameters(): array
    {
        return [
            'apiKey' => '',
            'testMode' => false,
        ];
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->getParameter('apiKey');
    }

    /**
     * @param  string $value
     * @return AbstractGateway
     */
    public function setApiKey(string $value)
    {
        return $this->setParameter('apiKey', $value);
    }

    /**
     * @param array $parameters
     * @return TODO
     */
    public function purchase($parameters = [])
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * @param array $parameters
     * @return TODO
     */
    public function getPurchaseStatus(array $parameters = [])
    {
        return $this->createRequest(PurchaseStatusRequest::class, $parameters);
    }
}

