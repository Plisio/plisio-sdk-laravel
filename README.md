# Plisio SDK for Laravel framework
## Laravel compatibility

| Laravel                      | plisio-sdk-laravel |
|:-----------------------------|:-------------------|
| 5.6.* - 8.* (PHP 7 required) | 1.0.0              |

## Installation

Use the package manager [composer](https://getcomposer.org/) to install Plisio SDK package.

```bash
composer require plisio/plisio-sdk-laravel
```
Publish config file.

```bash
php artisan vendor:publish --provider="Plisio\PlisioSdkLaravel\Providers\PlisioProvider"
```
Edit the config file located in `app/config/plisio.php` and enter your Plisio Api Key
# Usage
### Initialize Plisio SDK
To use Plisio sdk functionality you need to initialize it. The following examples will take into account that the initialization of the sdk has already taken place.
```php
use Plisio\PlisioSdkLaravel\Payment;

//Initializing payment gateway with api_key in app/config .
$plisioGateway = new Payment(config('plisio.api_key'));
```
### Get shop information
Get information about shop which api key is specified in the config file.
```php
$shopInfo = $plisioGateway->getShopInfo();
```
### Get balance information
Get balance of the specified wallet.
```php
$btcWalletBalance = $plisioGateway->getBalances('BTC');
```
### Get enabled cryptocurrencies
Get information about the cryptocurrencies that are enabled in the store. Specify $source_currency to get fiat/crypto rate otherwise USD/crypto will be shown.
```php
$currencies = $plisioGateway->getCurrencies('CAD');
```
### Create invoice and handle Plisio response
```php
//Data about the client and his order, which must be inserted into the invoice.
$params = [...];

$data = array(
    'order_name' => 'Order #' . $params['invoiceid'], //Merchant internal order name.
    'order_number' => $params['invoiceid'],           //Merchant internal order number. Must be a unique number in your store for each new store`s order.
    'description' => $params['order_description'],    //Optional order description.
    'source_amount' => number_format($params['amount'], 8, '.', ''),  //Invoice total float value in fiat currency.
    'source_currency' => $params['currency'],         //Fiat currency code. For example: USD, BRL, CAD etc.
    'cancel_url' => 'https://examplestore.com/failedOrder.php?id=' . $params['invoiceid'],  //User will be redirected to this link in a case of invoice payment failure.
    'callback_url' => 'https://examplestore.com/callback.php',       //The link to which you will receive a notification about a change in the status of the order.
    'success_url' => 'https://examplestore.com/successOrder.php?id=' . $params['invoiceid'],  //User will be redirected to this link in a case of invoice payment success.
    'email' => $params['clientdetails']['email'],     //User's email. If not specified user will be asked to enter his email on the invoice page.
    'plugin' => 'laravelSdk',                         //Payment gateway origin. This value will help Plisio to analyse any problem occurred with SDK functionality.
    'version' => '1.0.0'                              //Consider updating this setting every time you update the functionality related to this sdk.
    );
    
    //Create invoice and put response to the $response variable.
    $response = $plisioGateway->createTransaction($data);
    
    //Check the response and, depending on the result, redirect the user to Plisio for further payment or return to the checkout page with an error.
    if ($response && $response['status'] !== 'error' && !empty($response['data'])) {
        redirect($response['data']['invoice_url']);
        clearCart();
    } else {
        $errorMessage = implode(',', json_decode($response['data']['message'], true));
        redirectToCheckout();
    }
```
### Create white label invoice
In this case you should check if white label is enabled in the store:
```php
$shopInfo = $plisioGateway->getShopInfo();
$isWhiteLabel = $shopInfo['data']['white_label'];
```
If the white label is enabled, then when you create an invoice, you will receive full information about it, which will allow you to render the invoice on any page of your site.
### Verify callback data
When creating an invoice and changing its status, Plisio will send a callback to the address specified when creating the invoice. To verify the authenticity of a callback, use the verifyCallbackData function.
```php
//callback.php
$callbackData = $_POST;

if ($plisioGateway->verifyCallbackData($callbackData)) {
    //Change invoice status, notify user etc.
} else {
    //HTTP 403 error. Callback data is not valid!
}
```
# More about Plisio API
https://plisio.net/documentation

## License
[MIT](https://choosealicense.com/licenses/mit/)
