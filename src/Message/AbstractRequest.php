<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com> 2020
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com> 2020
 */

namespace Omnipay\BTCPayServer\Message;

use Symfony\Component\Dotenv\Dotenv;
use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;
use Omnipay\Common\Message\AbstractResponse as CommonAbstractResponse;
use BTCPayServer\Storage\EncryptedFilesystemStorage;
use BTCPayServer\Client\Client;
use BTCPayServer\Client\Adapter\CurlAdapter;
use BTCPayServer\Token;
use BTCPayServer\Invoice;
use BTCPayServer\Buyer;
use BTCPayServer\Item;
use BTCPayServer\Currency;

/**
 * TODO: Much of the gunt work is done in this, as _abstract_ class.
 * Think about moving the `sendData()` logic into concrete class like `PurchaseRequest`.
 */
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
        // e.g. https://my-btcpayserver.org/api' or https://test.my-btcpayserver.org/api'
        return $this->getTestMode() ?
            self::getEnv('BTCPAYSERVER_ENDPOINT_TEST') :
            self::getEnv('BTCPAYSERVER_ENDPOINT_LIVE');
    }

    /**
     * @param  array $data
     * @return AbstractResponse
     */
    public function sendData($data): CommonAbstractResponse
    {
        // TODO complete this with help from: docs/invoices/index.md
        $storageEngine = new EncryptedFilesystemStorage(self::getEnv('BTCPAYSERVER_CONF_ENC_PASS'));
        $privateKey = $storageEngine->load(self::getEnv('BTCPAYSERVER_PRIKEY_LOC'));
        $publicKey = $storageEngine->load(self::getEnv('BTCPAYSERVER_PUBKEY_LOC'));
        $token = (new Token())->setToken(self::getEnv('BTCPAYSERVER_INVOICE_TOKEN'));

        $client = new Client();
        $client->setUri(self::getEnv('BTCPAYSERVER_HOST'));
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setAdapter(new CurlAdapter());
        $client->setToken($token);

        // TEMP until we figure out how to get omnipay to properly populate $data
        $data['sku'] = $data['sku'] ?? 'DUMMY';
        $data['itemDesc'] = $data['itemDesc'] ?? 'DUMMY';
        $data['price'] = $data['price'] ?? '9.99';
        $data['buyer']['email'] = $data['buyer']['email'] ?? 'DUMMY@DUMMY.com';
        $data['url'] = $data['url'] ?? self::getEnv('BTCPAYSERVER_STORE_REDIRECT');
        $data['order_id'] = $data['order_id'] ?? 'DUMMY';

        // `Item` is used to keep track of a few things
        $item = (new Item())
            ->setCode($data['sku'])
            ->setDescription($data['itemDesc'])
            ->setPrice($data['price']);
        $buyer = (new Buyer())
            ->setEmail($data['buyer']['email'] );

        // Add the buyer's info to the invoice
        $invoice = (new Invoice())
            ->setBuyer($buyer)
            ->setItem($item)
            ->setUrl('https://' . self::getEnv('BTCPAYSERVER_HOST'))
            ->setCurrency(new Currency(self::getEnv('BTCPAYSERVER_STORE_DEFAULT_CURRENCY')))
            ->setOrderId($data['order_id'])
            ->setRedirectUrl(self::getEnv('BTCPAYSERVER_STORE_REDIRECT'));

        if ($callbackUrl = self::getEnv('BTCPAYSERVER_STORE_CALLBACK')) {
            // You'll receive Instant Payment Notifications (IPN) at this URL.
            // It should be SSL secured for security purposes
            $invoice->setNotificationUrl($callbackUrl);
        }

        if ($email = self::getEnv('BTCPAYSERVER_STORE_NOTIFICATION_EMAIL')) {
            $invoice->setNotificationEmail($email);
        }

        // Makes a request to BTCPayServer to its /invoices endpoint and creates
        // a new invoice record there
        $client->createInvoice($invoice);

        // A PurchaseResponse is created & returned for Omnipay to work with
        // TODO
        // - At this point, we should redirect users to $invoice->getUrl()
        // - Make use of getStatus() and only confirm purchase when == 'CONFIRMED'
        return new PurchaseResponse($this, $data);
    }

    /**
     * Internal response object factory.
     *
     * @param  AbstractResponse $response
     * @param  mixed            $data
     * @return PurchaseResponse
     */
    protected function createResponse($response, $data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($response, $data);
    }

}
