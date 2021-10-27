<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;

class MollieController extends Controller
{
    function mollie()
    {
        $mollie = new MollieApiClient();
        $mollie->setApiKey("test_vWbmdhrtRQvyVB5TSzvhGBSCU2Pw2f");
        return $mollie;
    }

    public function test()
    {
        $mollie = $this->mollie();
        $payments = $mollie->payments;

        $payment = $payments->create([
            "amount" => [
                "currency" => "EUR",
                "value" => "20.00"
            ],
            "method" => null,
            "description" => "Test, my 2000 cents",
            "redirectUrl" => "https://staging-api.examenfit.nl/",
            //"webhookUrl"  => "https://staging-api.examenfit.nl/",
        ]);

        return response()->json($payment);
    }

    public function payment()
    {
        $mollie = $this->mollie();
        $payments = $mollie->payments;

        $payment = $payments->get("tr_SyvJR3H9mv");

        return response()->json($payment);
    }

    public function checkout_url()
    {
        $mollie = $this->mollie();
        $payments = $mollie->payments;

        $id = "tr_SyvJR3H9mv";
        $payment = $payments->get($id);

        if ($payment->status !== 'open') {
            return response()->json($payment);
        }

        $checkout_url = [
            'checkout_url' => $payment->getCheckoutUrl()
        ];

        return response()->json($checkout_url);
    }

    public function webhook(Request $request)
    {
        $id = $request->input('id');

        $mollie = $this->mollie();
        $payments = $mollie->payments;

        $payment = $payments->get($id);

        return response()->noContent();
    }
}
