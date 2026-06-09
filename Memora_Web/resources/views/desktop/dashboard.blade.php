@extends('layouts.desktop')

@section('content')
<style>
    /* Hide top header for Dashboard to make it full screen */
    .top-header { display: none !important; }
    
    .dashboard-wrapper {
        padding: 40px;
        width: 100%;
        max-width: 100%;
        margin: 0;
        color: var(--text-dark);
        background: var(--bg-main);
        min-height: 100vh;
    }
    
    .dashboard-header {
        margin-bottom: 32px;
    }
    
    .dashboard-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #222;
        margin: 0 0 8px 0;
    }
    
    .dashboard-header p {
        color: #666;
        margin: 0;
        font-size: 15px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    .stat-card {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    }
    
    .stat-icon {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .icon-blue { background: rgba(51, 161, 255, 0.1); color: #33a1ff; }
    .icon-purple { background: rgba(123, 97, 255, 0.1); color: #7B61FF; }
    .icon-orange { background: rgba(255, 171, 0, 0.1); color: #ffab00; }
    .icon-green { background: rgba(56, 203, 137, 0.1); color: #38cb89; }
    .icon-red { background: rgba(255, 77, 79, 0.1); color: #ff4d4f; }
    
    .stat-info h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #222;
        line-height: 1.2;
    }
    
    .stat-info p {
        margin: 4px 0 0 0;
        font-size: 13px;
        color: #777;
        font-weight: 500;
        white-space: nowrap;
    }
    
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }
    
    @media (max-width: 1200px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .chart-card {
        background: #fff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    
    .chart-header {
        margin-bottom: 20px;
    }
    
    .chart-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #222;
    }
</style>

<!-- Load ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <h2>Dashboard Overview</h2>
        <p>Statistics and platform activities for The Archive.</p>
    </div>
    
    <div class="stats-grid">
        <!-- Total Members -->
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class='bx bx-group'></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-users">0</h3>
                <p>Total Members</p>
            </div>
        </div>
        
        <!-- Photos Uploaded -->
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <i class='bx bx-image-alt'></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-photos">0</h3>
                <p>Photos Uploaded</p>
            </div>
        </div>

        <!-- Total Posts -->
        <div class="stat-card">
            <div class="stat-icon icon-orange">
                <i class='bx bx-message-square-detail'></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-posts">0</h3>
                <p>Total Posts</p>
            </div>
        </div>
        
        <!-- Events Held -->
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <i class='bx bx-calendar-event'></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-events">0</h3>
                <p>Events Held</p>
            </div>
        </div>
        
        <!-- Reunions -->
        <div class="stat-card">
            <div class="stat-icon icon-red">
                <i class='bx bx-party'></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-reunions">0</h3>
                <p>Reunions</p>
            </div>
        </div>
    </div>
    
    <div class="charts-grid">
        <!-- Main Activity Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>Platform Activity (Last 6 Months)</h3>
            </div>
            <div id="activityChart"></div>
        </div>
        
        <!-- Engagement Donut Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>Content Distribution</h3>
            </div>
            <div id="distributionChart"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. Activity Area Chart ---
        var activityOptions = {
            series: [{
                name: 'Photos Uploaded',
                data: [0, 0, 0, 0, 0, 0, 0]
            }, {
                name: 'New Posts',
                data: [0, 0, 0, 0, 0, 0, 0]
            }],
            chart: {
                height: 350,
                type: 'area',
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: { enabled: true, delay: 150 },
                    dynamicAnimation: { enabled: true, speed: 350 }
                }
            },
            colors: ['#7B61FF', '#ffab00'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (value) { return Math.round(value); }
                }
            },
            grid: {
                borderColor: 'rgba(0,0,0,0.05)',
                strokeDashArray: 4,
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        var activityChart = new ApexCharts(document.querySelector("#activityChart"), activityOptions);
        activityChart.render();


        // --- 2. Content Distribution Donut Chart ---
        var distOptions = {
            series: [0, 0, 0, 0],
            chart: {
                type: 'donut',
                height: 350,
                fontFamily: 'Inter, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    dynamicAnimation: { enabled: true, speed: 350 }
                }
            },
            labels: ['General Posts', 'Reunions', 'Campus Events', 'Gallery Photos'],
            colors: ['#7B61FF', '#ff4d4f', '#38cb89', '#33a1ff'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: { show: true },
                            value: {
                                show: true,
                                fontSize: '24px',
                                fontWeight: 600
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#777',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0)
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom'
            },
            stroke: { show: false }
        };

        var distributionChart = new ApexCharts(document.querySelector("#distributionChart"), distOptions);
        distributionChart.render();

        // --- Real-time Data Fetching ---
        function fetchDashboardData() {
            fetch('{{ route("admin.dashboard.data") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Update text stats with simple animation
                animateValue('stat-users', data.stats.users);
                animateValue('stat-photos', data.stats.photos);
                animateValue('stat-posts', data.stats.posts);
                animateValue('stat-events', data.stats.events);
                animateValue('stat-reunions', data.stats.reunions);

                // Update charts
                activityChart.updateSeries([{
                    name: 'Photos Uploaded',
                    data: data.activity.photos
                }, {
                    name: 'New Posts',
                    data: data.activity.posts
                }]);
                
                if(data.activity.labels) {
                    activityChart.updateOptions({
                        xaxis: {
                            categories: data.activity.labels
                        }
                    });
                }

                // Ensure no zeros break the donut chart visualization (ApexCharts can handle it but good to be safe)
                const seriesData = data.distribution.map(val => val === 0 ? 0.001 : val);
                distributionChart.updateSeries(data.distribution);
            })
            .catch(error => console.error('Error fetching dashboard data:', error));
        }

        function animateValue(id, end, duration = 1000) {
            const obj = document.getElementById(id);
            if (!obj) return;
            const start = parseInt(obj.innerText.replace(/,/g, '')) || 0;
            if (start === end) return;
            
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerText = Math.floor(progress * (end - start) + start).toLocaleString('en-US');
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Fetch immediately
        fetchDashboardData();

        // Poll every 5 seconds for real-time effect
        setInterval(fetchDashboardData, 5000);
    });
</script>
@endsection
