<?php namespace App\Extensions\PaymentGateways\Freekassa;

use App\Events\PaymentEvent;
use App\Events\UserUpdateCreditsEvent;
use App\Models\PartnerDiscount;
use App\Models\Payment;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;


class FreekassaController {

    const URL = 'https://pay.freekassa.ru/';
    const IPS = ['168.119.157.136', '168.119.60.227',  '178.154.197.79', '51.250.54.238'];
    function payment(Request $request, string $shopProduct): void
    {
        $user = $request->user();
        $shopProduct = ShopProduct::findOrFail($shopProduct);
        $discount = PartnerDiscount::getDiscount();

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_id' => null,
            'payment_method' => 'freekassa',
            'type' => $shopProduct->type,
            'status' => 'open',
            'amount' => $shopProduct->quantity,
            'price' => $shopProduct->price - ($shopProduct->price * $discount / 100),
            'tax_value' => $shopProduct->getTaxValue(),
            'total_price' => $shopProduct->getTotalPrice(),
            'tax_percent' => $shopProduct->getTaxPercent(),
            'currency_code' => $shopProduct->currency_code,
            'shop_item_product_id' => $shopProduct->id,
        ]);

        // формируем адрес
        $url = self::URL . sprintf('?m=%s&oa=%d&currency=%s&o=%s&s=%s&em=%s', urlencode($this->merchantId()), $payment->total_price, $payment->currency_code, urlencode($payment->id), urlencode($this->sign($payment)), urlencode($user->email));

        // отправляем на оплату
        Redirect::to($url)->send();
    }

    function getIP() {
        if(isset($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
        return $_SERVER['REMOTE_ADDR'];
    }

    function checkStatus(Request $request): void
    {
        if (!in_array($this->getIP(), self::IPS))
            Redirect::route('home')->with('info', 'hacking attempt!')->send();

        $sign = md5(implode(':', [
            $this->merchantId(),
            $request->get('AMOUNT'),
            $this->secretWord(),
            $request->get('MERCHANT_ORDER_ID'),
        ]));

        if ($sign !== $request->get('SIGN'))
            Redirect::route('home')->with('info', 'hacking attempt!')->send();
    }

    function success(Request $request): void
    {
        Log::info('Freekassa success', $request->toArray());

        $this->checkStatus($request);

        $payment = Payment::findOrFail($request->get('MERCHANT_ORDER_ID'));
        $shopProduct = ShopProduct::findOrFail($payment->shop_item_product_id);

        $payment->update([
            'status' => 'paid',
        ]);

        event(new UserUpdateCreditsEvent($request->user()));
        event(new PaymentEvent($request->user(), $payment, $shopProduct));

        Redirect::route('home')->with('success', 'Payment successful')->send();
    }

    function fail(Request $request): void
    {
        Log::info('Freekassa fail', $request->toArray());

        $this->checkStatus($request);

        $payment = Payment::findOrFail($request->get('MERCHANT_ORDER_ID'));
        $shopProduct = ShopProduct::findOrFail($payment->shop_item_product_id);

        $payment->update([
            'status' => 'fail',
        ]);

        event(new PaymentEvent($request->user(), $payment, $shopProduct));

        Redirect::route('home')->with('info', 'Payment fail!')->send();
    }

    function alert(Request $request): void
    {
        Log::alert('Freekassa send alert', $request->toArray());
    }

    private function merchantId(): string
    {
        return env('FREEKASSA_MERCHANT_ID');
    }

    private function secretWord(): string
    {
        return env('FREEKASSA_SECRET_WORD');
    }

    private function sign(Payment $payment): string
    {
        Log::info('Freekassa sign', [
            $this->merchantId(),
            $payment->total_price,
            $this->secretWord(),
            $payment->currency_code,
            $payment->id,
        ]);
        return md5(implode(':', [
            $this->merchantId(),
            $payment->total_price,
            $this->secretWord(),
            $payment->currency_code,
            $payment->id,
        ]));
    }
}
