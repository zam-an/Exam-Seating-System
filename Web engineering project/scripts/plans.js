// Uses global API_BASE from main.js

document.addEventListener('DOMContentLoaded', function () {
    if (!checkAuth()) return;
    loadPlans();
    loadSemestersForPlan();
    loadRoomsForPlan();
    const form = document.getElementById('generatePlanForm');
    if (form) form.addEventListener('submit', handleGeneratePlan);
});

async function loadPlans() {
    const tableBody = document.querySelector('#plans-table tbody');
    if (!tableBody) return;
    tableBody.innerHTML = '';
    try {
        const response = await fetch(`${API_BASE}/plans`);
        const data = await response.json();
        if (!data.success) {
            showAlert(data.error || 'Failed to load plans', 'error');
            return;
        }
        const plans = data.plans || [];
        if (!plans.length) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="7" style="text-align:center;">No plans generated yet.</td>';
            tableBody.appendChild(emptyRow);
            return;
        }
        plans.forEach(plan => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${plan.id}</td>
                <td>${plan.title}</td>
                <td>${formatDate(plan.plan_date)}</td>
                <td>${plan.status || ''}</td>
                <td>${plan.total_students || 0}</td>
                <td>${plan.total_rooms || 0}</td>
                <td>
                    <button class="btn btn-secondary" ${plan.total_students ? '' : 'disabled'} onclick="downloadPlan(${plan.id})">PDF</button>
                    <button class="btn btn-danger" onclick="deletePlan(${plan.id})">Delete</button>
                </td>`;
            tableBody.appendChild(row);
        });
    } catch {
        showAlert('Failed to load plans', 'error');
    }
}

async function loadSemestersForPlan() {
    const container = document.getElementById('semestersChecklist');
    if (!container) return;
    container.innerHTML = '<p>Loading...</p>';
    try {
        const response = await fetch(`${API_BASE}/semesters`);
        const data = await response.json();
        container.innerHTML = '';
        (data.semesters || []).forEach(sem => {
            const label = document.createElement('label');
            label.className = 'checklist-item';
            label.innerHTML = `
                <input type="checkbox" value="${sem.id}">
                <span>${sem.title} (${sem.code})</span>`;
            container.appendChild(label);
        });
        if (!container.children.length) {
            container.innerHTML = '<p>No semesters available.</p>';
        }
    } catch {
        container.innerHTML = '<p>Failed to load semesters.</p>';
    }
}

async function loadRoomsForPlan() {
    const container = document.getElementById('roomsChecklist');
    if (!container) return;
    container.innerHTML = '<p>Loading...</p>';
    try {
        const response = await fetch(`${API_BASE}/rooms`);
        const data = await response.json();
        container.innerHTML = '';
        (data.rooms || []).forEach(room => {
            const label = document.createElement('label');
            label.className = 'checklist-item';
            label.innerHTML = `
                <input type="checkbox" value="${room.id}">
                <span>${room.name} (${room.code}) - ${room.capacity} seats</span>`;
            container.appendChild(label);
        });
        if (!container.children.length) {
            container.innerHTML = '<p>No rooms available.</p>';
        }
    } catch {
        container.innerHTML = '<p>Failed to load rooms.</p>';
    }
}

async function handleGeneratePlan(e) {
    e.preventDefault();
    const title = document.getElementById('planTitle').value.trim();
    const plan_date = document.getElementById('planDate').value;
    const strategy = document.getElementById('planStrategy').value;
    const semesterIds = [...document.querySelectorAll('#semestersChecklist input:checked')].map(el => el.value);
    const roomIds = [...document.querySelectorAll('#roomsChecklist input:checked')].map(el => el.value);

    if (!semesterIds.length) {
        showAlert('Select at least one semester.', 'error');
        return;
    }
    if (!roomIds.length) {
        showAlert('Select at least one room.', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/plans`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title,
                plan_date,
                strategy,
                semesters: semesterIds,
                rooms: roomIds
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Failed to generate plan.');
        }
        showAlert('Plan generated successfully!');
        closeModal('generatePlanModal');
        e.target.reset();
        document.querySelectorAll('#semestersChecklist input:checked').forEach(el => (el.checked = false));
        document.querySelectorAll('#roomsChecklist input:checked').forEach(el => (el.checked = false));
        loadPlans();
    } catch (error) {
        showAlert(error.message || 'Failed to generate plan.', 'error');
    }
}

async function deletePlan(id) {
    if (!confirm('Are you sure you want to delete this plan?')) return;
    try {
        await fetch(`${API_BASE}/plans/${id}`, { method: 'DELETE' });
        showAlert('Plan deleted successfully!');
        loadPlans();
    } catch {
        showAlert('Failed to delete plan', 'error');
    }
}

function formatDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString();
}

function downloadPlan(id) {
    window.open(`${API_BASE}/plan_pdf.php?id=${id}`, '_blank');
}