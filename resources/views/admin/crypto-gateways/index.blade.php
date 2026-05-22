@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Crypto Payment Gateways</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.crypto-gateways.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Gateway
            </a>
        </div>
    </div>

    @if($gateways->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Currencies</th>
                        <th>Fee %</th>
                        <th>Transactions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gateways as $gateway)
                        <tr>
                            <td>
                                <strong>{{ $gateway->name }}</strong>
                                <br>
                                <small class="text-muted">{{ $gateway->slug }}</small>
                            </td>
                            <td>
                                @if($gateway->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @foreach($gateway->supported_currencies as $currency)
                                    <span class="badge bg-info">{{ $currency }}</span>
                                @endforeach
                            </td>
                            <td>{{ $gateway->transaction_fee_percentage }}%</td>
                            <td>
                                <a href="{{ route('admin.crypto-gateways.transactions', $gateway) }}">
                                    {{ $gateway->transactions()->count() }} transactions
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.crypto-gateways.edit', $gateway) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.crypto-gateways.webhook-logs', $gateway) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-webhook"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.crypto-gateways.destroy', $gateway) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info">
            No crypto gateways configured yet. <a href="{{ route('admin.crypto-gateways.create') }}">Add one now</a>
        </div>
    @endif
</div>
@endsection
