<?php
use tamara\Configuration;
use tamara\Client;
use tamara\Model\Order\Order;
use tamara\Model\Money;
use tamara\Model\Order\Address;
use tamara\Model\Order\Consumer;
use tamara\Model\Order\MerchantUrl;
use tamara\Model\Order\OrderItemCollection;
use tamara\Model\Order\OrderItem;
use tamara\Model\Order\Discount;
use tamara\Request\Checkout\CreateCheckoutRequest;

$order = new Order();

$order->setOrderReferenceId(123456);
$order->setLocale('en_US');
$order->setCurrency('SAR');
$order->setTotalAmount(new Money(100.0, 'SAR');
$order->setCountryCode('SA');
$order->setPaymentType('PAY_BY_INSTALMENTS');
$order->setPlatform('Magento');
$order->setDescription('Some order description');
$order->setTaxAmount(new Money(0.00, 'SAR'));
$order->setShippingAmount(new Money(0.00, 'SAR'));

# order items
$orderItemCollection = new OrderItemCollection();
$orderItem = new OrderItem();
$orderItem->setName('Item name');
$orderItem->setQuantity(1);
$orderItem->setUnitPrice(new Money(100.0, 'SAR'));
$orderItem->setType('Electronic');
$orderItem->setSku('SKU-123');
$orderItem->setTotalAmount(new Money(100.0, 'SAR'));
$orderItem->setTaxAmount(new Money(0.0, 'SAR'));
$orderItem->setDiscountAmount(new Money(0.0, 'SAR'));
$orderItem->setReferenceId(123456);

$orderItemCollection->append($orderItem);
$order->setItems($orderItemCollection);

# billing address
$billing = new Address();
$billing->setFirstName('Mona');
$billing->setLastName('Lisa');
$billing->setLine1('Address line 1');
$billing->setLine2('Address line 2');
$billing->setRegion('');
$billing->setCity($orderData['payment_city']);
$billing->setPhoneNumber($orderData['telephone']);
$billing->setCountryCode($orderData['payment_iso_code_2']);
$order->setBillingAddress($billing);

# shipping address
$shipping = new Address();
$shipping->setFirstName('Mona');
$shipping->setLastName('Lisa');
$shipping->setLine1('Address line 1');
$shipping->setLine2('Address line 2');
$shipping->setRegion('');
$shipping->setCity($orderData['payment_city']);
$shipping->setPhoneNumber($orderData['telephone']);
$shipping->setCountryCode($orderData['payment_iso_code_2']);
$order->setShippingAddress($shipping);

# consumer
$consumer = new Consumer();
$consumer->setFirstName('Mona');
$consumer->setLastName('Lisa');
$consumer->setEmail('mona.lisa@example.com');
$consumer->setPhoneNumber('966523334444');
$order->setConsumer($consumer);

# merchant urls
$merchantUrl = new MerchantUrl();
$merchantUrl->setSuccessUrl('https://example.com/tamara/success');
$merchantUrl->setFailureUrl('https://example.com/tamara/failure');
$merchantUrl->setCancelUrl('https://example.com/tamara/cancel');
$merchantUrl->setNotificationUrl('https://example.com/tamara/notification');
$order->setMerchantUrl($merchantUrl);

# discount
$order->setDiscount(new Discount('Coupon', new Money(0.00, 'SAR'));

$client = Client::create(Configuration::create($url, $token));
$request = new CreateCheckoutRequest($order);

$response = $client->createCheckout($request);

if (!$response->isSuccess()) {
    $this->log($response->getErrors());
    return $this->handleError($response->getErrors());
}

$checkoutResponse = $response->getCheckoutResponse();

if ($checkoutResponse === null) {
    $this->log($response->getContent());

    return false;
}

$tamaraOrderId = $checkoutResponse->getOrderId();
$redirectUrl = $checkoutResponse->getCheckoutUrl();
// do redirection to $redirectUrl
