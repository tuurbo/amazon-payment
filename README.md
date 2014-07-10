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