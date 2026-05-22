@extends('layouts.app')

@php
function getStatusColor($status) {
    return match($status) {
        'pending' => 'warning',
        'waiting_confirmation' => 'info',
        'confirmed' => 'success',
        'failed' => 'danger',
        'expired' => 'secondary',
        'partially_paid' => 'warning',
        'refunded' => 'secondary',
        default => 'secondary',
    };
}
@endphp

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $gateway->name }} - Transactions</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.crypto-gateways.index') }}" class="btn btn-secondary">
                Back to Gateways
            </a>
        </div>
    </div>

    @if($transactions->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Transaction ID</th>
                        <th>Order</th>
                        <th>Crypto</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Confirmations</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>
                                <code>{{ substr($transaction->transaction_reference, 0, 20) }}...</code>
                            </td>
                            <td>
                                @if($transaction->order)
                                    #{{ $transaction->order->order_number }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $transaction->cryptocurrency }}</td>
                            <td>
                                {{ number_format($transaction->crypto_amount, 8) }} {{ $transaction->cryptocurrency }}
                                <br>
                                <small class="text-muted">{{ $transaction->fiat_currency }} {{ number_format($transaction->fiat_amount, 2) }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ getStatusColor($transaction->status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}
                                </span>
                            </td>
                            <td>
                                {{ $transaction->confirmations }} / {{ $transaction->required_confirmations }}
                            </td>
                            <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $transactions->links() }}
        </div>
    @else
        <div class="alert alert-info">
            No transactions found for this gateway.
        </div>
    @endif
</div>

@endsection
