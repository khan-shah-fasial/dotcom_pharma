<?php

namespace App\Http\Controllers;

use App\Models\CreditUse;
use App\Models\User;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_customers'])->only('index');
        $this->middleware(['permission:ban_customer'])->only('markPaid');
    }

    public function markPaid(CreditUse $creditUse)
    {
        if (!CreditUse::markPaid($creditUse)) {
            flash(translate('This credit is already cleared.'))->warning();
            return back();
        }

        flash(translate('Credit marked as paid.'))->success();
        return back();
    }

    public function index(Request $request)
    {
        $tableReady = CreditUse::tableReady();
        $search = trim((string) $request->input('search', ''));
        $useStatus = in_array($request->input('use_status'), [CreditUse::STATUS_RESERVED, CreditUse::STATUS_RELEASED, CreditUse::STATUS_PAID], true)
            ? $request->input('use_status')
            : '';

        $accounts = User::query()
            ->with('details')
            ->where('user_type', 'customer')
            ->whereNotNull('step')
            ->where(function ($query) {
                $query->where('credit_limit', '>', 0)
                    ->orWhere('credit_days', '>', 0)
                    ->orWhereIn('credit_status', ['1', 'active', 1]);
            });

        if ($search !== '') {
            $like = '%' . $search . '%';
            $accounts->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like);
                    });
            });
        }

        $accounts = $accounts->orderByDesc('id')->paginate(20)->withQueryString();
        $outstanding = CreditUse::outstandingByUser($accounts->pluck('id'));

        $uses = null;
        if ($tableReady) {
            $uses = CreditUse::query()
                ->with(['user.details', 'order'])
                ->when($useStatus !== '', function ($query) use ($useStatus) {
                    $query->where('status', $useStatus);
                })
                ->when($search !== '', function ($query) use ($search) {
                    $like = '%' . $search . '%';
                    $query->whereHas('user', function ($user) use ($like) {
                        $user->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhereHas('details', function ($details) use ($like) {
                                $details->where('company_name', 'like', $like);
                            });
                    });
                })
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'uses_page')
                ->withQueryString();
        }

        return view('backend.customer.credits.index', compact(
            'accounts',
            'outstanding',
            'uses',
            'tableReady',
            'search',
            'useStatus'
        ));
    }
}
