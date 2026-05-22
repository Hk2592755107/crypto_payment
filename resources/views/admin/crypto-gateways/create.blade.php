@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">Add Crypto Gateway</h2>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crypto-gateways.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Gateway Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug *</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" name="slug" value="{{ old('slug') }}" required>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">e.g., coinbase-commerce, nowpayments</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="api_endpoint" class="form-label">API Endpoint *</label>
                            <input type="url" class="form-control @error('api_endpoint') is-invalid @enderror" 
                                   id="api_endpoint" name="api_endpoint" value="{{ old('api_endpoint') }}" required>
                            @error('api_endpoint')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="webhook_endpoint" class="form-label">Webhook Endpoint</label>
                            <input type="url" class="form-control @error('webhook_endpoint') is-invalid @enderror" 
                                   id="webhook_endpoint" name="webhook_endpoint" value="{{ old('webhook_endpoint') }}">
                            @error('webhook_endpoint')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="supported_currencies" class="form-label">Supported Currencies *</label>
                            <select class="form-select @error('supported_currencies') is-invalid @enderror" 
                                    id="supported_currencies" name="supported_currencies[]" multiple required>
                                <option value="BTC" {{ in_array('BTC', old('supported_currencies', [])) ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                <option value="ETH" {{ in_array('ETH', old('supported_currencies', [])) ? 'selected' : '' }}>Ethereum (ETH)</option>
                                <option value="USDT" {{ in_array('USDT', old('supported_currencies', [])) ? 'selected' : '' }}>Tether (USDT)</option>
                                <option value="USDC" {{ in_array('USDC', old('supported_currencies', [])) ? 'selected' : '' }}>USD Coin (USDC)</option>
                                <option value="BNB" {{ in_array('BNB', old('supported_currencies', [])) ? 'selected' : '' }}>Binance Coin (BNB)</option>
                                <option value="LTC" {{ in_array('LTC', old('supported_currencies', [])) ? 'selected' : '' }}>Litecoin (LTC)</option>
                                <option value="DOGE" {{ in_array('DOGE', old('supported_currencies', [])) ? 'selected' : '' }}>Dogecoin (DOGE)</option>
                                <option value="DAI" {{ in_array('DAI', old('supported_currencies', [])) ? 'selected' : '' }}>Dai (DAI)</option>
                            </select>
                            @error('supported_currencies')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="api_key" class="form-label">API Key *</label>
                                <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                                       id="api_key" name="api_key" required>
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="webhook_secret" class="form-label">Webhook Secret</label>
                                <input type="password" class="form-control @error('webhook_secret') is-invalid @enderror" 
                                       id="webhook_secret" name="webhook_secret">
                                @error('webhook_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="transaction_fee_percentage" class="form-label">Transaction Fee %</label>
                                <input type="number" step="0.01" class="form-control @error('transaction_fee_percentage') is-invalid @enderror" 
                                       id="transaction_fee_percentage" name="transaction_fee_percentage" value="{{ old('transaction_fee_percentage', 0) }}">
                                @error('transaction_fee_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirmation_required" class="form-label">Confirmations Required</label>
                                <input type="number" class="form-control @error('confirmation_required') is-invalid @enderror" 
                                       id="confirmation_required" name="confirmation_required" value="{{ old('confirmation_required', 1) }}">
                                @error('confirmation_required')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_transaction_amount" class="form-label">Min Transaction Amount</label>
                                <input type="number" step="0.01" class="form-control @error('min_transaction_amount') is-invalid @enderror" 
                                       id="min_transaction_amount" name="min_transaction_amount" value="{{ old('min_transaction_amount', 0) }}">
                                @error('min_transaction_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_transaction_amount" class="form-label">Max Transaction Amount</label>
                                <input type="number" step="0.01" class="form-control @error('max_transaction_amount') is-invalid @enderror" 
                                       id="max_transaction_amount" name="max_transaction_amount" value="{{ old('max_transaction_amount') }}">
                                @error('max_transaction_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Gateway</button>
                            <a href="{{ route('admin.crypto-gateways.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
