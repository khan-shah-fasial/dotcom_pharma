<?php

namespace App\Http\Controllers;

use App\Models\CreditPayment;
use App\Models\CreditUse;
use App\Models\Order;
use App\Models\User;
use App\Services\CreditNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    protected const ACCOUNT_SORTS = ['id', 'name', 'credit_status', 'credit_days', 'credit_limit', 'credit_prepaid', 'available_sum', 'outstanding_sum'];

    protected const USE_SORTS = ['id', 'customer', 'order', 'amount', 'credit_days', 'started_on', 'due_on', 'status', 'kind', 'created_at'];

    protected const PAYMENT_SORTS = ['id', 'customer', 'purpose', 'amount', 'payment_method', 'status', 'created_at'];

    public function __construct()
    {
        $this->middleware(['permission:view_all_customers'])->only('index', 'payments', 'customerSearch');
        $this->middleware(['permission:ban_customer'])->only('markPaid');
    }

    public function markPaid(Request $request, CreditUse $creditUse)
    {
        $request->validate([
            'payment_method' => ['nullable', 'string', 'in:' . implode(',', array_keys(CreditPayment::offlineMethods()))],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            // Left empty, the whole open amount is paid. Less than that is a part payment.
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
        ]);

        $payment = null;
        $paid = 0.0;
        $remaining = 0.0;
        $result = DB::transaction(function () use ($request, $creditUse, &$payment, &$paid, &$remaining) {
            $owner = User::query()->whereKey($creditUse->user_id)->lockForUpdate()->first();
            $locked = CreditUse::query()->whereKey($creditUse->id)->lockForUpdate()->first();
            if (!$owner || !$locked || $locked->status !== CreditUse::STATUS_RESERVED) {
                return 'cleared';
            }

            $owed = round((float) $locked->amount, 2);
            $amount = $request->filled('amount') ? round((float) $request->input('amount'), 2) : $owed;
            if ($amount - $owed > 0.009) {
                return 'too_much';
            }

            $paid = CreditUse::applyPayment($owner, [$locked->id], $amount);
            if ($paid <= 0) {
                return 'cleared';
            }
            $remaining = round($owed - $paid, 2);

            // Keep a record of the money received, so it shows in credit payments for admin and customer.
            if (CreditPayment::tableReady()) {
                $payment = CreditPayment::query()->create([
                    'user_id' => $creditUse->user_id,
                    'purpose' => CreditPayment::PURPOSE_SETTLE,
                    'amount' => $paid,
                    'payment_method' => $request->input('payment_method') ?: 'other',
                    'payment_details' => CreditPayment::adminDetails($request->input('reference'), $request->input('note')),
                    'credit_use_ids' => (string) $creditUse->id,
                    'status' => CreditPayment::STATUS_COMPLETED,
                ]);
            }

            return 'ok';
        });

        if ($result === 'too_much') {
            flash(translate('The amount is more than what is owed on this credit.'))->warning();
            return back();
        }

        if ($result !== 'ok') {
            flash(translate('This credit is already cleared.'))->warning();
            return back();
        }

        if ($creditUse->user) {
            CreditNotifier::paymentReceived($creditUse->user, $paid, $payment, false);
        }

        flash($remaining > 0.009
            ? translate('Part payment recorded. Still owed') . ': ' . single_price($remaining)
            : translate('Credit marked as paid.'))->success();
        return back();
    }

    /**
     * Customer lookup for "Add credit account". Returns up to 20 customers with their current credit.
     */
    public function customerSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%' . $term . '%';
        $customers = User::query()
            ->with('details')
            ->where('user_type', 'customer')
            ->whereNotNull('step')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like);
                    });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => optional($user->details)->company_name ?: $user->name,
                'contact' => $user->phone ?: $user->email,
                'status' => $user->creditIsActive() ? 'active' : 'deactive',
                'limit' => (int) round((float) ($user->credit_limit ?? 0)),
                'days' => (int) ($user->credit_days ?? 0),
                'has_credit' => $user->creditIsActive() || (float) $user->credit_limit > 0 || (int) $user->credit_days > 0,
            ];
        })->values());
    }

    public function payments(Request $request)
    {
        $tableReady = CreditPayment::tableReady();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'purpose' => in_array($request->input('purpose'), CreditPayment::purposes(), true) ? $request->input('purpose') : '',
            'status' => in_array($request->input('status'), CreditPayment::statuses(), true) ? $request->input('status') : '',
            'method' => trim((string) $request->input('method', '')),
            'source' => in_array($request->input('source'), ['online', 'admin'], true) ? $request->input('source') : '',
            'date_from' => $this->dateOrEmpty($request->input('date_from')),
            'date_to' => $this->dateOrEmpty($request->input('date_to')),
        ];
        $filtersApplied = collect($filters)->contains(function ($value) {
            return $value !== '';
        });

        $sortBy = in_array($request->input('sort_by'), self::PAYMENT_SORTS, true) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $payments = null;
        $methods = collect();
        $totals = ['settle' => 0.0, 'topup' => 0.0];
        if ($tableReady) {
            $query = $this->paymentsQuery($filters);

            $completed = (clone $query)->where('status', CreditPayment::STATUS_COMPLETED);
            $totals['settle'] = round((float) (clone $completed)->where('purpose', CreditPayment::PURPOSE_SETTLE)->sum('amount'), 2);
            $totals['topup'] = round((float) (clone $completed)->where('purpose', CreditPayment::PURPOSE_TOPUP)->sum('amount'), 2);

            if ($sortBy === 'customer') {
                $query->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'credit_payments.user_id')->limit(1),
                    $sortDir
                );
            } else {
                $query->orderBy('credit_payments.' . $sortBy, $sortDir);
            }
            if ($sortBy !== 'id') {
                $query->orderBy('credit_payments.id', 'desc');
            }

            $payments = $query->with('user.details')->paginate(20)->withQueryString();
            $methods = CreditPayment::query()
                ->whereNotNull('payment_method')
                ->distinct()
                ->orderBy('payment_method')
                ->pluck('payment_method');
        }

        return view('backend.customer.credits.payments', compact(
            'tableReady',
            'payments',
            'methods',
            'totals',
            'filters',
            'filtersApplied',
            'sortBy',
            'sortDir'
        ));
    }

    protected function paymentsQuery(array $filters)
    {
        $query = CreditPayment::query()->select('credit_payments.*');

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($query) use ($like) {
                $query->whereHas('user', function ($user) use ($like) {
                    $user->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('details', function ($details) use ($like) {
                            $details->where('company_name', 'like', $like);
                        });
                })->orWhere('payment_details', 'like', $like);
            });
        }

        foreach (['purpose' => 'purpose', 'status' => 'status', 'method' => 'payment_method'] as $key => $column) {
            if ($filters[$key] !== '') {
                $query->where($column, $filters[$key]);
            }
        }

        if ($filters['source'] === 'admin') {
            $query->where('payment_details', 'like', '%"source":"admin"%');
        } elseif ($filters['source'] === 'online') {
            $query->where(function ($query) {
                $query->whereNull('payment_details')
                    ->orWhere('payment_details', 'not like', '%"source":"admin"%');
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
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

        $summary = $this->summary($tableReady, $prepaidReady);

        return view('backend.customer.credits.index', compact(
            'summary',
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

    /**
     * Totals across all customers for the summary strip on the Credit page (not affected by filters).
     */
    protected function summary(bool $tableReady, bool $prepaidReady): array
    {
        $summary = [
            'owed' => 0.0, 'owed_count' => 0,
            'overdue' => 0.0, 'overdue_count' => 0, 'overdue_customers' => 0,
            'due_week' => 0.0, 'due_week_count' => 0,
            'topup' => 0.0,
            'active' => 0,
        ];

        $summary['active'] = User::query()->where('user_type', 'customer')->where('credit_status', 'active')->count();
        if ($prepaidReady) {
            $summary['topup'] = round((float) User::query()->where('user_type', 'customer')->sum('credit_prepaid'), 2);
        }
        if (!$tableReady) {
            return $summary;
        }

        $today = now()->toDateString();
        $open = CreditUse::query()->where('status', CreditUse::STATUS_RESERVED);

        $summary['owed'] = round((float) (clone $open)->sum('amount'), 2);
        $summary['owed_count'] = (clone $open)->count();

        $overdue = (clone $open)->whereNotNull('due_on')->whereDate('due_on', '<', $today);
        $summary['overdue'] = round((float) (clone $overdue)->sum('amount'), 2);
        $summary['overdue_count'] = (clone $overdue)->count();
        $summary['overdue_customers'] = (clone $overdue)->distinct()->count('user_id');

        $week = (clone $open)->whereNotNull('due_on')
            ->whereDate('due_on', '>=', $today)
            ->whereDate('due_on', '<=', now()->addDays(7)->toDateString());
        $summary['due_week'] = round((float) (clone $week)->sum('amount'), 2);
        $summary['due_week_count'] = (clone $week)->count();

        return $summary;
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
