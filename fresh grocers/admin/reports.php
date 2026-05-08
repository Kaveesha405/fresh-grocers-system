<?php
$page_title = "Sales & Performance Reports";
include '../includes/admin-header.php';

$start_date  = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date    = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

$start_date  = mysqli_real_escape_string($conn, $start_date);
$end_date    = mysqli_real_escape_string($conn, $end_date);
$report_type = mysqli_real_escape_string($conn, $report_type);

// ── FETCH ALL DATA ALWAYS ──────────────────────────────────
$sales_rows = [];
$totals = ['total_revenue'=>0,'total_orders'=>0,'avg_order'=>0,'delivered'=>0,'canceled'=>0];
$chart_labels = $chart_revenue = $chart_orders = [];

$sr = $conn->query("SELECT DATE(o.OrderDate) AS date, COUNT(o.OrderID) AS total_orders, SUM(o.TotalAmount) AS total_revenue, AVG(o.TotalAmount) AS avg_order_value FROM `Order` o WHERE DATE(o.OrderDate) BETWEEN '$start_date' AND '$end_date' GROUP BY DATE(o.OrderDate) ORDER BY date DESC");
if ($sr) $sales_rows = $sr->fetch_all(MYSQLI_ASSOC);

$tr = $conn->query("SELECT COUNT(o.OrderID) AS total_orders, SUM(o.TotalAmount) AS total_revenue, AVG(o.TotalAmount) AS avg_order, COUNT(CASE WHEN o.OrderStatus='Delivered' THEN 1 END) AS delivered, COUNT(CASE WHEN o.OrderStatus='Canceled' THEN 1 END) AS canceled FROM `Order` o WHERE DATE(o.OrderDate) BETWEEN '$start_date' AND '$end_date'");
if ($tr) $totals = array_merge($totals, $tr->fetch_assoc());

foreach (array_reverse($sales_rows) as $r) {
    $chart_labels[]  = date('d M', strtotime($r['date']));
    $chart_revenue[] = round((float)$r['total_revenue'], 2);
    $chart_orders[]  = (int)$r['total_orders'];
}

$prod_rows = [];
$pr = $conn->query("SELECT p.ProductName, p.Category, SUM(oi.Quantity) AS units_sold, SUM(oi.Quantity * oi.UnitPrice) AS revenue, COUNT(DISTINCT o.OrderID) AS orders FROM OrderItem oi JOIN Product p ON oi.ProductID=p.ProductID JOIN `Order` o ON oi.OrderID=o.OrderID WHERE DATE(o.OrderDate) BETWEEN '$start_date' AND '$end_date' GROUP BY p.ProductID ORDER BY revenue DESC");
if ($pr) $prod_rows = $pr->fetch_all(MYSQLI_ASSOC);

$agent_rows = [];
$ar = $conn->query("SELECT CONCAT(da.FirstName,' ',da.LastName) AS agent_name, COUNT(o.OrderID) AS deliveries, COUNT(CASE WHEN o.OrderStatus='Delivered' THEN 1 END) AS completed, COUNT(CASE WHEN o.OrderStatus NOT IN ('Delivered','Canceled') THEN 1 END) AS pending, ROUND((COUNT(CASE WHEN o.OrderStatus='Delivered' THEN 1 END)/NULLIF(COUNT(o.OrderID),0))*100,2) AS completion_rate FROM DeliveryAgent da LEFT JOIN `Order` o ON da.DeliveryAgentID=o.DeliveryAgentID AND DATE(o.OrderDate) BETWEEN '$start_date' AND '$end_date' GROUP BY da.DeliveryAgentID ORDER BY deliveries DESC");
if ($ar) $agent_rows = $ar->fetch_all(MYSQLI_ASSOC);

$cust_rows = [];
$cr2 = $conn->query("SELECT CONCAT(c.FirstName,' ',c.LastName) AS customer_name, c.Email, COUNT(o.OrderID) AS total_orders, SUM(o.TotalAmount) AS lifetime_value, MAX(o.OrderDate) AS last_order_date FROM Customer c LEFT JOIN `Order` o ON c.CustomerID=o.CustomerID AND DATE(o.OrderDate) BETWEEN '$start_date' AND '$end_date' GROUP BY c.CustomerID HAVING total_orders > 0 ORDER BY lifetime_value DESC");
if ($cr2) $cust_rows = $cr2->fetch_all(MYSQLI_ASSOC);

$status_counts = [];
$scr = $conn->query("SELECT OrderStatus, COUNT(*) AS cnt FROM `Order` WHERE DATE(OrderDate) BETWEEN '$start_date' AND '$end_date' GROUP BY OrderStatus");
if ($scr) while ($s = $scr->fetch_assoc()) $status_counts[$s['OrderStatus']] = (int)$s['cnt'];

$maxRev  = !empty($chart_revenue) ? max($chart_revenue) : 0;
$pv      = array_column($prod_rows,'revenue');
$maxPRev = !empty($pv) ? max($pv) : 0;
$lv      = array_column($cust_rows,'lifetime_value');
$maxLV   = !empty($lv) ? max($lv) : 0;
$rate    = (!empty($totals['total_orders']) && $totals['total_orders'] > 0)
    ? round(($totals['delivered'] / $totals['total_orders']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $page_title; ?> - Fresh Grocers Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<style>
:root { --green:#28a745; --green-dark:#1a7a3c; --green-light:#f0fff4; }
.dashboard-bg { background:#f4f6f9; min-height:calc(100vh - 120px); padding-bottom:3rem; }
.filter-bar { background:#fff; border-radius:14px; border:1px solid #e9ecef; box-shadow:0 2px 8px rgba(0,0,0,.05); padding:1.25rem 1.5rem; }
.filter-bar .form-label { font-size:.78rem; font-weight:700; color:#6c757d; text-transform:uppercase; letter-spacing:.4px; margin-bottom:.3rem; }
.filter-bar .form-select, .filter-bar .form-control { font-size:.9rem; border-radius:8px; border:1px solid #dee2e6 !important; }
.filter-bar .form-select:focus, .filter-bar .form-control:focus { border-color:var(--green) !important; box-shadow:0 0 0 .2rem rgba(40,167,69,.15); }
.rpt-stat { background:#fff; border-radius:14px; border:1px solid #e9ecef; box-shadow:0 2px 8px rgba(0,0,0,.05); padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1rem; transition:transform .2s,box-shadow .2s; }
.rpt-stat:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.09); }
.rpt-stat .icon-box { width:54px; height:54px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0; }
.rpt-stat .label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#9aa0ac; margin-bottom:.2rem; }
.rpt-stat .value { font-size:1.5rem; font-weight:800; color:#212529; line-height:1.1; }
.rpt-stat .sub   { font-size:.75rem; color:#adb5bd; margin-top:.2rem; }
.chart-card { background:#fff; border-radius:14px; border:1px solid #e9ecef; box-shadow:0 2px 8px rgba(0,0,0,.05); padding:1.5rem; }
.chart-card .chart-title { font-size:.85rem; font-weight:700; color:#495057; text-transform:uppercase; letter-spacing:.5px; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
.rpt-table-wrap { background:#fff; border-radius:14px; border:1px solid #e9ecef; box-shadow:0 2px 8px rgba(0,0,0,.05); overflow:hidden; }
.rpt-table-wrap .table-responsive { max-height:520px; overflow-y:auto; }
.rpt-table-wrap .table-responsive::-webkit-scrollbar { width:5px; }
.rpt-table-wrap .table-responsive::-webkit-scrollbar-thumb { background:#dee2e6; border-radius:4px; }
.rpt-table-wrap table thead th { background:#f8f9fa; position:sticky; top:0; z-index:5; border-bottom:2px solid #e9ecef; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6c757d; padding:1rem; }
.rpt-table-wrap table tbody td { padding:.85rem 1rem; vertical-align:middle; border-bottom:1px solid #f5f5f5; font-size:.88rem; }
.rpt-table-wrap table tbody tr:hover { background:#f8fff9; }
.rpt-table-wrap table tbody tr:last-child td { border-bottom:none; }
.rpt-tabs { display:flex; gap:.5rem; flex-wrap:wrap; }
.rpt-tab { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem 1rem; border-radius:8px; font-size:.82rem; font-weight:600; border:1.5px solid #dee2e6; background:#fff; color:#6c757d; text-decoration:none; transition:all .18s; }
.rpt-tab:hover { border-color:var(--green); color:var(--green); background:var(--green-light); }
.rpt-tab.active { background:var(--green); border-color:var(--green); color:#fff; box-shadow:0 3px 8px rgba(40,167,69,.3); }
.prog-bar-wrap { display:flex; align-items:center; gap:.6rem; min-width:140px; }
.prog-bar { flex:1; height:7px; border-radius:4px; background:#e9ecef; overflow:hidden; }
.prog-bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#28a745,#20c997); }
.rank-badge { width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; }
.rank-1{background:#ffd700;color:#7a5c00;} .rank-2{background:#c0c0c0;color:#555;} .rank-3{background:#cd7f32;color:#fff;} .rank-n{background:#f1f3f5;color:#868e96;}
.empty-state { padding:4rem 1rem; text-align:center; }
.empty-state i { font-size:3.5rem; color:#dee2e6; }
.empty-state p { color:#adb5bd; font-size:.9rem; margin-top:.75rem; }
.bg-light-success{background:rgba(25,135,84,.15);color:#198754;} .bg-light-primary{background:rgba(13,110,253,.15);color:#0d6efd;} .bg-light-warning{background:rgba(255,193,7,.2);color:#d39e00;} .bg-light-info{background:rgba(13,202,240,.15);color:#0dcaf0;}

/* Download Modal */
.dl-card { border:2px solid #e9ecef; border-radius:12px; padding:1rem 1.25rem; cursor:pointer; transition:all .18s; background:#fff; user-select:none; }
.dl-card:hover { border-color:var(--green); background:var(--green-light); }
.dl-card.selected { border-color:var(--green); background:var(--green-light); box-shadow:0 0 0 3px rgba(40,167,69,.15); }
.dl-check { width:20px; height:20px; border-radius:50%; border:2px solid #dee2e6; display:flex; align-items:center; justify-content:center; font-size:.7rem; transition:all .18s; flex-shrink:0; }
.dl-card.selected .dl-check { background:var(--green); border-color:var(--green); color:#fff; }
.dl-progress-bar-track { height:8px; border-radius:4px; background:#e9ecef; overflow:hidden; margin-top:.5rem; }
.dl-progress-bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#28a745,#20c997); transition:width .3s ease; }

/* Render overlay - covers screen during capture */
#render-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 99998;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 1rem;
}
#render-overlay.show { display: flex; }
#render-overlay .overlay-msg {
    background: #fff;
    border-radius: 14px;
    padding: 1.5rem 2rem;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    min-width: 320px;
}

/* Render container - shown on screen briefly for html2canvas */
#render-area {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 1100px;
    background: #f4f6f9;
    z-index: 99999;
    display: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,.4);
}
#render-area.show { display: block; }

@media print {
    .no-print, .filter-bar, nav, footer { display:none !important; }
    .dashboard-bg { background:#fff !important; }
    .rpt-table-wrap .table-responsive { max-height:none !important; overflow:visible !important; }
}
</style>
</head>
<body>

<div class="dashboard-bg">
<div class="container-fluid py-4 px-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Sales &amp; Performance Reports</h3>
            <p class="text-muted small mb-0">Analyse business metrics, trends &amp; agent performance</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-success btn-sm rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#downloadModal">
                <i class="bi bi-download me-2"></i>Download Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-4 no-print">
        <form method="GET" action="reports.php" class="row g-3 align-items-end" id="filter-form">
            <div class="col-12 mb-1">
                <div class="rpt-tabs">
                    <?php
                    $tabs=['sales'=>['bi-bar-chart-line-fill','Sales Overview'],'products'=>['bi-box-seam-fill','Product Performance'],'agents'=>['bi-truck-front-fill','Delivery Agents'],'customers'=>['bi-people-fill','Top Customers']];
                    foreach($tabs as $key=>[$icon,$label]):
                    ?>
                    <a href="?report_type=<?php echo $key; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="rpt-tab <?php echo $report_type===$key?'active':''; ?>">
                        <i class="bi <?php echo $icon; ?>"></i> <?php echo $label; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label">Quick Range</label>
                <select class="form-select" id="quick-range">
                    <option value="">Custom</option><option value="today">Today</option><option value="7">Last 7 Days</option><option value="30">Last 30 Days</option><option value="month">This Month</option><option value="year">This Year</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6 d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-semibold flex-fill rounded-3"><i class="bi bi-funnel-fill me-1"></i>Generate</button>
                <a href="reports.php" class="btn btn-outline-secondary rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- ══ SALES ══════════════════════════════════════════ -->
    <?php if ($report_type === 'sales'): ?>
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="rpt-stat"><div class="icon-box bg-light-success"><i class="bi bi-cash-coin"></i></div><div><div class="label">Total Revenue</div><div class="value text-success">Rs. <?php echo number_format((float)($totals['total_revenue']??0),2); ?></div><div class="sub">All orders in period</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="rpt-stat"><div class="icon-box bg-light-primary"><i class="bi bi-bag-check-fill"></i></div><div><div class="label">Total Orders</div><div class="value"><?php echo number_format((int)($totals['total_orders']??0)); ?></div><div class="sub"><?php echo(int)($totals['delivered']??0);?> delivered &middot; <?php echo(int)($totals['canceled']??0);?> canceled</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="rpt-stat"><div class="icon-box bg-light-warning"><i class="bi bi-graph-up"></i></div><div><div class="label">Avg Order Value</div><div class="value">Rs. <?php echo number_format((float)($totals['avg_order']??0),2); ?></div><div class="sub">Per order average</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="rpt-stat"><div class="icon-box bg-light-info"><i class="bi bi-check2-circle"></i></div><div><div class="label">Delivery Rate</div><div class="value"><?php echo $rate; ?>%</div><div class="sub"><?php echo(int)($totals['delivered']??0);?> of <?php echo(int)($totals['total_orders']??0);?> orders</div></div></div></div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="chart-card"><div class="chart-title"><i class="bi bi-bar-chart-line-fill text-success"></i> Revenue Over Time</div><canvas id="revenueChart" height="90"></canvas></div></div>
        <div class="col-lg-4"><div class="chart-card"><div class="chart-title"><i class="bi bi-pie-chart-fill text-primary"></i> Order Status</div><canvas id="statusChart" height="160"></canvas></div></div>
    </div>
    <div class="chart-card mb-4"><div class="chart-title"><i class="bi bi-reception-4 text-warning"></i> Daily Order Volume</div><canvas id="ordersChart" height="55"></canvas></div>
    <div class="rpt-table-wrap">
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
            <span class="fw-bold text-dark small"><i class="bi bi-table me-2 text-success"></i>Daily Breakdown</span>
            <span class="badge bg-light text-muted"><?php echo count($sales_rows); ?> days</span>
        </div>
        <div class="table-responsive"><table class="table mb-0" id="main-table">
            <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th><th>Avg Order Value</th><th>Revenue Share</th></tr></thead>
            <tbody>
            <?php if(!empty($sales_rows)): foreach($sales_rows as $row):
                $share=$maxRev>0?round(($row['total_revenue']/$maxRev)*100):0; ?>
            <tr>
                <td class="fw-semibold"><i class="bi bi-calendar3 text-muted me-2"></i><?php echo date('d M Y, D',strtotime($row['date'])); ?></td>
                <td><span class="badge bg-light text-primary fw-bold px-2"><?php echo(int)$row['total_orders']; ?></span></td>
                <td class="fw-bold text-success">Rs. <?php echo number_format((float)$row['total_revenue'],2); ?></td>
                <td>Rs. <?php echo number_format((float)$row['avg_order_value'],2); ?></td>
                <td><div class="prog-bar-wrap"><div class="prog-bar"><div class="prog-bar-fill" style="width:<?php echo $share; ?>%"></div></div><span class="small text-muted"><?php echo $share; ?>%</span></div></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i><p>No sales data.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <!-- ══ PRODUCTS ═══════════════════════════════════════ -->
    <?php elseif ($report_type === 'products'): ?>
    <div class="chart-card mb-4"><div class="chart-title"><i class="bi bi-bar-chart-fill text-success"></i> Top Products by Revenue</div><canvas id="productChart" height="70"></canvas></div>
    <div class="rpt-table-wrap">
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
            <span class="fw-bold text-dark small"><i class="bi bi-box-seam-fill me-2 text-success"></i>Product Performance</span>
            <span class="badge bg-light text-muted"><?php echo count($prod_rows); ?> products</span>
        </div>
        <div class="table-responsive"><table class="table mb-0" id="main-table">
            <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Units Sold</th><th>Revenue</th><th>Orders</th><th>Revenue Share</th></tr></thead>
            <tbody>
            <?php if(!empty($prod_rows)): foreach($prod_rows as $i=>$row):
                $share=$maxPRev>0?round(($row['revenue']/$maxPRev)*100):0;
                $rank=$i+1; $rc=$rank===1?'rank-1':($rank===2?'rank-2':($rank===3?'rank-3':'rank-n')); ?>
            <tr>
                <td><span class="rank-badge <?php echo $rc; ?>"><?php echo $rank; ?></span></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($row['ProductName']); ?></td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['Category']); ?></span></td>
                <td><span class="fw-bold"><?php echo number_format((int)$row['units_sold']); ?></span> <small class="text-muted">units</small></td>
                <td class="fw-bold text-success">Rs. <?php echo number_format((float)$row['revenue'],2); ?></td>
                <td><?php echo(int)$row['orders']; ?></td>
                <td><div class="prog-bar-wrap"><div class="prog-bar"><div class="prog-bar-fill" style="width:<?php echo $share; ?>%"></div></div><span class="small text-muted"><?php echo $share; ?>%</span></div></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-box"></i><p>No product data.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <!-- ══ AGENTS ═════════════════════════════════════════ -->
    <?php elseif ($report_type === 'agents'): ?>
    <div class="chart-card mb-4"><div class="chart-title"><i class="bi bi-truck-front-fill text-success"></i> Agent Delivery Performance</div><canvas id="agentChart" height="70"></canvas></div>
    <div class="rpt-table-wrap">
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
            <span class="fw-bold text-dark small"><i class="bi bi-truck-front-fill me-2 text-success"></i>Delivery Agents</span>
            <span class="badge bg-light text-muted"><?php echo count($agent_rows); ?> agents</span>
        </div>
        <div class="table-responsive"><table class="table mb-0" id="main-table">
            <thead><tr><th>#</th><th>Agent Name</th><th>Total Deliveries</th><th>Completed</th><th>Pending</th><th>Completion Rate</th></tr></thead>
            <tbody>
            <?php if(!empty($agent_rows)): foreach($agent_rows as $i=>$row):
                $cr=(float)($row['completion_rate']??0); $col=$cr>=80?'#28a745':($cr>=50?'#ffc107':'#dc3545');
                $rank=$i+1; $rc=$rank===1?'rank-1':($rank===2?'rank-2':($rank===3?'rank-3':'rank-n')); ?>
            <tr>
                <td><span class="rank-badge <?php echo $rc; ?>"><?php echo $rank; ?></span></td>
                <td><div class="d-flex align-items-center gap-2"><div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;"><i class="bi bi-person-fill text-secondary"></i></div><span class="fw-semibold"><?php echo htmlspecialchars($row['agent_name']); ?></span></div></td>
                <td><span class="fw-bold"><?php echo(int)$row['deliveries']; ?></span></td>
                <td><span class="badge bg-success text-white px-2"><?php echo(int)$row['completed']; ?></span></td>
                <td><span class="badge bg-warning text-dark px-2"><?php echo(int)$row['pending']; ?></span></td>
                <td><div class="prog-bar-wrap"><div class="prog-bar"><div class="prog-bar-fill" style="width:<?php echo $cr; ?>%;background:<?php echo $col; ?>"></div></div><span class="fw-bold small" style="color:<?php echo $col; ?>;min-width:38px"><?php echo $cr; ?>%</span></div></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-truck"></i><p>No agent data.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <!-- ══ CUSTOMERS ══════════════════════════════════════ -->
    <?php elseif ($report_type === 'customers'): ?>
    <div class="chart-card mb-4"><div class="chart-title"><i class="bi bi-people-fill text-success"></i> Top 10 Customers by Lifetime Value</div><canvas id="customerChart" height="70"></canvas></div>
    <div class="rpt-table-wrap">
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
            <span class="fw-bold text-dark small"><i class="bi bi-people-fill me-2 text-success"></i>Customer Insights</span>
            <span class="badge bg-light text-muted"><?php echo count($cust_rows); ?> customers</span>
        </div>
        <div class="table-responsive"><table class="table mb-0" id="main-table">
            <thead><tr><th>#</th><th>Customer</th><th>Email</th><th>Orders</th><th>Lifetime Value</th><th>Last Order</th><th>Value Share</th></tr></thead>
            <tbody>
            <?php if(!empty($cust_rows)): foreach($cust_rows as $i=>$row):
                $share=$maxLV>0?round(($row['lifetime_value']/$maxLV)*100):0;
                $rank=$i+1; $rc=$rank===1?'rank-1':($rank===2?'rank-2':($rank===3?'rank-3':'rank-n')); ?>
            <tr>
                <td><span class="rank-badge <?php echo $rc; ?>"><?php echo $rank; ?></span></td>
                <td><div class="d-flex align-items-center gap-2"><div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;"><i class="bi bi-person-fill text-success"></i></div><span class="fw-semibold"><?php echo htmlspecialchars($row['customer_name']); ?></span></div></td>
                <td><small class="text-muted"><?php echo htmlspecialchars($row['Email']??'—'); ?></small></td>
                <td><span class="badge bg-light text-primary fw-bold px-2"><?php echo(int)$row['total_orders']; ?></span></td>
                <td class="fw-bold text-success">Rs. <?php echo number_format((float)($row['lifetime_value']??0),2); ?></td>
                <td><?php echo !empty($row['last_order_date'])?'<small>'.date('d M Y',strtotime($row['last_order_date'])).'</small>':'<small class="text-muted">—</small>'; ?></td>
                <td><div class="prog-bar-wrap"><div class="prog-bar"><div class="prog-bar-fill" style="width:<?php echo $share; ?>%"></div></div><span class="small text-muted"><?php echo $share; ?>%</span></div></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i><p>No customer data.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- ══ DOWNLOAD MODAL ══════════════════════════════════════ -->
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-download me-2 text-success"></i>Download Reports as Images</h5>
                    <p class="text-muted small mb-0">Select which reports to download as high-quality PNG images.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.4px;">Choose Reports</span>
                    <button class="btn btn-sm btn-link text-success fw-bold p-0 text-decoration-none" id="selectAllBtn" onclick="toggleSelectAll()">Deselect All</button>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6"><div class="dl-card selected" id="dlCard-sales" onclick="toggleDlCard('sales')"><div class="d-flex justify-content-between align-items-start"><div style="font-size:1.8rem;">📊</div><div class="dl-check"><i class="bi bi-check-lg"></i></div></div><div class="fw-bold" style="font-size:.85rem;">Sales Overview</div><div style="font-size:.72rem;color:#adb5bd;">Revenue, orders &amp; daily breakdown</div></div></div>
                    <div class="col-6"><div class="dl-card selected" id="dlCard-products" onclick="toggleDlCard('products')"><div class="d-flex justify-content-between align-items-start"><div style="font-size:1.8rem;">📦</div><div class="dl-check"><i class="bi bi-check-lg"></i></div></div><div class="fw-bold" style="font-size:.85rem;">Product Performance</div><div style="font-size:.72rem;color:#adb5bd;">Top products by units &amp; revenue</div></div></div>
                    <div class="col-6"><div class="dl-card selected" id="dlCard-agents" onclick="toggleDlCard('agents')"><div class="d-flex justify-content-between align-items-start"><div style="font-size:1.8rem;">🚚</div><div class="dl-check"><i class="bi bi-check-lg"></i></div></div><div class="fw-bold" style="font-size:.85rem;">Delivery Agents</div><div style="font-size:.72rem;color:#adb5bd;">Completion rates &amp; performance</div></div></div>
                    <div class="col-6"><div class="dl-card selected" id="dlCard-customers" onclick="toggleDlCard('customers')"><div class="d-flex justify-content-between align-items-start"><div style="font-size:1.8rem;">👥</div><div class="dl-check"><i class="bi bi-check-lg"></i></div></div><div class="fw-bold" style="font-size:.85rem;">Top Customers</div><div style="font-size:.72rem;color:#adb5bd;">Lifetime value &amp; order history</div></div></div>
                </div>
                <div id="dlProgressWrap" style="display:none;">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-semibold text-muted" id="dlProgressLabel">Preparing...</small>
                        <small class="text-success fw-bold" id="dlProgressPct">0%</small>
                    </div>
                    <div class="dl-progress-bar-track"><div class="dl-progress-bar-fill" id="dlProgressBar" style="width:0%"></div></div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-semibold" id="dlStartBtn" onclick="startDownload()">
                    <i class="bi bi-download me-1"></i>Download Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay shown during capture so user sees progress -->
<div id="render-overlay">
    <div class="overlay-msg">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">📸</div>
        <div class="fw-bold mb-1" id="overlayTitle">Generating Report Image...</div>
        <div class="text-muted small mb-3" id="overlaySubtitle">Please wait</div>
        <div class="dl-progress-bar-track"><div class="dl-progress-bar-fill" id="overlayBar" style="width:0%"></div></div>
        <div class="mt-2 small text-muted" id="overlayPct">0%</div>
    </div>
</div>

<!-- Render area: shown briefly on screen during html2canvas capture -->
<div id="render-area"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
Chart.defaults.font.family="-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif";
Chart.defaults.font.size=12; Chart.defaults.color='#6c757d';

/* Quick Range */
document.getElementById('quick-range')?.addEventListener('change',function(){
    const val=this.value; if(!val) return;
    const today=new Date(); let start,end=today.toISOString().split('T')[0];
    if(val==='today'){start=end;}
    else if(val==='month'){start=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01';}
    else if(val==='year'){start=today.getFullYear()+'-01-01';}
    else{const d=new Date();d.setDate(d.getDate()-parseInt(val));start=d.toISOString().split('T')[0];}
    document.querySelector('[name=start_date]').value=start;
    document.querySelector('[name=end_date]').value=end;
    document.getElementById('filter-form').submit();
});

/* Live charts */
<?php if($report_type==='sales' && !empty($chart_labels)): ?>
const labels=<?php echo json_encode($chart_labels);?>,revenue=<?php echo json_encode($chart_revenue);?>,ordersData=<?php echo json_encode($chart_orders);?>;
new Chart(document.getElementById('revenueChart'),{type:'line',data:{labels,datasets:[{label:'Revenue',data:revenue,borderColor:'#28a745',backgroundColor:'rgba(40,167,69,0.08)',borderWidth:2.5,pointBackgroundColor:'#28a745',pointRadius:4,fill:true,tension:0.4}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' Rs. '+c.parsed.y.toLocaleString('en-LK',{minimumFractionDigits:2})}}},scales:{y:{grid:{color:'#f0f0f0'},ticks:{callback:v=>'Rs. '+v.toLocaleString()}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('ordersChart'),{type:'bar',data:{labels,datasets:[{data:ordersData,backgroundColor:'rgba(13,110,253,0.15)',borderColor:'#0d6efd',borderWidth:1.5,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{grid:{color:'#f0f0f0'},beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}});
const sl=<?php echo json_encode(array_keys($status_counts));?>,sd=<?php echo json_encode(array_values($status_counts));?>,sc={Pending:'#ffc107',Confirmed:'#0d6efd',Delivered:'#28a745',Canceled:'#dc3545'};
new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:sl,datasets:[{data:sd,backgroundColor:sl.map(l=>sc[l]||'#adb5bd'),borderWidth:2,borderColor:'#fff',hoverOffset:6}]},options:{responsive:true,cutout:'65%',plugins:{legend:{position:'bottom',labels:{padding:12,boxWidth:12}}}}});
<?php endif;?>
<?php if($report_type==='products' && !empty($prod_rows)):?>
new Chart(document.getElementById('productChart'),{type:'bar',data:{labels:<?php echo json_encode(array_column(array_slice($prod_rows,0,8),'ProductName'));?>,datasets:[{data:<?php echo json_encode(array_column(array_slice($prod_rows,0,8),'revenue'));?>,backgroundColor:'rgba(40,167,69,0.18)',borderColor:'#28a745',borderWidth:1.5,borderRadius:6}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' Rs. '+parseFloat(c.parsed.x).toLocaleString('en-LK',{minimumFractionDigits:2})}}},scales:{x:{grid:{color:'#f0f0f0'},ticks:{callback:v=>'Rs. '+v.toLocaleString()}},y:{grid:{display:false}}}}});
<?php endif;?>
<?php if($report_type==='agents' && !empty($agent_rows)):?>
new Chart(document.getElementById('agentChart'),{type:'bar',data:{labels:<?php echo json_encode(array_column(array_slice($agent_rows,0,10),'agent_name'));?>,datasets:[{label:'Completed',data:<?php echo json_encode(array_column(array_slice($agent_rows,0,10),'completed'));?>,backgroundColor:'rgba(40,167,69,0.7)',borderRadius:4},{label:'Pending',data:<?php echo json_encode(array_column(array_slice($agent_rows,0,10),'pending'));?>,backgroundColor:'rgba(255,193,7,0.7)',borderRadius:4}]},options:{responsive:true,plugins:{legend:{position:'top'}},scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,grid:{color:'#f0f0f0'},ticks:{stepSize:1}}}}});
<?php endif;?>
<?php if($report_type==='customers' && !empty($cust_rows)):?>
new Chart(document.getElementById('customerChart'),{type:'bar',data:{labels:<?php echo json_encode(array_column(array_slice($cust_rows,0,10),'customer_name'));?>,datasets:[{data:<?php echo json_encode(array_column(array_slice($cust_rows,0,10),'lifetime_value'));?>,backgroundColor:'rgba(13,110,253,0.15)',borderColor:'#0d6efd',borderWidth:1.5,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' Rs. '+parseFloat(c.parsed.y).toLocaleString('en-LK',{minimumFractionDigits:2})}}},scales:{y:{grid:{color:'#f0f0f0'},ticks:{callback:v=>'Rs. '+v.toLocaleString()}},x:{grid:{display:false}}}}});
<?php endif;?>

/* ══════════════════════════════════════════════════════
   ALL PHP DATA FOR JS RENDERING
══════════════════════════════════════════════════════ */
const D={
    period:'<?php echo date("d M Y",strtotime($start_date))." – ".date("d M Y",strtotime($end_date));?>',
    generatedAt: new Date().toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}),
    sales:{
        totals:<?php echo json_encode($totals);?>,
        rows:<?php echo json_encode($sales_rows);?>,
        chartRevenue:<?php echo json_encode($chart_revenue);?>,
        statusLabels:<?php echo json_encode(array_keys($status_counts));?>,
        statusData:<?php echo json_encode(array_values($status_counts));?>,
        rate:<?php echo json_encode($rate);?>,
        maxRev:<?php echo json_encode($maxRev);?>
    },
    products:{rows:<?php echo json_encode($prod_rows);?>,maxRev:<?php echo json_encode($maxPRev);?>},
    agents:{rows:<?php echo json_encode($agent_rows);?>},
    customers:{rows:<?php echo json_encode($cust_rows);?>,maxLV:<?php echo json_encode($maxLV);?>}
};

/* ── Modal selection ── */
const sel=new Set(['sales','products','agents','customers']);
function toggleDlCard(k){
    const c=document.getElementById('dlCard-'+k);
    sel.has(k)?(sel.delete(k),c.classList.remove('selected')):(sel.add(k),c.classList.add('selected'));
    document.getElementById('selectAllBtn').textContent=sel.size===4?'Deselect All':'Select All';
}
function toggleSelectAll(){
    const all=['sales','products','agents','customers'];
    sel.size===4
        ?(all.forEach(k=>{sel.delete(k);document.getElementById('dlCard-'+k).classList.remove('selected');}),document.getElementById('selectAllBtn').textContent='Select All')
        :(all.forEach(k=>{sel.add(k);document.getElementById('dlCard-'+k).classList.add('selected');}),document.getElementById('selectAllBtn').textContent='Deselect All');
}

/* ── Progress helpers ── */
function setProgress(pct,label){
    ['dlProgressBar','overlayBar'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.width=pct+'%';});
    const lbl=document.getElementById('dlProgressLabel'); if(lbl)lbl.textContent=label;
    const pctEl=document.getElementById('dlProgressPct'); if(pctEl)pctEl.textContent=pct+'%';
    const opct=document.getElementById('overlayPct'); if(opct)opct.textContent=pct+'%';
}

/* ── Style helpers ── */
const Rs=v=>'Rs. '+parseFloat(v||0).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2});
const rankSty=i=>i===0?'background:#ffd700;color:#7a5c00;':i===1?'background:#c0c0c0;color:#555;':i===2?'background:#cd7f32;color:#fff;':'background:#f1f3f5;color:#868e96;';
const TH='padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;border-bottom:2px solid #e9ecef;background:#f8f9fa;white-space:nowrap;';
const TD='padding:10px 14px;border-bottom:1px solid #f5f5f5;font-size:13px;vertical-align:middle;';
const bar=(pct,col='#28a745')=>`<div style="display:flex;align-items:center;gap:8px;"><div style="flex:1;height:6px;border-radius:3px;background:#e9ecef;overflow:hidden;min-width:70px;"><div style="width:${pct}%;height:100%;background:${col};border-radius:3px;"></div></div><span style="font-size:11px;color:#adb5bd;min-width:28px;">${pct}%</span></div>`;

/* ── Page header ── */
const header=(title,icon)=>`
<div style="background:linear-gradient(135deg,#166534 0%,#16a34a 55%,#22c55e 100%);padding:26px 36px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <div style="color:rgba(255,255,255,.65);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">🥦 FRESH GROCERS — ADMIN REPORT</div>
        <div style="color:#fff;font-size:22px;font-weight:800;letter-spacing:-.5px;">${icon} ${title}</div>
        <div style="color:rgba(255,255,255,.75);font-size:12px;margin-top:5px;">
            <span style="background:rgba(255,255,255,.15);padding:2px 10px;border-radius:20px;margin-right:8px;">📅 ${D.period}</span>
            <span style="background:rgba(255,255,255,.15);padding:2px 10px;border-radius:20px;">🕐 Generated: ${D.generatedAt}</span>
        </div>
    </div>
    <div style="background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.35);border-radius:12px;padding:12px 20px;color:#fff;text-align:center;">
        <div style="font-size:26px;">🥦</div>
        <div style="font-size:11px;font-weight:700;margin-top:2px;">Fresh Grocers</div>
        <div style="font-size:10px;opacity:.7;">Admin System</div>
    </div>
</div>`;

/* ── Page footer ── */
const footer=()=>`<div style="background:#f8f9fa;border-top:2px solid #e9ecef;padding:12px 36px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:11px;color:#adb5bd;font-weight:600;">🥦 Fresh Grocers Admin System</span>
    <span style="font-size:11px;color:#dc3545;font-weight:600;">⚠ CONFIDENTIAL — INTERNAL USE ONLY</span>
    <span style="font-size:11px;color:#adb5bd;">Generated ${new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'long',year:'numeric'})}</span>
</div>`;

/* ── Stat card ── */
const statCard=(icon,label,value,sub,bg,col)=>`
<div style="background:#fff;border-radius:12px;border:1px solid #e9ecef;padding:18px 20px;display:flex;align-items:center;gap:14px;">
    <div style="width:52px;height:52px;border-radius:11px;background:${bg};display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">${icon}</div>
    <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9aa0ac;">${label}</div>
        <div style="font-size:22px;font-weight:800;color:${col};line-height:1.1;">${value}</div>
        <div style="font-size:11px;color:#adb5bd;margin-top:2px;">${sub}</div>
    </div>
</div>`;

/* ═══════════ BUILD FUNCTIONS ═══════════ */
function buildSalesHTML(){
    const d=D.sales, t=d.totals;
    const stats=`<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
        ${statCard('💰','Total Revenue',Rs(t.total_revenue),'All orders in period','rgba(25,135,84,.12)','#16a34a')}
        ${statCard('🛍️','Total Orders',parseInt(t.total_orders||0).toLocaleString(),parseInt(t.delivered||0)+' delivered · '+parseInt(t.canceled||0)+' canceled','rgba(13,110,253,.12)','#212529')}
        ${statCard('📈','Avg Order Value',Rs(t.avg_order),'Per order average','rgba(255,193,7,.18)','#212529')}
        ${statCard('✅','Delivery Rate',d.rate+'%',parseInt(t.delivered||0)+' of '+parseInt(t.total_orders||0)+' orders','rgba(13,202,240,.12)','#212529')}
    </div>`;

    // Status summary row
    const statusCols={Delivered:'#16a34a',Pending:'#d97706',Canceled:'#dc2626',Confirmed:'#2563eb'};
    const statusBadges=(d.statusLabels||[]).map((l,i)=>`
        <div style="background:#fff;border-radius:10px;border:1px solid #e9ecef;padding:12px 16px;text-align:center;min-width:120px;">
            <div style="font-size:20px;margin-bottom:4px;">${l==='Delivered'?'✅':l==='Pending'?'⏳':l==='Canceled'?'❌':'📋'}</div>
            <div style="font-size:20px;font-weight:800;color:${statusCols[l]||'#6c757d'};">${(d.statusData||[])[i]||0}</div>
            <div style="font-size:11px;color:#9aa0ac;font-weight:600;">${l}</div>
        </div>`).join('');
    const statusRow=`<div style="background:#f8f9fa;border-radius:12px;border:1px solid #e9ecef;padding:16px 20px;margin-bottom:22px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;margin-bottom:12px;">📊 Order Status Breakdown</div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">${statusBadges}</div>
    </div>`;

    let rows='';
    (d.rows||[]).forEach(r=>{
        const share=d.maxRev>0?Math.round((r.total_revenue/d.maxRev)*100):0;
        const dt=new Date(r.date).toLocaleDateString('en-GB',{weekday:'short',day:'2-digit',month:'short',year:'numeric'});
        rows+=`<tr>
            <td style="${TD}font-weight:600;">${dt}</td>
            <td style="${TD}"><span style="background:rgba(13,110,253,.1);color:#0d6efd;font-weight:700;padding:3px 12px;border-radius:6px;font-size:12px;">${r.total_orders}</span></td>
            <td style="${TD}font-weight:700;color:#16a34a;">${Rs(r.total_revenue)}</td>
            <td style="${TD}color:#6c757d;">${Rs(r.avg_order_value)}</td>
            <td style="${TD}">${bar(share)}</td>
        </tr>`;
    });
    const tbl=`<div style="background:#fff;border-radius:12px;border:1px solid #e9ecef;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e9ecef;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#495057;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
            <span>📅 Daily Breakdown</span><span style="background:#e9ecef;color:#6c757d;padding:2px 10px;border-radius:10px;font-size:11px;">${(d.rows||[]).length} days</span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr><th style="${TH}">Date</th><th style="${TH}">Orders</th><th style="${TH}">Revenue</th><th style="${TH}">Avg Value</th><th style="${TH}">Revenue Share</th></tr></thead>
            <tbody>${rows||noData(5)}</tbody>
        </table>
    </div>`;
    return stats+statusRow+tbl;
}

function buildProductsHTML(){
    const d=D.products;
    let rows='';
    (d.rows||[]).forEach((r,i)=>{
        const share=d.maxRev>0?Math.round((r.revenue/d.maxRev)*100):0;
        const tier=r.revenue>10000?{l:'🔥 HOT',bg:'rgba(239,68,68,.1)',c:'#dc2626'}:r.revenue>5000?{l:'⭐ TOP',bg:'rgba(245,158,11,.1)',c:'#d97706'}:{l:'',bg:'',c:''};
        rows+=`<tr style="${i%2?'background:#fafafa':''}">
            <td style="${TD}"><span style="width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;${rankSty(i)}">${i+1}</span></td>
            <td style="${TD}font-weight:600;">${r.ProductName}${tier.l?` <span style="background:${tier.bg};color:${tier.c};padding:1px 7px;border-radius:5px;font-size:10px;font-weight:700;">${tier.l}</span>`:''}</td>
            <td style="${TD}"><span style="background:#e9ecef;color:#495057;padding:2px 10px;border-radius:5px;font-size:11px;">${r.Category||'—'}</span></td>
            <td style="${TD}font-weight:700;">${parseInt(r.units_sold||0).toLocaleString()} <span style="color:#adb5bd;font-weight:400;font-size:11px;">units</span></td>
            <td style="${TD}font-weight:800;color:#16a34a;">${Rs(r.revenue)}</td>
            <td style="${TD}">${r.orders}</td>
            <td style="${TD}">${bar(share)}</td>
        </tr>`;
    });
    return `<div style="background:#fff;border-radius:12px;border:1px solid #e9ecef;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e9ecef;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#495057;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
            <span>📦 Product Performance</span><span style="background:#e9ecef;color:#6c757d;padding:2px 10px;border-radius:10px;font-size:11px;">${(d.rows||[]).length} products</span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>${['#','Product','Category','Units Sold','Revenue','Orders','Revenue Share'].map(h=>`<th style="${TH}">${h}</th>`).join('')}</tr></thead>
            <tbody>${rows||noData(7)}</tbody>
        </table>
    </div>`;
}

function buildAgentsHTML(){
    const d=D.agents;
    // Summary KPIs
    const rows_d=d.rows||[];
    const totalDel=rows_d.reduce((s,r)=>s+parseInt(r.deliveries||0),0);
    const totalComp=rows_d.reduce((s,r)=>s+parseInt(r.completed||0),0);
    const totalPend=rows_d.reduce((s,r)=>s+parseInt(r.pending||0),0);
    const avgRate=rows_d.length?Math.round(rows_d.reduce((s,r)=>s+parseFloat(r.completion_rate||0),0)/rows_d.length):0;
    const kpis=`<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
        ${statCard('🚚','Total Deliveries',totalDel.toLocaleString(),'All agents combined','rgba(13,110,253,.12)','#212529')}
        ${statCard('✅','Completed',totalComp.toLocaleString(),'Successfully delivered','rgba(25,135,84,.12)','#16a34a')}
        ${statCard('⏳','Pending',totalPend.toLocaleString(),'Awaiting delivery','rgba(255,193,7,.18)','#d97706')}
        ${statCard('📊','Avg Rate',avgRate+'%','Across all agents','rgba(13,202,240,.12)','#212529')}
    </div>`;

    let rows='';
    rows_d.forEach((r,i)=>{
        const cr=parseFloat(r.completion_rate||0);
        const col=cr>=80?'#16a34a':cr>=50?'#d97706':'#dc2626';
        const bgCol=cr>=80?'rgba(22,163,74,.1)':cr>=50?'rgba(217,119,6,.1)':'rgba(220,38,38,.1)';
        const label=cr>=80?'EXCELLENT':cr>=60?'GOOD':cr>=40?'AVERAGE':'LOW';
        rows+=`<tr style="${i%2?'background:#fafafa':''}">
            <td style="${TD}"><span style="width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;${rankSty(i)}">${i+1}</span></td>
            <td style="${TD}font-weight:600;">🧑 ${r.agent_name}</td>
            <td style="${TD}font-weight:700;">${r.deliveries||0}</td>
            <td style="${TD}"><span style="background:rgba(22,163,74,.12);color:#16a34a;font-weight:700;padding:3px 12px;border-radius:6px;font-size:12px;">${r.completed||0}</span></td>
            <td style="${TD}"><span style="background:rgba(217,119,6,.12);color:#d97706;font-weight:700;padding:3px 12px;border-radius:6px;font-size:12px;">${r.pending||0}</span></td>
            <td style="${TD}">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="flex:1;height:8px;border-radius:4px;background:#e9ecef;overflow:hidden;min-width:70px;"><div style="width:${cr}%;height:100%;background:${col};border-radius:4px;"></div></div>
                    <span style="font-size:13px;font-weight:800;color:${col};min-width:38px;">${cr}%</span>
                    <span style="background:${bgCol};color:${col};padding:2px 8px;border-radius:5px;font-size:10px;font-weight:700;">${label}</span>
                </div>
            </td>
        </tr>`;
    });
    return kpis+`<div style="background:#fff;border-radius:12px;border:1px solid #e9ecef;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e9ecef;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#495057;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
            <span>🚚 Agent Performance Details</span><span style="background:#e9ecef;color:#6c757d;padding:2px 10px;border-radius:10px;font-size:11px;">${rows_d.length} agents</span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>${['#','Agent Name','Deliveries','Completed','Pending','Completion Rate'].map(h=>`<th style="${TH}">${h}</th>`).join('')}</tr></thead>
            <tbody>${rows||noData(6)}</tbody>
        </table>
    </div>`;
}

function buildCustomersHTML(){
    const d=D.customers;
    const rows_d=d.rows||[];
    const totalRev=rows_d.reduce((s,r)=>s+parseFloat(r.lifetime_value||0),0);
    const totalOrd=rows_d.reduce((s,r)=>s+parseInt(r.total_orders||0),0);
    const vip=rows_d.filter(r=>r.lifetime_value>=5000).length;
    const kpis=`<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
        ${statCard('👥','Total Customers',rows_d.length.toLocaleString(),'In this period','rgba(13,110,253,.12)','#212529')}
        ${statCard('💰','Total Revenue',Rs(totalRev),'From all customers','rgba(25,135,84,.12)','#16a34a')}
        ${statCard('🛍️','Total Orders',totalOrd.toLocaleString(),'Combined orders','rgba(255,193,7,.18)','#212529')}
        ${statCard('⭐','VIP Customers',vip,'Lifetime value ≥ Rs. 5,000','rgba(239,68,68,.1)','#dc2626')}
    </div>`;

    let rows='';
    rows_d.forEach((r,i)=>{
        const share=d.maxLV>0?Math.round((r.lifetime_value/d.maxLV)*100):0;
        const lastOrder=r.last_order_date?new Date(r.last_order_date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}):'—';
        const tier=r.lifetime_value>=5000?{l:'⭐ VIP',bg:'rgba(245,158,11,.12)',c:'#d97706'}:r.lifetime_value>=2000?{l:'💚 LOYAL',bg:'rgba(22,163,74,.1)',c:'#16a34a'}:{l:'🔵 REGULAR',bg:'rgba(107,114,128,.08)',c:'#6b7280'};
        rows+=`<tr style="${i%2?'background:#fafafa':''}">
            <td style="${TD}"><span style="width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;${rankSty(i)}">${i+1}</span></td>
            <td style="${TD}font-weight:600;">${r.customer_name} <span style="background:${tier.bg};color:${tier.c};padding:1px 7px;border-radius:5px;font-size:10px;font-weight:700;margin-left:4px;">${tier.l}</span></td>
            <td style="${TD}font-size:12px;color:#6c757d;">${r.Email||'—'}</td>
            <td style="${TD}font-weight:700;">${r.total_orders||0}</td>
            <td style="${TD}font-weight:800;color:#16a34a;">${Rs(r.lifetime_value)}</td>
            <td style="${TD}font-size:12px;">${lastOrder}</td>
            <td style="${TD}">${bar(share)}</td>
        </tr>`;
    });
    return kpis+`<div style="background:#fff;border-radius:12px;border:1px solid #e9ecef;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e9ecef;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#495057;background:#fafafa;display:flex;justify-content:space-between;align-items:center;">
            <span>👥 Customer Insights</span><span style="background:#e9ecef;color:#6c757d;padding:2px 10px;border-radius:10px;font-size:11px;">${rows_d.length} customers</span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>${['#','Customer','Email','Orders','Lifetime Value','Last Order','Value Share'].map(h=>`<th style="${TH}">${h}</th>`).join('')}</tr></thead>
            <tbody>${rows||noData(7)}</tbody>
        </table>
    </div>`;
}

const noData=cols=>`<tr><td colspan="${cols}" style="text-align:center;padding:2rem;color:#adb5bd;font-style:italic;">No data available for this period</td></tr>`;

const reportConfig={
    sales:    {title:'Sales Overview',      icon:'📊',fn:buildSalesHTML},
    products: {title:'Product Performance', icon:'📦',fn:buildProductsHTML},
    agents:   {title:'Delivery Agents',     icon:'🚚',fn:buildAgentsHTML},
    customers:{title:'Top Customers',       icon:'👥',fn:buildCustomersHTML}
};

/* ══ MAIN DOWNLOAD FUNCTION ══ */
async function startDownload(){
    if(sel.size===0){alert('Please select at least one report.');return;}

    const btn=document.getElementById('dlStartBtn');
    const overlay=document.getElementById('render-overlay');
    const renderArea=document.getElementById('render-area');
    const progressWrap=document.getElementById('dlProgressWrap');

    // Safely close modal first (avoids z-index issues with html2canvas)
    try{
        const modalEl=document.getElementById('downloadModal');
        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.hide();
    }catch(e){
        console.warn('Modal hide failed (non-fatal):',e);
    }

    // Defensive cleanup: remove any leftover backdrops and modal-open body class
    document.querySelectorAll('.modal-backdrop').forEach(el=>el.remove());
    document.body.classList.remove('modal-open');

    await new Promise(r=>setTimeout(r,350)); // wait for modal close animation

    btn.disabled=true;
    progressWrap.style.display='block';
    setProgress(0,'Starting...');

    const keys=[...sel];

    try{
        for(let i=0;i<keys.length;i++){
            const key=keys[i];
            const cfg=reportConfig[key];

            // Update overlay
            document.getElementById('overlayTitle').textContent=`Capturing ${cfg.title}...`;
            document.getElementById('overlaySubtitle').textContent=`Image ${i+1} of ${keys.length}`;
            overlay && overlay.classList.add('show');
            setProgress(Math.round((i/keys.length)*90),'Building layout...');

            // Build HTML
            renderArea.innerHTML=`
                <div style="background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
                    ${header(cfg.title,cfg.icon)}
                    <div style="padding:28px 36px;background:#f4f6f9;">${cfg.fn()}</div>
                    ${footer()}
                </div>`;

            // Show the render area on screen so html2canvas can see it
            renderArea.style.display='block';

            // Double rAF + delay ensures the browser has painted
            await new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)));
            await new Promise(r=>setTimeout(r,400));

            setProgress(Math.round((i/keys.length)*90)+5,'Capturing image...');

            try{
                const canvas=await html2canvas(renderArea.firstElementChild,{
                    scale:2,
                    useCORS:true,
                    allowTaint:true,
                    backgroundColor:'#f4f6f9',
                    logging:false,
                    removeContainer:false
                });

                // Download via blob — NO page reload
                await new Promise((resolve)=>{
                    canvas.toBlob(blob=>{
                        const url=URL.createObjectURL(blob);
                        const a=document.createElement('a');
                        a.href=url;
                        a.download=`FreshGrocers_${cfg.title.replace(/\s+/g,'_')}_<?php echo date('Y-m-d');?>.png`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        setTimeout(()=>URL.revokeObjectURL(url),8000);
                        resolve();
                    },'image/png');
                });

            }catch(err){
                console.error('Capture error:',err);
                alert('Could not capture "'+cfg.title+'". See console for details.');
            }

            // Hide render area between captures
            renderArea.style.display='none';
            renderArea.innerHTML='';

            setProgress(Math.round(((i+1)/keys.length)*100),`Done ${i+1}/${keys.length}`);
            await new Promise(r=>setTimeout(r,300));
        }
    }catch(e){
        console.error('Unexpected error during download process:',e);
        alert('An unexpected error occurred. See console for details.');
    }finally{
        // Ensure overlay and controls are reset even on errors
        overlay && overlay.classList.remove('show');
        renderArea && (renderArea.style.display='none');
        renderArea && (renderArea.innerHTML='');

        // Remove any leftover modal backdrop in case bootstrap failed to remove it
        document.querySelectorAll('.modal-backdrop').forEach(el=>el.remove());
        document.body.classList.remove('modal-open');

        btn.disabled=false;
        btn.innerHTML='<i class="bi bi-download me-1"></i>Download Selected';
        setProgress(0,'Preparing...');
        progressWrap.style.display='none';
    }
}
</script>

<?php include '../includes/admin-footer.php'; ?>