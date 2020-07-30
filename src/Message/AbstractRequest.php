<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Omnipay\BTCPayServer\Message;

use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;
use Omnipay\Common\Message\AbstractResponse as CommonAbstractResponse;
use Bitpay\Storage\EncryptedFilesystemStorage;
use Bitpay\Client\Client as BTCPayClient;
use Bitpay\Client\Adapter\CurlAdapter;
use Bitpay\Token;
use Bitpay\Invoice;
use Bitpay\Buyer;
use Bitpay\Item;
use Bitpay\Currency;
use Omnipay\BTCPayServer\Exception\BTCPayException;

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
     * @var string
     */
    protected $pairingToken = '';

    /**
     * @var string
     */
    protected $keyPrivateLocation = '';

    /**
     * @var string
     */
    protected $keyPublicLocation = '';

    /**
     * @var string
     */
    protected $serverLocation = '';

    /**
     * @var string
     */
    protected $configEncryptionPasswd = 'TopSecretPassword'; // Ummm TODO

    /**
     * @var string
     */
    protected $configIPNCallbackURL = '';

    /**
     * @return void
     */
    public function __construct()
    {
        // e.g. https://bitpay.com/api'
        $this->liveEndpoint = getenv('BTCPAYSERVER_ENDPOINT_LIVE');
        // e.g. https://test.bitpay.com/api
        $this->testEndpoint = getenv('BTCPAYSERVER_ENDPOINT_TEST');
        $this->pairingToken = getenv('BTCPAYSERVER_PAIRING_TOKEN');
        //$this->keyPrivateLocation = getenv('BTCPAYSERVER_PRIKEY_LOC') ?? '/tmp/bitpay.pri';
        //$this->keyPublicLocation = getenv('BTCPAYSERVER_PUBKEY_LOC') ?? '/tmp/bitpay.pub';
        $this->serverLocation = getenv('BTCPAYSERVER_URL');
        //$this->configEncryptionPasswd = getenv('BTCPAYSERVER_CONF_ENC_PASS'); // TODO use DEK instead
        $this->configIPNCallbackURL = getenv('BTCPAYSERVER_CONF_IPN_CALLBACK_URL');
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
     * @todo 1. This method should contain all BitPay/BTCPayServer-specific stuff, merged into $data AFAICT
     *          Put this: https://github.com/btcpayserver/php-bitpay-client/blob/master/examples/tutorial/003_createInvoice.php
     *          ..into sendData().
     *       2. Debug what we get out of `$data` when running a shop, before it's sent to btcpay
     *       4. TODO: Add 'item' array/object to $data representing the thing that was purchased
     *       3. All the stuff from 1. should prob be munged into `$data` and sent along with it
     *       ...maybe this is better to go into `PurchaseRequest`??
     *
     *       See:
     *
     *       - https://github.com/thephpleague/omnipay-common/search?q=sendData&unscoped_q=sendData
     *       - https://github.com/thephpleague/omnipay-braintree/search?q=setData&unscoped_q=sendData
     */
    public function sendData($data): CommonAbstractResponse
    {
        // Untested, buggy as hell code for sending invoice data to BTCPayServer...
        // See 002.php for explanation
        // Password may need to be updated if you changed it
        $storageEngine = new EncryptedFilesystemStorage($this->configEncryptionPasswd);
        $privateKey = $storageEngine->load('/home/russellmichell/htdocs/catathon/bitpay.pri');
        $publicKey = $storageEngine->load('/home/russellmichell/htdocs/catathon/bitpay.pub');
        $client = new BTCPayClient();
        $adapter = new CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setAdapter($adapter);

        // The last object that must be injected is the token object.
        $token = new Token();
        $token->setToken('UpdateThisValue'); // TODO - UPDATE THIS VALUE, where's it come from!?

        // Token object is injected into the client
        $client->setToken($token);

        /**
         * Create an Invoice object. Ensure to check the `InvoiceInterface` for methods
         * able to uses.
         */
        // `Item` is used to keep track of a few things
        $item = (new Item())
            ->setCode($data['sku'] ?? 'TODO')
            ->setDescription($data['itemDesc'] ?? 'TODO')
            ->setPrice($data['price']);
        $buyer = (new Buyer())->setEmail($data['buyer']['email'] ?? 'todo@todo.com');

        // Add the buyer's info to the invoice
        $invoice = (new Invoice())->setBuyer($buyer);


        $invoice->setItem($item);

        /**
         * BTCPayServer supports multiple currencies. Most shopping cart applications
         * have a defined set of currencies that can be used.
         * Setting this to one of the supported currencies will create an invoice using
         * the exchange rate for that currency.
         *
         * @see  https://test.bitpay.com/bitcoin-exchange-rates for supported currencies
         * @todo Shopping cart will need to support XBT
         */
        $invoice
            ->setCurrency(new Currency('BTC'))
            ->setOrderId($data['order_id'] ?? 'TODO')
            // You'll receive Instant Payment Notifications (IPN) at this URL.
            // It should be SSL secured for security purposes
            ->setNotificationUrl($this->configIPNCallbackURL);

        /**
         * Updates the invoice with new information such as the invoice id and the URL where
         * a customer can view the invoice.
         */
        try {
            $invoice = $client->createInvoice($invoice);
        } catch (\Exception $e) {
            throw new BTCPayException($e->getMessage());
        }

        // TODO Wrap a new `PurchaseResponse` object with the properties of `$invoice`
        $response = (new PurchaseResponse())->setInvoice($invoice); // Pseudo-code

        // TODO possibly all this guff isn't even needed. Just the createInvoice() logic??
        /*
        $response = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            [
                'Authorization' => sprintf('Basic %s:', base64_encode($this->getApiKey()))
            ],
            $data
        );
        */

        return $this->response = $this->createResponse(
            $response,
            json_decode($response->getBody()->getContents(),
            true
        ));
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
