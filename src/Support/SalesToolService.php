<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Sale\Models\Team;
use Webkul\Sale\Models\TeamMember;

class SalesToolService
{
    use HasQueryHelpers;

    public function salesPipelineSummary(): array
    {
        $model = Order::class;
        $openScope = fn (Builder $query) => $query->whereIn('state', ['draft', 'sent']);
        $openPipelineCount = $this->count($model, $openScope);
        $openPipelineAmount = $this->sum($model, 'amount_total', $openScope);

        return [
            'states'                    => $this->groupCount($model, 'state'),
            'invoice_status'            => $this->groupCount($model, 'invoice_status'),
            'total_amount'              => $this->sum($model, 'amount_total'),
            'open_pipeline_count'       => $openPipelineCount,
            'open_pipeline_amount'      => $openPipelineAmount,
            'avg_open_pipeline_deal'    => $this->ratio($openPipelineAmount, $openPipelineCount),
            'top_pipeline_teams'        => $this->groupSumLimit($model, 'team_id', 'amount_total', $openScope),
            'top_pipeline_salespersons' => $this->groupSumLimit($model, 'user_id', 'amount_total', $openScope),
        ];
    }

    public function salesOrderFulfillmentStatus(): array
    {
        $model = Order::class;
        $today = Carbon::today()->toDateString();
        $commitmentSet = $this->count($model, fn (Builder $query) => $query->whereNotNull('commitment_date'));
        $overdueCommitmentsScope = fn (Builder $query) => $query
            ->whereNotNull('commitment_date')
            ->whereDate('commitment_date', '<', $today)
            ->whereNotIn('delivery_status', ['done']);
        $overdueCommitments = $this->count($model, $overdueCommitmentsScope);

        return [
            'delivery_status'                           => $this->groupCount($model, 'delivery_status'),
            'commitment_set'                            => $commitmentSet,
            'no_commitment_date_count'                  => $this->count($model, fn (Builder $query) => $query->whereNull('commitment_date')),
            'overdue_commitments'                       => $overdueCommitments,
            'overdue_commitment_ratio'                  => $this->ratio($overdueCommitments, $commitmentSet),
            'top_teams_with_overdue_commitments'        => $this->groupCountLimit($model, 'team_id', $overdueCommitmentsScope),
            'top_salespersons_with_overdue_commitments' => $this->groupCountLimit($model, 'user_id', $overdueCommitmentsScope),
        ];
    }

    public function salesTeamPerformance(): array
    {
        $teamModel = Team::class;
        $teamMemberModel = TeamMember::class;
        $orderModel = Order::class;

        return [
            'team_count'            => $this->count($teamModel),
            'team_member_count'     => $this->count($teamMemberModel),
            'sales_orders'          => $this->count($orderModel),
            'unassigned_orders'     => $this->count($orderModel, fn (Builder $query) => $query->whereNull('team_id')),
            'orders_by_team'        => $this->groupCountLimit($orderModel, 'team_id'),
            'revenue_by_team'       => $this->groupSumLimit($orderModel, 'team_id', 'amount_total'),
            'members_by_team'       => $this->groupCountLimit($teamMemberModel, 'team_id'),
            'teams_without_members' => $this->count($teamModel, fn (Builder $query) => $query->doesntHave('members')),
        ];
    }

    public function salesQuotationConversion(): array
    {
        $model = Order::class;
        $draftOrSent = $this->count($model, fn (Builder $query) => $query->whereIn('state', ['draft', 'sent']));
        $confirmedSale = $this->count($model, fn (Builder $query) => $query->where('state', 'sale'));
        $last30DraftOrSent = $this->count($model, fn (Builder $query) => $query
            ->whereDate('date_order', '>=', Carbon::today()->subDays(30)->toDateString())
            ->whereIn('state', ['draft', 'sent']));
        $last30Confirmed = $this->count($model, fn (Builder $query) => $query
            ->whereDate('date_order', '>=', Carbon::today()->subDays(30)->toDateString())
            ->where('state', 'sale'));

        return [
            'draft_or_sent'             => $draftOrSent,
            'confirmed_sale'            => $confirmedSale,
            'conversion_ratio'          => $this->ratio($confirmedSale, $draftOrSent + $confirmedSale),
            'last_30_days_ratio'        => $this->ratio($last30Confirmed, $last30DraftOrSent + $last30Confirmed),
            'conversion_by_team'        => $this->groupCountLimit($model, 'team_id', fn (Builder $query) => $query->where('state', 'sale')),
            'conversion_by_salesperson' => $this->groupCountLimit($model, 'user_id', fn (Builder $query) => $query->where('state', 'sale')),
        ];
    }

    public function salesOrderInsights(): array
    {
        $orderModel = Order::class;
        $lineModel = OrderLine::class;
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek()->toDateString();
        $startOfMonth = $today->copy()->startOfMonth()->toDateString();
        $startOfYear = $today->copy()->startOfYear()->toDateString();
        $last7Days = $today->copy()->subDays(6)->toDateString();
        $last30Days = $today->copy()->subDays(29)->toDateString();

        $recentOrders = $this->baseQuery($orderModel)
            ->with([
                'user:id,name',
                'team:id,name',
                'partner:id,name,email,phone',
                'lines.product:id,name,reference',
            ])
            ->orderByDesc('date_order')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $lastOrder = $recentOrders->first();

        return [
            'recent_orders' => $recentOrders->map(function ($order): array {
                $products = collect($order->lines ?? [])
                    ->map(function ($line): array {
                        return [
                            'name'       => (string) ($line->product?->name ?? $line->name ?? 'Unknown'),
                            'sku'        => (string) ($line->product?->reference ?? ''),
                            'qty'        => (float) ($line->product_uom_qty ?? 0),
                            'price_unit' => (float) ($line->price_unit ?? 0),
                            'subtotal'   => (float) ($line->price_subtotal ?? 0),
                        ];
                    })
                    ->take(15)
                    ->values()
                    ->all();

                return [
                    'id'              => (int) $order->id,
                    'order'           => (string) ($order->name ?? ''),
                    'date'            => (string) ($order->date_order ?? $order->created_at?->toDateString() ?? ''),
                    'state'           => (string) ($order->state?->value ?? $order->state ?? ''),
                    'amount_untaxed'  => (float) ($order->amount_untaxed ?? 0),
                    'amount_tax'      => (float) ($order->amount_tax ?? 0),
                    'amount_total'    => (float) ($order->amount_total ?? 0),
                    'salesperson'     => (string) ($order->user?->name ?? 'Unassigned'),
                    'team'            => (string) ($order->team?->name ?? 'Unassigned'),
                    'customer'        => (string) ($order->partner?->name ?? 'Unknown'),
                    'customer_email'  => (string) ($order->partner?->email ?? ''),
                    'customer_phone'  => (string) ($order->partner?->phone ?? ''),
                    'invoice_status'  => (string) ($order->invoice_status?->value ?? $order->invoice_status ?? 'no_invoice'),
                    'delivery_status' => (string) ($order->delivery_status?->value ?? $order->delivery_status ?? 'to_deliver'),
                    'commitment_date' => (string) ($order->commitment_date ?? ''),
                    'product_count'   => (int) collect($order->lines ?? [])->count(),
                    'products'        => $products,
                ];
            })->values()->all(),
            'last_5_total_value'        => round((float) $recentOrders->sum('amount_total'), 2),
            'last_5_total_untaxed'      => round((float) $recentOrders->sum('amount_untaxed'), 2),
            'last_5_total_tax'          => round((float) $recentOrders->sum('amount_tax'), 2),
            'last_order_by'             => (string) ($lastOrder?->user?->name ?? 'Unknown'),
            'last_order_products_count' => (int) collect($lastOrder?->lines ?? [])->count(),
            'last_order_product_names'  => collect($lastOrder?->lines ?? [])
                ->map(fn ($line): string => (string) ($line->product?->name ?? $line->name ?? 'Unknown'))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'summary' => [
                'total_orders'              => $this->count($orderModel),
                'total_value'               => round($this->sum($orderModel, 'amount_total'), 2),
                'avg_order_value'           => round($this->average($orderModel, 'amount_total'), 2),
                'orders_by_state'           => $this->groupCount($orderModel, 'state'),
                'orders_by_invoice_status'  => $this->groupCount($orderModel, 'invoice_status'),
                'orders_by_delivery_status' => $this->groupCount($orderModel, 'delivery_status'),
            ],
            'date_windows' => [
                'today'        => $today->toDateString(),
                'this_week'    => $startOfWeek,
                'this_month'   => $startOfMonth,
                'this_year'    => $startOfYear,
                'last_7_days'  => $last7Days,
                'last_30_days' => $last30Days,
            ],
            'orders_received' => [
                'today' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', $today->toDateString())),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', $today->toDateString())),
                ],
                'this_week' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfWeek)),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfWeek)),
                ],
                'this_month' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfMonth)),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfMonth)),
                ],
                'this_year' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfYear)),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', '>=', $startOfYear)),
                ],
                'last_7_days' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', '>=', $last7Days)),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', '>=', $last7Days)),
                ],
                'last_30_days' => [
                    'count' => $this->count($orderModel, fn (Builder $query) => $query->whereDate('date_order', '>=', $last30Days)),
                    'value' => $this->sum($orderModel, 'amount_total', fn (Builder $query) => $query->whereDate('date_order', '>=', $last30Days)),
                ],
            ],
            'team_assignment' => [
                'unassigned_orders_total'        => $this->count($orderModel, fn (Builder $query) => $query->whereNull('team_id')),
                'unassigned_orders_last_30_days' => $this->count($orderModel, fn (Builder $query) => $query
                    ->whereNull('team_id')
                    ->whereDate('date_order', '>=', $last30Days)),
            ],
            'top_products_this_month' => $this->groupSumLimit($lineModel, 'product_id', 'product_uom_qty', fn (Builder $query) => $query
                ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.order_id')
                ->whereDate('sales_orders.date_order', '>=', $startOfMonth)),
            'top_products_last_30_days' => $this->groupSumLimit($lineModel, 'product_id', 'product_uom_qty', fn (Builder $query) => $query
                ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.order_id')
                ->whereDate('sales_orders.date_order', '>=', $last30Days)),
            'top_salespersons_this_month' => $this->groupCountLimit($orderModel, 'user_id', fn (Builder $query) => $query
                ->whereDate('date_order', '>=', $startOfMonth)),
            'top_salespersons_last_30_days' => $this->groupCountLimit($orderModel, 'user_id', fn (Builder $query) => $query
                ->whereDate('date_order', '>=', $last30Days)),
            'top_customers_last_30_days' => $this->groupSumLimit($orderModel, 'partner_id', 'amount_total', fn (Builder $query) => $query
                ->whereDate('date_order', '>=', $last30Days)),
        ];
    }
}
