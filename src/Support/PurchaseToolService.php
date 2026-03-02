<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\Requisition;

class PurchaseToolService
{
    use HasQueryHelpers;

    public function purchaseOrdersPending(): array
    {
        $model = Order::class;
        $pendingScope = fn (Builder $query) => $query->whereIn('state', ['draft', 'sent', 'to_approve']);
        $pendingCount = $this->count($model, $pendingScope);
        $pendingAmount = $this->sum($model, 'total_amount', $pendingScope);

        return [
            'states'                => $this->groupCount($model, 'state'),
            'pending_count'         => $pendingCount,
            'pending_total_amount'  => $pendingAmount,
            'avg_pending_order'     => $this->ratio($pendingAmount, $pendingCount),
            'top_pending_suppliers' => $this->groupSumLimit($model, 'partner_id', 'total_amount', $pendingScope),
            'top_purchasers'        => $this->groupCountLimit($model, 'user_id', $pendingScope),
        ];
    }

    public function purchaseRequisitionQueue(): array
    {
        $model = Requisition::class;
        $openScope = fn (Builder $query) => $query->whereIn('state', ['draft', 'confirmed']);

        return [
            'total'          => $this->count($model),
            'states'         => $this->groupCount($model, 'state'),
            'types'          => $this->groupCount($model, 'type'),
            'open_count'     => $this->count($model, $openScope),
            'top_requesters' => $this->groupCountLimit($model, 'creator_id', $openScope),
            'top_purchasers' => $this->groupCountLimit($model, 'user_id', $openScope),
        ];
    }

    public function purchaseSupplierDeliveryRisk(): array
    {
        $model = Order::class;
        $waitingScope = fn (Builder $query) => $query->whereIn('receipt_status', ['to_receive', 'waiting']);

        return [
            'planned_but_not_approved' => $this->count($model, fn (Builder $query) => $query
                ->whereNotNull('planned_at')
                ->whereNull('approved_at')),
            'waiting_receipt'          => $this->count($model, $waitingScope),
            'aged_waiting_receipt_7d'  => $this->count($model, fn (Builder $query) => $query
                ->whereIn('receipt_status', ['to_receive', 'waiting'])
                ->whereDate('ordered_at', '<', Carbon::today()->subDays(7)->toDateString())),
            'receipt_status_breakdown' => $this->groupCount($model, 'receipt_status'),
            'top_risk_suppliers'       => $this->groupCountLimit($model, 'partner_id', $waitingScope),
            'top_risk_purchasers'      => $this->groupCountLimit($model, 'user_id', $waitingScope),
        ];
    }

    public function purchaseSpendSummary(): array
    {
        $model = Order::class;
        $last30 = $this->sum($model, 'total_amount', fn (Builder $query) => $query
            ->whereDate('ordered_at', '>=', Carbon::today()->subDays(30)->toDateString()));

        return [
            'orders_count'           => $this->count($model),
            'total_untaxed_amount'   => $this->sum($model, 'untaxed_amount'),
            'total_amount'           => $this->sum($model, 'total_amount'),
            'total_cc_amount'        => $this->sum($model, 'total_cc_amount'),
            'last_30_days_amount'    => $last30,
            'top_suppliers_by_spend' => $this->groupSumLimit($model, 'partner_id', 'total_amount'),
            'spend_by_purchaser'     => $this->groupSumLimit($model, 'user_id', 'total_amount'),
        ];
    }
}
