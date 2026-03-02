<?php

namespace Webkul\Mcp\Support;

use Illuminate\Support\Collection;
use Webkul\Accounting\Models\Journal;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Sale\Models\Team;
use Webkul\Security\Models\User;

class BusinessToolService
{
    use HasQueryHelpers;

    public function __construct(
        protected InvoiceToolService $invoiceService,
        protected AccountingToolService $accountingService,
        protected InventoryToolService $inventoryService,
        protected SalesToolService $salesService,
        protected PurchaseToolService $purchaseService,
        protected ProjectToolService $projectService,
    ) {}

    public function run(string $metric): array
    {
        $payload = match ($metric) {
            // Invoice Metrics
            'invoice_list'              => $this->invoiceService->invoiceList(),
            'invoice_overdue_summary'   => $this->invoiceService->invoiceOverdueSummary(),
            'invoice_aging_buckets'     => $this->invoiceService->invoiceAgingBuckets(),
            'invoice_payment_followups' => $this->invoiceService->invoicePaymentFollowups(),
            'invoice_status_breakdown'  => $this->invoiceService->invoiceStatusBreakdown(),
            'credit_notes_overview'     => $this->invoiceService->creditNotesOverview(),

            // Accounting - Summary Metrics
            'account_receivable_summary'      => $this->accountingService->accountReceivableSummary(),
            'account_payable_summary'         => $this->accountingService->accountPayableSummary(),
            'account_move_state_breakdown'    => $this->accountingService->accountMoveStateBreakdown(),
            'account_payment_state_breakdown' => $this->accountingService->accountPaymentStateBreakdown(),
            'bank_statement_queue'            => $this->accountingService->bankStatementQueue(),

            // Accounting - Health Metrics
            'accounting_journal_entry_health'   => $this->accountingService->accountingJournalEntryHealth(),
            'accounting_unposted_entries'       => $this->accountingService->accountingUnpostedEntries(),
            'accounting_tax_liability_snapshot' => $this->accountingService->accountingTaxLiabilitySnapshot(),
            'accounting_cashflow_snapshot'      => $this->accountingService->accountingCashflowSnapshot(),

            // Inventory Metrics
            'inventory_low_stock_alerts'    => $this->inventoryService->inventoryLowStockAlerts(),
            'inventory_out_of_stock_items'  => $this->inventoryService->inventoryOutOfStockItems(),
            'inventory_replenishment_queue' => $this->inventoryService->inventoryReplenishmentQueue(),
            'inventory_location_balance'    => $this->inventoryService->inventoryLocationBalance(),
            'inventory_operation_backlog'   => $this->inventoryService->inventoryOperationBacklog(),
            'inventory_warehouse_kpis'      => $this->inventoryService->inventoryWarehouseKpis(),

            // Sales Metrics
            'sales_pipeline_summary'         => $this->salesService->salesPipelineSummary(),
            'sales_order_fulfillment_status' => $this->salesService->salesOrderFulfillmentStatus(),
            'sales_team_performance'         => $this->salesService->salesTeamPerformance(),
            'sales_quotation_conversion'     => $this->salesService->salesQuotationConversion(),
            'sales_order_insights'           => $this->salesService->salesOrderInsights(),

            // Purchase Metrics
            'purchase_orders_pending'         => $this->purchaseService->purchaseOrdersPending(),
            'purchase_requisition_queue'      => $this->purchaseService->purchaseRequisitionQueue(),
            'purchase_supplier_delivery_risk' => $this->purchaseService->purchaseSupplierDeliveryRisk(),
            'purchase_spend_summary'          => $this->purchaseService->purchaseSpendSummary(),

            // Project Metrics
            'project_status_overview'   => $this->projectService->projectStatusOverview(),
            'project_task_backlog'      => $this->projectService->projectTaskBacklog(),
            'project_deadline_risk'     => $this->projectService->projectDeadlineRisk(),
            'project_timesheet_summary' => $this->projectService->projectTimesheetSummary(),

            default => [
                'error' => "Unknown business metric [{$metric}].",
            ],
        };

        return $this->decorateMetricPayload($metric, $payload);
    }

    protected function decorateMetricPayload(string $metric, array $payload): array
    {
        $fieldModelMap = [
            'invoice_list' => [
                'top_partners_by_invoice_count' => Partner::class,
                'top_salespersons_by_invoices'  => User::class,
            ],
            'invoice_overdue_summary' => [
                'top_overdue_partners'   => Partner::class,
                'overdue_by_salesperson' => User::class,
            ],
            'invoice_payment_followups' => [
                'top_followup_partners'     => Partner::class,
                'top_followup_salespersons' => User::class,
            ],
            'credit_notes_overview' => [
                'customer_credit_notes.top_partners'     => Partner::class,
                'customer_credit_notes.top_salespersons' => User::class,
                'vendor_refunds.top_vendors'             => Partner::class,
            ],
            'account_receivable_summary' => [
                'top_receivable_partners' => Partner::class,
                'top_salespersons_by_ar'  => User::class,
            ],
            'account_payable_summary' => [
                'top_payable_partners'  => Partner::class,
                'top_purchasers_by_ap'  => User::class,
            ],
            'accounting_journal_entry_health' => [
                'entries_by_journal' => Journal::class,
            ],
            'accounting_unposted_entries' => [
                'draft_by_journal' => Journal::class,
            ],
            'inventory_low_stock_alerts' => [
                'top_manual_replenishment_products' => Product::class,
                'affected_warehouses'               => Warehouse::class,
            ],
            'inventory_out_of_stock_items' => [
                'top_negative_products' => Product::class,
            ],
            'inventory_replenishment_queue' => [
                'replenishment_by_warehouse' => Warehouse::class,
            ],
            'inventory_location_balance' => [
                'top_locations_by_qty'    => Location::class,
                'top_products_by_on_hand' => Product::class,
            ],
            'inventory_operation_backlog' => [
                'top_backlog_owners'         => User::class,
                'top_operation_types'        => OperationType::class,
                'responsible_user_breakdown' => User::class,
            ],
            'sales_pipeline_summary' => [
                'top_pipeline_teams'        => Team::class,
                'top_pipeline_salespersons' => User::class,
            ],
            'sales_order_fulfillment_status' => [
                'top_teams_with_overdue_commitments'        => Team::class,
                'top_salespersons_with_overdue_commitments' => User::class,
            ],
            'sales_team_performance' => [
                'orders_by_team'  => Team::class,
                'revenue_by_team' => Team::class,
                'members_by_team' => Team::class,
            ],
            'sales_quotation_conversion' => [
                'conversion_by_team'        => Team::class,
                'conversion_by_salesperson' => User::class,
            ],
            'purchase_orders_pending' => [
                'top_pending_suppliers' => Partner::class,
                'top_purchasers'        => User::class,
            ],
            'purchase_requisition_queue' => [
                'top_requesters' => User::class,
                'top_purchasers' => User::class,
            ],
            'purchase_supplier_delivery_risk' => [
                'top_risk_suppliers'  => Partner::class,
                'top_risk_purchasers' => User::class,
            ],
            'purchase_spend_summary' => [
                'top_suppliers_by_spend' => Partner::class,
                'spend_by_purchaser'     => User::class,
            ],
            'project_status_overview' => [
                'projects_by_manager' => User::class,
            ],
            'project_task_backlog' => [
                'top_projects_by_open_tasks'  => Project::class,
                'top_assignees_by_open_tasks' => User::class,
            ],
            'project_deadline_risk' => [
                'top_projects_by_overdue_tasks'    => Project::class,
                'top_assignees_with_overdue_tasks' => User::class,
            ],
            'project_timesheet_summary' => [
                'top_projects_by_hours' => Project::class,
                'top_users_by_hours'    => User::class,
            ],
            'sales_order_insights' => [
                'top_products_this_month'       => Product::class,
                'top_products_last_30_days'     => Product::class,
                'top_salespersons_this_month'   => User::class,
                'top_salespersons_last_30_days' => User::class,
                'top_customers_last_30_days'    => Partner::class,
            ],
            'project_delivery_insights' => [
                'top_projects_by_open_tasks'       => Project::class,
                'top_projects_by_hours_this_month' => Project::class,
            ],
            'inventory_stock_insights' => [
                'top_products_by_on_hand'  => Product::class,
                'top_products_by_shortage' => Product::class,
            ],
        ];

        foreach ($fieldModelMap[$metric] ?? [] as $field => $modelClass) {
            if (str_contains((string) $field, '.')) {
                [$parent, $child] = explode('.', (string) $field, 2);

                if (isset($payload[$parent][$child]) && is_array($payload[$parent][$child])) {
                    $payload[$parent][$child] = $this->resolveRankedListLabels($payload[$parent][$child], $modelClass);
                }
            } elseif (isset($payload[$field]) && is_array($payload[$field])) {
                $payload[$field] = $this->resolveRankedListLabels($payload[$field], $modelClass);
            }
        }

        if ($metric === 'sales_team_performance') {
            $payload += $this->buildSalesTeamPerformanceSummary($payload);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildSalesTeamPerformanceSummary(array $payload): array
    {
        $ordersByTeam = collect($payload['orders_by_team'] ?? []);
        $revenueByTeam = collect($payload['revenue_by_team'] ?? []);
        $membersByTeam = collect($payload['members_by_team'] ?? []);

        $toIndex = function (Collection $rows): Collection {
            return $rows
                ->mapWithKeys(function (array $row): array {
                    $id = $row['id'] ?? null;
                    $key = $id === null ? '__unassigned__' : (string) $id;

                    return [$key => $row];
                });
        };

        $ordersByKey = $toIndex($ordersByTeam);
        $revenueByKey = $toIndex($revenueByTeam);
        $membersByKey = $toIndex($membersByTeam);

        $teamKeys = $ordersByKey->keys()
            ->merge($revenueByKey->keys())
            ->merge($membersByKey->keys())
            ->unique()
            ->values();

        $rows = $teamKeys->map(function (string $teamKey) use ($ordersByKey, $revenueByKey, $membersByKey): array {
            $orderRow = $ordersByKey->get($teamKey);
            $revenueRow = $revenueByKey->get($teamKey);
            $memberRow = $membersByKey->get($teamKey);

            $orders = (int) ($orderRow['total'] ?? 0);
            $revenue = (float) ($revenueRow['total'] ?? 0);
            $members = (int) ($memberRow['total'] ?? 0);

            $ordersPerMember = $members > 0 ? round($orders / $members, 2) : 0.0;
            $revenuePerMember = $members > 0 ? round($revenue / $members, 2) : 0.0;
            $isUnassigned = $teamKey === '__unassigned__';

            $reason = $members === 0
                ? 'No team members assigned'
                : ($revenue <= 0 ? 'No revenue captured' : 'Based on revenue and order throughput per member');

            return [
                'id'                 => $isUnassigned ? null : (int) $teamKey,
                'label'              => $isUnassigned
                    ? 'Unassigned'
                    : (string) (($revenueRow['label'] ?? $orderRow['label'] ?? $memberRow['label']) ?: $teamKey),
                'orders'             => $orders,
                'revenue'            => round($revenue, 2),
                'members'            => $members,
                'orders_per_member'  => $ordersPerMember,
                'revenue_per_member' => $revenuePerMember,
                'reason'             => $reason,
                'is_unassigned'      => $isUnassigned,
            ];
        })->values();

        $assignedRows = $rows->where('is_unassigned', false)->values();
        $unassignedRow = $rows->firstWhere('is_unassigned', true);

        $topPerformers = $assignedRows
            ->sortByDesc('revenue_per_member')
            ->sortByDesc('revenue')
            ->take(3)
            ->values()
            ->all();

        $weakPerformers = $assignedRows
            ->sortBy('revenue_per_member')
            ->sortBy('revenue')
            ->take(3)
            ->values()
            ->all();

        return [
            'top_performers'      => $topPerformers,
            'weak_performers'     => $weakPerformers,
            'unassigned_snapshot' => $unassignedRow,
        ];
    }
}
