<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $keys = $merchant->apiKeys()->latest()->get();

        return view('merchant.api-keys.index', compact('merchant', 'keys'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
        ]);

        $permissions = $request->input('permissions');
        if (! is_array($permissions) || $permissions === []) {
            $permissions = ApiKey::defaultPermissions();
        }

        ['model' => $key, 'raw_key' => $rawKey] = ApiKey::generate(
            $merchant,
            $request->input('name'),
            $permissions,
        );

        AuditLog::record('api_key.created', $key);

        // Flash raw key once — cannot be retrieved again
        return back()->with('new_api_key', $rawKey)->with('success', 'API key created. Copy it now!');
    }

    public function revoke(Request $request, Merchant $merchant, ApiKey $apiKey)
    {
        abort_unless($apiKey->merchant_id === $merchant->id, 403);

        $apiKey->update(['is_active' => false]);
        AuditLog::record('api_key.revoked', $apiKey);

        return back()->with('success', 'API key revoked.');
    }
}
