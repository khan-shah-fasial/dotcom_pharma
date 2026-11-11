<?php

namespace App\Http\Controllers;

use App\Models\CreditPayment;
use App\Models\CreditUse;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

class CustomerCreditController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $usesReady = CreditUse::tableReady();
        $paymentsReady = CreditPayment::tableReady();

        $filters = [
            'purpose' => in_array($request->input('purpose'), [CreditPayment::PURPOSE_TOPUP, CreditPayment::PURPOSE_SETTLE], true) ? $request->input('purpose') : '',
            'method' => trim((string) $request->input('method', '')),
            'date_from' => $this->dateOrEmpty($request->input('date_from')),
            'date_to' => $this->dateOrEmpty($request->input('date_to')),
        ];
        $filtersApplied = collect($filters)->contains(function ($value) {
            return $value !== '';
        });

        $openSortBy = in_array($request->input('open_sort_by'), ['order', 'amount', 'due_on', 'created_at'], true) ? $request->input('open_sort_by') : 'due_on';
        $openSortDir = strtolower((string) $request->input('open_sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortBy = in_array($request->input('sort_by'), ['created_at', 'purpose', 'amount', 'payment_method'], true) ? $request->input('sort_by') : 'created_at';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $openUses = collect();
        if ($usesReady) {
            $openQuery = CreditUse::query()
                ->with('order')
                ->where('user_id', $user->id)
                ->where('status', CreditUse::STATUS_RESERVED);
            if ($openSortBy === 'order') {
                $openQuery->orderBy(
                    \App\Models\Order::query()->select('code')->whereColumn('orders.id', 'credit_uses.order_id')->limit(1),
                    $openSortDir
                );
            } elseif ($openSortBy === 'due_on') {
                // Credit not yet dispatched has no due date; keep it after dated credit.
                $openQuery->orderByRaw('due_on IS NULL')->orderBy('due_on', $openSortDir);
            } else {
                $openQuery->orderBy($openSortBy, $openSortDir);
            }
            $openUses = $openQuery->orderBy('id')->get();
        }

        $payments = null;
        if ($paymentsReady) {
            $payments = CreditPayment::query()
                ->where('user_id', $user->id)
                ->where('status', CreditPayment::STATUS_COMPLETED)
                ->when($filters['purpose'] !== '', function ($query) use ($filters) {
                    $query->where('purpose', $filters['purpose']);
                })
                ->when($filters['method'] !== '', function ($query) use ($filters) {
                    $query->where('payment_method', $filters['method']);
                })
                ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                    $query->whereDate('created_at', '>=', $filters['date_from']);
                })
                ->when($filters['date_to'] !== '', function ($query) use ($filters) {
                    $query->whereDate('created_at', '<=', $filters['date_to']);
                })
                ->orderBy($sortBy, $sortDir)
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();
        }

        $paymentMethodsUsed = $paymentsReady
            ? CreditPayment::query()
                ->where('user_id', $user->id)
                ->where('status', CreditPayment::STATUS_COMPLETED)
                ->whereNotNull('payment_method')
                ->distinct()
                ->orderBy('payment_method')
                ->pluck('payment_method')
            : collect();

        return view('frontend.user.credit.index', [
            'filters' => $filters,
            'filtersApplied' => $filtersApplied,
            'openSortBy' => $openSortBy,
            'openSortDir' => $openSortDir,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'paymentMethodsUsed' => $paymentMethodsUsed,
            'user' => $user,
            'usesReady' => $usesReady,
            'paymentsReady' => $paymentsReady,
            'openUses' => $openUses,
            'payments' => $payments,
            'available' => CreditUse::availableFor($user),
            'prepaid' => CreditUse::prepaidFor($user),
            'prepaidReady' => CreditUse::prepaidColumnReady() && CreditUse::kindColumnReady(),
            'outstanding' => CreditUse::outstandingFor($user),
            'canTopUp' => $user->creditIsActive() && (int) $user->credit_days >= 1,
            'blocked' => CreditUse::blocksPurchase($user),
            'paymentMethods' => get_activate_payment_methods(),
        ]);
    }

    public function topup(Request $request)
    {
        if ($denied = $this->paymentsNotReady()) {
            return $denied;
        }

        $user = Auth::user();
        if (!$user->creditIsActive() || (int) $user->credit_days < 1) {
            flash(translate('Credit is not active, so it cannot be topped up.'))->warning();
            return back();
        }

        if (!CreditUse::prepaidColumnReady() || !CreditUse::kindColumnReady()) {
            flash(translate('Credit top up is not ready yet. Run sqlupdates/credit_prepaid.sql on the database.'))->warning();
            return back();
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'payment_option' => ['required', 'string'],
        ]);

        if ($denied = $this->unknownPaymentMethod($request)) {
            return $denied;
        }

        $payment = CreditPayment::query()->create([
            'user_id' => $user->id,
            'purpose' => CreditPayment::PURPOSE_TOPUP,
            'amount' => round((float) $request->input('amount'), 2),
            'payment_method' => $request->input('payment_option'),
            'status' => CreditPayment::STATUS_PENDING,
        ]);

        return $this->startGateway($request, $payment);
    }

    public function pay(Request $request)
    {
        if ($denied = $this->paymentsNotReady()) {
            return $denied;
        }

        $request->validate([
            'credit_use_ids' => ['required', 'array', 'min:1'],
            'credit_use_ids.*' => ['integer'],
            'payment_option' => ['required', 'string'],
        ]);

        if ($denied = $this->unknownPaymentMethod($request)) {
            return $denied;
        }

        $ids = collect($request->input('credit_use_ids'))->map(function ($id) {
            return (int) $id;
        })->unique()->values();
        $uses = CreditUse::query()
            ->where('user_id', Auth::id())
            ->where('status', CreditUse::STATUS_RESERVED)
            ->whereIn('id', $ids)
            ->get();

        if ($uses->count() !== $ids->count()) {
            flash(translate('Choose an open credit to pay.'))->warning();
            return back();
        }

        $amount = round((float) $uses->sum('amount'), 2);
        if ($amount <= 0) {
            flash(translate('Choose an open credit to pay.'))->warning();
            return back();
        }

        $payment = CreditPayment::query()->create([
            'user_id' => Auth::id(),
            'purpose' => CreditPayment::PURPOSE_SETTLE,
            'amount' => $amount,
            'payment_method' => $request->input('payment_option'),
            'credit_use_ids' => $uses->pluck('id')->implode(','),
            'status' => CreditPayment::STATUS_PENDING,
        ]);

        $request->merge(['amount' => $amount]);

        return $this->startGateway($request, $payment);
    }

    public function paymentDone($payment_data, $payment_details)
    {
        $id = $this->creditPaymentId($payment_data);
        if (!$id) {
            return null;
        }

        if (!CreditPayment::tableReady()) {
            flash(translate('Credit payments are not ready yet. Run sqlupdates/credit_payments.sql on the database.'))->warning();
            return redirect()->route('customer.credit.index');
        }

        return DB::transaction(function () use ($id, $payment_data, $payment_details) {
            $payment = CreditPayment::query()->whereKey($id)->lockForUpdate()->first();
            if (!$payment) {
                $this->forgetPaymentSession();
                flash(translate('Credit payment could not be completed.'))->warning();
                return redirect()->route('customer.credit.index');
            }

            if ($payment->status === CreditPayment::STATUS_COMPLETED) {
                $this->forgetPaymentSession();
                flash(translate('Credit payment completed'))->success();
                return redirect()->route('customer.credit.index');
            }

            if (!Auth::check() || (int) Auth::id() !== (int) $payment->user_id) {
                flash(translate('Credit payment could not be completed.'))->warning();
                return redirect()->route('customer.credit.index');
            }

            $paidAmount = $this->paidAmount($payment_data);
            if ($paidAmount !== null && abs($paidAmount - (float) $payment->amount) > 0.009) {
                $this->failPayment($payment, $payment_details);
                flash(translate('The paid amount does not match this credit payment.'))->warning();
                return redirect()->route('customer.credit.index');
            }

            $user = User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
            if (!$user) {
                $this->failPayment($payment, $payment_details);
                flash(translate('Credit payment could not be completed.'))->warning();
                return redirect()->route('customer.credit.index');
            }

            if ($payment->purpose === CreditPayment::PURPOSE_TOPUP) {
                if (!CreditUse::prepaidColumnReady() || !CreditUse::kindColumnReady()) {
                    $this->failPayment($payment, $payment_details);
                    flash(translate('Credit top up is not ready yet. Run sqlupdates/credit_prepaid.sql on the database.'))->warning();
                    return redirect()->route('customer.credit.index');
                }

                $user->credit_prepaid = round((float) ($user->credit_prepaid ?? 0) + (float) $payment->amount, 2);
                $user->save();
            } elseif ($payment->purpose === CreditPayment::PURPOSE_SETTLE) {
                if (!$this->settleUses($payment, $user)) {
                    $this->failPayment($payment, $payment_details);
                    flash(translate('This payment could not be matched to an open credit. Contact the shop with your payment reference.'))->warning();
                    return redirect()->route('customer.credit.index');
                }
            } else {
                $this->failPayment($payment, $payment_details);
                flash(translate('Credit payment could not be completed.'))->warning();
                return redirect()->route('customer.credit.index');
            }

            $payment->status = CreditPayment::STATUS_COMPLETED;
            $payment->payment_details = $this->detailsText($payment_details);
            $payment->save();
            $this->forgetPaymentSession();

            flash($payment->purpose === CreditPayment::PURPOSE_TOPUP
                ? translate('Credit topped up.')
                : translate('Credit paid.'))->success();

            return redirect()->route('customer.credit.index');
        });
    }

    protected function startGateway(Request $request, CreditPayment $payment)
    {
        $request->merge([
            'amount' => $payment->amount,
            'payment_option' => $payment->payment_method,
        ]);
        $request->session()->put('payment_type', 'wallet_payment');
        $request->session()->put('payment_data', [
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'credit_payment_id' => $payment->id,
            'purpose' => $payment->purpose,
            'user_id' => $payment->user_id,
        ]);

        $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $payment->payment_method))) . 'Controller';
        if (class_exists($decorator)) {
            return (new $decorator)->pay($request);
        }

        $payment->status = CreditPayment::STATUS_FAILED;
        $payment->save();
        $this->forgetPaymentSession();
        flash(translate('This payment method is not available for credit.'))->warning();

        return redirect()->route('customer.credit.index');
    }

    protected function settleUses(CreditPayment $payment, User $user): bool
    {
        $ids = collect(explode(',', (string) $payment->credit_use_ids))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->unique()
            ->values();
        if ($ids->isEmpty() || !CreditUse::tableReady()) {
            return false;
        }

        $uses = CreditUse::query()
            ->where('user_id', $user->id)
            ->where('status', CreditUse::STATUS_RESERVED)
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
        if ($uses->count() !== $ids->count()) {
            return false;
        }

        $sum = round((float) $uses->sum('amount'), 2);
        if (abs($sum - (float) $payment->amount) > 0.009) {
            return false;
        }

        foreach ($uses as $use) {
            $use->status = CreditUse::STATUS_PAID;
            $use->released_at = now();
            $use->save();
        }

        CreditUse::rememberAvailable($user->fresh());

        return true;
    }

    protected function paymentsNotReady()
    {
        if (CreditPayment::tableReady() && CreditUse::tableReady()) {
            return null;
        }

        flash(translate('Credit payments are not ready yet. Run sqlupdates/credit_payments.sql on the database.'))->warning();

        return back();
    }

    protected function unknownPaymentMethod(Request $request)
    {
        $methods = get_activate_payment_methods()->pluck('name')->all();
        if (in_array($request->input('payment_option'), $methods, true)) {
            return null;
        }

        flash(translate('Please select a payment method.'))->warning();

        return back();
    }

    protected function creditPaymentId($payment_data): ?int
    {
        $data = $this->asArray($payment_data);
        $session = Session::get('payment_data');
        $session = is_array($session) ? $session : [];
        $id = $data['credit_payment_id'] ?? ($session['credit_payment_id'] ?? null);

        return $id ? (int) $id : null;
    }

    protected function paidAmount($payment_data): ?float
    {
        $data = $this->asArray($payment_data);
        if (!array_key_exists('amount', $data) || $data['amount'] === null || $data['amount'] === '') {
            return null;
        }

        return round((float) $data['amount'], 2);
    }

    protected function asArray($payment_data): array
    {
        if (is_array($payment_data)) {
            return $payment_data;
        }

        if (is_object($payment_data)) {
            return (array) $payment_data;
        }

        return [];
    }

    protected function detailsText($payment_details): string
    {
        if (is_string($payment_details)) {
            return $payment_details;
        }

        return json_encode($payment_details);
    }

    protected function failPayment(CreditPayment $payment, $payment_details): void
    {
        $payment->status = CreditPayment::STATUS_FAILED;
        $payment->payment_details = $this->detailsText($payment_details);
        $payment->save();
        $this->forgetPaymentSession();
    }

    protected function dateOrEmpty($value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    protected function forgetPaymentSession(): void
    {
        Session::forget('payment_data');
        Session::forget('payment_type');
    }
}
