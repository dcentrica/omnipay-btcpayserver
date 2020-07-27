<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\Omnipay\BTCPayServer\Message;

/**
 * BitPay Purchase Request
 * 
 * TODO: 
 * TODO: Populate with contents of: https://github.com/btcpayserver/php-bitpay-client/blob/master/examples/tutorial/003_createInvoice.php
 * And: https://github.com/btcpayserver/php-bitpay-client/blob/master/examples/CreateInvoice.php
 * 
 * @see:
 * - \Omnipay\Common\Message\AbstractRequest::getData()
 */
class PurchaseRequest extends AbstractRequest
{

    /**
     * @return array
     */
    public function getData(): array
    {
        $this->validate('amount', 'currency');

        // The following data come from Omnipay itself and assume there's a cart
        // and related functionality from which the following can be determined.
        return [
            'price' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'posData' => $this->getTransactionId(),
            'itemDesc' => $this->getDescription(),
            'notificationURL' => $this->getNotifyUrl(),
            'redirectURL' => $this->getReturnUrl(),
        ];
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return parent::getEndpoint() . '/invoice';
    }

}
