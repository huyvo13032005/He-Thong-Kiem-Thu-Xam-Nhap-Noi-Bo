<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg">
        <div class="stat-card">
            <i class="fa-solid fa-users stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['total_users'] ?? 0) ?></div><div class="stat-label">Total Users</div></div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="stat-card">
            <i class="fa-solid fa-crosshairs stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['active_engagements'] ?? 0) ?></div><div class="stat-label">Active Engagement</div></div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="stat-card critical">
            <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['critical_findings'] ?? 0) ?></div><div class="stat-label">Critical Findings</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg">
        <div class="stat-card warning">
            <i class="fa-solid fa-hourglass-half stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['pending_approval'] ?? 0) ?></div><div class="stat-label">Pending Approval</div></div>
        </div>
    </div>
    <div class="col-md-6 col-lg">
        <div class="stat-card success">
            <i class="fa-solid fa-circle-check stat-icon"></i>
            <div><div class="stat-value"><?= (int)($cards['closed_findings'] ?? 0) ?></div><div class="stat-label">Closed Findings</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-panel">
            <h6 class="mb-3">Findings theo Severity</h6>
            <canvas id="chartSeverity" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel">
            <h6 class="mb-3">Findings theo Status</h6>
            <canvas id="chartStatus" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel">
            <h6 class="mb-3">Engagement theo Phòng ban</h6>
            <canvas id="chartDept" height="220"></canvas>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
const severityData = ' . json_encode($bySeverity) . ';
const statusData = ' . json_encode($byStatus) . ';
const deptData = ' . json_encode($byDept) . ';

new Chart(document.getElementById("chartSeverity"), {
    type: "doughnut",
    data: {
        labels: severityData.map(x => x.severity),
        datasets: [{ data: severityData.map(x => x.total),
            backgroundColor: ["#e5484d","#f5893b","#eab308","#2f6fed","#8a94a6"] }]
    }
});
new Chart(document.getElementById("chartStatus"), {
    type: "bar",
    data: {
        labels: statusData.map(x => x.status),
        datasets: [{ label: "Findings", data: statusData.map(x => x.total), backgroundColor: "#16283f" }]
    },
    options: { plugins: { legend: { display: false } } }
});
new Chart(document.getElementById("chartDept"), {
    type: "pie",
    data: {
        labels: deptData.map(x => x.department),
        datasets: [{ data: deptData.map(x => x.total),
            backgroundColor: ["#2f6fed","#22a06b","#f5893b","#eab308","#e5484d"] }]
    }
});
</script>';
?>
