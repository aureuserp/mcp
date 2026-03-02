<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Route;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;

class InventoryToolService
{
    use HasQueryHelpers;

    public function inventoryLowStockAlerts(): array
    {
        $model = OrderPoint::class;
        $replenishScope = fn (Builder $query) => $query->where('qty_to_order_manual', '>', 0);

        $belowMinCount = 0;

        try {
            $belowMinCount = (int) DB::table('inventories_order_points')
                ->join('inventories_product_quantities', function ($join): void {
                    $join->on('inventories_order_points.product_id', '=', 'inventories_product_quantities.product_id')
                        ->on('inventories_order_points.location_id', '=', 'inventories_product_quantities.location_id');
                })
                ->where('inventories_order_points.product_min_qty', '>', 0)
                ->whereColumn('inventories_product_quantities.quantity', '<', 'inventories_order_points.product_min_qty')
                ->whereNull('inventories_order_points.deleted_at')
                ->count();
        } catch (\Throwable) {
            $belowMinCount = 0;
        }

        return [
            'order_points'                      => $this->count($model),
            'min_qty_set'                       => $this->count($model, fn (Builder $query) => $query->whereNotNull('product_min_qty')),
            'below_min_stock_count'             => $belowMinCount,
            'manual_replenish_candidates'       => $this->count($model, fn (Builder $query) => $query->where('qty_to_order_manual', '>', 0)),
            'qty_to_order_total'                => $this->sum($model, 'qty_to_order_manual', $replenishScope),
            'top_manual_replenishment_products' => $this->groupSumLimit($model, 'product_id', 'qty_to_order_manual', $replenishScope),
            'affected_warehouses'               => $this->groupCountLimit($model, 'warehouse_id', $replenishScope),
        ];
    }

    public function inventoryOutOfStockItems(): array
    {
        $model = ProductQuantity::class;

        return [
            'out_of_stock_locations' => $this->count($model, fn (Builder $query) => $query->where('quantity', '<=', 0)),
            'negative_stock_rows'    => $this->count($model, fn (Builder $query) => $query->where('quantity', '<', 0)),
            'top_negative_products'  => $this->groupSumLimit($model, 'product_id', 'quantity', fn (Builder $query) => $query->where('quantity', '<', 0), 5, 'asc'),
        ];
    }

    public function inventoryReplenishmentQueue(): array
    {
        return [
            'order_points_total'         => $this->count(OrderPoint::class),
            'total_qty_to_order'         => $this->sum(OrderPoint::class, 'qty_to_order_manual'),
            'draft_operations'           => $this->count(Operation::class, fn (Builder $query) => $query->where('state', 'draft')),
            'replenishment_by_warehouse' => $this->groupCountLimit(OrderPoint::class, 'warehouse_id'),
        ];
    }

    public function inventoryLocationBalance(): array
    {
        $onHand = $this->sum(ProductQuantity::class, 'quantity');
        $reserved = $this->sum(ProductQuantity::class, 'reserved_quantity');

        return [
            'locations'               => $this->count(Location::class),
            'stock_rows'              => $this->count(ProductQuantity::class),
            'total_on_hand_qty'       => $onHand,
            'total_reserved_qty'      => $reserved,
            'total_available_qty'     => $onHand - $reserved,
            'top_locations_by_qty'    => $this->groupSumLimit(ProductQuantity::class, 'location_id', 'quantity'),
            'top_products_by_on_hand' => $this->groupSumLimit(ProductQuantity::class, 'product_id', 'quantity'),
        ];
    }

    public function inventoryOperationBacklog(): array
    {
        $model = Operation::class;
        $today = Carbon::today()->toDateTimeString();
        $openScope = fn (Builder $query) => $query->whereNotIn('state', ['done', 'cancel']);

        return [
            'states'                     => $this->groupCount($model, 'state'),
            'total'                      => $this->count($model),
            'deadline_issue_count'       => $this->count($model, fn (Builder $query) => $query->where('has_deadline_issue', true)),
            'overdue_operations'         => $this->count($model, fn (Builder $query) => $query
                ->whereNotNull('deadline')
                ->where('deadline', '<', $today)
                ->whereNotIn('state', ['done', 'cancel'])),
            'top_backlog_owners'         => $this->groupCountLimit($model, 'owner_id', $openScope),
            'top_operation_types'        => $this->groupCountLimit($model, 'operation_type_id', $openScope),
            'responsible_user_breakdown' => $this->groupCountLimit($model, 'user_id', $openScope),
        ];
    }

    public function inventoryWarehouseKpis(): array
    {
        $opModel = Operation::class;

        return [
            'warehouse_count'              => $this->count(Warehouse::class),
            'operation_type_count'         => $this->count(OperationType::class),
            'route_count'                  => $this->count(Route::class),
            'pending_operations'           => $this->count($opModel, fn (Builder $query) => $query->whereNotIn('state', ['done', 'cancel'])),
            'operations_last_30_days'      => $this->count($opModel, fn (Builder $query) => $query
                ->whereDate('created_at', '>=', Carbon::today()->subDays(30)->toDateString())),
            'done_operations_last_30_days' => $this->count($opModel, fn (Builder $query) => $query
                ->where('state', 'done')
                ->whereDate('date_done', '>=', Carbon::today()->subDays(30)->toDateString())),
        ];
    }
}
