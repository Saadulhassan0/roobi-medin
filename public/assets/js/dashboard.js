document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar Toggle ---
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }

    // --- AI Assistant Toggle ---
    const aiFab = document.getElementById('ai-fab');
    const aiPanel = document.getElementById('ai-panel');
    const aiClose = document.getElementById('ai-close');

    if (aiFab && aiPanel && aiClose) {
        aiFab.addEventListener('click', () => {
            aiPanel.classList.add('active');
        });

        aiClose.addEventListener('click', () => {
            aiPanel.classList.remove('active');
        });
    }

    // --- Initialize Charts if Canvas Exists ---
    const mainChartCtx = document.getElementById('mainChart');
    const secondaryChartCtx = document.getElementById('secondaryChart');

    if (mainChartCtx || secondaryChartCtx) {
        // Fetch dynamic data
        fetch('../../api/admin/analytics.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (mainChartCtx) {
                        new Chart(mainChartCtx, {
                            type: 'line',
                            data: {
                                labels: data.revenue.labels,
                                datasets: [{
                                    label: 'Revenue ($)',
                                    data: data.revenue.data,
                                    borderColor: '#22D3EE',
                                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.7)' } },
                                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.7)' } }
                                }
                            }
                        });
                    }

                    if (secondaryChartCtx) {
                        new Chart(secondaryChartCtx, {
                            type: 'doughnut',
                            data: {
                                labels: data.inventory.labels,
                                datasets: [{
                                    data: data.inventory.data,
                                    backgroundColor: ['#3B82F6', '#22D3EE', '#10B981', '#F59E0B', '#8B5CF6'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom', labels: { color: 'rgba(255, 255, 255, 0.7)' } }
                                },
                                cutout: '70%'
                            }
                        });
                    }
                }
            })
            .catch(err => console.error("Failed to load chart data", err));
    }
});
