@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">Edit Crypto Gateway</h2>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crypto-gateways.update', $gateway) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Gateway Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $gateway->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="{{ $gateway->slug }}" disabled>
                            <small class="text-muted">Slug cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $gateway->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="api_key" class="form-label">API Key *</label>
                                <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                                       id="api_key" name="api_key" value="{{ old('api_key', $gateway->getApiKey()) }}" required>
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="webhook_secret" class="form-label">Webhook Secret</label>
                                <input type="password" class="form-control @error('webhook_secret') is-invalid @enderror" 
                                       id="webhook_secret" name="webhook_secret" value="{{ old('webhook_secret', $gateway->getWebhookSecret()) }}">
                                @error('webhook_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="transaction_fee_percentage" class="form-label">Transaction Fee %</label>
                                <input type="number" step="0.01" class="form-control @error('transaction_fee_percentage') is-invalid @enderror" 
                                       id="transaction_fee_percentage" name="transaction_fee_percentage" value="{{ old('transaction_fee_percentage', $gateway->transaction_fee_percentage) }}">
                                @error('transaction_fee_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirmation_required" class="form-label">Confirmations Required</label>
                                <input type="number" class="form-control @error('confirmation_required') is-invalid @enderror" 
                                       id="confirmation_required" name="confirmation_required" value="{{ old('confirmation_required', $gateway->confirmation_required) }}">
                                @error('confirmation_required')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active', $gateway->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Gateway</button>
                            <a href="{{ route('admin.crypto-gateways.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
