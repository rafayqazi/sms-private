document.addEventListener('DOMContentLoaded', function () {
    const filterClass = document.getElementById('filter-class');
    const filterGender = document.getElementById('filter-gender');
    const filterSearch = document.getElementById('filter-search');
    const tableBody = document.getElementById('students-table-body');
    const sortHeader = document.getElementById('sort-gr');
    const sortIcon = document.getElementById('sort-icon');

    let genderChart = null;
    let classChart = null;
    let currentSortBy = '';
    let currentOrder = '';

    function initCharts(stats) {
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const classCtx = document.getElementById('classChart').getContext('2d');

        // Gender Chart
        genderChart = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(stats.gender),
                datasets: [{
                    data: Object.values(stats.gender),
                    backgroundColor: ['#3b82f6', '#ec4899', '#94a3b8'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Class Chart
        classChart = new Chart(classCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(stats.class),
                datasets: [{
                    label: 'Students',
                    data: Object.values(stats.class),
                    backgroundColor: '#10b981',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    function updateCharts(stats) {
        if (!genderChart || !classChart) {
            initCharts(stats);
            return;
        }

        // Update Gender Chart
        genderChart.data.labels = Object.keys(stats.gender);
        genderChart.data.datasets[0].data = Object.values(stats.gender);
        genderChart.update();

        // Update Class Chart
        classChart.data.labels = Object.keys(stats.class);
        classChart.data.datasets[0].data = Object.values(stats.class);
        classChart.update();
    }

    function updateTable() {
        const classVal = filterClass.value;
        const genderVal = filterGender.value;
        const searchVal = filterSearch.value;

        const baseUrl = (typeof API_BASE_URL !== 'undefined') ? API_BASE_URL : 'api/';
        let url = `${baseUrl}get_students.php?json=1&class=${encodeURIComponent(classVal)}&gender=${encodeURIComponent(genderVal)}&search=${encodeURIComponent(searchVal)}`;

        if (currentSortBy) {
            url += `&sort_by=${encodeURIComponent(currentSortBy)}&order=${encodeURIComponent(currentOrder)}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = data.html;
                updateCharts(data.stats);
            })
            .catch(error => console.error('Error fetching students:', error));
    }

    // Initial load for charts
    updateTable();

    if (filterClass) filterClass.addEventListener('change', updateTable);
    if (filterGender) filterGender.addEventListener('change', updateTable);
    if (filterSearch) filterSearch.addEventListener('input', updateTable);

    if (sortHeader) {
        sortHeader.addEventListener('click', function () {
            currentSortBy = 'gr_no';
            if (currentOrder === 'ASC') {
                currentOrder = 'DESC';
                sortIcon.className = 'fas fa-sort-down';
            } else {
                currentOrder = 'ASC';
                sortIcon.className = 'fas fa-sort-up';
            }
            updateTable();
        });
    }
});
