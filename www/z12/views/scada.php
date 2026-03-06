<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .scada-container { position: relative; display: inline-block; width: 100%; max-width: 800px; }
    .scada-bg { width: 100%; height: auto; border: 1px solid #ccc; }
    
    .sensor-badge {
        position: absolute;
        padding: 5px 10px;
        background: rgba(255, 255, 255, 0.9);
        color: #000000;
        border: 2px solid black;
        border-radius: 5px;
        font-weight: bold;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
    }
    
    .icon-status { position: absolute; font-size: 2rem; display: none; }
    
    #val-x1 { top: 20%; left: 30%; border-color: red; }
    #val-x2 { top: 40%; left: 30%; border-color: blue; }
    #val-x3 { top: 20%; left: 60%; }
    #val-x4 { top: 50%; left: 60%; }
    #val-x5 { top: 80%; left: 60%; }
    
    #icon-pozar { top: 10%; left: 80%; color: red; }
    #icon-zalanie { top: 10%; left: 70%; color: blue; }
    #icon-wentylator { top: 80%; left: 20%; color: gray; }
    
    .spin-slow { animation: spin 2s linear infinite; }
    .spin-fast { animation: spin 0.5s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div class="row">
    <!-- Kolumna Rzutu Budynku -->
    <div class="col-lg-7">
        <div class="scada-container">
            <img src="../assets/rzut.svg" alt="Rzut Budynku" class="scada-bg" onerror="this.src='https://via.placeholder.com/800x600?text=Brak+pliku+assets/rzut.svg'">
            
            <!-- Temperatury -->
            <div id="val-x1" class="sensor-badge">X1: <span class="val">--</span>°C</div>
            <div id="val-x2" class="sensor-badge">X2: <span class="val">--</span>°C</div>
            <div id="val-x3" class="sensor-badge">X3: <span class="val">--</span>°C</div>
            <div id="val-x4" class="sensor-badge">X4: <span class="val">--</span>°C</div>
            <div id="val-x5" class="sensor-badge">X5: <span class="val">--</span>°C</div>

            <!-- Ikony Stanów -->
            <i id="icon-pozar" class="fas fa-fire icon-status"></i>
            <i id="icon-zalanie" class="fas fa-water icon-status"></i>
            <i id="icon-wentylator" class="fas fa-fan icon-status" style="display:block;"></i>
        </div>
    </div>
    
    <!-- Kolumna Wykresu -->
    <div class="col-lg-5">
        <canvas id="scadaChart" width="400" height="300"></canvas>
    </div>
</div>

<script>
let scadaChart;

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

            document.getElementById('icon-pozar').style.display = latest.pozar == 1 ? 'block' : 'none';
            document.getElementById('icon-zalanie').style.display = latest.zalanie == 1 ? 'block' : 'none';
            
            const fan = document.getElementById('icon-wentylator');
            fan.className = 'fas fa-fan icon-status'; 
            fan.style.display = 'block';
            if(latest.wentylacja == 1) fan.classList.add('spin-slow');
            else if(latest.wentylacja == 2) fan.classList.add('spin-fast');
        });
}

document.addEventListener("DOMContentLoaded", () => {
    initChart();
    updateScadaData();
    setInterval(updateScadaData, 2000);
});
</script>