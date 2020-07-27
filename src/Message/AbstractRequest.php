<?php

/**
 * @package dcentrica/omnipay-btcpayserverpay
 * @author  Russell Michell <hello@dcentrica.com>
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 */

namespace Dcentrica\Omnipay\BTCPayServer\Message;

use \Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;
use \Bitpay\Storage\EncryptedFilesystemStorage;
use \Bitpay\Client\Client as BTCPayClient;
use \Bitpay\Client\Adapter\CurlAdapter;
use \Bitpay\Token;
use \Bitpay\Invoice;
use \Bitpay\Buyer;
use \Bitpay\Item;
use \Bitpay\Currency;

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
     * @return void
     */
    public function __construct()
    {
        // e.g. https://bitpay.com/api'
        $this->liveEndpoint = getenv('BTCPAYSERVER_ENDPOINT_LIVE');
        // e.g. https://test.bitpay.com/api
        $this->testEndpoint = getenv('BTCPAYSERVER_ENDPOINT_TEST');
        $this->pairingToken = getenv('BTCPAYSERVER_PAIRING_TOKEN');
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
     *       3. All the stuff from 1. should prob be munged into `$data` and sent along with it 
     * 
     *       See:
     * 
     *       - https://github.com/thephpleague/omnipay-common/search?q=sendData&unscoped_q=sendData
     *       - https://github.com/thephpleague/omnipay-braintree/search?q=setData&unscoped_q=sendData
     */
    public function sendData($data): AbstractResponse
    {
        $response = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            [
                'Authorization' => sprintf('Basic %s:', base64_encode($this->getApiKey()))
            ],
            $data
        );

        return $this->response = $this->createResponse(
            $response,
            json_decode($response->getBody()->getContents(),
            true
        ));
    }

    /**
     * @todo Finish this off / refactor as necessary
     * 
     * @return array
     */
    public function createInvoice(): array
    {
        // See 002.php for explanation
        // Password may need to be updated if you changed it
        $storageEngine = new EncryptedFilesystemStorage('YourTopSecretPassword');
        $privateKey = $storageEngine->load('/tmp/bitpay.pri');
        $publicKey = $storageEngine->load('/tmp/bitpay.pub');
        $client        = new Client();
        $adapter       = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setPrivateKey($privateKey);
        $client->setPublicKey($publicKey);
        $client->setUri('https://btcpay.server/');
        $client->setAdapter($adapter);
        // ---------------------------

        /**
         * The last object that must be injected is the token object.
         */
        $token = new \Bitpay\Token();
        $token->setToken('UpdateThisValue'); // UPDATE THIS VALUE

        /**
         * Token object is injected into the client
         */
        $client->setToken($token);

        /**
         * This is where we will start to create an Invoice object, make sure to check
         * the InvoiceInterface for methods that you can use.
         */
        $invoice = new \Bitpay\Invoice();

        $buyer = new \Bitpay\Buyer();
        $buyer
            ->setEmail('buyeremail@test.com');

        // Add the buyers info to invoice
        $invoice->setBuyer($buyer);

        /**
         * Item is used to keep track of a few things
         */
        $item = new \Bitpay\Item();
        $item
            ->setCode('skuNumber')
            ->setDescription('General Description of Item')
            ->setPrice('1.99');
        $invoice->setItem($item);

        /**
         * BitPay supports multiple different currencies. Most shopping cart applications
         * and applications in general have defined set of currencies that can be used.
         * Setting this to one of the supported currencies will create an invoice using
         * the exchange rate for that currency.
         *
         * @see https://test.bitpay.com/bitcoin-exchange-rates for supported currencies
         */
        $invoice->setCurrency(new \Bitpay\Currency('USD'));

        // Configure the rest of the invoice
        $invoice
            ->setOrderId('OrderIdFromYourSystem')
            // You will receive IPN's at this URL, should be HTTPS for security purposes!
            ->setNotificationUrl('https://store.example.com/bitpay/callback');


        /**
         * Updates invoice with new information such as the invoice id and the URL where
         * a customer can view the invoice.
         */
        try {
            echo "Creating invoice at BitPay now.".PHP_EOL;
            $client->createInvoice($invoice);
        } catch (\Exception $e) {
            echo "Exception occured: " . $e->getMessage().PHP_EOL;
            $request  = $client->getRequest();
            $response = $client->getResponse();
            echo (string) $request.PHP_EOL.PHP_EOL.PHP_EOL;
            echo (string) $response.PHP_EOL.PHP_EOL;
            exit(1); // We do not want to continue if something went wrong
        }

        echo 'Invoice "'.$invoice->getId().'" created, see '.$invoice->getUrl().PHP_EOL;
        echo "Verbose details.".PHP_EOL;
        print_r($invoice);
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
