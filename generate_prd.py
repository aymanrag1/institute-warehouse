#!/usr/bin/env python3
"""
Generate Laravel PRD for Institute Warehouse Management System as .docx
"""

from docx import Document
from docx.shared import Pt, RGBColor, Inches, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import copy

doc = Document()

# ── Page margins ──────────────────────────────────────────────────────────────
for section in doc.sections:
    section.top_margin    = Cm(2)
    section.bottom_margin = Cm(2)
    section.left_margin   = Cm(2.5)
    section.right_margin  = Cm(2.5)

# ── Helpers ───────────────────────────────────────────────────────────────────

def set_cell_bg(cell, hex_color):
    tc   = cell._tc
    tcPr = tc.get_or_add_tcPr()
    shd  = OxmlElement('w:shd')
    shd.set(qn('w:val'),   'clear')
    shd.set(qn('w:color'), 'auto')
    shd.set(qn('w:fill'),  hex_color)
    tcPr.append(shd)

def add_heading(doc, text, level=1):
    p = doc.add_heading(text, level=level)
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    run = p.runs[0] if p.runs else p.add_run(text)
    run.font.color.rgb = RGBColor(0x1F, 0x49, 0x7D) if level == 1 else RGBColor(0x2E, 0x74, 0xB5)
    return p

def add_para(doc, text, bold=False, italic=False, size=11):
    p = doc.add_paragraph()
    run = p.add_run(text)
    run.bold   = bold
    run.italic = italic
    run.font.size = Pt(size)
    return p

def add_table(doc, headers, rows, header_color='2E74B5'):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = 'Table Grid'
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    hdr_cells = table.rows[0].cells
    for i, h in enumerate(headers):
        hdr_cells[i].text = h
        set_cell_bg(hdr_cells[i], header_color)
        run = hdr_cells[i].paragraphs[0].runs[0]
        run.font.bold  = True
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        run.font.size  = Pt(10)
        hdr_cells[i].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER

    for row_data in rows:
        row_cells = table.add_row().cells
        for i, val in enumerate(row_data):
            row_cells[i].text = str(val)
            row_cells[i].paragraphs[0].runs[0].font.size = Pt(10)

    return table

def add_code_block(doc, code_text):
    p = doc.add_paragraph()
    p.style = 'No Spacing'
    run = p.add_run(code_text)
    run.font.name = 'Courier New'
    run.font.size = Pt(9)
    run.font.color.rgb = RGBColor(0x24, 0x29, 0x2E)
    # Light gray background via paragraph shading
    pPr = p._p.get_or_add_pPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:val'),   'clear')
    shd.set(qn('w:color'), 'auto')
    shd.set(qn('w:fill'),  'F0F0F0')
    pPr.append(shd)
    return p

# ══════════════════════════════════════════════════════════════════════════════
# COVER PAGE
# ══════════════════════════════════════════════════════════════════════════════

doc.add_paragraph()
title_p = doc.add_paragraph()
title_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
title_run = title_p.add_run('Product Requirements Document (PRD)')
title_run.font.size  = Pt(24)
title_run.font.bold  = True
title_run.font.color.rgb = RGBColor(0x1F, 0x49, 0x7D)

sub_p = doc.add_paragraph()
sub_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
sub_run = sub_p.add_run('Institute Warehouse Management System')
sub_run.font.size  = Pt(18)
sub_run.font.bold  = True
sub_run.font.color.rgb = RGBColor(0x2E, 0x74, 0xB5)

doc.add_paragraph()

meta_data = [
    ('Version',       '1.0.0'),
    ('Date',          'March 2026'),
    ('Platform',      'Laravel 11 / PHP 8.2+'),
    ('Architecture',  'MVC – REST API + Blade Views'),
    ('Author',        'AYMAN RAGAB'),
    ('Type',          'B2B – Educational Institute ERP'),
    ('Database',      'MySQL 8.0+'),
    ('Auth',          'Laravel Breeze / Sanctum'),
]
meta_table = doc.add_table(rows=len(meta_data), cols=2)
meta_table.alignment = WD_TABLE_ALIGNMENT.CENTER
for i, (k, v) in enumerate(meta_data):
    cells = meta_table.rows[i].cells
    cells[0].text = k
    cells[0].paragraphs[0].runs[0].font.bold = True
    cells[0].paragraphs[0].runs[0].font.size = Pt(11)
    set_cell_bg(cells[0], 'EBF3FB')
    cells[1].text = v
    cells[1].paragraphs[0].runs[0].font.size = Pt(11)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# TABLE OF CONTENTS (manual)
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, 'Table of Contents', level=1)
toc_items = [
    '1. Executive Summary',
    '2. Product Overview',
    '3. Problem Statement & Market Opportunity',
    '4. Goals & Success Metrics (KPIs)',
    '5. User Personas',
    '6. Functional Modules & Requirements',
    '   6.1  Products Module',
    '   6.2  Opening Balance Module',
    '   6.3  Add Orders (Stock-In) Module',
    '   6.4  Withdrawal Orders (Stock-Out) Module',
    '   6.5  Return Orders Module',
    '   6.6  Purchase Requests Module',
    '   6.7  Suppliers Module',
    '   6.8  Categories & Departments Module',
    '   6.9  Approval & Electronic Signature Module',
    '   6.10 Reports Module',
    '   6.11 Settings & Configuration Module',
    '   6.12 Import / Export Module',
    '7. User Stories',
    '8. Non-Functional Requirements',
    '9. Laravel Technical Architecture',
    '10. Database Design',
    '11. API Endpoints',
    '12. Permissions & Role System',
    '13. Security Requirements',
    '14. UI/UX Requirements',
    '15. Testing Requirements',
    '16. Roadmap & Future Development',
    '17. Risks & Constraints',
]
for item in toc_items:
    p = doc.add_paragraph(item)
    p.paragraph_format.space_after = Pt(3)
    if not item.startswith('   '):
        p.runs[0].font.bold = True
    p.runs[0].font.size = Pt(10)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 1. EXECUTIVE SUMMARY
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '1. Executive Summary', level=1)
add_para(doc,
    'The Institute Warehouse Management System is a comprehensive Laravel-based web application '
    'providing a full-cycle inventory management solution for educational institutions (institutes, '
    'colleges, training centres). The system operates as a standalone application or integrates '
    'with the RSYI HR System, covering the complete inventory lifecycle: procurement, stock-in, '
    'stock-out, returns, and reporting — with a complete multi-level electronic approval workflow.',
    size=11)

doc.add_paragraph()
add_para(doc, 'Core Capabilities:', bold=True)
core = [
    'Full inventory cycle: Add → Withdraw → Return → Report',
    'FIFO (First-In First-Out) stock depletion for accurate cost tracking',
    'Multi-level electronic approval with digital signature',
    'HR integration: automatic linkage of departments and employees',
    'Bilingual interface: Arabic (RTL) and English',
    'Role-based access control (RBAC) with granular permissions',
    'Return permit system: normal returns and custody returns',
    'Auto-generated purchase requests when stock hits minimum level',
]
for item in core:
    p = doc.add_paragraph(item, style='List Bullet')
    p.runs[0].font.size = Pt(11)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 2. PRODUCT OVERVIEW
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '2. Product Overview', level=1)

add_heading(doc, '2.1 What Is the System?', level=2)
add_para(doc,
    'A Laravel 11 web application with a RESTful API backend and Blade-rendered admin panel. '
    'All business logic lives in dedicated Service classes; database access is handled by '
    'Eloquent ORM; routes are grouped by role middleware.', size=11)

doc.add_paragraph()
add_table(doc,
    ['Aspect', 'Details'],
    [
        ('Type',            'Laravel Admin Panel + REST API'),
        ('Language',        'PHP 8.2+'),
        ('Framework',       'Laravel 11'),
        ('Database',        'MySQL 8.0+ (table prefix: iw_)'),
        ('Authentication',  'Laravel Breeze / Sanctum (API tokens)'),
        ('Frontend',        'Blade + Alpine.js + Tailwind CSS'),
        ('API Style',       'RESTful JSON API'),
        ('HR Integration',  'RSYI HR System (optional, via API or shared DB)'),
        ('Caching',         'Redis / Laravel Cache'),
        ('Queue',           'Laravel Queue (notifications, exports)'),
    ]
)

doc.add_paragraph()
add_heading(doc, '2.2 Laravel Directory Structure', level=2)
add_code_block(doc,
"""app/
├── Http/
│   ├── Controllers/
│   │   ├── ProductController.php
│   │   ├── AddOrderController.php
│   │   ├── WithdrawalOrderController.php
│   │   ├── ReturnOrderController.php
│   │   ├── PurchaseRequestController.php
│   │   ├── SupplierController.php
│   │   ├── CategoryController.php
│   │   ├── DepartmentController.php
│   │   ├── ReportController.php
│   │   ├── SettingController.php
│   │   └── SignatureController.php
│   ├── Middleware/
│   │   ├── CheckPermission.php
│   │   └── SetLocale.php
│   └── Requests/          (Form Request validation)
├── Models/
│   ├── Product.php
│   ├── AddOrder.php
│   ├── AddOrderItem.php
│   ├── WithdrawalOrder.php
│   ├── WithdrawalOrderItem.php
│   ├── ReturnOrder.php
│   ├── ReturnOrderItem.php
│   ├── Transaction.php
│   ├── PurchaseRequest.php
│   ├── Supplier.php
│   ├── Category.php
│   ├── Department.php
│   └── UserPermission.php
├── Services/
│   ├── FifoService.php
│   ├── StockService.php
│   ├── OrderNumberService.php
│   ├── SignatureService.php
│   └── ReportService.php
├── Policies/              (Laravel Policies for RBAC)
└── Notifications/
    └── OrderApprovalNotification.php
database/
├── migrations/
└── seeders/
resources/views/           (Blade templates)
routes/
├── web.php
└── api.php
""")

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 3. PROBLEM STATEMENT
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '3. Problem Statement & Market Opportunity', level=1)

add_heading(doc, '3.1 Current Problems', level=2)
add_table(doc,
    ['Problem', 'Impact'],
    [
        ('Manual inventory management (Excel/paper)',     'Data errors, time waste, difficult tracking'),
        ('No formal approval system',                     'Stock issued without official authorisation'),
        ('Inability to track item movement',              'Unknown who issued what and when'),
        ('No low-stock alerts',                           'Critical items run out unnoticed'),
        ('Stock not linked to departments/employees',     'Cannot allocate costs per department'),
        ('Unstructured procurement process',              'Budget waste, duplicate orders'),
        ('No return/custody-return workflow',             'Issued items never formally returned to stock'),
    ]
)

doc.add_paragraph()
add_heading(doc, '3.2 Proposed Solution', level=2)
solutions = [
    'Full digital archive for all stock movements',
    'Approval workflow ensuring accountability before any issuance',
    'Instant reports supporting management decisions',
    'Seamless HR integration for department/employee data',
    'Return permit system for both normal returns and custody returns',
]
for s in solutions:
    p = doc.add_paragraph(s, style='List Bullet')
    p.runs[0].font.size = Pt(11)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 4. GOALS & KPIs
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '4. Goals & Success Metrics (KPIs)', level=1)

add_heading(doc, '4.1 Strategic Goals', level=2)
add_table(doc,
    ['Goal', 'Description'],
    [
        ('Digitise Inventory Management',   'Eliminate paper-based procedures 100%'),
        ('Control Issuance',                'Require formal approval before any stock disbursement'),
        ('Transparency',                    'Full audit trail for every movement with user & timestamp'),
        ('Purchasing Efficiency',           'Auto-generate purchase requests at minimum stock level'),
        ('Cost Accuracy',                   'Apply FIFO for precise issuance cost calculation'),
        ('Return Accountability',           'Formal permit system for all stock returns'),
    ]
)

doc.add_paragraph()
add_heading(doc, '4.2 KPIs', level=2)
add_table(doc,
    ['Metric', 'Target Value'],
    [
        ('Stock balance accuracy',                '99.9%'),
        ('Withdrawal permit processing time',     '< 24 hours'),
        ('Report generation time',                '< 5 seconds'),
        ('Items managed digitally',               '100%'),
        ('Items that ran out without an alert',   '0%'),
        ('API response time (p95)',               '< 300 ms'),
        ('System uptime',                         '99.5%'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 5. USER PERSONAS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '5. User Personas', level=1)

personas = [
    ('System Administrator', 'admin', 'Configure system, manage permissions, view all data',
     'Full access (all permissions)', 'Comprehensive overview, aggregated reports, user management'),
    ('Warehouse Keeper', 'warehouse_keeper', 'Data entry, create permits, track stock',
     'read/write on core modules', 'Easy permit creation UI, order tracking'),
    ('Dean / Approver', 'approver', 'Review and approve withdrawal orders and purchase requests',
     'Approval permission only', 'Quick pending-order view, approve with electronic signature'),
    ('Department Head', 'dept_head', 'Request stock for their department, track consumption',
     'View own department only, create withdrawal', 'Department consumption report'),
    ('Procurement Officer', 'procurement', 'Manage purchase requests, liaise with suppliers',
     'read/write on purchase requests & suppliers', 'Supplier list, historical pricing'),
]
for name, role, resp, perms, needs in personas:
    add_heading(doc, f'5.x  {name}  (role: {role})', level=2)
    add_table(doc,
        ['Attribute', 'Details'],
        [
            ('Role Slug',        role),
            ('Responsibilities', resp),
            ('Permissions',      perms),
            ('Needs',            needs),
        ]
    )
    doc.add_paragraph()

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 6. FUNCTIONAL MODULES
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '6. Functional Modules & Requirements', level=1)

# ── 6.1 Products ──────────────────────────────────────────────────────────────
add_heading(doc, '6.1  Products Module', level=2)
add_para(doc, 'Laravel files: app/Models/Product.php  |  app/Http/Controllers/ProductController.php  |  app/Services/StockService.php', italic=True, size=10)
add_para(doc, 'Central registry for all inventory items. Every stock operation starts and ends here.', size=11)
doc.add_paragraph()
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-P-001', 'Create new item with full data (name, SKU, category, unit, min/max stock, price)', 'Must Have'),
        ('PRD-P-002', 'Edit existing item data', 'Must Have'),
        ('PRD-P-003', 'Delete item (check no transactions exist)', 'Must Have'),
        ('PRD-P-004', 'List items with current balance', 'Must Have'),
        ('PRD-P-005', 'Search and filter (by name, SKU, category)', 'Must Have'),
        ('PRD-P-006', 'Sync balances from actual transactions', 'Must Have'),
        ('PRD-P-007', 'Import items from Excel (via Laravel Excel)', 'Should Have'),
        ('PRD-P-008', 'Flag items at minimum stock level', 'Must Have'),
        ('PRD-P-009', 'Flag out-of-stock items (balance = 0)', 'Must Have'),
        ('PRD-P-010', 'Calculate weighted average purchase price', 'Nice to Have'),
    ]
)
doc.add_paragraph()
add_para(doc, 'Business Logic:', bold=True)
add_para(doc, 'current_stock = opening_balance + SUM(add_transactions) – SUM(withdrawal_transactions) + SUM(return_transactions)', size=10)
add_para(doc, 'An item cannot be deleted if it has entries in iw_transactions or iw_add_order_items.', size=10)
doc.add_paragraph()

# ── 6.2 Opening Balance ────────────────────────────────────────────────────────
add_heading(doc, '6.2  Opening Balance Module', level=2)
add_para(doc, 'Laravel files: app/Models/Transaction.php  |  app/Http/Controllers/OpeningBalanceController.php', italic=True, size=10)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-OB-001', 'Enter opening balance for a specific item', 'Must Have'),
        ('PRD-OB-002', 'Set date of opening balance', 'Must Have'),
        ('PRD-OB-003', 'Enter unit price for opening balance', 'Must Have'),
        ('PRD-OB-004', 'Import opening balances from Excel', 'Should Have'),
        ('PRD-OB-005', 'Delete opening balance and reverse stock impact', 'Must Have'),
        ('PRD-OB-006', 'Full reset of opening balances', 'Should Have'),
        ('PRD-OB-007', 'Prevent duplicate opening balance per item (replace, not add)', 'Must Have'),
    ]
)
doc.add_paragraph()
add_para(doc, 'Opening balance is stored as a Transaction with transaction_type = "opening_balance".', size=10)
add_para(doc, 'Cannot add a new opening balance for an item that already has withdrawal transactions.', size=10)
doc.add_paragraph()

# ── 6.3 Add Orders ────────────────────────────────────────────────────────────
add_heading(doc, '6.3  Add Orders (Stock-In) Module', level=2)
add_para(doc, 'Laravel files: app/Models/AddOrder.php  |  AddOrderItem.php  |  app/Http/Controllers/AddOrderController.php', italic=True, size=10)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-AO-001', 'Create multi-item add order', 'Must Have'),
        ('PRD-AO-002', 'Link order to a specific supplier', 'Must Have'),
        ('PRD-AO-003', 'Enter quantity and price per item', 'Must Have'),
        ('PRD-AO-004', 'Auto-calculate order total', 'Must Have'),
        ('PRD-AO-005', 'Auto-generate sequential order number (ADD-YYYYMM-NNNN)', 'Must Have'),
        ('PRD-AO-006', 'Print add order permit', 'Must Have'),
        ('PRD-AO-007', 'Edit order with stock recalculation', 'Should Have'),
        ('PRD-AO-008', 'Delete order and reverse stock impact', 'Should Have'),
        ('PRD-AO-009', 'Attach invoice/reference number', 'Should Have'),
        ('PRD-AO-010', 'Update stock balance immediately on save', 'Must Have'),
        ('PRD-AO-011', 'Record FIFO transactions per line item', 'Must Have'),
    ]
)
doc.add_paragraph()
add_para(doc, 'Order Number Format: ADD-{YYYYMM}-{SEQUENCE}  →  Example: ADD-202602-0001', size=10)
doc.add_paragraph()

# ── 6.4 Withdrawal Orders ─────────────────────────────────────────────────────
add_heading(doc, '6.4  Withdrawal Orders (Stock-Out) Module', level=2)
add_para(doc, 'Laravel files: app/Models/WithdrawalOrder.php  |  WithdrawalOrderItem.php  |  app/Http/Controllers/WithdrawalOrderController.php  |  app/Services/FifoService.php', italic=True, size=10)
add_para(doc, 'The most complex module. Manages stock disbursement with multi-level approval.', size=11)
doc.add_paragraph()
add_heading(doc, 'Order Types', level=3)
add_table(doc,
    ['Type', 'Description', 'Stock Impact'],
    [
        ('normal',  'Standard withdrawal for a department', 'Deducts from stock'),
        ('custody', 'Temporary loan/custody',               'No stock deduction'),
    ]
)
doc.add_paragraph()
add_heading(doc, 'Workflow States', level=3)
add_table(doc,
    ['Status', 'Description', 'Who Can Action'],
    [
        ('pending',   'Awaiting approval',              'Warehouse Keeper / Requester'),
        ('approved',  'Approved by dean with signature','Dean / Approver'),
        ('completed', 'Executed, stock deducted',       'Warehouse Keeper'),
        ('cancelled', 'Cancelled',                      'Requester or Manager'),
    ]
)
doc.add_paragraph()
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-WO-001', 'Create multi-item withdrawal order', 'Must Have'),
        ('PRD-WO-002', 'Specify requesting department and employee', 'Must Have'),
        ('PRD-WO-003', 'Verify stock availability before creation', 'Must Have'),
        ('PRD-WO-004', 'Submit order for approval with notification', 'Must Have'),
        ('PRD-WO-005', 'Approver can adjust quantities', 'Must Have'),
        ('PRD-WO-006', 'Approve order with electronic signature', 'Must Have'),
        ('PRD-WO-007', 'Deduct stock using FIFO on completion', 'Must Have'),
        ('PRD-WO-008', 'Print withdrawal permit with signature', 'Must Have'),
        ('PRD-WO-009', 'Cancel order and reverse impact', 'Must Have'),
        ('PRD-WO-010', 'Create custody order (no stock deduction)', 'Should Have'),
        ('PRD-WO-011', 'Auto-generate sequential order number', 'Must Have'),
        ('PRD-WO-012', 'Filter orders by status/date/department', 'Should Have'),
        ('PRD-WO-013', 'Create return order from completed withdrawal', 'Must Have'),
    ]
)
doc.add_paragraph()
add_heading(doc, 'FIFO Algorithm', level=3)
add_code_block(doc,
"""// FifoService.php
public function deduct(int $productId, int $qty, int $orderId): void
{
    DB::transaction(function () use ($productId, $qty, $orderId) {
        $batches = Transaction::where('product_id', $productId)
                              ->whereIn('transaction_type', ['add', 'opening_balance', 'return'])
                              ->where('remaining_qty', '>', 0)
                              ->orderBy('created_at')
                              ->lockForUpdate()
                              ->get();
        $remaining = $qty;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($batch->remaining_qty, $remaining);
            $batch->decrement('remaining_qty', $take);
            $remaining -= $take;
        }
        if ($remaining > 0) {
            throw new InsufficientStockException("Not enough stock for product #{$productId}");
        }
        Transaction::create([
            'transaction_type' => 'withdraw',
            'product_id'       => $productId,
            'quantity'         => $qty,
            'remaining_qty'    => 0,
            'order_id'         => $orderId,
            'created_by'       => auth()->id(),
        ]);
        Product::where('id', $productId)->decrement('current_stock', $qty);
    });
}
""")
doc.add_paragraph()

# ── 6.5 Return Orders ─────────────────────────────────────────────────────────
add_heading(doc, '6.5  Return Orders Module', level=2)
add_para(doc, 'Laravel files: app/Models/ReturnOrder.php  |  ReturnOrderItem.php  |  app/Http/Controllers/ReturnOrderController.php', italic=True, size=10)
add_para(doc,
    'Handles two distinct return flows: normal returns that add stock back to inventory, '
    'and custody returns that are audit-only (no stock change).', size=11)
doc.add_paragraph()
add_heading(doc, 'Return Types', level=3)
add_table(doc,
    ['Type', 'Order Number Format', 'Stock Impact', 'Transaction Type'],
    [
        ('normal',  'RT-YYYY-NNNNN',  'Adds back to stock (FIFO pool)', 'return'),
        ('custody', 'CRT-YYYY-NNNNN', 'Audit only — no stock change',    'custody_return'),
    ]
)
doc.add_paragraph()
add_heading(doc, 'Workflow States', level=3)
add_table(doc,
    ['Status', 'Description'],
    [
        ('pending',   'Return order created, awaiting approval'),
        ('approved',  'Approved by authorised signer'),
        ('completed', 'Executed: stock restored (normal) or logged (custody)'),
        ('rejected',  'Rejected with reason'),
    ]
)
doc.add_paragraph()
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-RO-001', 'Create return order linked to original withdrawal order', 'Must Have'),
        ('PRD-RO-002', 'Support normal return (stock restored via FIFO)', 'Must Have'),
        ('PRD-RO-003', 'Support custody return (audit only, no stock change)', 'Must Have'),
        ('PRD-RO-004', 'Approve return with electronic signature', 'Must Have'),
        ('PRD-RO-005', 'Execute return: add transaction + update stock', 'Must Have'),
        ('PRD-RO-006', 'Print return permit', 'Must Have'),
        ('PRD-RO-007', 'Reject return with reason', 'Must Have'),
        ('PRD-RO-008', 'Delete pending return orders', 'Should Have'),
        ('PRD-RO-009', 'Filter returns by type and status', 'Must Have'),
        ('PRD-RO-010', 'Navigate to create return from withdrawal order page', 'Should Have'),
    ]
)
doc.add_paragraph()

# ── 6.6 Purchase Requests ──────────────────────────────────────────────────────
add_heading(doc, '6.6  Purchase Requests Module', level=2)
add_para(doc, 'Laravel files: app/Models/PurchaseRequest.php  |  app/Http/Controllers/PurchaseRequestController.php', italic=True, size=10)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-PR-001', 'Create manual purchase request', 'Must Have'),
        ('PRD-PR-002', 'Auto-generate request for items at minimum stock', 'Must Have'),
        ('PRD-PR-003', 'Filter auto-generation by category', 'Should Have'),
        ('PRD-PR-004', 'Show last purchase price per item', 'Must Have'),
        ('PRD-PR-005', 'Approve request with electronic signature', 'Must Have'),
        ('PRD-PR-006', 'Print purchase request', 'Must Have'),
        ('PRD-PR-007', 'Track request status (pending / approved / completed)', 'Must Have'),
        ('PRD-PR-008', 'Link purchase request to add order on receipt', 'Nice to Have'),
    ]
)
doc.add_paragraph()
add_para(doc, 'Auto-generation logic: items where current_stock <= min_stock. Suggested quantity = max_stock - current_stock. Reference price = last unit_price from iw_add_order_items.', size=10)
doc.add_paragraph()

# ── 6.7 Suppliers ──────────────────────────────────────────────────────────────
add_heading(doc, '6.7  Suppliers Module', level=2)
add_para(doc, 'Laravel files: app/Models/Supplier.php  |  app/Http/Controllers/SupplierController.php', italic=True, size=10)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-SUP-001', 'Add new supplier with full data', 'Must Have'),
        ('PRD-SUP-002', 'Edit supplier data', 'Must Have'),
        ('PRD-SUP-003', 'Delete supplier (check no linked orders)', 'Must Have'),
        ('PRD-SUP-004', 'Upload commercial registration / tax card (file storage)', 'Should Have'),
        ('PRD-SUP-005', 'View supplier purchase history', 'Should Have'),
    ]
)
doc.add_paragraph()

# ── 6.8 Categories & Departments ───────────────────────────────────────────────
add_heading(doc, '6.8  Categories & Departments Module', level=2)
add_para(doc, 'Manages product categories and organisational departments. Departments can be synced from the RSYI HR System.', size=11)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-CAT-001', 'CRUD for product categories', 'Must Have'),
        ('PRD-DEP-001', 'CRUD for departments', 'Must Have'),
        ('PRD-DEP-002', 'Import departments from HR system (API sync)', 'Should Have'),
        ('PRD-DEP-003', 'Link departments to employees from HR system', 'Should Have'),
    ]
)
doc.add_paragraph()

# ── 6.9 Approval & Electronic Signature ────────────────────────────────────────
add_heading(doc, '6.9  Approval & Electronic Signature Module', level=2)
add_para(doc, 'Laravel files: app/Models/UserPermission.php  |  app/Services/SignatureService.php', italic=True, size=10)
add_table(doc,
    ['ID', 'Requirement', 'Priority'],
    [
        ('PRD-SIG-001', 'User uploads signature image (stored in storage/app/public/signatures/)', 'Must Have'),
        ('PRD-SIG-002', 'Signature embedded in printed permits', 'Must Have'),
        ('PRD-SIG-003', 'Approval recorded with approver ID, timestamp, signature URL', 'Must Have'),
        ('PRD-SIG-004', 'Only authorised approvers can approve orders', 'Must Have'),
        ('PRD-SIG-005', 'Signature viewable in order detail modal', 'Should Have'),
    ]
)
doc.add_paragraph()

# ── 6.10 Reports ────────────────────────────────────────────────────────────────
add_heading(doc, '6.10  Reports Module', level=2)
add_para(doc, 'Laravel files: app/Services/ReportService.php  |  app/Http/Controllers/ReportController.php', italic=True, size=10)
add_table(doc,
    ['Report', 'Description', 'Export Formats'],
    [
        ('Stock Report',                   'Current balance for all items',                         'PDF, Excel'),
        ('Low Stock Report',               'Items at or below minimum level',                      'PDF, Excel'),
        ('Out of Stock Report',            'Items with zero balance',                              'PDF, Excel'),
        ('Transactions Report',            'All movements filtered by date/type/item',             'PDF, Excel'),
        ('Department Consumption Report',  'Total withdrawal cost per department in a period',     'PDF, Excel'),
        ('Product Movement Report',        'Full in/out history for a specific item',              'PDF, Excel'),
        ('Supplier Purchase Report',       'Total purchases per supplier in a period',             'PDF, Excel'),
    ]
)
doc.add_paragraph()

# ── 6.11 Settings ──────────────────────────────────────────────────────────────
add_heading(doc, '6.11  Settings & Configuration Module', level=2)
add_table(doc,
    ['Setting', 'Description'],
    [
        ('Institute name',      'Displayed on printed permits'),
        ('Logo URL',            'Displayed on printed permits'),
        ('Currency',            'Currency label used in reports'),
        ('HR API URL',          'Base URL for RSYI HR System integration'),
        ('HR API Key',          'Authentication key for HR API'),
        ('Default language',    'ar or en'),
    ]
)
doc.add_paragraph()

# ── 6.12 Import/Export ─────────────────────────────────────────────────────────
add_heading(doc, '6.12  Import / Export Module', level=2)
add_para(doc, 'Uses the maatwebsite/excel package (Laravel Excel) for all spreadsheet operations.', size=11)
add_table(doc,
    ['Operation', 'Entity', 'Package'],
    [
        ('Import', 'Products',         'Laravel Excel (Maatwebsite)'),
        ('Import', 'Opening Balances', 'Laravel Excel'),
        ('Export', 'All Reports',      'Laravel Excel + DomPDF / Barryvdh Laravel-DOMPDF'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 7. USER STORIES
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '7. User Stories', level=1)

stories = [
    ('Warehouse Keeper', 'create a multi-item withdrawal order',
     'stock is formally disbursed with a sequential permit number'),
    ('Dean / Approver', 'review pending withdrawal orders and approve with my digital signature',
     'disbursement is authorised and my signature appears on the printed permit'),
    ('Warehouse Keeper', 'execute an approved withdrawal order',
     'stock is deducted using FIFO and a transaction record is created'),
    ('Warehouse Keeper', 'create a return order from a completed withdrawal',
     'returned items are added back to stock and a return permit is printed'),
    ('Warehouse Keeper', 'create a custody return for a completed custody order',
     'the return is logged in the audit trail without changing stock balances'),
    ('System Admin', 'generate a purchase request automatically for low-stock items',
     'procurement can order the right quantities before stockout'),
    ('Procurement Officer', 'see the last purchase price for each item',
     'I can negotiate better deals with suppliers'),
    ('Department Head', 'see a consumption report for my department',
     'I can monitor spending and plan budgets'),
    ('Warehouse Keeper', 'import a product list from Excel',
     'initial setup is done quickly without manual entry'),
    ('System Admin', 'assign granular permissions per user',
     'each role accesses only what they need'),
]
for actor, action, outcome in stories:
    p = doc.add_paragraph()
    p.add_run(f'As a ').font.size = Pt(11)
    r = p.add_run(actor)
    r.font.bold = True
    r.font.size = Pt(11)
    p.add_run(f', I want to ').font.size = Pt(11)
    p.add_run(action).font.italic = True
    p.add_run(f', so that ').font.size = Pt(11)
    p.add_run(outcome).font.size = Pt(11)
    p.paragraph_format.space_after = Pt(6)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 8. NON-FUNCTIONAL REQUIREMENTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '8. Non-Functional Requirements', level=1)

add_table(doc,
    ['Category', 'Requirement'],
    [
        ('Performance',    'API response time p95 < 300 ms; report generation < 5 s'),
        ('Scalability',    'Support 100+ concurrent users without degradation'),
        ('Availability',   '99.5% uptime; zero-downtime deployment via rolling update'),
        ('Security',       'CSRF protection, XSS prevention, SQL injection prevention via Eloquent parameterised queries'),
        ('Data Integrity', 'All stock mutations in DB::transaction() blocks with rollback on failure'),
        ('Localisation',   'Full Arabic (RTL) and English support via Laravel lang files'),
        ('Auditability',   'All create/update/delete actions logged in an audit_logs table with user_id and timestamp'),
        ('Backups',        'Daily automated DB backup; 30-day retention'),
        ('Accessibility',  'WCAG 2.1 AA compliance for the admin panel'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 9. LARAVEL TECHNICAL ARCHITECTURE
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '9. Laravel Technical Architecture', level=1)

add_heading(doc, '9.1 Technology Stack', level=2)
add_table(doc,
    ['Layer', 'Technology', 'Version'],
    [
        ('Backend Framework',  'Laravel',              '11.x'),
        ('Language',           'PHP',                  '8.2+'),
        ('Database',           'MySQL',                '8.0+'),
        ('Cache / Queue',      'Redis',                '7.x'),
        ('Frontend',           'Blade + Alpine.js',    'Alpine 3.x'),
        ('CSS Framework',      'Tailwind CSS',         '3.x'),
        ('Authentication',     'Laravel Breeze / Sanctum', 'latest'),
        ('Excel',              'maatwebsite/excel',    '3.x'),
        ('PDF',                'barryvdh/laravel-dompdf', 'latest'),
        ('Testing',            'PHPUnit + Pest',       'Pest 3.x'),
        ('CI/CD',              'GitHub Actions',       '-'),
        ('Server',             'Nginx + PHP-FPM',      '-'),
    ]
)

doc.add_paragraph()
add_heading(doc, '9.2 Service Layer Pattern', level=2)
add_para(doc, 'Controllers remain thin — they validate input (via Form Requests) and delegate to Service classes:', size=11)
add_code_block(doc,
"""// app/Http/Controllers/WithdrawalOrderController.php
public function complete(WithdrawalOrder $order, StockService $stock): JsonResponse
{
    $this->authorize('complete', $order);
    $stock->completeWithdrawal($order);          // all logic in Service
    return response()->json(['success' => true]);
}

// app/Services/StockService.php
public function completeWithdrawal(WithdrawalOrder $order): void
{
    if ($order->status !== 'approved') {
        throw new InvalidOrderStateException();
    }
    DB::transaction(function () use ($order) {
        foreach ($order->items as $item) {
            $this->fifo->deduct($item->product_id, $item->approved_quantity, $order->id);
        }
        $order->update(['status' => 'completed']);
    });
}
""")

doc.add_paragraph()
add_heading(doc, '9.3 Route Structure', level=2)
add_code_block(doc,
"""// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('warehouse')->name('warehouse.')->group(function () {

        Route::resource('products',           ProductController::class);
        Route::resource('add-orders',         AddOrderController::class);
        Route::resource('withdrawal-orders',  WithdrawalOrderController::class);
        Route::resource('return-orders',      ReturnOrderController::class);
        Route::resource('purchase-requests',  PurchaseRequestController::class);
        Route::resource('suppliers',          SupplierController::class);
        Route::resource('categories',         CategoryController::class);
        Route::resource('departments',        DepartmentController::class);

        // State transitions
        Route::post('withdrawal-orders/{order}/approve',   [WithdrawalOrderController::class, 'approve'])
             ->name('withdrawal-orders.approve')
             ->middleware('can:approve,order');
        Route::post('withdrawal-orders/{order}/complete',  [WithdrawalOrderController::class, 'complete'])
             ->name('withdrawal-orders.complete');
        Route::post('return-orders/{order}/approve',       [ReturnOrderController::class, 'approve'])
             ->name('return-orders.approve');
        Route::post('return-orders/{order}/complete',      [ReturnOrderController::class, 'complete'])
             ->name('return-orders.complete');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('stock',                  [ReportController::class, 'stock']);
            Route::get('low-stock',              [ReportController::class, 'lowStock']);
            Route::get('out-of-stock',           [ReportController::class, 'outOfStock']);
            Route::get('transactions',           [ReportController::class, 'transactions']);
            Route::get('department-consumption', [ReportController::class, 'deptConsumption']);
            Route::get('product-movement',       [ReportController::class, 'productMovement']);
        });

        // Print
        Route::get('print/add-permit/{order}',      [PrintController::class, 'addPermit']);
        Route::get('print/withdrawal-permit/{order}',[PrintController::class, 'withdrawalPermit']);
        Route::get('print/return-permit/{order}',   [PrintController::class, 'returnPermit']);
        Route::get('print/purchase-request/{req}',  [PrintController::class, 'purchaseRequest']);

        // Settings & Signature
        Route::get('settings',           [SettingController::class, 'index']);
        Route::put('settings',           [SettingController::class, 'update']);
        Route::post('signature/upload',  [SignatureController::class, 'upload']);
    });
});
""")

doc.add_paragraph()
add_heading(doc, '9.4 Event / Notification Flow', level=2)
add_table(doc,
    ['Event', 'Listener / Action'],
    [
        ('WithdrawalOrderCreated',   'Notify approvers via database + email notification'),
        ('WithdrawalOrderApproved',  'Notify requester; trigger print-ready status'),
        ('StockLevelLow',            'Notify procurement officer; optionally auto-create purchase request'),
        ('ReturnOrderCompleted',     'Update stock (if normal); log transaction; notify warehouse keeper'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 10. DATABASE DESIGN
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '10. Database Design', level=1)
add_para(doc, 'All tables use the prefix iw_. Managed by Laravel Migrations.', italic=True, size=10)
doc.add_paragraph()

tables = {
    'iw_products': [
        ('id',            'bigint UNSIGNED PK AUTO_INCREMENT'),
        ('name',          'varchar(255) NOT NULL'),
        ('sku',           'varchar(100) UNIQUE NULLABLE'),
        ('category_id',   'bigint FK → iw_categories.id'),
        ('unit',          'varchar(50) NOT NULL'),
        ('min_stock',     'int DEFAULT 0'),
        ('max_stock',     'int NULLABLE'),
        ('price',         'decimal(12,2) DEFAULT 0'),
        ('current_stock', 'int DEFAULT 0  [computed & cached]'),
        ('created_at',    'timestamp'),
        ('updated_at',    'timestamp'),
    ],
    'iw_transactions': [
        ('id',               'bigint UNSIGNED PK'),
        ('transaction_type', "ENUM('opening_balance','add','withdraw','return','custody_return')"),
        ('product_id',       'bigint FK → iw_products.id'),
        ('order_id',         'bigint NULLABLE [polymorphic reference]'),
        ('order_type',       "varchar(50) [add_order | withdrawal_order | return_order]"),
        ('quantity',         'int NOT NULL'),
        ('remaining_qty',    'int DEFAULT 0  [FIFO pool]'),
        ('unit_price',       'decimal(12,2) DEFAULT 0'),
        ('notes',            'text NULLABLE'),
        ('created_by',       'bigint FK → users.id'),
        ('created_at',       'timestamp'),
    ],
    'iw_add_orders': [
        ('id',           'bigint UNSIGNED PK'),
        ('order_number', 'varchar(50) UNIQUE'),
        ('supplier_id',  'bigint FK → iw_suppliers.id NULLABLE'),
        ('invoice_ref',  'varchar(100) NULLABLE'),
        ('total_amount', 'decimal(14,2) DEFAULT 0'),
        ('notes',        'text NULLABLE'),
        ('created_by',   'bigint FK → users.id'),
        ('created_at',   'timestamp'),
        ('updated_at',   'timestamp'),
    ],
    'iw_add_order_items': [
        ('id',         'bigint UNSIGNED PK'),
        ('order_id',   'bigint FK → iw_add_orders.id'),
        ('product_id', 'bigint FK → iw_products.id'),
        ('quantity',   'int NOT NULL'),
        ('unit_price', 'decimal(12,2) NOT NULL'),
    ],
    'iw_withdrawal_orders': [
        ('id',              'bigint UNSIGNED PK'),
        ('order_number',    'varchar(50) UNIQUE'),
        ('order_type',      "ENUM('normal','custody') DEFAULT 'normal'"),
        ('department_id',   'bigint FK → iw_departments.id DEFAULT 0'),
        ('employee_id',     'bigint FK → users.id DEFAULT 0'),
        ('department_name', 'varchar(255) NULLABLE'),
        ('employee_name',   'varchar(255) NULLABLE'),
        ('status',          "ENUM('pending','approved','completed','cancelled') DEFAULT 'pending'"),
        ('notes',           'text NULLABLE'),
        ('approved_by',     'bigint FK → users.id NULLABLE'),
        ('approved_at',     'timestamp NULLABLE'),
        ('signature_url',   'varchar(500) NULLABLE'),
        ('created_by',      'bigint FK → users.id'),
        ('created_at',      'timestamp'),
        ('updated_at',      'timestamp'),
    ],
    'iw_withdrawal_order_items': [
        ('id',                'bigint UNSIGNED PK'),
        ('order_id',          'bigint FK → iw_withdrawal_orders.id'),
        ('product_id',        'bigint FK → iw_products.id'),
        ('quantity',          'int NOT NULL'),
        ('approved_quantity', 'int NULLABLE'),
        ('unit_price',        'decimal(12,2) DEFAULT 0'),
    ],
    'iw_return_orders': [
        ('id',               'bigint UNSIGNED PK'),
        ('order_number',     "varchar(50) UNIQUE  [RT-YYYY-NNNNN or CRT-YYYY-NNNNN]"),
        ('order_type',       "ENUM('normal','custody') DEFAULT 'normal'"),
        ('original_order_id','bigint FK → iw_withdrawal_orders.id NULLABLE'),
        ('department_id',    'bigint DEFAULT 0'),
        ('employee_id',      'bigint DEFAULT 0'),
        ('department_name',  'varchar(255) NULLABLE'),
        ('employee_name',    'varchar(255) NULLABLE'),
        ('status',           "ENUM('pending','approved','completed','rejected') DEFAULT 'pending'"),
        ('notes',            'text NULLABLE'),
        ('approved_by',      'bigint FK → users.id NULLABLE'),
        ('approved_at',      'timestamp NULLABLE'),
        ('signature_url',    'varchar(500) NULLABLE'),
        ('rejection_reason', 'text NULLABLE'),
        ('created_by',       'bigint FK → users.id'),
        ('created_at',       'timestamp'),
        ('updated_at',       'timestamp'),
    ],
    'iw_return_order_items': [
        ('id',                'bigint UNSIGNED PK'),
        ('order_id',          'bigint FK → iw_return_orders.id'),
        ('product_id',        'bigint FK → iw_products.id'),
        ('quantity',          'int NOT NULL'),
        ('approved_quantity', 'int NULLABLE'),
        ('unit_price',        'decimal(12,2) DEFAULT 0'),
    ],
    'iw_purchase_requests': [
        ('id',           'bigint UNSIGNED PK'),
        ('order_number', 'varchar(50) UNIQUE'),
        ('status',       "ENUM('pending','approved','completed') DEFAULT 'pending'"),
        ('notes',        'text NULLABLE'),
        ('approved_by',  'bigint FK → users.id NULLABLE'),
        ('approved_at',  'timestamp NULLABLE'),
        ('signature_url','varchar(500) NULLABLE'),
        ('created_by',   'bigint FK → users.id'),
        ('created_at',   'timestamp'),
        ('updated_at',   'timestamp'),
    ],
    'iw_purchase_request_items': [
        ('id',               'bigint UNSIGNED PK'),
        ('request_id',       'bigint FK → iw_purchase_requests.id'),
        ('product_id',       'bigint FK → iw_products.id'),
        ('quantity',         'int NOT NULL'),
        ('unit_price',       'decimal(12,2) DEFAULT 0'),
        ('supplier_id',      'bigint NULLABLE'),
    ],
    'iw_suppliers': [
        ('id',           'bigint UNSIGNED PK'),
        ('name',         'varchar(255) NOT NULL'),
        ('phone',        'varchar(50) NULLABLE'),
        ('email',        'varchar(255) NULLABLE'),
        ('address',      'text NULLABLE'),
        ('tax_number',   'varchar(100) NULLABLE'),
        ('cr_file',      'varchar(500) NULLABLE  [storage path]'),
        ('created_by',   'bigint FK → users.id'),
        ('created_at',   'timestamp'),
        ('updated_at',   'timestamp'),
    ],
    'iw_categories': [
        ('id',         'bigint UNSIGNED PK'),
        ('name',       'varchar(255) NOT NULL'),
        ('created_at', 'timestamp'),
        ('updated_at', 'timestamp'),
    ],
    'iw_departments': [
        ('id',         'bigint UNSIGNED PK'),
        ('name',       'varchar(255) NOT NULL'),
        ('hr_id',      'int NULLABLE  [ID in RSYI HR system]'),
        ('created_at', 'timestamp'),
        ('updated_at', 'timestamp'),
    ],
    'iw_user_permissions': [
        ('id',          'bigint UNSIGNED PK'),
        ('user_id',     'bigint FK → users.id'),
        ('permission',  'varchar(100) NOT NULL'),
        ('created_at',  'timestamp'),
    ],
    'iw_settings': [
        ('id',    'bigint UNSIGNED PK'),
        ('key',   'varchar(100) UNIQUE NOT NULL'),
        ('value', 'text NULLABLE'),
    ],
}

for tbl, cols in tables.items():
    add_heading(doc, tbl, level=3)
    add_table(doc, ['Column', 'Type / Description'], cols, header_color='365F91')
    doc.add_paragraph()

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 11. API ENDPOINTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '11. API Endpoints', level=1)
add_para(doc, 'All endpoints are prefixed with /api/warehouse and require Bearer token authentication (Sanctum).', size=11)
doc.add_paragraph()

api_endpoints = [
    # Products
    ('GET',    '/products',                         'List all products with current stock'),
    ('POST',   '/products',                         'Create product'),
    ('GET',    '/products/{id}',                    'Get product detail'),
    ('PUT',    '/products/{id}',                    'Update product'),
    ('DELETE', '/products/{id}',                    'Delete product'),
    ('POST',   '/products/import',                  'Import from Excel'),
    # Add Orders
    ('GET',    '/add-orders',                       'List add orders'),
    ('POST',   '/add-orders',                       'Create add order'),
    ('GET',    '/add-orders/{id}',                  'Get add order detail'),
    ('DELETE', '/add-orders/{id}',                  'Delete add order (reverse stock)'),
    # Withdrawal Orders
    ('GET',    '/withdrawal-orders',                'List withdrawal orders (filterable)'),
    ('POST',   '/withdrawal-orders',                'Create withdrawal order'),
    ('GET',    '/withdrawal-orders/{id}',           'Get withdrawal order detail'),
    ('POST',   '/withdrawal-orders/{id}/approve',   'Approve with signature'),
    ('POST',   '/withdrawal-orders/{id}/complete',  'Execute (deduct stock via FIFO)'),
    ('POST',   '/withdrawal-orders/{id}/cancel',    'Cancel order'),
    # Return Orders
    ('GET',    '/return-orders',                    'List return orders'),
    ('POST',   '/return-orders',                    'Create return order'),
    ('GET',    '/return-orders/{id}',               'Get return order detail'),
    ('POST',   '/return-orders/{id}/approve',       'Approve with signature'),
    ('POST',   '/return-orders/{id}/complete',      'Execute (add to stock / log)'),
    ('POST',   '/return-orders/{id}/reject',        'Reject with reason'),
    ('DELETE', '/return-orders/{id}',               'Delete pending return order'),
    # Purchase Requests
    ('GET',    '/purchase-requests',                'List purchase requests'),
    ('POST',   '/purchase-requests',                'Create manual request'),
    ('POST',   '/purchase-requests/auto-generate',  'Auto-generate from low-stock items'),
    ('POST',   '/purchase-requests/{id}/approve',   'Approve with signature'),
    # Reports
    ('GET',    '/reports/stock',                    'Stock report (all items)'),
    ('GET',    '/reports/low-stock',                'Low stock items'),
    ('GET',    '/reports/out-of-stock',             'Out-of-stock items'),
    ('GET',    '/reports/transactions',             'Transaction history (date/type filter)'),
    ('GET',    '/reports/department-consumption',   'Consumption per department'),
    ('GET',    '/reports/product-movement',         'Movement history for one product'),
    # Misc
    ('GET',    '/suppliers',                        'List suppliers'),
    ('POST',   '/suppliers',                        'Create supplier'),
    ('GET',    '/categories',                       'List categories'),
    ('GET',    '/departments',                      'List departments'),
    ('GET',    '/settings',                         'Get all settings'),
    ('PUT',    '/settings',                         'Update settings'),
    ('POST',   '/signature/upload',                 'Upload user signature image'),
]

add_table(doc,
    ['Method', 'Endpoint', 'Description'],
    api_endpoints
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 12. PERMISSIONS & ROLE SYSTEM
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '12. Permissions & Role System', level=1)
add_para(doc, 'Implemented using Laravel Policies and a custom iw_user_permissions table. Gates are registered in AuthServiceProvider.', size=11)
doc.add_paragraph()

add_table(doc,
    ['Permission Slug', 'admin', 'warehouse_keeper', 'approver', 'dept_head', 'procurement'],
    [
        ('products.view',            '✓', '✓', '✓', '✓', '✗'),
        ('products.create',          '✓', '✓', '✗', '✗', '✗'),
        ('products.edit',            '✓', '✓', '✗', '✗', '✗'),
        ('products.delete',          '✓', '✗', '✗', '✗', '✗'),
        ('add_orders.view',          '✓', '✓', '✗', '✗', '✗'),
        ('add_orders.create',        '✓', '✓', '✗', '✗', '✗'),
        ('withdrawal_orders.view',   '✓', '✓', '✓', 'own', '✗'),
        ('withdrawal_orders.create', '✓', '✓', '✗', '✓', '✗'),
        ('withdrawal_orders.approve','✓', '✗', '✓', '✗', '✗'),
        ('withdrawal_orders.complete','✓','✓', '✗', '✗', '✗'),
        ('return_orders.create',     '✓', '✓', '✗', '✗', '✗'),
        ('return_orders.approve',    '✓', '✗', '✓', '✗', '✗'),
        ('purchase_requests.view',   '✓', '✓', '✗', '✗', '✓'),
        ('purchase_requests.create', '✓', '✓', '✗', '✗', '✓'),
        ('purchase_requests.approve','✓', '✗', '✓', '✗', '✗'),
        ('reports.view',             '✓', '✓', '✓', 'own', '✓'),
        ('settings.manage',          '✓', '✗', '✗', '✗', '✗'),
        ('permissions.manage',       '✓', '✗', '✗', '✗', '✗'),
    ]
)

doc.add_paragraph()
add_para(doc, 'Laravel Policy Example:', bold=True)
add_code_block(doc,
"""// app/Policies/WithdrawalOrderPolicy.php
public function approve(User $user, WithdrawalOrder $order): bool
{
    return $user->hasPermission('withdrawal_orders.approve')
        && $order->status === 'pending';
}

public function complete(User $user, WithdrawalOrder $order): bool
{
    return $user->hasPermission('withdrawal_orders.complete')
        && $order->status === 'approved';
}
""")

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 13. SECURITY REQUIREMENTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '13. Security Requirements', level=1)

add_table(doc,
    ['Category', 'Requirement', 'Laravel Implementation'],
    [
        ('Authentication',    'Session-based auth for web, token for API',       'Laravel Breeze (web) + Sanctum (API)'),
        ('Authorisation',     'RBAC with least-privilege principle',             'Laravel Policies + Gates'),
        ('CSRF',              'All state-changing web requests protected',       'Laravel CSRF middleware (default)'),
        ('XSS',               'All output escaped',                              'Blade {{ }} escaping; never {!! !!} with user input'),
        ('SQL Injection',     'Parameterised queries only',                      'Eloquent ORM + Query Builder bindings'),
        ('Input Validation',  'Server-side validation on all inputs',            'Laravel Form Requests with rules()'),
        ('File Upload',       'Validate mime type, size, virus scan',            'Storage::putFile + mime validation'),
        ('Rate Limiting',     'API rate limiting to prevent abuse',              'throttle:60,1 middleware on API routes'),
        ('Audit Logging',     'Log all data mutations',                          'Model events (created/updated/deleted) → audit_logs'),
        ('Encryption',        'Sensitive settings encrypted at rest',            'Laravel encrypt() / Crypt facade'),
        ('HTTPS',             'All traffic over TLS',                            'Nginx + Let\'s Encrypt / server config'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 14. UI/UX REQUIREMENTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '14. UI/UX Requirements', level=1)

add_table(doc,
    ['Requirement', 'Details'],
    [
        ('Bilingual',            'Full Arabic (RTL) and English support; switchable via user preference'),
        ('Responsive Design',    'Works on desktop, tablet; mobile-friendly tables'),
        ('Dark/Light Mode',      'Optional; stored in user preferences'),
        ('Print Permits',        'Clean print layout: institute logo, name, order data, signature, QR code'),
        ('Status Badges',        'Colour-coded: pending=yellow, approved=green, completed=blue, rejected=red'),
        ('Loading States',       'Spinners on all async actions'),
        ('Confirmation Dialogs', 'Required for destructive actions (delete, cancel)'),
        ('Toast Notifications',  'Success/error feedback after each action'),
        ('Modals',               'Create/View order flows use slide-over or modal dialogs'),
        ('Data Tables',          'Sortable, filterable, paginated for all list views'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 15. TESTING REQUIREMENTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '15. Testing Requirements', level=1)

add_table(doc,
    ['Test Type', 'Tool', 'Coverage Target', 'Key Scenarios'],
    [
        ('Unit Tests',       'Pest PHP',         '80%+ per Service class', 'FifoService, StockService, OrderNumberService'),
        ('Feature Tests',    'Pest + Laravel HTTP', '70%+ per Controller', 'Full workflow: create → approve → complete for each order type'),
        ('Database Tests',   'RefreshDatabase trait', '100% of migrations', 'All FK constraints, default values, indexes'),
        ('API Tests',        'Pest + TestCase',   '100% of API endpoints', 'Auth, validation, response shape, status codes'),
        ('Policy Tests',     'Pest',              'All policies',          'Each role can/cannot perform each action'),
        ('Browser Tests',    'Laravel Dusk',      'Critical paths',        'Approval workflow, print preview'),
    ]
)

doc.add_paragraph()
add_heading(doc, 'Critical Test Scenarios', level=2)
scenarios = [
    'FIFO deduction: two batches at different prices; verify correct remaining_qty after partial deduction',
    'Insufficient stock: attempt to create withdrawal exceeding balance → expect 422 validation error',
    'Concurrent withdrawals: two simultaneous requests for the last unit → only one succeeds (lockForUpdate)',
    'Return to stock: complete normal return → verify current_stock increased and transaction recorded',
    'Custody return: complete custody return → verify current_stock unchanged, transaction logged',
    'Permission gate: non-approver attempts to call approve endpoint → expect 403',
    'Order number uniqueness: generate 1000 orders concurrently → no duplicate order numbers',
]
for s in scenarios:
    p = doc.add_paragraph(s, style='List Bullet')
    p.runs[0].font.size = Pt(10)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 16. ROADMAP
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '16. Roadmap & Future Development', level=1)

add_table(doc,
    ['Phase', 'Milestone', 'Features'],
    [
        ('Phase 1 — MVP', 'v1.0',
         'Products, Opening Balance, Add Orders, Withdrawal Orders, FIFO, Approval, Print'),
        ('Phase 2 — Core Complete', 'v1.5',
         'Return Orders, Custody Return, Purchase Requests, Suppliers, Basic Reports'),
        ('Phase 3 — Advanced', 'v2.0',
         'Full Reports (Excel/PDF export), Department Consumption, Low-Stock Alerts, Auto Purchase Requests'),
        ('Phase 4 — Integration', 'v2.5',
         'RSYI HR System API integration, Department/Employee sync, Multi-branch support'),
        ('Phase 5 — Intelligence', 'v3.0',
         'Predictive reorder suggestions, spending analytics dashboard, mobile app (Expo React Native)'),
    ]
)

doc.add_page_break()

# ══════════════════════════════════════════════════════════════════════════════
# 17. RISKS & CONSTRAINTS
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, '17. Risks & Constraints', level=1)

add_table(doc,
    ['Risk', 'Likelihood', 'Impact', 'Mitigation'],
    [
        ('Concurrent stock deduction race condition',
         'Medium', 'High',
         'Use DB::transaction + lockForUpdate in FifoService; write concurrency tests'),
        ('HR system API downtime / schema change',
         'Medium', 'Medium',
         'Cache department/employee data locally; graceful fallback to manual entry'),
        ('Large Excel imports (10k+ rows) timing out',
         'Low', 'Medium',
         'Process via Laravel Queue job with progress tracking'),
        ('User resistance to digital workflow',
         'Medium', 'Medium',
         'Training sessions; simplified UI; bilingual support'),
        ('Report generation slowness on large datasets',
         'Medium', 'Medium',
         'Eager-load relations; add DB indexes; async export via queue'),
        ('FIFO balance drift due to manual DB edits',
         'Low', 'High',
         'Restrict direct DB access; add stock reconciliation command (php artisan iw:reconcile)'),
        ('File storage growth (signatures, documents)',
         'Low', 'Low',
         'Implement S3-compatible object storage (Laravel Filesystem abstraction)'),
    ]
)

doc.add_paragraph()

# ══════════════════════════════════════════════════════════════════════════════
# APPENDIX A: Migration Example
# ══════════════════════════════════════════════════════════════════════════════
add_heading(doc, 'Appendix A: Sample Laravel Migration', level=1)
add_code_block(doc,
"""<?php
// database/migrations/2026_03_01_000001_create_iw_return_orders_table.php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('iw_return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->enum('order_type', ['normal', 'custody'])->default('normal');
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->unsignedBigInteger('department_id')->default(0);
            $table->unsignedBigInteger('employee_id')->default(0);
            $table->string('department_name')->nullable();
            $table->string('employee_name')->nullable();
            $table->enum('status', ['pending','approved','completed','rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signature_url', 500)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('status');
            $table->index('order_type');
            $table->index('original_order_id');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('iw_return_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('iw_return_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('iw_products');
            $table->integer('quantity');
            $table->integer('approved_quantity')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iw_return_order_items');
        Schema::dropIfExists('iw_return_orders');
    }
};
""")

doc.add_paragraph()
add_heading(doc, 'Appendix B: Order Number Generation Service', level=1)
add_code_block(doc,
"""<?php
// app/Services/OrderNumberService.php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;

class OrderNumberService
{
    public function generate(string $prefix, string $table, string $column = 'order_number'): string
    {
        return DB::transaction(function () use ($prefix, $table, $column) {
            $year = now()->format('Y');
            $like = "{$prefix}-{$year}-%";

            $last = DB::table($table)
                      ->where($column, 'like', $like)
                      ->lockForUpdate()
                      ->orderByDesc($column)
                      ->value($column);

            $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

            return sprintf('%s-%s-%05d', $prefix, $year, $seq);
        });
    }
}

// Usage:
// ADD-YYYYMM-NNNN  → $service->generate('ADD-'.now()->format('Ym'), 'iw_add_orders')
// WD-YYYY-NNNNN    → $service->generate('WD-'.now()->format('Y'),   'iw_withdrawal_orders')
// RT-YYYY-NNNNN    → $service->generate('RT-'.now()->format('Y'),   'iw_return_orders')
// CRT-YYYY-NNNNN   → $service->generate('CRT-'.now()->format('Y'),  'iw_return_orders')
""")

# ── Save ───────────────────────────────────────────────────────────────────────
output_path = '/home/user/institute-warehouse/Institute-Warehouse-Laravel-PRD.docx'
doc.save(output_path)
print(f'PRD saved to: {output_path}')
