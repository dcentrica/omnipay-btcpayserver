<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\BTCPayServer\Message;

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

