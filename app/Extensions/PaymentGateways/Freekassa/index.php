<?php namespace App\Extensions\PaymentGateways\Freekassa;

use Illuminate\Http\Request;

function Freekassa(Request $request)
{
    (new FreekassaController())->payment($request, $request->shopProduct);
}
