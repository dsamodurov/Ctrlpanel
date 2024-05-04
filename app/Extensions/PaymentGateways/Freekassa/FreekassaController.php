<?php namespace App\Extensions\PaymentGateways\Freekassa;

use App\Events\PaymentEvent;
use App\Events\UserUpdateCreditsEvent;
use App\Models\PartnerDiscount;
use App\Models\Payment;
use App\Models\ShopProduct;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;


class FreekassaController {

    const URL = 'https://api.freekassa.ru/v1/';

    private function request(string $method, array $data = []): ?array
    {
        if(!env('FREEKASSA_SHOP_ID')) {
            Log::error('Set FREEKASSA_SHOP_ID in .env');
            return null;
        } else {
            $data['shopId'] = env('FREEKASSA_SHOP_ID');
            $data['nonce'] = time();
        }


        ksort($data);
        if(!env('FREEKASSA_API_KEY')) {
            Log::error('Set FREEKASSA_API_KEY in .env');
            return null;
        } else {
            $sign = hash_hmac('sha256', implode('|', $data), env('FREEKASSA_API_KEY'));
            $data['signature'] = $sign;
        }

        try {
            $res = Http::timeout(10)
                ->post(self::URL.$method, $data);
        } catch (Exception $err) {
            Log::error('FreekassaController:request '.$err->getMessage());
            return null;
        }

        if ($res->failed()) {
            Log::error('FreekassaController:request HTTP '.$res->status(), $res->json());
            return null;
        } else {
            Log::info('FreekassaController:request OK',
                array_merge(compact('method', 'data'), ['response' => $res->json()]));
            return $res->json();
        }

    }
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
//        $url = self::URL . sprintf('?m=%s&oa=%s&currency=%s&o=%s&s=%s&em=%s', urlencode($this->merchantId()), $payment->total_price, $payment->currency_code, urlencode($payment->id), urlencode($this->sign($payment)), urlencode($user->email));
       $this->request('currencies');

        $response = $this->request('orders/create', [
            'paymentId' => $payment->id,
            //'i' => 6,
            'email' => $user->email,
            'ip' => $request->getClientIp(),
            'amount' => $payment->total_price,
            'currency' => $payment->currency_code
        ]);

        if(is_array($response) && $response['type'] === 'success') {
            $payment->payment_id = $response['orderId'];
            $payment->save();

            Redirect::to($response['location'])->send();
        } else {
            Redirect::route('home')->with('info', 'Payment fail!')->send();
        }
    }

    function success(Request $request): void
    {
        Log::info('Freekassa success', $request->toArray());

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
        return md5(implode(':', [
            $this->merchantId(),
            $payment->total_price,
            $this->secretWord(),
            $payment->currency_code,
            $payment->id,
        ]));
    }
}
