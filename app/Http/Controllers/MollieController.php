<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;

class MollieController extends Controller
{
    function getMollie()
    {
        $mollie = new MollieApiClient();
        $mollie->setApiKey("test_vWbmdhrtRQvyVB5TSzvhGBSCU2Pw2f");
        return $mollie;
    }

    function getPayments()
    {
        $mollie = $this->getMollie();
        $payments = $mollie->payments;
        return $payments;
    }

    function createPayment($description, $amount)
    {
        $payments = $this->getPayments();
        $payment = $payments->create([
            "amount" => [
                "currency" => "EUR",
                "value" => number_format($amount, 2, '.', '')
            ],
            "method" => null,
            "description" => $description,
            "redirectUrl" => "https://staging-api.examenfit.nl/",
            //"webhookUrl"  => "https://staging-api.examenfit.nl/",
        ]);
        return $payment;
    }

    function getPayment($id)
    {
        $payments = $this->getPayments();
        $payment = $payments->get($payment_id);
        return $payment;
    }

    // www
    public function test()
    {
        $payment = $this->createPayment("Test, my 2000 cents", 20.00);

        return response()->json($payment);
    }

    public function payment()
    {
        $id = "tr_SyvJR3H9mv";
        $payment = $this->getPayment($id);

        return response()->json($payment);
    }

    public function checkout_url()
    {
        $id = "tr_SyvJR3H9mv";
        $payment = $this->getPayment($id);

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

        $mollie = $this->getMollie();
        $payments = $mollie->payments;

        $payment = $payments->get($id);

        return response()->noContent();
    }
}
