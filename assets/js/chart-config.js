/**
 * Chart.js Configuration - Dynamic charts from database
 * Fetches data from API and updates charts
 */

let barChart, pieChart, lineChart;

// Chart color palette
const CHART_COLORS = {
    blue: 'rgba(54, 162, 235, 0.8)',
    orange: 'rgba(255, 159, 64, 0.8)',
    green: 'rgba(75, 192, 192, 0.8)',
    red: 'rgba(255, 99, 132, 0.8)',
    purple: 'rgba(153, 102, 255, 0.8)',
    yellow: 'rgba(255, 205, 86, 0.8)',
    cyan: 'rgba(75, 192, 192, 0.8)',
    gray: 'rgba(201, 203, 207, 0.8)'
};

const COLOR_ARRAY = Object.values(CHART_COLORS);

/**
 * Fetch chart data from API with optional filters
 */
async function fetchChartData() {
    const period = document.getElementById('filterPeriod')?.value || '';
    const productGroup = document.getElementById('filterProductGroup')?.value || '';
    const product = document.getElementById('filterProduct')?.value || '';
    
    const params = new URLSearchParams();
    if (period) params.append('period', period);
    if (productGroup) params.append('product_group_id', productGroup);
    if (product) params.append('product_id', product);
    
    const url = '../api/chart-data.php' + (params.toString() ? '?' + params : '');
    const res = await fetch(url);
    
    if (!res.ok) throw new Error('Failed to fetch chart data');
    return res.json();
}

/**
 * Update summary cards
 */
function updateSummary(data) {
    const s = data.summary;
    const recordsEl = document.getElementById('statRecords');
    const salesEl = document.getElementById('statSales');
    const qtyEl = document.getElementById('statQuantity');
    
    if (recordsEl) recordsEl.textContent = parseInt(s.total_records).toLocaleString();
    if (salesEl) salesEl.textContent = parseFloat(s.total_sales).toLocaleString(undefined, { minimumFractionDigits: 2 });
    if (qtyEl) qtyEl.textContent = parseInt(s.total_quantity).toLocaleString();
}

/**
 * Build bar chart data - grouped by period/year
 */
function buildBarChartData(salesByPeriod) {
    const labels = [...new Set(salesByPeriod.map(s => `Period ${s.period}`))];
    const years = [...new Set(salesByPeriod.map(s => s.year))].sort();
    
    const datasets = years.map((year, i) => ({
        label: year,
        data: labels.map(lbl => {
            const p = parseInt(lbl.replace('Period ', ''));
            const row = salesByPeriod.find(s => s.period === p && s.year === year);
            return row ? parseFloat(row.total_amount) : 0;
        }),
        backgroundColor: COLOR_ARRAY[i % COLOR_ARRAY.length],
        borderColor: COLOR_ARRAY[i % COLOR_ARRAY.length].replace('0.8', '1'),
        borderWidth: 1
    }));
    
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: datasets.length ? datasets : [{ label: 'Sales', data: [0], backgroundColor: CHART_COLORS.blue }]
    };
}

/**
 * Build pie chart data
 */
function buildPieChartData(salesByGroup) {
    return {
        labels: salesByGroup.map(s => s.name),
        datasets: [{
            data: salesByGroup.map(s => parseFloat(s.total_amount)),
            backgroundColor: salesByGroup.map((_, i) => COLOR_ARRAY[i % COLOR_ARRAY.length]),
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
}

/**
 * Build line chart data - trend over period/year
 */
function buildLineChartData(salesByPeriod) {
    const sorted = [...salesByPeriod].sort((a, b) => {
        if (a.year !== b.year) return a.year - b.year;
        return a.period - b.period;
    });
    
    const labels = sorted.map(s => `P${s.period}/${s.year}`);
    const data = sorted.map(s => parseFloat(s.total_amount));
    
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Sales Amount',
            data: data.length ? data : [0],
            borderColor: CHART_COLORS.blue,
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            fill: true,
            tension: 0.3
        }]
    };
}

/**
 * Initialize or update Bar Chart
 */
function updateBarChart(data) {
    const ctx = document.getElementById('salesBarChart');
    if (!ctx) return;
    
    const chartData = buildBarChartData(data.salesByPeriod);
    
    if (barChart) {
        barChart.data = chartData;
        barChart.update();
    } else {
        barChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Sum of Sales' }
                    }
                }
            }
        });
    }
}

/**
 * Initialize or update Pie Chart
 */
function updatePieChart(data) {
    const ctx = document.getElementById('salesPieChart');
    if (!ctx) return;
    
    const chartData = buildPieChartData(data.salesByGroup);
    
    if (pieChart) {
        pieChart.data = chartData;
        pieChart.update();
    } else {
        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }
}

/**
 * Initialize or update Line Chart
 */
function updateLineChart(data) {
    const ctx = document.getElementById('salesLineChart');
    if (!ctx) return;
    
    const chartData = buildLineChartData(data.salesByPeriod);
    
    if (lineChart) {
        lineChart.data = chartData;
        lineChart.update();
    } else {
        lineChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Sales Amount' }
                    }
                }
            }
        });
    }
}

/**
 * Load all charts and summary
 */
async function loadCharts() {
    try {
        const data = await fetchChartData();
        if (data.error) throw new Error(data.error);
        
        updateSummary(data);
        updateBarChart(data);
        updatePieChart(data);
        updateLineChart(data);
    } catch (err) {
        console.error('Chart load error:', err);
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    loadCharts();
    
    document.getElementById('btnApplyFilters')?.addEventListener('click', loadCharts);
    document.getElementById('btnResetFilters')?.addEventListener('click', () => {
        document.getElementById('filterPeriod').value = '';
        document.getElementById('filterProductGroup').value = '';
        document.getElementById('filterProduct').value = '';
        loadCharts();
    });
});
