const ctx1 = document.getElementById('chartStage1').getContext('2d');
const ctx2 = document.getElementById('chartStage2').getContext('2d');

const MAX_POINTS = 30;

const config = {
    type: 'line',
    options: { 
        responsive: true, 
        animation: { duration: 300 },
        scales: { 
            y: { 
                beginAtZero: false,
                grace: '5%'
            } 
        }
    }
};

// ===== CHART 1 =====
let chart1 = new Chart(ctx1, {
    ...config,
    data: {
        labels: [],
        datasets: [
            { 
                label: 'Norm. Entropy', 
                data: [], 
                borderColor: 'blue', 
                fill: false,
                tension: 0.3,
                pointRadius: 3
            },
            { 
                label: 'Threshold', 
                data: [], 
                borderColor: 'red', 
                borderDash: [5, 5], 
                fill: false,
                tension: 0.3
            }
        ]
    }
});

// ===== CHART 2 =====
let chart2 = new Chart(ctx2, {
    ...config,
    data: {
        labels: [],
        datasets: [
            { 
                label: 'Norm. ESIP', 
                data: [], 
                borderColor: 'purple', 
                fill: false,
                tension: 0.3
            },
            { 
                label: 'Thres. ESIP', 
                data: [], 
                borderColor: 'orange', 
                borderDash: [5, 5], 
                fill: false,
                tension: 0.3
            }
        ]
    }
});

let lastTimestamp = "";

function updateDashboard() {

    // ===== GET STATS =====
    $.getJSON('get_stats.php', function(data) {

        if(!data || data.timestamp === lastTimestamp) {
            return;
        }

        lastTimestamp = data.timestamp;
        let time = data.timestamp.split(' ')[1];

        // ===== STAGE 1 CHART =====
        chart1.data.labels.push(time);
        chart1.data.datasets[0].data.push(data.normalized_entropy);
        chart1.data.datasets[1].data.push(data.threshold);

        // Warna titik berdasarkan status
        let pointColor = "blue";
        if(data.final_result === "ATTACK") pointColor = "red";
        else if(data.final_result === "SUS") pointColor = "orange";

        chart1.data.datasets[0].pointBackgroundColor = 
            chart1.data.datasets[0].data.map(() => pointColor);

        if(chart1.data.labels.length > MAX_POINTS) {
            chart1.data.labels.shift();
            chart1.data.datasets.forEach(ds => ds.data.shift());
        }

        chart1.update();

        // ===== STAGE 2 CHART (ONLY IF SUS/ATTACK) =====
        if(data.final_result !== "NORMAL") {

            chart2.data.labels.push(time);
            chart2.data.datasets[0].data.push(data.normalized_esip);
            chart2.data.datasets[1].data.push(data.threshold_esip);

            if(chart2.data.labels.length > MAX_POINTS) {
                chart2.data.labels.shift();
                chart2.data.datasets.forEach(ds => ds.data.shift());
            }

            chart2.update();
        }

        // ===== UI STATUS =====
        $("#final-verdict").text(data.final_result);

        let statusClass = "text-success";
        if(data.final_result === "ATTACK") statusClass = "text-danger";
        else if(data.final_result === "SUS") statusClass = "text-warning";

        $("#final-verdict").attr('class', 'fw-bold ' + statusClass);

        $("#last-update").text("Last Window: " + data.timestamp);

        // Optional tampil angka rapi
        $("#entropy-val").text(data.normalized_entropy_fmt);
        $("#threshold-val").text(data.threshold_fmt);
        $("#esip-val").text(data.normalized_esip_fmt);
        $("#thres-esip-val").text(data.threshold_esip_fmt);
    });

    // ===== LOGS =====
    $.getJSON('get_logs.php', function(data) {

        let rows = '';

        if(data.length > 0) {
            data.forEach(log => {
                rows += `<tr>
                    <td>${log.timestamp}</td>
                    <td>${log.ip}</td>
                    <td>${log.request_count}</td>
                    <td>${log.probability_fmt}</td>
                </tr>`;
            });
        } else {
            rows = '<tr><td colspan="4" class="text-center text-muted">No suspicious IP detected</td></tr>';
        }

        $("#log-body").html(rows);
    });
}

// Interval tetap (sudah oke)
setInterval(updateDashboard, 2000);