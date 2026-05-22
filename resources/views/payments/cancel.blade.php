@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Payment Cancelled</h3>
                </div>
                <div class="card-body text-center">
                    <div class="cancel-icon mb-4">
                        <i class="fas fa-times-circle" style="font-size: 80px; color: #dc3545;"></i>
                    </div>

                    <h4 class="mb-3">Payment has been cancelled</h4>

                    <div class="alert alert-danger">
                        <p class="mb-0">Your payment has been cancelled. No funds have been deducted from your account.</p>
                    </div>

                    @if($transaction)
                    <div class="transaction-details mt-4 mb-4">
                        <h5>Transaction Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Transaction ID:</strong></td>
                                <td>{{ $transaction->transaction_reference }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-danger">Cancelled</span></td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    <div class="form-group mt-4">
                        <a href="/" class="btn btn-primary btn-lg w-100">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
