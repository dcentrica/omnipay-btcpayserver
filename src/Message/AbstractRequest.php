<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Omnipay\BTCPayServer\Message;

use Symfony\Component\Dotenv\Dotenv;
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
use Bitpay\Network\Testnet;
use Omnipay\BTCPayServer\Exception\BTCPayException;

abstract class AbstractRequest extends CommonAbstractRequest
{
    /**
     * Get value of an environment variable.
     *
     * @param  string $name
     * @return mixed  Value of the environment variable, or false if not set.
     * @throws Exception
     * @todo Move this into a more suitable class.
     */
    protected static function getEnv(string $name)
    {
        if (!$baseDir = $_SERVER['DOCUMENT_ROOT'] ?? null) {
            throw new \Exception(('Cannot discover webserver document root!'));
        }

        $basePath  = str_replace('public', '', $_SERVER['DOCUMENT_ROOT']);
        $envFilePath = realpath($basePath . '/.env');

        if (!file_exists($envFilePath)) {
            throw new \Exception('.env file not found!');
        }

        // Exports .env vars
        (new Dotenv())->load($envFilePath);

        switch (true) {
            case  is_array($_ENV) && array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case  is_array($_SERVER) && array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                return getenv($name);
        }
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        // See YML config
        return $this->getParameter('apiKey');
    }

    /**
     * @param  string
     * @return AbstractRequest
     */
    public function setApiKey(string $value)
    {
        // See YML config
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
        // e.g. https://my-bitpayserver.org/api' or https://test.my-bitpayserver.org/api'
        return $this->getTestMode() ?
            self::getEnv('BTCPAYSERVER_ENDPOINT_TEST') :
            self::getEnv('BTCPAYSERVER_ENDPOINT_LIVE');
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
        $storageEngine = new EncryptedFilesystemStorage(self::getEnv('BTCPAYSERVER_CONF_ENC_PASS'));
        $privateKey = $storageEngine->load(self::getEnv('BTCPAYSERVER_PRIKEY_LOC'));
        $publicKey = $storageEngine->load(self::getEnv('BTCPAYSERVER_PUBKEY_LOC'));
        $client = new BTCPayClient();
        $adapter = new CurlAdapter();
        $client->setNetwork(new Testnet());
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
        $invoice = (new Invoice())
            ->setBuyer($buyer)
            ->setItem($item);

        /**
         * BTCPayServer supports multiple currencies. Most shopping cart applications
         * have a defined set of currencies that can be used.
         * Setting this to one of the supported currencies will create an invoice using
         * the exchange rate for that currency.
         *
         * @see  https://test.bitpay.com/bitcoin-exchange-rates for supported currencies
         */
        $invoice
            ->setUrl('https://' . self::getEnv('BTCPAYSERVER_HOST'))
            ->setCurrency(new Currency('BTC'))
            ->setOrderId($data['order_id'] ?? 'TODO')
            // You'll receive Instant Payment Notifications (IPN) at this URL.
            // It should be SSL secured for security purposes
            ->setNotificationUrl(self::getEnv('BTCPAYSERVER_STORE_CALLBACK'));

        /**
         * Updates the invoice with new information such as the invoice id and the URL where
         * a customer can view the invoice.
         */
        $invoice = $client->createInvoice($invoice);

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
