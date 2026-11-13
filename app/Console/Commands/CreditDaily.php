<?php

namespace App\Console\Commands;

use App\Models\CreditPayment;
use App\Models\CreditUse;
use App\Services\CreditNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditDaily extends Command
{
    // "Due soon" reminders go out when this many days are left (0 = the due date itself).
    public const REMINDER_DAYS = [7, 3, 1, 0];

    // Overdue reminders repeat this often until the credit is paid.
    public const OVERDUE_REPEAT_DAYS = 7;

    protected $signature = 'credit:daily {--dry-run : List what would be sent or changed without doing it}';

    protected $description = 'Send credit due-soon and overdue reminders, and close credit payments left pending at the gateway';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (CreditUse::remindersReady()) {
            $this->dueSoon($dryRun);
            $this->overdue($dryRun);
        } else {
            $this->warn('Credit reminders skipped: run sqlupdates/credit_notifications.sql on the database.');
        }

        $this->expirePending($dryRun);

        return self::SUCCESS;
    }

    protected function dueSoon(bool $dryRun): void
    {
        $today = now()->startOfDay();
        $dates = collect(self::REMINDER_DAYS)->map(function ($days) use ($today) {
            return $today->copy()->addDays($days)->toDateString();
        })->all();

        $uses = CreditUse::query()
            ->with(['user.details', 'order'])
            ->where('status', CreditUse::STATUS_RESERVED)
            ->whereNotNull('due_on')
            ->whereIn(DB::raw('DATE(due_on)'), $dates)
            ->where(function ($query) use ($today) {
                // At most one reminder a day, even if the job runs more than once.
                $query->whereNull('due_reminder_sent_at')
                    ->orWhere('due_reminder_sent_at', '<', $today);
            })
            ->get();

        foreach ($uses as $use) {
            $this->line('Due soon: credit #' . $use->id . ' due ' . $use->due_on->format('d-m-Y') . ' (' . $use->daysLeft() . ' days left)');
            if ($dryRun) {
                continue;
            }

            CreditNotifier::dueSoon($use);
            // Marked even if mail failed, so the customer does not get the same in-site notice twice a day.
            $use->due_reminder_sent_at = now();
            $use->save();
        }

        $this->info('Due-soon reminders: ' . $uses->count());
    }

    protected function overdue(bool $dryRun): void
    {
        $uses = CreditUse::query()
            ->with(['user.details', 'order'])
            ->where('status', CreditUse::STATUS_RESERVED)
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('overdue_reminder_sent_at')
                    ->orWhere('overdue_reminder_sent_at', '<=', now()->subDays(self::OVERDUE_REPEAT_DAYS));
            })
            ->get();

        foreach ($uses as $use) {
            $this->line('Overdue: credit #' . $use->id . ' was due ' . $use->due_on->format('d-m-Y'));
            if ($dryRun) {
                continue;
            }

            CreditNotifier::overdue($use);
            $use->overdue_reminder_sent_at = now();
            $use->save();
        }

        $this->info('Overdue reminders: ' . $uses->count());
    }

    protected function expirePending(bool $dryRun): void
    {
        if (!CreditPayment::tableReady()) {
            return;
        }

        $query = CreditPayment::query()
            ->where('status', CreditPayment::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(CreditPayment::PENDING_EXPIRY_HOURS));
        $count = (clone $query)->count();

        if (!$dryRun && $count > 0) {
            // Nothing was added or paid for these, so no balance changes; they are only closed.
            $query->update(['status' => CreditPayment::STATUS_FAILED, 'updated_at' => now()]);
        }

        $this->info('Pending credit payments closed: ' . $count);
    }
}
