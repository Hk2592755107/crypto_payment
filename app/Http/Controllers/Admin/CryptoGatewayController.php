<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoGateway;
use Illuminate\Http\Request;

class CryptoGatewayController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $gateways = CryptoGateway::all();
        return view('admin.crypto-gateways.index', ['gateways' => $gateways]);
    }

    public function create()
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        return view('admin.crypto-gateways.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $validated = $request->validate([
            'name' => 'required|string|unique:crypto_gateways',
            'slug' => 'required|string|unique:crypto_gateways',
            'description' => 'nullable|string',
            'api_endpoint' => 'required|string|url',
            'webhook_endpoint' => 'nullable|string|url',
            'supported_currencies' => 'required|array',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'transaction_fee_percentage' => 'numeric|min:0|max:100',
            'min_transaction_amount' => 'numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'confirmation_required' => 'integer|min:1',
            'api_key' => 'required|string',
            'webhook_secret' => 'nullable|string',
        ]);

        $config = [
            'api_key' => $validated['api_key'],
            'webhook_secret' => $validated['webhook_secret'] ?? null,
        ];

        CryptoGateway::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'api_endpoint' => $validated['api_endpoint'],
            'webhook_endpoint' => $validated['webhook_endpoint'],
            'supported_currencies' => $validated['supported_currencies'],
            'is_active' => $validated['is_active'] ?? false,
            'priority' => $validated['priority'] ?? 0,
            'transaction_fee_percentage' => $validated['transaction_fee_percentage'] ?? 0,
            'min_transaction_amount' => $validated['min_transaction_amount'] ?? 0,
            'max_transaction_amount' => $validated['max_transaction_amount'] ?? null,
            'confirmation_required' => $validated['confirmation_required'] ?? 1,
            'config' => $config,
        ]);

        return redirect()->route('admin.crypto-gateways.index')->with('success', 'Gateway created successfully');
    }

    public function edit(CryptoGateway $crypto_gateway)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        return view('admin.crypto-gateways.edit', ['gateway' => $crypto_gateway]);
    }

    public function update(Request $request, CryptoGateway $crypto_gateway)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $gateway = $crypto_gateway;
        $validated = $request->validate([
            'name' => 'required|string|unique:crypto_gateways,name,' . $gateway->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'transaction_fee_percentage' => 'numeric|min:0|max:100',
            'min_transaction_amount' => 'numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'confirmation_required' => 'integer|min:1',
            'api_key' => 'required|string',
            'webhook_secret' => 'nullable|string',
        ]);

        $config = [
            'api_key' => $validated['api_key'],
            'webhook_secret' => $validated['webhook_secret'] ?? null,
        ];

        $gateway->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? false,
            'priority' => $validated['priority'] ?? 0,
            'transaction_fee_percentage' => $validated['transaction_fee_percentage'] ?? 0,
            'min_transaction_amount' => $validated['min_transaction_amount'] ?? 0,
            'max_transaction_amount' => $validated['max_transaction_amount'] ?? null,
            'confirmation_required' => $validated['confirmation_required'] ?? 1,
            'config' => $config,
        ]);

        return redirect()->route('admin.crypto-gateways.index')->with('success', 'Gateway updated successfully');
    }

    public function destroy(CryptoGateway $crypto_gateway)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $crypto_gateway->delete();
        return redirect()->route('admin.crypto-gateways.index')->with('success', 'Gateway deleted successfully');
    }

    public function transactions(CryptoGateway $crypto_gateway)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $transactions = $crypto_gateway->transactions()->latest()->paginate(20);
        return view('admin.crypto-gateways.transactions', [
            'gateway' => $crypto_gateway,
            'transactions' => $transactions,
        ]);
    }

    public function webhookLogs(CryptoGateway $crypto_gateway)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        $logs = $crypto_gateway->webhookLogs()->latest()->paginate(20);
        return view('admin.crypto-gateways.webhook-logs', [
            'gateway' => $crypto_gateway,
            'logs' => $logs,
        ]);
    }
}
