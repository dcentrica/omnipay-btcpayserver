<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com> 2020
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com> 2020
 */

namespace Omnipay\BTCPayServer\Message;

/**
 * BTCPayServer Purchase Status Response
 */
class PurchaseStatusResponse extends PurchaseResponse
{
    /**
     * @return boolean
     */
    public function isSuccessful(): bool
    {
        return !$this->getMessage();
    }

    /**
     * @return boolean
     */
    public function isRedirect(): bool
    {
        return false;
    }

}

