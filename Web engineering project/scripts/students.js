// Uses global API_BASE from main.js

document.addEventListener('DOMContentLoaded', function () {
    if (!checkAuth()) return;
    loadStudents();
    loadSemestersForImport();
    const addForm = document.getElementById('addStudentForm');
    const editForm = document.getElementById('editStudentForm');
    if (addForm) addForm.addEventListener('submit', handleAddStudent);
    if (editForm) editForm.addEventListener('submit', handleEditStudent);
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', handleImportStudents);
    }
});

async function loadSemestersForImport() {
    const select = document.getElementById('importSemester');
    if (!select) return;
    select.innerHTML = '<option value="">Select Semester</option>';
    try {
        const response = await fetch(`${API_BASE}/semesters`);
        const data = await response.json();
        (data.semesters || []).forEach(sem => {
            const option = document.createElement('option');
            option.value = sem.id;
            option.textContent = `${sem.title} (${sem.code})`;
            select.appendChild(option);
        });
    } catch {
        showAlert('Failed to load semesters', 'error');
    }
}

async function loadStudents() {
    const tableBody = document.querySelector('#students-table tbody');
    if (!tableBody) return;
    tableBody.innerHTML = '';
    try {
        const response = await fetch(`${API_BASE}/students`);
        const data = await response.json();
        data.students.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.id}</td>
                <td>${student.roll_no}</td>
                <td>${student.full_name}</td>
                <td>${student.seat_pref || ''}</td>
                <td>${student.semester_id || ''}</td>
                <td>
                    <button class="btn btn-info" onclick="editStudent(${student.id}, '${student.roll_no}', '${student.full_name}', '${student.seat_pref}', '${student.semester_id}')">Edit</button>
                    <button class="btn btn-danger" onclick="deleteStudent(${student.id})">Delete</button>
                </td>`;
            tableBody.appendChild(row);
        });
    } catch {
        showAlert('Failed to load students', 'error');
    }
}

async function handleAddStudent(e) {
    e.preventDefault();
    const roll_no = document.getElementById('studentRollNo').value;
    const full_name = document.getElementById('studentFullName').value;
    const seat_pref = document.getElementById('studentSeatPref').value;
    const semester_id = document.getElementById('studentSemesterId').value;
    try {
        await fetch(`${API_BASE}/students`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ roll_no, full_name, seat_pref, semester_id })
        });
        showAlert('Student added successfully!');
        closeModal('addStudentModal');
        e.target.reset();
        loadStudents();
    } catch {
        showAlert('Failed to add student', 'error');
    }
}

function editStudent(id, roll_no, full_name, seat_pref, semester_id) {
    document.getElementById('editStudentId').value = id;
    document.getElementById('editStudentRollNo').value = roll_no;
    document.getElementById('editStudentFullName').value = full_name;
    document.getElementById('editStudentSeatPref').value = seat_pref;
    document.getElementById('editStudentSemesterId').value = semester_id;
    openModal('editStudentModal');
}

async function handleEditStudent(e) {
    e.preventDefault();
    const id = document.getElementById('editStudentId').value;
    const roll_no = document.getElementById('editStudentRollNo').value;
    const full_name = document.getElementById('editStudentFullName').value;
    const seat_pref = document.getElementById('editStudentSeatPref').value;
    const semester_id = document.getElementById('editStudentSemesterId').value;
    try {
        await fetch(`${API_BASE}/students/${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ roll_no, full_name, seat_pref, semester_id })
        });
        showAlert('Student updated successfully!');
        closeModal('editStudentModal');
        loadStudents();
    } catch {
        showAlert('Failed to update student', 'error');
    }
}

async function deleteStudent(id) {
    if (!confirm('Are you sure you want to delete this student?')) return;
    try {
        await fetch(`${API_BASE}/students/${id}`, { method: 'DELETE' });
        showAlert('Student deleted successfully!');
        loadStudents();
    } catch {
        showAlert('Failed to delete student', 'error');
    }
}

function parseCsv(text) {
    return text
        .split(/\r?\n/)
        .map(line => line.trim())
        .filter(Boolean)
        .map(line => {
            const [roll_no = '', full_name = '', seat_pref = ''] = line.split(',').map(item => item.trim());
            return { roll_no, full_name, seat_pref };
        })
        .filter(row => row.roll_no && row.full_name);
}

async function handleImportStudents(e) {
    e.preventDefault();
    const fileInput = document.getElementById('csvFile');
    const semesterSelect = document.getElementById('importSemester');
    if (!fileInput?.files?.length) {
        showAlert('Please choose a CSV file.', 'error');
        return;
    }
    if (!semesterSelect?.value) {
        showAlert('Please select a semester.', 'error');
        return;
    }

    const semester_id = semesterSelect.value;
    const reader = new FileReader();
    reader.onload = async () => {
        const rows = parseCsv(reader.result || '');
        if (!rows.length) {
            showAlert('CSV is empty or invalid.', 'error');
            return;
        }

        let successCount = 0;
        for (const row of rows) {
            try {
                await fetch(`${API_BASE}/students`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        roll_no: row.roll_no,
                        full_name: row.full_name,
                        seat_pref: row.seat_pref || null,
                        semester_id
                    })
                });
                successCount++;
            } catch {
                // skip failed row but continue
            }
        }

        showAlert(`Imported ${successCount} student(s).`);
        closeModal('importModal');
        document.getElementById('importForm').reset();
        loadStudents();
    };
    reader.onerror = () => showAlert('Failed to read the CSV file.', 'error');
    reader.readAsText(fileInput.files[0]);
}