const ctx1 = document.getElementById('chartStage1').getContext('2d');
const ctx2 = document.getElementById('chartStage2').getContext('2d');

const config = {
    type: 'line',
    options: { responsive: true, scales: { y: { min: 0, max: 1.1 } } }
};

// Inisialisasi Chart 1
let chart1 = new Chart(ctx1, {
    ...config,
    data: {
        labels: [],
        datasets: [
            { label: 'Norm. Entropy', data: [], borderColor: 'blue', fill: false },
            { label: 'Threshold', data: [], borderColor: 'red', borderDash: [5, 5], fill: false }
        ]
    }
});

// Inisialisasi Chart 2
let chart2 = new Chart(ctx2, {
    ...config,
    data: {
        labels: [],
        datasets: [
            { label: 'Norm. ESIP', data: [], borderColor: 'purple', fill: false },
            { label: 'Thres. ESIP', data: [], borderColor: 'orange', borderDash: [5, 5], fill: false }
        ]
    }
});

function updateDashboard() {
    // 1. Ambil Stats
    $.getJSON('get_stats.php', function(data) {
        if(!data) return;

        let time = data.timestamp.split(' ')[1]; // ambil jamnya saja
        
        // Update Chart 1
        if(chart1.data.labels.length > 10) chart1.data.labels.shift();
        chart1.data.labels.push(time);
        chart1.data.datasets[0].data.push(data.normalized_entropy);
        chart1.data.datasets[1].data.push(data.threshold);
        chart1.update();

        // Update Chart 2
        chart2.data.labels.push(time);
        chart2.data.datasets[0].data.push(data.normalized_esip);
        chart2.data.datasets[1].data.push(data.threshold_esip);
        if(chart2.data.labels.length > 10) chart2.data.labels.shift();
        chart2.update();

        // Update Verdict
        $("#final-verdict").text(data.final_result);
        if(data.final_result === "ATTACK") {
            $("#final-verdict").attr('class', 'fw-bold text-danger');
        } else if(data.final_result === "SUS") {
            $("#final-verdict").attr('class', 'fw-bold text-warning');
        } else {
            $("#final-verdict").attr('class', 'fw-bold text-success');
        }
        $("#last-update").text("Last update: " + data.timestamp);
    });

    // 2. Ambil Logs
    $.getJSON('get_logs.php', function(data) {
        let rows = '';
        data.forEach(log => {
            rows += `<tr>
                <td>${log.timestamp}</td>
                <td>${log.ip}</td>
                <td>${log.request_count}</td>
                <td>${log.probability}</td>
            </tr>`;
        });
        $("#log-body").html(rows);
    });
}

// Jalankan setiap 2 detik
setInterval(updateDashboard, 2000);