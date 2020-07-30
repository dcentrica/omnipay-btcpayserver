<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\BTCPayServer\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * BitPay Purchase Response
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
     * @return string
     */
    public function getMessage(): string
    {
        if (isset($this->data['error'])) {
            return $this->data['error']['type'] . ': ' . $this->data['error']['message'];
        }
    }

    /**
     * @return string
     */
    public function getTransactionReference(): string
    {
        if (isset($this->data['id'])) {
            return $this->data['id'];
        }
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        if (isset($this->data['url'])) {
            return $this->data['url'];
        }

        return ''
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
