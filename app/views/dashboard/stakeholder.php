<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-panel">
            <h6 class="mb-3">Findings theo Severity</h6>
            <canvas id="chartSeverity" height="240"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel">
            <h6 class="mb-3">Engagement theo Phòng ban</h6>
            <canvas id="chartDept" height="240"></canvas>
        </div>
    </div>
</div>
<div class="card-panel mt-4">
    <p class="text-muted mb-0"><i class="fa-solid fa-eye me-1"></i>
    Tài khoản Stakeholder chỉ có quyền xem — không thể chỉnh sửa dữ liệu. Báo cáo PDF chi tiết xem tại mục Reports.</p>
</div>

<?php
$extraScripts = '<script>
const severityData = ' . json_encode($bySeverity) . ';
const deptData = ' . json_encode($byDept) . ';
new Chart(document.getElementById("chartSeverity"), {
    type: "doughnut",
    data: { labels: severityData.map(x => x.severity),
        datasets: [{ data: severityData.map(x => x.total),
            backgroundColor: ["#e5484d","#f5893b","#eab308","#2f6fed","#8a94a6"] }] }
});
new Chart(document.getElementById("chartDept"), {
    type: "pie",
    data: { labels: deptData.map(x => x.department),
        datasets: [{ data: deptData.map(x => x.total),
            backgroundColor: ["#2f6fed","#22a06b","#f5893b","#eab308","#e5484d"] }] }
});
</script>';
?>
