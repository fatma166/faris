<?php namespace App\Http\Controllers;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use App\Model\BusinessSetting;
use App\Model\Currency;
use Illuminate\Http\Request;
/*use Tamara\Checkout\Checkout;
use Tamara\Checkout\Order;
use Tamara\Checkout\PaymentType;*/
//use Tamara\Facades\Tamara;
//use Tamara\Requests\Checkout\CreateOrderRequest;
/*
use Illuminate\Support\Facades\App;
use Tamara\Configuration;
use Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara\Request\Checkout\Order;
use Tamara\Request\Checkout\Order\Amount;
use Tamara\Request\Checkout\Order\Item;
use Tamara\Request\Checkout\Order\Shipping\Address;
use Tamara\Request\Checkout\Order\ShoppingCart;
use Tamara\Client;*/

use Illuminate\Support\Facades\App;
use phpseclib3\Crypt\Random;
use Tamara\Configuration;
use Tamara\Client;
use Tamara\Model\Order\Order;
use Tamara\Model\Money;
use Tamara\Model\Order\Address;
use Tamara\Model\Order\Consumer;
use Tamara\Model\Order\MerchantUrl;
use Tamara\Model\Order\OrderItemCollection;
use Tamara\Model\Order\OrderItem;
use Tamara\Model\Order\Discount;
use Tamara\Request\Checkout\CreateCheckoutRequest;

class TamaraController extends Controller
{
public function create(Request $request)
{
    $config = Helpers::get_business_settings('tamara');
    // Initialize the Tamara client
    //$client = new Client($config['tamara_client_id'], $config['tamara_secret']);

    $country_code = Helpers::get_business_settings('country_code');
    $currency_model = Helpers::get_business_settings('currency_model');
    if ($currency_model == 'multi_currency') {
        $currency_code = 'USD';
    } else {
        $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
        $currency_code = Currency::find($default)->code;
    }

    //setup order
    $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
    $value = CartManager::cart_grand_total() - $discount;
  $cart_data= CartManager::get_cart()->first();
    $unique_id = OrderManager::gen_unique_id();
    $order = new Order();
    $order->setOrderReferenceId($unique_id );
    $order->setLocale(app::getLocale());
    $order->setCurrency($currency_code);
    $order->setTotalAmount(new Money($value, $currency_code));
    $order->setCountryCode($country_code);
   $order->setPaymentType('PAY_BY_INSTALMENTS');
   // $order->setPlatform('Magento');
   // $order->setDescription('Some order description');
    $order->setTaxAmount(new Money($cart_data['tax'], $currency_code));
    $order->setShippingAmount(new Money($cart_data['shipping_cost'] , $currency_code));

    # order items
    $orderItemCollection = new OrderItemCollection();
    $orderItem = new OrderItem();
    $orderItem->setName($cart_data['name']);
    $orderItem->setQuantity($cart_data['quantity']);
    $orderItem->setUnitPrice(new Money($value, $currency_code));
    $orderItem->setType($cart_data['product_type']);
   // $orderItem->setSku('SKU-123');
    $orderItem->setTotalAmount(new Money($value, $currency_code));
    $orderItem->setTaxAmount(new Money($cart_data['tax'], $currency_code));
    $orderItem->setDiscountAmount(new Money($cart_data['discount'], $currency_code));
   // $orderItem->setReferenceId(123456);

    $orderItemCollection->append($orderItem);
    $order->setItems($orderItemCollection);


    # billing address
    $user = Helpers::get_customer();
    $billing = new Address();
    $billing->setFirstName($user['f_name']);
    $billing->setLastName($user['l_name']);
   // $billing->setLine1('Address line 1');
   // $billing->setLine2('Address line 2');
   // $billing->setRegion('');
   // $billing->setCity($orderData['payment_city']);
    $billing->setPhoneNumber($user['phone']);
   // $billing->setCountryCode($orderData['payment_iso_code_2']);
    $order->setBillingAddress($billing);

    # merchant urls
    $merchantUrl = new MerchantUrl();
    $merchantUrl->setSuccessUrl(/*'https://farisgrp.com/tamara/success'*/ route('payment-success'));
    $merchantUrl->setFailureUrl(/*'https://farisgrp.com/tamara/failure'*/ route('payment-fail'));
    $merchantUrl->setCancelUrl('https://farisgrp.com/tamara/cancel');
    $merchantUrl->setNotificationUrl('https://farisgrp.com/tamara/notification');
    $order->setMerchantUrl($merchantUrl);
   // dd($order);
    # discount
   // $order->setDiscount(new Discount('Coupon', new Money(0.00, 'SAR'));
   // $this->getToken($url,$token);
    $url=$config['tamara_url'];
    $token=$config['tamara_token'];
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

    // Redirect the customer to the Tamara checkout page to complete the payment
    return redirect()->to($response->getRedirectUrl());

}
    public function getToken()
    {
        $config = Helpers::get_business_settings('tamara');
        $response = $this->cURL(
            'https://tamara/api/auth/tokens',
            ['api_key' => $config['api_key']]
        );

        return $response->token;
    }
    public function createOrder($token)
    {
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;
        //$value = Convert::usdToegp($value);

        $items = [];
        foreach (CartManager::get_cart() as $detail) {
            array_push($items, [
                'name' => $detail->product['name'],
                'description' => $detail->product['name'],
                'quantity' => $detail['quantity'],
                'amount_cents' => $detail['price'],
            ]);
        }

       /* $data = [
            "auth_token" => $token,
            "delivery_needed" => "false",
            "amount_cents" => round($value,2) * 100,
            "currency" => "EGP",
            "items" => $items,

        ];*/
      /*  $response = $this->cURL(
            'https://accept.paymob.com/api/ecommerce/orders',
            $data
        );
*/
        return $items;
    }



public function callback($url,$token)
{
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
    return($redirectUrl);
}
}
