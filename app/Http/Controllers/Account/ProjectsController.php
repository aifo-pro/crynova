<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/*
 * "Мои проекты" — the project list (cards/grid).
 */
class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $merchants = $user->accessibleMerchants()
            ->withCount('invoices')
            ->with('currencies', 'user')
            ->latest()
            ->get();

        return view('account.projects', compact('merchants'));
    }
}
