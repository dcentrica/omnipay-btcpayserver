# Omnipay: BtcPayServer

**Omnipay driver for the BTCPayServer Bitcoin payment processor**

[![Latest Stable Version](https://poser.pugx.org/dcentrica/omnipay-btcpayserver/version.png)](https://packagist.org/packages/dcentrica/onipay-btcpayserver)
[![Total Downloads](https://poser.pugx.org/dcentrica/omnipay-btcpayserverpay/d/total.png)](https://packagist.org/packages/dcentrica/omnipay-btcpayserver)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements [BTCPayServer](https://btcpayserver.org/) support for Omnipay.

## TODO

This library should just wrap around the tutorial notes from the btcpayserver lib: https://github.com/btcpayserver/php-bitpay-client/blob/master/examples/tutorial/003_createInvoice.php

* Patch key-generation and pairing scripts from btcpayserver/btcpayserver-php-client lib and ref them in this README

## Requirements

* See PHP and library dependencies in `composer.json`
* A `.env` file used to manage environment variables

## Installation

This Omnipay driver is installed via [Composer](http://getcomposer.org/). To install it, simply add it
to your `composer.json` file or just run `composer require dcentrica/omnipay-btcpayserver`

```json
{
    "require": {
        "dcentrica/omnipay-btcpayserver": "dev-master"
    }
}
```

## Configuration

This library assumes you're using a `.env` file for your application configuration. Adapt the following example to suit your environment:

```shell
BTCPAYSERVER_HOST="https://my-omnipay-aware-store.com/")
BTCPAYSERVER_STORE_DEFAULT_CURRENCY="NZD"
# A URL for returning shoppers back to your website after a successful purchase
BTCPAYSERVER_STORE_REDIRECT="https://my-omnipay-aware-store.com/store/confirmed/"
# Optional: IPN notifications are sent here if set
BTCPAYSERVER_STORE_CALLBACK="https://my-omnipay-aware-store.com/store/ipncallback/"
BTCPAYSERVER_INVOICE_TOKEN=""
# Used to encrypt public & private keys on the server's filesystem
BTCPAYSERVER_CONF_ENC_PASS="YourTopSecretPassword"
# Absolute path to the private-key, generated according to
# https://github.com/btcpayserver/btcpayserver-php-client/blob/master/examples/tutorial/001_generateKeys.php
BTCPAYSERVER_PRIKEY_LOC=""
# Absolute path to the public-key, generated according to
# https://github.com/btcpayserver/btcpayserver-php-client/blob/master/examples/tutorial/001_generateKeys.php
BTCPAYSERVER_PUBKEY_LOC=""
# Test BTCPayServer endpoint 
BTCPAYSERVER_ENDPOINT_TEST=""
# Live BTCPayServer endpoint 
BTCPAYSERVER_ENDPOINT_LIVE=""
# Optionally receive emails at this address when purchases are made
BTCPAYSERVER_STORE_NOTIFICATION_EMAIL="notifications@store.com"
```

If you want to dispplay payment QR codes in a modal within your web store, a little JavaScript is required, an example is below:

```javascript
<script src="//my.btcpayserver.instance.com/modal/btcpay.js"></script>
<script src="/path/to/my/jsquery.min.js"></script>
<script>
    // As an example, `invoiceId` could be configured to come through as a GET param
    // The jQuery selector should identify the checkout button of your store
    $('#my-checkout-button').on('click', function(e) { window.btcpay.showInvoice(invoiceId); });
</script>
```

## Basic Usage

The following gateways are provided by this package:

* BTCPayServer

For general Omnipay usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository. For BTCPayServer usage instructions, please see the main [BTCPayServer](https://github.com/btcpayserver/php-bitpay-client) repository and the [comprehensive docs](http://docs.btcpayserver.org/).

## Support

### Omnipay

If you are having general issues with Omnipay, we suggest posting on [Stack Overflow](http://stackoverflow.com/). Be sure to add the [omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project, or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug with Omnipay itself, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-bitpay/issues),
or better yet, fork the library and submit a pull request.

### Omnipay BTCPayServer Driver

If you believe you have found a bug with _this driver_, please report it using our [GitHub issue tracker](https://github.com/dcentrica/omnipay-btcpayserver/issues).

### BTCPayServer

For help with BTCPayServer:

* [visit the website](https://docs.btcpayserver.org/)
* Sign-up to the [Mattermost channels](https://chat.btcpayserver.org/btcpayserver/).

