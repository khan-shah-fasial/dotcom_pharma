<?php

namespace App\Http\Controllers;

use App\Models\CreditUse;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    protected const ACCOUNT_SORTS = ['id', 'name', 'credit_status', 'credit_days', 'credit_limit', 'credit_prepaid', 'available_sum', 'outstanding_sum'];

    protected const USE_SORTS = ['id', 'customer', 'order', 'amount', 'credit_days', 'started_on', 'due_on', 'status', 'kind', 'created_at'];

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
        $prepaidReady = CreditUse::prepaidColumnReady();
        $kindReady = CreditUse::kindColumnReady();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'credit_status' => in_array($request->input('credit_status'), ['active', 'inactive'], true) ? $request->input('credit_status') : '',
            'balance' => in_array($request->input('balance'), ['outstanding', 'overdue', 'prepaid', 'clear'], true) ? $request->input('balance') : '',
            'use_status' => in_array($request->input('use_status'), [CreditUse::STATUS_RESERVED, CreditUse::STATUS_RELEASED, CreditUse::STATUS_PAID, 'overdue'], true) ? $request->input('use_status') : '',
            'kind' => $kindReady && in_array($request->input('kind'), [CreditUse::KIND_FACILITY, CreditUse::KIND_PREPAID], true) ? $request->input('kind') : '',
            'order_code' => trim((string) $request->input('order_code', '')),
            'due_from' => $this->dateOrEmpty($request->input('due_from')),
            'due_to' => $this->dateOrEmpty($request->input('due_to')),
        ];

        $sortBy = in_array($request->input('sort_by'), self::ACCOUNT_SORTS, true) ? $request->input('sort_by') : 'id';
        if ($sortBy === 'credit_prepaid' && !$prepaidReady) {
            $sortBy = 'id';
        }
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $usesSortBy = in_array($request->input('uses_sort_by'), self::USE_SORTS, true) ? $request->input('uses_sort_by') : 'id';
        if ($usesSortBy === 'kind' && !$kindReady) {
            $usesSortBy = 'id';
        }
        $usesSortDir = strtolower((string) $request->input('uses_sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $accounts = $this->accountsQuery($filters, $tableReady, $prepaidReady);
        $this->sortAccounts($accounts, $sortBy, $sortDir);
        $accounts = $accounts->paginate(20)->withQueryString();
        $outstanding = CreditUse::outstandingByUser($accounts->pluck('id'));
        $overdueIds = $tableReady
            ? CreditUse::query()
                ->whereIn('user_id', $accounts->pluck('id'))
                ->where('status', CreditUse::STATUS_RESERVED)
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString())
                ->distinct()
                ->pluck('user_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all()
            : [];

        $uses = null;
        if ($tableReady) {
            $usesQuery = $this->usesQuery($filters, $kindReady);
            $this->sortUses($usesQuery, $usesSortBy, $usesSortDir);
            $uses = $usesQuery->paginate(20, ['credit_uses.*'], 'uses_page')->withQueryString();
        }

        $filtersApplied = collect($filters)->contains(function ($value) {
            return $value !== '';
        });

        return view('backend.customer.credits.index', compact(
            'accounts',
            'outstanding',
            'overdueIds',
            'uses',
            'tableReady',
            'prepaidReady',
            'kindReady',
            'filters',
            'filtersApplied',
            'sortBy',
            'sortDir',
            'usesSortBy',
            'usesSortDir'
        ));
    }

    protected function accountsQuery(array $filters, bool $tableReady, bool $prepaidReady)
    {
        $outstandingSql = $tableReady
            ? "(SELECT COALESCE(SUM(cu.amount), 0) FROM credit_uses cu WHERE cu.user_id = users.id AND cu.status = 'reserved')"
            : '0';

        $query = User::query()
            ->with('details')
            ->select('users.*')
            ->selectRaw($outstandingSql . ' AS outstanding_sum')
            ->selectRaw("CASE WHEN users.credit_status IN ('1', 'active') THEN GREATEST(COALESCE(users.credit_limit, 0) - " . $outstandingSql . ', 0) ELSE 0 END AS available_sum')
            ->where('user_type', 'customer')
            ->whereNotNull('step')
            ->where(function ($query) use ($prepaidReady) {
                $query->where('credit_limit', '>', 0)
                    ->orWhere('credit_days', '>', 0)
                    ->orWhereIn('credit_status', ['1', 'active', 1]);
                if ($prepaidReady) {
                    $query->orWhere('credit_prepaid', '>', 0);
                }
            });

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like);
                    });
            });
        }

        if ($filters['credit_status'] === 'active') {
            $query->whereIn('credit_status', ['1', 'active']);
        } elseif ($filters['credit_status'] === 'inactive') {
            $query->where(function ($query) {
                $query->whereNull('credit_status')->orWhereNotIn('credit_status', ['1', 'active']);
            });
        }

        if ($filters['balance'] === 'prepaid' && $prepaidReady) {
            $query->where('credit_prepaid', '>', 0);
        } elseif ($tableReady && in_array($filters['balance'], ['outstanding', 'overdue', 'clear'], true)) {
            $method = $filters['balance'] === 'clear' ? 'whereNotExists' : 'whereExists';
            $overdue = $filters['balance'] === 'overdue';
            $query->{$method}(function ($sub) use ($overdue) {
                $sub->select(DB::raw(1))
                    ->from('credit_uses')
                    ->whereColumn('credit_uses.user_id', 'users.id')
                    ->where('credit_uses.status', CreditUse::STATUS_RESERVED);
                if ($overdue) {
                    $sub->whereNotNull('credit_uses.due_on')
                        ->whereDate('credit_uses.due_on', '<', now()->toDateString());
                }
            });
        }

        return $query;
    }

    protected function sortAccounts($query, string $sortBy, string $sortDir): void
    {
        if (in_array($sortBy, ['available_sum', 'outstanding_sum'], true)) {
            $query->orderBy($sortBy, $sortDir);
        } elseif (in_array($sortBy, ['credit_limit', 'credit_prepaid', 'credit_days'], true)) {
            $query->orderByRaw('CAST(COALESCE(users.' . $sortBy . ', 0) AS DECIMAL(20,2)) ' . $sortDir);
        } else {
            $query->orderBy('users.' . $sortBy, $sortDir);
        }

        if ($sortBy !== 'id') {
            $query->orderBy('users.id', 'desc');
        }
    }

    protected function usesQuery(array $filters, bool $kindReady)
    {
        $query = CreditUse::query()->with(['user.details', 'order']);

        if ($filters['use_status'] === 'overdue') {
            $query->where('status', CreditUse::STATUS_RESERVED)
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString());
        } elseif ($filters['use_status'] !== '') {
            $query->where('status', $filters['use_status']);
        }

        if ($kindReady && $filters['kind'] !== '') {
            $query->where('kind', $filters['kind']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->whereHas('user', function ($user) use ($like) {
                $user->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like);
                    });
            });
        }

        if ($filters['order_code'] !== '') {
            $code = '%' . $filters['order_code'] . '%';
            $query->whereHas('order', function ($order) use ($code) {
                $order->where('code', 'like', $code);
            });
        }

        if ($filters['due_from'] !== '') {
            $query->whereDate('due_on', '>=', $filters['due_from']);
        }
        if ($filters['due_to'] !== '') {
            $query->whereDate('due_on', '<=', $filters['due_to']);
        }

        return $query;
    }

    protected function sortUses($query, string $sortBy, string $sortDir): void
    {
        if ($sortBy === 'customer') {
            $query->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'credit_uses.user_id')->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'order') {
            $query->orderBy(
                Order::query()->select('code')->whereColumn('orders.id', 'credit_uses.order_id')->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy('credit_uses.' . $sortBy, $sortDir);
        }

        if ($sortBy !== 'id') {
            $query->orderBy('credit_uses.id', 'desc');
        }
    }

    protected function dateOrEmpty($value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }
}
