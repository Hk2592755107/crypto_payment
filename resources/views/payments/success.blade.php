@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Payment Successful</h3>
                </div>
                <div class="card-body text-center">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle" style="font-size: 80px; color: #28a745;"></i>
                    </div>

                    <h4 class="mb-3">Your payment has been confirmed!</h4>

                    <div class="alert alert-success">
                        <p class="mb-0">Thank you for your payment. Your order has been marked as paid.</p>
                    </div>

                    <div class="transaction-details mt-4 mb-4">
                        <h5>Transaction Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Transaction ID:</strong></td>
                                <td>{{ $transaction->transaction_reference }}</td>
                            </tr>
                            <tr>
                                <td><strong>Cryptocurrency:</strong></td>
                                <td>{{ $transaction->cryptocurrency }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount Paid:</strong></td>
                                <td>{{ number_format($transaction->amount_received, 8) }} {{ $transaction->cryptocurrency }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fiat Amount:</strong></td>
                                <td>{{ $transaction->fiat_currency }} {{ number_format($transaction->fiat_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Confirmations:</strong></td>
                                <td>{{ $transaction->confirmations }} / {{ $transaction->required_confirmations }}</td>
                            </tr>
                            <tr>
                                <td><strong>Confirmed At:</strong></td>
                                <td>{{ $transaction->confirmed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</td>
                            </tr>
                            @if($transaction->transaction_hash)
                            <tr>
                                <td><strong>Transaction Hash:</strong></td>
                                <td><code>{{ $transaction->transaction_hash }}</code></td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    @if($order)
                    <div class="order-details mt-4 mb-4">
                        <h5>Order Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Order #:</strong></td>
                                <td>{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-success">{{ ucfirst($order->status) }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Paid At:</strong></td>
                                <td>{{ $order->paid_at?->format('M d, Y H:i:s') ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    <div class="form-group mt-4">
                        @if($order)
                        <a href="{{ url('/') }}" class="btn btn-primary btn-lg w-100 mb-2">
                            Back to Home
                        </a>
                        @endif
                        <a href="/" class="btn btn-secondary btn-lg w-100">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .success-icon {
        animation: scaleIn 0.5s ease-in-out;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>
@endsection
