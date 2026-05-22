@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Pay with Crypto</h3>
                </div>
                <div class="card-body">
                    <div class="order-summary mb-4">
                        <h5>Order Summary</h5>
                        <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                        <p><strong>Amount:</strong> {{ $order->currency }} {{ number_format($order->total_amount, 2) }}</p>
                    </div>

                    <hr>

                    <h5 class="mb-3">Select Cryptocurrency</h5>

                    <div class="row mb-4">
                        @foreach($currencies as $currency)
                            <div class="col-md-3 col-6 mb-3">
                                <div class="crypto-option p-3 border rounded text-center" 
                                     onclick="selectCrypto(this, '{{ $currency }}')">
                                    <input type="radio" name="cryptocurrency" value="{{ $currency }}" 
                                           id="crypto_{{ $currency }}" class="d-none">
                                    <strong>{{ $currency }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-primary btn-lg w-100" 
                            id="payBtn" onclick="proceedToPayment()" disabled>
                        Proceed to Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .crypto-option {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .crypto-option:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }
    .crypto-option.selected {
        border-color: #007bff;
        background-color: #e7f3ff;
    }
</style>

<script>
    function selectCrypto(el, currency) {
        document.querySelectorAll('.crypto-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        el.querySelector('input[type=radio]').checked = true;
        document.getElementById('payBtn').disabled = false;
    }

    function proceedToPayment() {
        const cryptocurrency = document.querySelector('input[name="cryptocurrency"]:checked')?.value;
        if (!cryptocurrency) return;

        const btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Payment...';

        fetch('/payments/pay/{{ $order->id }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ cryptocurrency })
        })
        .then(response => {
            if (!response.ok) return response.text().then(t => { throw new Error(t); });
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = 'Proceed to Payment';
            }
        })
        .catch(error => {
            let msg = 'An error occurred. Please try again.';
            try { const p = JSON.parse(error.message); msg = p.message || p.error || msg; } catch(e) { if(error.message) msg = error.message.substring(0,200); }
            alert(msg);
            btn.disabled = false;
            btn.innerHTML = 'Proceed to Payment';
        });
    }
</script>
@endsection
