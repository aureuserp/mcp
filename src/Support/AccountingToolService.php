<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Models\BankStatement;
use Webkul\Account\Models\BankStatementLine;
use Webkul\Account\Models\Move;
use Webkul\Accounting\Models\Bill;
use Webkul\Accounting\Models\Invoice;
use Webkul\Accounting\Models\JournalEntry;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;

class AccountingToolService
{
    use HasQueryHelpers;

    public function accountReceivableSummary(): array
    {
        $model = Move::class;
        $today = Carbon::today()->toDateString();
        $openScope = fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);
        $overdueScope = fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment'])
            ->whereDate('invoice_date_due', '<', $today);
        $openCount = $this->count($model, $openScope);
        $openAmount = $this->sum($model, 'amount_residual', $openScope);

        return [
            'open_count'              => $openCount,
            'open_amount'             => $openAmount,
            'overdue_count'           => $this->count($model, $overdueScope),
            'overdue_amount'          => $this->sum($model, 'amount_residual', $overdueScope),
            'avg_open_amount'         => $this->ratio($openAmount, $openCount),
            'top_receivable_partners' => $this->groupSumLimit($model, 'partner_id', 'amount_residual', $openScope),
            'top_salespersons_by_ar'  => $this->groupSumLimit($model, 'invoice_user_id', 'amount_residual', $openScope),
        ];
    }

    public function accountPayableSummary(): array
    {
        $model = Move::class;
        $today = Carbon::today()->toDateString();
        $openScope = fn (Builder $query) => $query
            ->whereIn('move_type', ['in_invoice', 'in_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);
        $overdueScope = fn (Builder $query) => $query
            ->whereIn('move_type', ['in_invoice', 'in_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment'])
            ->whereDate('invoice_date_due', '<', $today);
        $openCount = $this->count($model, $openScope);
        $openAmount = $this->sum($model, 'amount_residual', $openScope);

        return [
            'open_count'           => $openCount,
            'open_amount'          => $openAmount,
            'overdue_count'        => $this->count($model, $overdueScope),
            'overdue_amount'       => $this->sum($model, 'amount_residual', $overdueScope),
            'avg_open_amount'      => $this->ratio($openAmount, $openCount),
            'top_payable_partners' => $this->groupSumLimit($model, 'partner_id', 'amount_residual', $openScope),
            'top_purchasers_by_ap' => $this->groupSumLimit($model, 'invoice_user_id', 'amount_residual', $openScope),
        ];
    }

    public function accountMoveStateBreakdown(): array
    {
        $model = Move::class;

        return [
            'total'         => $this->count($model),
            'by_state'      => $this->groupCount($model, 'state'),
            'by_move_type'  => $this->groupCount($model, 'move_type'),
            'total_amount'  => $this->sum($model, 'amount_total'),
        ];
    }

    public function accountPaymentStateBreakdown(): array
    {
        $model = Move::class;
        $total = $this->count($model);
        $postedCount = $this->count($model, fn (Builder $query) => $query->where('state', 'posted'));

        return [
            'total'              => $total,
            'by_payment_state'   => $this->groupCount($model, 'payment_state'),
            'posted_count'       => $postedCount,
            'unpaid_amount'      => $this->sum($model, 'amount_residual', fn (Builder $query) => $query
                ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment'])),
        ];
    }

    public function bankStatementQueue(): array
    {
        $model = BankStatement::class;
        $lineModel = BankStatementLine::class;

        return [
            'total_bank_statements'     => $this->count($model),
            'open_or_draft'             => $this->count($model, fn (Builder $query) => $query->whereIn('state', ['draft', 'open'])),
            'states'                    => $this->groupCount($model, 'state'),
            'unreconciled_lines'        => $this->count($lineModel, fn (Builder $query) => $query->where('is_reconciled', false)),
            'total_unreconciled_amount' => $this->sum($lineModel, 'amount', fn (Builder $query) => $query->where('is_reconciled', false)),
        ];
    }

    public function accountingJournalEntryHealth(): array
    {
        $model = JournalEntry::class;
        $total = $this->count($model);
        $posted = $this->count($model, fn (Builder $query) => $query->where('state', 'posted'));
        $draft = $this->count($model, fn (Builder $query) => $query->where('state', 'draft'));
        $cancelled = $this->count($model, fn (Builder $query) => $query->where('state', 'cancel'));

        return [
            'total'              => $total,
            'posted'             => $posted,
            'draft'              => $draft,
            'cancelled'          => $cancelled,
            'posting_ratio'      => $this->ratio($posted, $total),
            'entries_by_journal' => $this->groupCountLimit($model, 'journal_id'),
        ];
    }

    public function accountingUnpostedEntries(): array
    {
        $model = JournalEntry::class;
        $draftCount = $this->count($model, fn (Builder $query) => $query->where('state', 'draft'));
        $draftAmount = $this->sum($model, 'amount_total', fn (Builder $query) => $query->where('state', 'draft'));

        return [
            'draft_count'        => $draftCount,
            'draft_amount_total' => $draftAmount,
            'avg_draft_amount'   => $this->ratio($draftAmount, $draftCount),
            'draft_by_journal'   => $this->groupCountLimit($model, 'journal_id', fn (Builder $query) => $query->where('state', 'draft')),
        ];
    }

    public function accountingTaxLiabilitySnapshot(): array
    {
        $model = Invoice::class;
        $outboundTypes = ['out_invoice', 'out_receipt'];
        $inboundTypes = ['in_invoice', 'in_receipt'];

        $collectedTax = $this->sum($model, 'amount_tax', fn (Builder $query) => $query
            ->whereIn('move_type', $outboundTypes)
            ->where('state', 'posted'));
        $deductibleTax = $this->sum($model, 'amount_tax', fn (Builder $query) => $query
            ->whereIn('move_type', $inboundTypes)
            ->where('state', 'posted'));
        $draftOutboundTax = $this->sum($model, 'amount_tax', fn (Builder $query) => $query
            ->whereIn('move_type', $outboundTypes)
            ->where('state', 'draft'));

        return [
            'collected_tax'    => $collectedTax,
            'deductible_tax'   => $deductibleTax,
            'net_tax_payable'  => $collectedTax - $deductibleTax,
            'draft_tax_total'  => $draftOutboundTax,
            'net_tax_exposure' => ($collectedTax - $deductibleTax) + $draftOutboundTax,
        ];
    }

    public function accountingCashflowSnapshot(): array
    {
        $receivable = $this->sum(Invoice::class, 'amount_residual', fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));
        $payable = $this->sum(Bill::class, 'amount_residual', fn (Builder $query) => $query
            ->whereIn('move_type', ['in_invoice', 'in_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));

        return [
            'receivable_open_amount' => $receivable,
            'payable_open_amount'    => $payable,
            'net_exposure'           => $receivable - $payable,
        ];
    }
}
