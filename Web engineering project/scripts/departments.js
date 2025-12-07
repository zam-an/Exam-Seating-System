// Uses global API_BASE from main.js

document.addEventListener('DOMContentLoaded', function () {
    if (!checkAuth()) return;
    loadDepartments();
    document.getElementById('addDeptForm').addEventListener('submit', handleAddDepartment);
    document.getElementById('editDeptForm').addEventListener('submit', handleEditDepartment);
});

async function loadDepartments() {
    const tableBody = document.querySelector('#departments-table tbody');
    tableBody.innerHTML = '';
    try {
        const response = await fetch(`${API_BASE}/departments`);
        const data = await response.json();
        data.departments.forEach(dept => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dept.id}</td>
                <td>${dept.name}</td>
                <td>
                    <button class="btn btn-info" onclick="editDepartment(${dept.id}, '${dept.name}')">Edit</button>
                    <button class="btn btn-danger" onclick="deleteDepartment(${dept.id})">Delete</button>
                </td>`;
            tableBody.appendChild(row);
        });
    } catch (err) {
        showAlert('Failed to load departments', 'error');
    }
}

async function handleAddDepartment(e) {
    e.preventDefault();
    const name = document.getElementById('deptName').value;
    try {
        await fetch(`${API_BASE}/departments`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name })
        });
        showAlert('Department added successfully!');
        closeModal('addDeptModal');
        e.target.reset();
        loadDepartments();
    } catch {
        showAlert('Failed to add department', 'error');
    }
}

function editDepartment(id, name) {
    document.getElementById('editDeptId').value = id;
    document.getElementById('editDeptName').value = name;
    openModal('editDeptModal');
}

async function handleEditDepartment(e) {
    e.preventDefault();
    const id = document.getElementById('editDeptId').value;
    const name = document.getElementById('editDeptName').value;
    try {
        await fetch(`${API_BASE}/departments/${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name })
        });
        showAlert('Department updated successfully!');
        closeModal('editDeptModal');
        loadDepartments();
    } catch {
        showAlert('Failed to update department', 'error');
    }
}

async function deleteDepartment(id) {
    if (!confirm('Are you sure you want to delete this department?')) return;
    try {
        await fetch(`${API_BASE}/departments/${id}`, { method: 'DELETE' });
        showAlert('Department deleted successfully!');
        loadDepartments();
    } catch {
        showAlert('Failed to delete department', 'error');
    }
}