@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0">Payment Pending</h3>
                </div>
                <div class="card-body text-center">
                    <div class="pending-icon mb-4">
                        <i class="fas fa-hourglass-half" style="font-size: 80px; color: #ffc107;"></i>
                    </div>

                    <h4 class="mb-3">Your payment is being processed</h4>

                    <div class="alert alert-warning">
                        <p class="mb-0">We've received your payment and it's waiting for confirmation on the blockchain. This usually takes 1-5 minutes.</p>
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
                                <td><strong>Amount:</strong></td>
                                <td>{{ number_format($transaction->crypto_amount, 8) }} {{ $transaction->cryptocurrency }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-warning">{{ ucfirst($transaction->status) }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Confirmations:</strong></td>
                                <td><span id="confirmations">{{ $transaction->confirmations }}</span> / {{ $transaction->required_confirmations }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="form-group mt-4">
                        <button type="button" class="btn btn-primary btn-lg w-100 mb-2" onclick="checkStatus()">
                            <i class="fas fa-sync-alt"></i> Check Status
                        </button>
                        <a href="{{ route('payments.confirm', $transaction) }}" class="btn btn-secondary btn-lg w-100">
                            Back to Payment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function checkStatus() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';

        fetch(`/payments/check-status/{{ $transaction->id }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('confirmations').textContent = data.confirmations;
                
                if (data.status === 'confirmed') {
                    window.location.href = '/payments/success?transaction_id={{ $transaction->transaction_reference }}';
                } else {
                    alert('Payment is still pending. Please check again in a moment.');
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to check status'));
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check Status';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check Status';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const autoCheckInterval = setInterval(() => {
            fetch(`/payments/status/{{ $transaction->id }}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('confirmations').textContent = data.confirmations;
                    
                    if (data.status === 'confirmed') {
                        clearInterval(autoCheckInterval);
                        window.location.href = '/payments/success?transaction_id={{ $transaction->transaction_reference }}';
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 15000);
    });
</script>
@endsection
