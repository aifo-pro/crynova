<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\IntegrationModule;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/*
 * Account-level integration hub pages. Modules & Brandbook render real content
 * scoped to a selected project; API is a docs+keys page; Exchange is a placeholder.
 */
class HubController extends Controller
{
    public function api(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.api', compact('merchants', 'merchant'));
    }

    public function modules()
    {
        // Curated, admin-managed download catalog — no merchant data here.
        $modules = IntegrationModule::where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return view('account.integration.modules', compact('modules'));
    }

    public function downloadModule(IntegrationModule $module)
    {
        abort_unless($module->isDownloadable(), 404);

        if ($module->external_url) {
            return redirect()->away($module->external_url);
        }

        return Storage::disk('public')->download($module->file_path, $module->slug . '.' . pathinfo($module->file_path, PATHINFO_EXTENSION));
    }

    public function widget(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.widget', compact('merchants', 'merchant'));
    }

    public function brandbook(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.brandbook', compact('merchants', 'merchant'));
    }

    public function exchange(Request $request)
    {
        return view('account.integration.exchange');
    }

    /** Resolve the selected project from ?project= or default to the first. */
    private function selected(Request $request, $merchants): ?Merchant
    {
        if ($id = $request->integer('project')) {
            return $merchants->firstWhere('id', $id) ?? $merchants->first();
        }

        return $merchants->first();
    }
}
