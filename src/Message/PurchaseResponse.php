<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com> 2020
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com> 2020
 */

namespace Omnipay\BTCPayServer\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * BTCPayServer Purchase Response
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * @return boolean
     */
    public function isSuccessful(): bool
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function isRedirect(): bool
    {
        return !isset($this->data['error']);
    }

    /**
     * @return mixed string|void
     */
    public function getMessage()
    {
        if (isset($this->data['error'])) {
            return $this->data['error']['type'] . ': ' . $this->data['error']['message'];
        }
    }

    /**
     * @return mixed string|void
     */
    public function getTransactionReference()
    {
        if (isset($this->data['id'])) {
            return $this->data['id'];
        }
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        if (isset($this->data['url'])) {
            return $this->data['url'];
        }
    }

    /**
     * @return string
     */
    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    /**
     * @return void
     */
    public function getRedirectData(): void
    {
    }

}
