# Login and Pay with Amazon

Didn't like the official amazon package so i made this one. Its much lighter and easier to setup.

*This is my first public package, don't beat me up :)*

## Installation
Install through Composer.
```
"require": {
    "tuurbo/amazon-payment": "~1.0"
}
```

### Laravel 4 Setup
Next, update app/config/app.php to include a reference to this package's service provider in the providers array and the facade in the aliases array.

```
'providers' => [
   'Tuurbo\AmazonPayment\AmazonPaymentServiceProvider'
]
```

```
'aliases' => [
    'AmazonPayment' => 'Tuurbo\AmazonPayment\AmazonPaymentFacade'
]
```

create an app/config/amazon.php config file.
```
return [
    'sandbox_mode' => true,
    'store_name' => 'ACME Inc',
    'statement_name' => 'AcmeInc 555-555-5555',
    'client_id' => '',
    'seller_id' => '',
    'access_key' => '',
    'secret_key' => '',
];
```

### Native setup
```
$config = [
    'sandbox_mode' => true,
    'client_id' => '',
    'seller_id' => '',
    'access_key' => '',
    'secret_key' => '',
    'store_name' => 'ACME Inc',
    'statement_name' => 'AcmeInc 555-555-5555'
];

try {

    $amazonPayment = new Tuurbo\AmazonPayment\AmazonPayment($config);

    $response = $amazonPayment->setOrderDetails(...);

} catch (\Exception $e) {

    // catch errors

}
```


## Example Usage:
User is sent to amazon and redirected back with the access token.
```
<script type="text/javascript">
    var amazonClientId = '';
    var amazonSellerId = '';

    window.onAmazonLoginReady = function(){
        amazon.Login.setClientId(amazonClientId);
    };
</script>
<script type="text/javascript" src="<?php echo AmazonPayment::script() ?>"></script>
<script type='text/javascript'>
    var authRequest;
    OffAmazonPayments.Button("AmazonPayButton", amazonSellerId, {
        type: "LwA",
        authorization: function() {
            loginOptions = { scope: "profile payments:widget payments:shipping_address" };
            authRequest = amazon.Login.authorize(loginOptions);
        },
        onSignIn: function(orderReference) {
            <!-- redirect page -->
            authRequest.onComplete('/login?amazon_id=' + orderReference.getAmazonOrderReferenceId());
        },
        onError: function(error) {
            alert(error.getErrorCode() + ": " + error.getErrorMessage());
        }
    });
</script>

<div id="AmazonPayButton"></div>
```

This is the page amazon redirected the user back to.
```
// get access token
$accessToken = $_GET['access_token'];

// login user
AmazonPayment::login($accessToken);

// get user details
$details = AmazonPayment::getLoginDetails($accessToken);

// save details for later
$_SESSION['amazon_id'] = $accessToken;
$_SESSION['amazon_details'] = $details;
```

Create the users order after they submit it.
```
try {

	$amazonReferenceId = $_SESSION['amazon_id'];

	// create customers order as usual
	...

	// set amazon order details
	AmazonPayment::setOrderDetails([
	    'referenceId' => $amazonReferenceId,
	    'amount' => 109.99,
	    'orderId' => 12345,
	    'note' => 'delivery hours 2pm-5pm'
	]);

	// comfirm the amazon order
	AmazonPayment::confirmOrder([
	    'referenceId' => $amazonReferenceId,
	]);

	// get amazon order details.
	// save the response to your customers order
	$amazon = AmazonPayment::getOrderDetails([
	    'referenceId' => $amazonReferenceId,
	]);

} catch (\Exception $e) {

	// log error.
	// tell customer something went wrong.

}
```

## Available Methods

```
AmazonPayment::setOrderDetails()
AmazonPayment::getOrderDetails()
AmazonPayment::confirmOrder()
AmazonPayment::authorize()
AmazonPayment::authorizeAndCapture()
AmazonPayment::capture()
AmazonPayment::closeOrder()
AmazonPayment::cancelOrder()
AmazonPayment::refund()
AmazonPayment::login()
AmazonPayment::getLoginDetails()
AmazonPayment::script()
```

Amazon docs: http://docs.developer.amazonservices.com/en_US/apa_guide/APAGuide_Introduction.html
