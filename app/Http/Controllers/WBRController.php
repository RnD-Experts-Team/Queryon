<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Feedback;
use App\Models\ReimbursementRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WBRController extends Controller
{
    public function exportJson(string $store, string $date): JsonResponse
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        [$weekStart, $weekEnd] = $this->isoBusinessWeek($day);

        $storeNumber = $this->extractStoreNumber($store);

        $feedbacks = Feedback::query()
            ->whereBetween('submitted_at', [
                $weekStart->startOfDay(),
                $weekEnd->endOfDay(),
            ])
            ->get()
            ->filter(function (Feedback $feedback) use ($storeNumber) {
                return $this->storeLabelMatchesStoreNumber($feedback->store_label, $storeNumber);
            })
            ->values();

        $complaints = Complaint::query()
            ->whereBetween('submitted_at', [
                $weekStart->startOfDay(),
                $weekEnd->endOfDay(),
            ])
            ->get()
            ->filter(function (Complaint $complaint) use ($storeNumber) {
                return $this->storeLabelMatchesStoreNumber($complaint->store_label, $storeNumber);
            })
            ->values();

        $moneyOwed = $this->moneyOwedBetween($weekStart, $weekEnd)
            ->filter(function (ReimbursementRequest $request) use ($storeNumber) {
                return $this->storeLabelMatchesStoreNumber($request->store_label, $storeNumber);
            })
            ->values();

        return response()->json([
            'store' => $store,
            'store_number' => $storeNumber,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),

            'complaints' => $complaints,
            'feedbacks' => $feedbacks,
            'money_owed' => $moneyOwed,
        ]);
    }

    public function exportJsonBulk(Request $request): JsonResponse
    {
        $request->validate([
            'stores'     => ['required'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date'   => ['required', 'date_format:Y-m-d'],
        ]);

        $startDate = CarbonImmutable::parse($request->query('start_date'))->startOfDay();
        $endDate   = CarbonImmutable::parse($request->query('end_date'))->endOfDay();

        $storesInput = $request->input('stores');
        $filterAll   = ($storesInput === 'all');

        $requestedStoreNumbers = [];
        if (!$filterAll) {
            $slugs = is_array($storesInput) ? $storesInput : [$storesInput];
            foreach ($slugs as $slug) {
                if (preg_match('/-\s*(\d+)\s*$/', trim($slug), $m)) {
                    $requestedStoreNumbers[] = (int) $m[1];
                }
            }
        }

        $feedbacks = Feedback::query()
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->get();

        $complaints = Complaint::query()
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->get();

        $moneyOwed = $this->moneyOwedBetween($startDate, $endDate);

        $storeMap = [];

        $addRecord = function (?string $storeLabel, string $bucket, $record) use (
            &$storeMap, $filterAll, $requestedStoreNumbers
        ) {
            if ($storeLabel === null) return;
            if (!preg_match('/-\s*(\d+)\s*$/', $storeLabel, $m)) return;
            $storeNumber = (int) $m[1];

            if (!$filterAll && !in_array($storeNumber, $requestedStoreNumbers, true)) return;

            if (!isset($storeMap[$storeNumber])) {
                $storeMap[$storeNumber] = [
                    'store_label'  => $storeLabel,
                    'store_number' => $storeNumber,
                    'complaints'   => [],
                    'feedbacks'    => [],
                    'money_owed'   => [],
                ];
            }
            $storeMap[$storeNumber][$bucket][] = $record;
        };

        foreach ($feedbacks  as $r) { $addRecord($r->store_label, 'feedbacks',  $r); }
        foreach ($complaints as $r) { $addRecord($r->store_label, 'complaints', $r); }
        foreach ($moneyOwed  as $r) { $addRecord($r->store_label, 'money_owed', $r); }

        if (!$filterAll) {
            foreach ($requestedStoreNumbers as $n) {
                if (!isset($storeMap[$n])) {
                    $storeMap[$n] = [
                        'store_label'  => null,
                        'store_number' => $n,
                        'complaints'   => [],
                        'feedbacks'    => [],
                        'money_owed'   => [],
                    ];
                }
            }
        }

        ksort($storeMap);

        return response()->json([
            'start_date' => $startDate->toDateString(),
            'end_date'   => $endDate->toDateString(),
            'stores'     => array_values($storeMap),
        ]);
    }

    /**
     * Expense Date was hidden on the Cognito form, so recent requests only carry
     * the Week# the store manager typed. Those are matched through the week
     * instead of the date, which keeps older requests working unchanged.
     */
    private function moneyOwedBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        return ReimbursementRequest::query()
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($withoutDate) use ($from, $to) {
                        // Bounded so this never grows into a full table scan
                        $withoutDate->whereNull('expense_date')
                            ->whereBetween('created_at', [$from->subMonths(2), $to->addMonths(2)]);
                    });
            })
            ->get()
            ->filter(function (ReimbursementRequest $request) use ($from, $to) {
                $date = $this->effectiveDate($request);

                return $date !== null && $date->gte($from) && $date->lte($to);
            })
            ->values();
    }

    private function effectiveDate(ReimbursementRequest $request): ?CarbonImmutable
    {
        if ($request->expense_date) {
            return CarbonImmutable::parse($request->expense_date)->startOfDay();
        }

        if (! $request->week_number || ! $request->created_at) {
            return null;
        }

        $submittedAt = CarbonImmutable::parse($request->created_at);
        $date = $this->weekStartDate($submittedAt->year, (int) $request->week_number);

        // A late week submitted in early January belongs to the previous year
        if ($date->greaterThan($submittedAt)) {
            $date = $this->weekStartDate($submittedAt->year - 1, (int) $request->week_number);
        }

        return $date;
    }

    /**
     * First day of an accounting week, matching the Little Caesars calendar:
     * weeks run Tuesday to Monday and week 1 is the one holding January 1st.
     */
    private function weekStartDate(int $year, int $week): CarbonImmutable
    {
        return CarbonImmutable::create($year, 1, 1)
            ->startOfWeek(CarbonInterface::TUESDAY)
            ->addWeeks($week - 1);
    }

    private function isoBusinessWeek(CarbonImmutable $date): array
    {
        $start = $date->startOfWeek(CarbonInterface::TUESDAY);

        return [$start, $start->addDays(6)];
    }

    private function extractStoreNumber(string $store): int
    {
        $lastSegment = substr($store, -2);

        return (int) $lastSegment;
    }

    private function storeLabelMatchesStoreNumber(?string $storeLabel, int $storeNumber): bool
    {
        if ($storeLabel === null) {
            return false;
        }

        /*
         * Matches store labels like:
         * "Montgomery CIN - 12"
         * "Broad - 3"
         *
         * It extracts the number after the final dash.
         */
        if (!preg_match('/-\s*(\d+)\s*$/', $storeLabel, $matches)) {
            return false;
        }

        return (int) $matches[1] === $storeNumber;
    }
}