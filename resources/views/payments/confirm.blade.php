@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Complete Your Payment</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Payment Status:</strong> <span id="status-badge" class="badge bg-warning">{{ ucfirst($transaction->status) }}</span>
                    </div>

                    <div class="payment-details mb-4">
                        <h5>Payment Details</h5>
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
                                <td><strong>Amount to Pay:</strong></td>
                                <td>
                                    <strong id="crypto-amount">{{ number_format($transaction->crypto_amount, 8) }}</strong> {{ $transaction->cryptocurrency }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Fiat Amount:</strong></td>
                                <td>{{ $transaction->fiat_currency }} {{ number_format($transaction->fiat_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Exchange Rate:</strong></td>
                                <td>1 {{ $transaction->cryptocurrency }} = {{ number_format($transaction->exchange_rate, 2) }} {{ $transaction->fiat_currency }}</td>
                            </tr>
                            <tr>
                                <td><strong>Expires At:</strong></td>
                                <td>
                                    <span id="expiry-time">{{ $transaction->expires_at->format('M d, Y H:i:s') }}</span>
                                    <span id="countdown" class="text-danger ms-2"></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="payment-address mb-4">
                        <h5>Send Payment To This Address</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="qr-code text-center mb-3">
                                    @php
                                        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($transaction->payment_address);
                                    @endphp
                                    <img src="{{ $qrCodeUrl }}" 
                                         alt="QR Code" class="img-fluid" style="max-width: 300px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="address-box p-3 bg-light rounded">
                                    <p class="text-muted small">Wallet Address:</p>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="address-input" 
                                               value="{{ $transaction->payment_address }}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="copyAddress()">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-status mb-4">
                        <h5>Payment Status</h5>
                        <div class="status-info p-3 bg-light rounded">
                            <p><strong>Status:</strong> <span id="status-text" class="badge bg-warning">{{ ucfirst($transaction->status) }}</span></p>
                            <p><strong>Confirmations:</strong> <span id="confirmations">{{ $transaction->confirmations }}</span> / {{ $transaction->required_confirmations }}</p>
                            <p><strong>Amount Received:</strong> <span id="amount-received">{{ number_format($transaction->amount_received, 8) }}</span> {{ $transaction->cryptocurrency }}</p>
                            <p id="confirmed-message" style="display: none;" class="text-success mb-0">
                                <strong>✓ Payment Confirmed!</strong>
                            </p>
                        </div>
                    </div>

                    <div class="instructions mb-4">
                        <h5>Instructions</h5>
                        <ol>
                            <li>Copy the wallet address above or scan the QR code</li>
                            <li>Send exactly <strong id="instruction-amount">{{ number_format($transaction->crypto_amount, 8) }}</strong> {{ $transaction->cryptocurrency }} to this address</li>
                            <li>Wait for payment confirmation (usually 1-5 minutes)</li>
                            <li>Your order will be automatically marked as paid once confirmed</li>
                        </ol>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-primary w-100 mb-2" onclick="checkPaymentStatus()">
                            <i class="fas fa-sync-alt"></i> Check Payment Status
                        </button>
                        <a href="{{ url('/') }}" class="btn btn-secondary w-100">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .payment-address {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .address-box {
        word-break: break-all;
    }

    .status-info {
        border-left: 4px solid #007bff;
    }
</style>

<script>
    let checkInterval;
    const transactionId = {{ $transaction->id }};
    const expiresAt = new Date('{{ $transaction->expires_at }}');

    function copyAddress() {
        const addressInput = document.getElementById('address-input');
        addressInput.select();
        document.execCommand('copy');
        
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    }

    function checkPaymentStatus() {
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';

        fetch('/payments/status/{{ $transaction->id }}', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                updatePaymentStatus(data);
            } else {
                alert('Error: ' + (data.error || 'Failed to check status'));
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check Payment Status';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check Payment Status';
        });
    }

    function updatePaymentStatus(data) {
        const statusText = data.status;
        const confirmations = data.confirmations;
        const amountReceived = data.amount_received;

        document.getElementById('status-text').textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
        document.getElementById('status-badge').textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
        document.getElementById('confirmations').textContent = confirmations;
        document.getElementById('amount-received').textContent = parseFloat(amountReceived).toFixed(8);

        if (statusText === 'confirmed') {
            document.getElementById('confirmed-message').style.display = 'block';
            document.getElementById('status-badge').className = 'badge bg-success';
            document.getElementById('status-text').className = 'badge bg-success';
            clearInterval(checkInterval);
            
            setTimeout(() => {
                window.location.href = '/payments/success?transaction_id={{ $transaction->transaction_reference }}';
            }, 2000);
        }
    }

    function updateCountdown() {
        const now = new Date();
        const diff = expiresAt - now;

        if (diff <= 0) {
            document.getElementById('countdown').textContent = '(Expired)';
            clearInterval(checkInterval);
            return;
        }

        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        document.getElementById('countdown').textContent = `(${hours}h ${minutes}m ${seconds}s remaining)`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCountdown();
        setInterval(updateCountdown, 1000);

        checkInterval = setInterval(checkPaymentStatus, 10000);
    });
</script>
@endsection
