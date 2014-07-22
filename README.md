Login and Pay with Amazon
==============

### Needs refactoring and is missing some methods.

### USE WITH CAUTION!

Amazon docs: http://docs.developer.amazonservices.com/en_US/apa_guide/APAGuide_Introduction.html

Example usage:

```
AmazonPayment::setOrderDetails([
	'referenceId' => 'XXX-XXXXXXXX-XXX',
	'amount' => 100,
	'orderId' => 12345,
	'storeName' => 'ACME INC',
	'note' => 'blah...'
]);
```

TODO: A lot of the methods can have extra fields appended.. read the link below to add more.
http://docs.developer.amazonservices.com/en_US/off_amazon_payments/OffAmazonPayments_AuthorizeOnBillingAgreement.html