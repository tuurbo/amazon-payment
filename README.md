# Login and Pay with Amazon

Didn't like the official amazon package so i made this one. Its much lighter and easier to setup. I'm using this in production on a ecommerce site and it works well.

## Installation
Install through Composer.
```
composer require tuurbo/amazon-payment
```

### Laravel 4 or 5 Setup
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

add to the app/config/services.php config file.
```php
<?php

return [

    ...

    'amazonpayment' => [
        'sandbox_mode' => true,
        'store_name' => 'ACME Inc',
        'statement_name' => 'AcmeInc 555-555-5555',
        'client_id' => '',
        'seller_id' => '',
        'access_key' => '',
        'secret_key' => '',
    ]
];
```

### Native Setup
```php
<?php

$config = [
    'sandbox_mode' => true,
    'client_id' => '',
    'seller_id' => '',
    'access_key' => '',
    'secret_key' => '',
    'store_name' => 'ACME Inc',
    'statement_name' => 'AcmeInc 555-555-5555'
];

$amazonPayment = new Tuurbo\AmazonPayment\AmazonPayment(
    new Tuurbo\AmazonPayment\AmazonPaymentClient(
        new GuzzleHttp\Client, $config
    ),
    $config
);

try {

    $response = $amazonPayment->setOrderDetails(...);

} catch (\Exception $e) {

    // catch errors

}
```

## Two Scenario's
Each step below represents a page on your site.
```
// User logs in.    // User already logged in, amazon auto redirects to Checkout.
1. Login ---------> Cart ----------> Checkout

                    // User isn't logged in, amazon asks them to login, then redirects to Checkout.
2.                  Cart ----------> Checkout

```

## Example Usage (Scenario 1):
User is redirected to amazon when they click the Amazon Login button, then to your sites login page.

Login View example:

```html
<script type="text/javascript">
	var amazonClientId = '...';
	var amazonSellerId = '...';
	window.onAmazonLoginReady = function(){
		amazon.Login.setClientId(amazonClientId);
	};
</script>
<script type="text/javascript" src="<?= AmazonPayment::script() ?>"></script>
<script type="text/javascript">
	var authRequest;
	OffAmazonPayments.Button("AmazonPayButton", amazonSellerId, {
		type: "LwA",
		authorization: function() {
			loginOptions = { scope: "profile payments:widget payments:shipping_address" };
			authRequest = amazon.Login.authorize(loginOptions);
		},
		onSignIn: function(orderReference) {
			<!-- redirect page ex: http://example.com/login -->
			authRequest.onComplete('/login?amazon_id=' + orderReference.getAmazonOrderReferenceId());
		},
		onError: function(error) {
			alert(error.getErrorCode() + ": " + error.getErrorMessage());
		}
	});
</script>
```

Login Controller: `GET -> http://example.com/login`
```php
<?php

if (Input::has('amazon_id') && Input::has('access_token')) {
    try {
        AmazonPayment::login(Input::get('access_token'));
        $amazon = AmazonPayment::getLoginDetails(Input::get('access_token'));

        // check if user has already logged in with Amazon
        $user = User::where('amazon_id', $amazon['user_id'])->first();

        // Update and login Amazon user
        if ($user) {
            $validator = Validator::make([
                'email' => $amazon['email'],
            ], [
                'email' => 'unique:user,email,'.$user->id,
            ]);

            $user->last_login = new Datetime;

            // update their current amazon email
            if ($validator->passes()) {
                $user->email = $amazon['email'];
            }

            $user->save();

            Auth::loginUsingId($user->id);

            return Redirect::intended('/account');
        }

        // if they dont have an amazon linked account,
        // check if there amazon email is already taken
        $user = \User::where('email', $amazon['email'])->first();

        // email address is already taken
        // this is optional if you only except amazon logins
        if ($user) {

            // redirect to a page to link their amazon account
            // to their non-amazon account that they already have on your site.
            // example...
            $credentials = Crypt::encrypt(json_encode([
                'amazon_id' => $amazon['user_id'],
                'amazon_email' => $amazon['email'],
            ]));

            // redirect and then decrypt the data and make them put in the their non-amazon password to merge their amazon account.
            return Redirect::to('/register/connect-amazon/'.$credentials);
        }

    } catch (\Exception $e) {

        Session::forget('amazon');

        return Redirect::to('/login')
            ->with('failure_message', 'There was an error trying to login with your Amazon account.');
    }

    // If no user already exists, create a new amazon user account
    $user = new \User;
    $user->amazon_id = $amazon['user_id'];
    $user->email = $amazon['email'];
    $user->name = $amazon['name'];
    $user->signup_at = 'normal amazon account';
    $user->save();

    Auth::user()->loginUsingId($user->id);

    return Redirect::intended('/account');
}

```

continue to scenario 2 for Amazon Checkout...

## Example Usage (Scenario 2):
User is redirected to amazon when they click the Amazon Pay button, then back to your sites checkout page.

Shopping Cart: `GET -> http://example.com/cart`
```html
<script type="text/javascript">
    var amazonClientId = '...';
    var amazonSellerId = '...';
	window.onAmazonLoginReady = function() {
		amazon.Login.setClientId(amazonClientId);
	};
</script>
<script type="text/javascript" src="<?= AmazonPayment::script() ?>"></script>
<script type="text/javascript">
	var authRequest;
	OffAmazonPayments.Button("AmazonPayButton", amazonSellerId, {
		type:  "PwA",
		color: "Gold",
		size:  "medium",
		useAmazonAddressBook: true,
		agreementType: 'BillingAgreement',
		authorization: function() {
			var loginOptions = {scope: 'profile payments:widget payments:shipping_address'};

            <!-- redirect page ex: http://example.com/amazon/checkout -->
			authRequest = amazon.Login.authorize(loginOptions, "/amazon/checkout");
		},
		onError: function(error) {
			alert(error.getErrorCode() + ": " + error.getMessage());
		}
	});
</script>

<!-- Amazon Pay button is displayed here -->
<div id="AmazonPayButton"></div>
```

After the user logs into Amazon, Amazon redirects them back to your site.

Amazon Checkout Controller: `GET -> http://example.com/amazon/checkout`
```php
<?php

// get access token
$accessToken = $_GET['access_token'];

try {
    // get user details, use them if needed
	$amazonUser = AmazonPayment::getLoginDetails($accessToken);

} catch (\Exception $e) {

    // Redirect back to cart page if error
	return Redirect::to('/cart')
        ->with('failure_message', 'Failed to connect to your Amazon account. Please try again.');
}

// Laravel Auth example:
// login user if their Amazon user_id is found in your users table
// Obviously for this to work, you would have created the user entry at some other point in your app, maybe the account register page or something
$user = User::where('amazon_id', $amazonUser['user_id'])->first();

// If user is found, log them in
if ($user) {
    Auth::loginUsingId($user->id);
}

return View::make(...);
```

Amazon Checkout View example:
```html
<script type="text/javascript">
	var authRequest, referenceId;
	var amazonClientId = '...';
	var amazonSellerId = '...';
</script>
<script type="text/javascript">
	window.onAmazonLoginReady = function() {
		amazon.Login.setClientId(amazonClientId);
	};
</script>
<script type="text/javascript" src="<?= AmazonPayment::script() ?>"></script>
<script type="text/javascript">

	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: amazonSellerId,
		displayMode: 'Edit',
		design: { size: { width: 600, height: 250 } },
		onOrderReferenceCreate: function(orderReference) {
		    referenceId = orderReference.getAmazonOrderReferenceId();
		    // add the referenceId to a hidden input to be posted when the user submits their order
		    $('#reference_id').val(referenceId);
		},
		onAddressSelect: function(){

			// disable "submit order" button until payment has been loaded or added via widget
			$('#submit-order').prop('disable', true);

			// calculate shipping, by passing `referenceId` via ajax to your server
			// and using the `getOrderDetails()` call below to calculate shipping, taxes, etc...

			// AmazonPayment::getOrderDetails([
			//     'referenceId' => $_POST['referenceId']
			// ]);

			$.ajax({
				url: '/checkout/calculate_shipping',
				type: 'post',
				data: { referenceId: referenceId }
			})
			.success(function(response){
				// do something with the response...
				// like display shipping or taxes to the customer
			});

			// init payment widget
			new OffAmazonPayments.Widgets.Wallet({
				sellerId: amazonSellerId,
				amazonOrderReferenceId: referenceId,
				displayMode: 'Edit',
				design: { size: { width: 600, height: 250 } },
				onPaymentSelect: function(orderReference){
					// enable "submit order" button
					$('#submit-order').prop('disable', false);
				}
			}).bind("AmazonWalletWidget");

		},
		onError: function(error) {
			// window.location = '/amazon/checkout?session_expired=true';
			// alert(error.getErrorCode() + ": " + error.getMessage());
		}
	}).bind("AmazonAddressWidget");

</script>

<!-- Address widget will be displayed here -->
<div id="AmazonAddressWidget"></div>

<!-- Payment widget will be displayed here -->
<div id="AmazonWalletWidget"></div>

<!-- put these is your checkout form to be posted when the user submits their order -->
<input type="hidden" name="access_token" value="<?= $_GET['access_token'] ?>">
<input type="hidden" name="reference_id" id="reference_id">
```

Create the users order after they submit it.

Amazon Checkout Controller: `POST -> http://example.com/checkout`
```php
<?php

// If using Laravel "Input::get('...')" can be used in place of "$_POST['...']"

// get access token
$accessToken = $_POST['access_token'];

// get amazon order id
$amazonReferenceId = $_POST['reference_id'];

try {

    // get user details
    $amazonUser = AmazonPayment::getLoginDetails($accessToken);

} catch (\Exception $e) {

    // Redirect back to cart page if error
    return Redirect::to('/cart')
        ->with('failure_message', 'Failed to connect to your Amazon account. Please try again.');

}

// (optional) Wrap your Order transaction with the below code to revert the order if the amazon payment fails.
// DB::beginTransaction();

// create customers order
$order = new Order;
$order->email = $amazonUser['email'];
$order->amazon_id = $amazonReferenceId;
$order->grand_total = 109.99;
$order->etc...
$order->save();

try {

	// set amazon order details
	AmazonPayment::setOrderDetails([
	    'referenceId' => $amazonReferenceId,
	    'amount' => 109.99,
	    'orderId' => $order->id,
        // optional note from customer
	    'note' => $_POST['note']
	]);

	// comfirm the amazon order
	AmazonPayment::confirmOrder([
	    'referenceId' => $amazonReferenceId,
	]);

	// get amazon order details and
	// save the response to your customers order
	$amazon = AmazonPayment::getOrderDetails([
	    'referenceId' => $amazonReferenceId,
	]);

    $address = $amazon['details']['Destination']['PhysicalDestination'];

    // Update the order address, city, etc...
    $order->shipping_city = $address['City'];
    $order->shipping_state = $address['StateOrRegion'];
    $order->save();


// log error.
// tell customer something went wrong.
// maybe delete `$order->delete()` or rollback `DB::rollback();` your websites internal order in the database since it wasn't approved by Amazon

} catch (\Tuurbo\AmazonPayment\Exceptions\OrderReferenceNotModifiableException $e) {
    // DB::rollback();

    return Redirect::to('/secure/cart')->with('warning_message', 'Your order has already been placed and is not modifiable online. Please call '.config('site.company.phone').' to make changes.');
} catch (\Exception $e) {
    // DB::rollback();

    return Redirect::to('/secure/cart')->with('warning_message', 'There was an error with your order. Please try again.');
}

// DB::commit();

// other checkout stuff...
```

Example response from AmazonPayment::getOrderDetails()
```json
{
    "details": {
        "AmazonOrderReferenceId": "SXX-XXXXXXX-XXXXXXX",
        "ExpirationTimestamp": "2015-01-29T22:35:40.555Z",
        "OrderTotal": {
            "Amount": "4637.43",
            "CurrencyCode": "USD"
        },
        "Destination": {
           "DestinationType": "Physical",
            "PhysicalDestination": {
                "PostalCode": "60602",
                "CountryCode": "US",
                "StateOrRegion": "IL",
                "City": "Chicago"
            }
        },
        "OrderReferenceStatus": {
            "State": "Draft"
        },
        "ReleaseEnvironment": "Sandbox",
        "SellerOrderAttributes": {
            "StoreName": "ACME Inc",
            "SellerOrderId": "12345"
        },
        "CreationTimestamp": "2014-08-02T22:35:40.555Z"
    },
    "requestId": "12345678-557f-6ae2-a2ab-ef6db5a325a2"
}
```

## Available Methods

```php
<?php

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
