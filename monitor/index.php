<!DOCTYPE html>
<html lang="id">
<head>
    <title>DDoS Real-time Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <h2 class="mb-4">DDoS Detection Dashboard</h2>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6>Verdict Terakhir</h6>
                    <h2 id="final-verdict" class="fw-bold">-</h2>
                    <small id="last-update" class="text-muted"></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">Tahap 1: Entropy & Threshold</div>
                <div class="card-body">
                    <canvas id="chartStage1"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">Tahap 2: ESIP Re-evaluation</div>
                <div class="card-body">
                    <canvas id="chartStage2"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    System Monitoring (CPU & RAM)
                </div>
                <div class="card-body">
                    
                    <!-- Chart -->
                    <canvas id="chartSystem"></canvas>

                    <!-- Text Info -->
                    <div class="mt-3 text-center">
                        <strong>CPU:</strong> <span id="cpu-val">-</span> |
                        <strong>RAM:</strong> <span id="ram-val">-</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ============================== -->
        <!-- ACTIVE ATTACKERS -->
        <!-- ============================== -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    Active Attackers (Latest per IP)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>IP Address</th>
                                <th>Req Count</th>
                                <th>Prob</th>
                            </tr>
                        </thead>
                        <tbody id="active-log-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================== -->
        <!-- LIVE FEED -->
        <!-- ============================== -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    Live Suspicious IP Logs (Raw)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>IP Address</th>
                                <th>Req Count</th>
                                <th>Prob</th>
                            </tr>
                        </thead>
                        <tbody id="live-log-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/custom.js"></script>
</body>
</html>