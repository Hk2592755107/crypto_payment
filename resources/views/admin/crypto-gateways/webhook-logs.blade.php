@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $gateway->name }} - Webhook Logs</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.crypto-gateways.index') }}" class="btn btn-secondary">
                Back to Gateways
            </a>
        </div>
    </div>

    @if($logs->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Event Type</th>
                        <th>Status</th>
                        <th>Signature Verified</th>
                        <th>Processed</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->event_type }}</td>
                            <td>
                                <span class="badge bg-{{ $log->status === 'processed' ? 'success' : 'danger' }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td>
                                @if($log->signature_verified)
                                    <span class="badge bg-success">✓ Verified</span>
                                @else
                                    <span class="badge bg-danger">✗ Failed</span>
                                @endif
                            </td>
                            <td>
                                @if($log->processed_at)
                                    {{ $log->processed_at->format('M d, Y H:i:s') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                        data-bs-target="#payloadModal{{ $log->id }}">
                                    View Payload
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="payloadModal{{ $log->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Webhook Payload</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <pre><code>{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</code></pre>
                                        @if($log->error_message)
                                            <div class="alert alert-danger mt-3">
                                                <strong>Error:</strong> {{ $log->error_message }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
    @else
        <div class="alert alert-info">
            No webhook logs found for this gateway.
        </div>
    @endif
</div>
@endsection
