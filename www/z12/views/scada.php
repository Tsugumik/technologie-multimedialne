<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .scada-container { position: relative; display: inline-block; width: 100%; max-width: 800px; }
    .scada-bg { width: 100%; height: auto; border: 1px solid #ccc; }
    
    .room-overlay {
        position: absolute;
        opacity: 0.4;
        transition: background-color 0.5s ease;
        z-index: 1;
    }

    #room-x3 { top: 10%; left: 45%; width: 45%; height: 28%; }
    #room-x4 { top: 38%; left: 45%; width: 45%; height: 28%; }
    #room-x5 { top: 66%; left: 45%; width: 45%; height: 28%; }

    .sensor-badge {
        position: absolute;
        padding: 5px 10px;
        background: rgba(255, 255, 255, 0.9);
        color: #000000;
        border: 2px solid black;
        border-radius: 5px;
        font-weight: bold;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        z-index: 2;
    }
    
    .icon-status { position: absolute; font-size: 2rem; display: none; z-index: 2; }
    
    #val-x1 { top: 30%; left: 15%; border-color: red; display: flex; align-items: center; gap: 5px; }
    #val-x2 { top: 55%; left: 15%; border-color: blue; display: flex; align-items: center; gap: 5px; }
    #val-x3 { top: 23%; left: 60%; }
    #val-x4 { top: 48%; left: 60%; }
    #val-x5 { top: 80%; left: 60%; }
    
    #icon-pozar { top: 10%; left: 80%; color: red; }
    #icon-zalanie { top: 10%; left: 70%; color: blue; }
    #icon-wentylator { top: 80%; left: 20%; color: gray; }
    
    .spin-slow { animation: spin 2s linear infinite; }
    .spin-fast { animation: spin 0.5s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div class="row">
    <div class="col-lg-7">
        <div class="scada-container mb-3">
            <img src="../assets/rzut.svg" alt="Rzut Budynku" class="scada-bg" onerror="this.src='https://via.placeholder.com/800x600?text=Brak+pliku+assets/rzut.svg'">
            
            <div id="room-x3" class="room-overlay"></div>
            <div id="room-x4" class="room-overlay"></div>
            <div id="room-x5" class="room-overlay"></div>

            <div id="val-x1" class="sensor-badge">
                <i class="fas fa-arrow-right" style="color: red;"></i> X1: <span class="val">--</span>°C
            </div>
            <div id="val-x2" class="sensor-badge">
                <i class="fas fa-arrow-left" style="color: blue;"></i> X2: <span class="val">--</span>°C
            </div>
            <div id="val-x3" class="sensor-badge">X3: <span class="val">--</span>°C</div>
            <div id="val-x4" class="sensor-badge">X4: <span class="val">--</span>°C</div>
            <div id="val-x5" class="sensor-badge">X5: <span class="val">--</span>°C</div>

            <i id="icon-pozar" class="fas fa-fire icon-status"></i>
            <i id="icon-zalanie" class="fas fa-water icon-status"></i>
            <i id="icon-wentylator" class="fas fa-fan icon-status" style="display:block;"></i>
        </div>
    </div>
    
    <div class="col-lg-5">
        <canvas id="scadaChart" width="400" height="300"></canvas>
        
        <div class="mt-4 text-center">
            <h5>Zasilanie / Powrót (Google Charts)</h5>
            <div class="d-flex justify-content-center gap-4">
                <div id="gauge_x1" style="width: 150px; height: 150px;"></div>
                <div id="gauge_x2" style="width: 150px; height: 150px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
let scadaChart;
let googleChartsLoaded = false;
let gaugeChart1, gaugeData1, gaugeOptions1;
let gaugeChart2, gaugeData2, gaugeOptions2;

google.charts.load('current', {'packages':['gauge']});
google.charts.setOnLoadCallback(() => {
    googleChartsLoaded = true;

    gaugeData1 = google.visualization.arrayToDataTable([ ['Label', 'Value'], ['X1 (Zas)', 0] ]);
    gaugeData2 = google.visualization.arrayToDataTable([ ['Label', 'Value'], ['X2 (Pow)', 0] ]);

    gaugeOptions1 = {
        width: 150, height: 150,
        redFrom: 80, redTo: 100,
        yellowFrom: 60, yellowTo: 80,
        minorTicks: 5, max: 100, min: -50
    };
    gaugeOptions2 = {
        width: 150, height: 150,
        redFrom: 80, redTo: 100,
        yellowFrom: 60, yellowTo: 80,
        minorTicks: 5, max: 100, min: -50
    };

    gaugeChart1 = new google.visualization.Gauge(document.getElementById('gauge_x1'));
    gaugeChart2 = new google.visualization.Gauge(document.getElementById('gauge_x2'));

    gaugeChart1.draw(gaugeData1, gaugeOptions1);
    gaugeChart2.draw(gaugeData2, gaugeOptions2);
});

function initChart() {
    const ctx = document.getElementById('scadaChart').getContext('2d');
    scadaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'X1', borderColor: 'red', data: [], fill: false, tension: 0.1 },
                { label: 'X2', borderColor: 'blue', data: [], fill: false, tension: 0.1 },
                { label: 'X3', borderColor: 'green', data: [], fill: false, tension: 0.1 },
                { label: 'X4', borderColor: 'orange', data: [], fill: false, tension: 0.1 },
                { label: 'X5', borderColor: 'purple', data: [], fill: false, tension: 0.1 }
            ]
        },
        options: {
            responsive: true,
            animation: false,
            scales: { y: { beginAtZero: false } }
        }
    });
}

function getRoomColor(temp) {
    if (temp < 10) return 'blue';
    if (temp >= 10 && temp < 20) return 'green';
    if (temp >= 20 && temp < 25) return 'transparent';
    if (temp >= 25 && temp < 30) return 'orange';
    if (temp >= 30) return 'red';
    return 'transparent';
}

function updateScadaData() {
    fetch('api.php?action=history')
        .then(response => response.json())
        .then(data => {
            const labels = data.map(row => row.datetime.split(' ')[1]);
            
            scadaChart.data.labels = labels;
            scadaChart.data.datasets[0].data = data.map(row => row.x1);
            scadaChart.data.datasets[1].data = data.map(row => row.x2);
            scadaChart.data.datasets[2].data = data.map(row => row.x3);
            scadaChart.data.datasets[3].data = data.map(row => row.x4);
            scadaChart.data.datasets[4].data = data.map(row => row.x5);
            scadaChart.update();
        });

    fetch('api.php?action=latest')
        .then(response => response.json())
        .then(latest => {
            if(!latest) return;
            
            document.querySelector('#val-x1 .val').textContent = latest.x1;
            document.querySelector('#val-x2 .val').textContent = latest.x2;
            document.querySelector('#val-x3 .val').textContent = latest.x3;
            document.querySelector('#val-x4 .val').textContent = latest.x4;
            document.querySelector('#val-x5 .val').textContent = latest.x5;

            document.getElementById('room-x3').style.backgroundColor = getRoomColor(latest.x3);
            document.getElementById('room-x4').style.backgroundColor = getRoomColor(latest.x4);
            document.getElementById('room-x5').style.backgroundColor = getRoomColor(latest.x5);

            document.getElementById('icon-pozar').style.display = latest.pozar == 1 ? 'block' : 'none';
            document.getElementById('icon-zalanie').style.display = latest.zalanie == 1 ? 'block' : 'none';
            
            const fan = document.getElementById('icon-wentylator');
            fan.className = 'fas fa-fan icon-status'; 
            fan.style.display = 'block';
            if(latest.wentylacja == 1) fan.classList.add('spin-slow');
            else if(latest.wentylacja == 2) fan.classList.add('spin-fast');

            if (googleChartsLoaded) {
                gaugeData1.setValue(0, 1, parseFloat(latest.x1));
                gaugeChart1.draw(gaugeData1, gaugeOptions1);

                gaugeData2.setValue(0, 1, parseFloat(latest.x2));
                gaugeChart2.draw(gaugeData2, gaugeOptions2);
            }
        });
}

document.addEventListener("DOMContentLoaded", () => {
    initChart();
    updateScadaData();
    setInterval(updateScadaData, 2000);
});
</script>