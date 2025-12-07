// Uses global API_BASE from main.js

document.addEventListener('DOMContentLoaded', function() {
    if (!checkAuth()) return;
    loadDepartmentsForSemesterDropdowns();
    loadSemesters();
    document.getElementById('addSemesterForm').addEventListener('submit', handleAddSemester);
    document.getElementById('editSemesterForm').addEventListener('submit', handleEditSemester);
});

async function loadDepartmentsForSemesterDropdowns() {
    // Fill department selects from live API
    const addDeptSelect = document.getElementById('semesterDept');
    const editDeptSelect = document.getElementById('editSemesterDept');
    addDeptSelect.innerHTML = '<option value="">Select...</option>';
    editDeptSelect.innerHTML = '<option value="">Select...</option>';
    try {
        const res = await fetch(`${API_BASE}/departments`);
        const data = await res.json();
        data.departments.forEach(dept => {
            addDeptSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
            editDeptSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
        });
    } catch(e){ /* fallback empty */ }
}

async function loadSemesters() {
    const tableBody = document.querySelector('#semesters-table tbody');
    tableBody.innerHTML = '';
    try {
        const response = await fetch(`${API_BASE}/semesters`);
        const data = await response.json();
        data.semesters.forEach(s => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${s.id}</td>
                <td>${s.title}</td>
                <td>${s.code}</td>
                <td>${s.department_id || ''}</td>
                <td>${s.exam_date || ''}</td>
                <td>
                    <button class="btn btn-info" onclick="editSemester(${s.id}, '${s.title}', '${s.code}', '${s.department_id}', '${s.exam_date}')">Edit</button>
                    <button class="btn btn-danger" onclick="deleteSemester(${s.id})">Delete</button>
                </td>`;
            tableBody.appendChild(row);
        });
    } catch (err) {
        showAlert('Failed to load semesters', 'error');
    }
}

async function handleAddSemester(e) {
    e.preventDefault();
    const title = document.getElementById('semesterTitle').value;
    const code = document.getElementById('semesterCode').value;
    const department_id = document.getElementById('semesterDept').value;
    const exam_date = document.getElementById('semesterDate').value;
    try {
        await fetch(`${API_BASE}/semesters`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ title, code, department_id, exam_date })
        });
        showAlert('Semester added successfully!');
        closeModal('addSemesterModal');
        e.target.reset();
        loadSemesters();
    } catch(err) {
        showAlert('Failed to add semester', 'error');
    }
}

function editSemester(id, title, code, department_id, exam_date) {
    document.getElementById('editSemesterId').value = id;
    document.getElementById('editSemesterTitle').value = title;
    document.getElementById('editSemesterCode').value = code;
    document.getElementById('editSemesterDept').value = department_id;
    document.getElementById('editSemesterDate').value = exam_date;
    openModal('editSemesterModal');
}

async function handleEditSemester(e) {
    e.preventDefault();
    const id = document.getElementById('editSemesterId').value;
    const title = document.getElementById('editSemesterTitle').value;
    const code = document.getElementById('editSemesterCode').value;
    const department_id = document.getElementById('editSemesterDept').value;
    const exam_date = document.getElementById('editSemesterDate').value;
    try {
        await fetch(`${API_BASE}/semesters/${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ title, code, department_id, exam_date })
        });
        showAlert('Semester updated successfully!');
        closeModal('editSemesterModal');
        loadSemesters();
    } catch(err) {
        showAlert('Failed to update semester', 'error');
    }
}

async function deleteSemester(id) {
    if (!confirm('Are you sure you want to delete this semester?')) return;
    try {
        await fetch(`${API_BASE}/semesters/${id}`, { method: 'DELETE' });
        showAlert('Semester deleted successfully!');
        loadSemesters();
    } catch(err) {
        showAlert('Failed to delete semester', 'error');
    }
}