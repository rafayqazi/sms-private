document.addEventListener('DOMContentLoaded', function () {
    const filterClass = document.getElementById('filter-class');
    const filterGender = document.getElementById('filter-gender');
    const filterSearch = document.getElementById('filter-search');
    const tableBody = document.getElementById('students-table-body');
    const sortHeader = document.getElementById('sort-gr');
    const sortIcon = document.getElementById('sort-icon');

    let currentSortBy = '';
    let currentOrder = '';

    function updateTable() {
        const classVal = filterClass.value;
        const genderVal = filterGender.value;
        const searchVal = filterSearch.value;

        // Updated path to API
        let url = `api/get_students.php?class=${encodeURIComponent(classVal)}&gender=${encodeURIComponent(genderVal)}&search=${encodeURIComponent(searchVal)}`;

        if (currentSortBy) {
            url += `&sort_by=${encodeURIComponent(currentSortBy)}&order=${encodeURIComponent(currentOrder)}`;
        }

        fetch(url)
            .then(response => response.text())
            .then(html => {
                tableBody.innerHTML = html;
            })
            .catch(error => console.error('Error fetching students:', error));
    }

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
