<?php

namespace Webkul\Mcp\Servers;

use Laravel\Mcp\Server;
use Webkul\Mcp\Prompts\Admin\InventorySummaryPrompt;
use Webkul\Mcp\Prompts\Admin\InvoiceSummaryPrompt;
use Webkul\Mcp\Prompts\Admin\ProjectSummaryPrompt;
use Webkul\Mcp\Prompts\Admin\PurchaseSummaryPrompt;
use Webkul\Mcp\Prompts\Admin\SalesSummaryPrompt;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountingCashflowSnapshotTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountingJournalEntryHealthTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountingTaxLiabilitySnapshotTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountingUnpostedEntriesTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountMoveStateBreakdownTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountPayableSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountPaymentStateBreakdownTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\AccountReceivableSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\BankStatementQueueTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\CreditNotesOverviewTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceAgingBucketsTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceListTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceOverdueSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoicePaymentFollowupsTool;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceStatusBreakdownTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryLocationBalanceTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryLowStockAlertsTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryOperationBacklogTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryOutOfStockItemsTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryReplenishmentQueueTool;
use Webkul\Mcp\Tools\Admin\Business\Inventories\InventoryWarehouseKpisTool;
use Webkul\Mcp\Tools\Admin\Business\Projects\ProjectDeadlineRiskTool;
use Webkul\Mcp\Tools\Admin\Business\Projects\ProjectStatusOverviewTool;
use Webkul\Mcp\Tools\Admin\Business\Projects\ProjectTaskBacklogTool;
use Webkul\Mcp\Tools\Admin\Business\Projects\ProjectTimesheetSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Purchases\PurchaseOrdersPendingTool;
use Webkul\Mcp\Tools\Admin\Business\Purchases\PurchaseRequisitionQueueTool;
use Webkul\Mcp\Tools\Admin\Business\Purchases\PurchaseSpendSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Purchases\PurchaseSupplierDeliveryRiskTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesOrderFulfillmentStatusTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesOrderInsightsTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesPipelineSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesQuotationConversionTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesTeamPerformanceTool;

class ErpAgentServer extends Server
{
    protected string $name = 'Aureus ERP Agent';

    protected string $version = '1.0.0';

    public int $maxPaginationLength = 200;

    public int $defaultPaginationLength = 200;

    protected string $instructions = <<<'MARKDOWN'
        This MCP server provides comprehensive business insights without logging into the system.
        All tools are READ-ONLY - they only retrieve data, no write operations.

        ## Sales Management (5 tools)
        - sales_order_insights: Recent orders, revenue, counts by period, top performers
        - sales_order_fulfillment_status: Delivery status, overdue commitments and opportunities
       
        - sales_pipeline_summary: Leads and opportunities
        - sales_team_performance: Team metrics
        - sales_quotation_conversion: Quote to order rates

        ## Purchase Management (4 tools)
        - purchase_orders_pending: Pending POs
        - purchase_spend_summary: Spending analysis
        - purchase_requisition_queue: Pending requisitions
        - purchase_supplier_delivery_risk: Delivery risks

        ## Invoice Management (6 tools)
        - invoice_list: All customer invoices
        - invoice_overdue_summary: Overdue amounts
        - invoice_aging_buckets: 0-7, 8-30, 31+ days
        - invoice_payment_followups: Follow-up candidates
        - invoice_status_breakdown: States & payment states
        - credit_notes_overview: Customer credit notes & vendor refunds

        ## Accounting (9 tools)
        - account_receivable_summary: AR amounts
        - account_payable_summary: AP amounts
        - account_move_state_breakdown: Journal states
        - account_payment_state_breakdown: Payment states
        - accounting_cashflow_snapshot: Cash flow
        - accounting_tax_liability_snapshot: Tax obligations
        - accounting_journal_entry_health: Entry issues
        - accounting_unposted_entries: Unposted entries
        - bank_statement_queue: Pending reconciliation

        ## Inventory/Warehouse (6 tools)
        - inventory_low_stock_alerts: Low stock
        - inventory_out_of_stock_items: Out of stock
        - inventory_replenishment_queue: Reorder items
        - inventory_location_balance: Stock by location
        - inventory_operation_backlog: Pending ops
        - inventory_warehouse_kpis: Warehouse metrics

        ## Project Management (4 tools)
        - project_status_overview: Active projects
        - project_task_backlog: Tasks by status
        - project_deadline_risk: Overdue tasks
        - project_timesheet_summary: Time logged

        ## Example Questions
        - "How many orders this week?", "Total sales this month"
        - "Pending purchase orders?", "Top suppliers?"
        - "Overdue invoices?", "Accounts receivable?"
        - "Low stock products?", "Stock by warehouse?"
        - "Active projects?", "Overdue tasks?"
    MARKDOWN;

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        // Sales
        SalesOrderInsightsTool::class,
        SalesOrderFulfillmentStatusTool::class,
        SalesPipelineSummaryTool::class,
        SalesTeamPerformanceTool::class,
        SalesQuotationConversionTool::class,

        // Purchases
        PurchaseOrdersPendingTool::class,
        PurchaseSpendSummaryTool::class,
        PurchaseRequisitionQueueTool::class,
        PurchaseSupplierDeliveryRiskTool::class,

        // Invoices
        InvoiceListTool::class,
        InvoiceOverdueSummaryTool::class,
        InvoiceAgingBucketsTool::class,
        InvoicePaymentFollowupsTool::class,
        InvoiceStatusBreakdownTool::class,
        CreditNotesOverviewTool::class,

        // Accounting
        AccountReceivableSummaryTool::class,
        AccountPayableSummaryTool::class,
        AccountMoveStateBreakdownTool::class,
        AccountPaymentStateBreakdownTool::class,
        AccountingCashflowSnapshotTool::class,
        AccountingTaxLiabilitySnapshotTool::class,
        AccountingJournalEntryHealthTool::class,
        AccountingUnpostedEntriesTool::class,
        BankStatementQueueTool::class,

        // Inventory
        InventoryLowStockAlertsTool::class,
        InventoryOutOfStockItemsTool::class,
        InventoryReplenishmentQueueTool::class,
        InventoryLocationBalanceTool::class,
        InventoryOperationBacklogTool::class,
        InventoryWarehouseKpisTool::class,

        // Projects
        ProjectStatusOverviewTool::class,
        ProjectTaskBacklogTool::class,
        ProjectDeadlineRiskTool::class,
        ProjectTimesheetSummaryTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        SalesSummaryPrompt::class,
        PurchaseSummaryPrompt::class,
        InvoiceSummaryPrompt::class,
        InventorySummaryPrompt::class,
        ProjectSummaryPrompt::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];
}
