<?php

namespace App\Http\Controllers;

use App\Models\CryptoTransaction;
use App\Models\Order;
use App\Services\CryptoPaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected CryptoPaymentService $paymentService) {}

    public function checkout(Order $order)
    {
        if (!$order->isPending()) {
            return redirect('/')->with('error', 'Order is not pending payment');
        }

        $currencies = $this->paymentService->getSupportedCurrencies();

        return view('payments.checkout', compact('order', 'currencies'));
    }

    public function pay(Request $request, Order $order)
    {
        $validated = $request->validate([
            'cryptocurrency' => 'required|string|min:2|max:10',
        ]);

        try {
            $transaction = $this->paymentService->createPayment($order, strtoupper($validated['cryptocurrency']));

            // If invoice_url exists, redirect to NOWPayments hosted checkout
            $invoiceUrl = $transaction->metadata['invoice_url'] ?? null;
            if ($invoiceUrl) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $invoiceUrl,
                ]);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => route('payments.confirm', $transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function confirm(CryptoTransaction $transaction)
    {
        return view('payments.confirm', [
            'transaction' => $transaction,
            'order' => $transaction->order,
        ]);
    }

    public function status(CryptoTransaction $transaction)
    {
        $this->paymentService->checkPaymentStatus($transaction);
        $status = $this->paymentService->getPaymentStatus($transaction);

        return response()->json($status);
    }

    public function success(Request $request)
    {
        $txnRef = $request->query('transaction_id');
        $transaction = $txnRef ? CryptoTransaction::where('transaction_reference', $txnRef)->first() : null;

        if ($transaction && $transaction->isConfirmed()) {
            return view('payments.success', ['transaction' => $transaction, 'order' => $transaction->order]);
        }

        return view('payments.pending', ['transaction' => $transaction]);
    }

    public function cancel()
    {
        return view('payments.cancel');
    }
}
