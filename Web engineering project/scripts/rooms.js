// Uses global API_BASE from main.js

document.addEventListener('DOMContentLoaded', function() {
    if (!checkAuth()) return;
    loadRooms();
    
    const addForm = document.getElementById('addRoomForm');
    const editForm = document.getElementById('editRoomForm');
    
    if (addForm) {
        addForm.addEventListener('submit', handleAddRoom);
        console.log('Add room form listener attached');
    } else {
        console.error('Add room form not found!');
    }
    
    if (editForm) {
        editForm.addEventListener('submit', handleEditRoom);
        console.log('Edit room form listener attached');
    } else {
        console.error('Edit room form not found!');
    }
});

async function loadRooms() {
    const tableBody = document.querySelector('#rooms-table tbody');
    tableBody.innerHTML = '';
    try {
        const response = await fetch(`${API_BASE}/rooms.php`);
        const data = await response.json();
        data.rooms.forEach(room => {
            const row = document.createElement('tr');
            // Escape special characters to prevent JavaScript errors
            const safeCode = (room.code || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeName = (room.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            
            row.innerHTML = `
                <td>${room.id}</td>
                <td>${room.code}</td>
                <td>${room.name}</td>
                <td>${room.capacity}</td>
                <td>${room.rows} × ${room.cols}</td>
                <td>
                    <button class="btn btn-info" onclick="editRoom(${room.id}, '${safeCode}', '${safeName}', ${room.capacity || 0}, ${room.rows || 0}, ${room.cols || 0})">Edit</button>
                    <button class="btn btn-danger" onclick="deleteRoom(${room.id})">Delete</button>
                </td>`;
            tableBody.appendChild(row);
        });
    } catch {
        showAlert('Failed to load rooms', 'error');
    }
}

async function handleAddRoom(e) {
    e.preventDefault();
    const code = document.getElementById('roomCode').value;
    const name = document.getElementById('roomName').value;
    const capacity = document.getElementById('roomCapacity').value;
    const rows = document.getElementById('roomRows').value;
    const cols = document.getElementById('roomCols').value;
    try {
        const response = await fetch(`${API_BASE}/rooms`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code, name, capacity, rows, cols })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert('Room added successfully!');
            closeModal('addRoomModal');
            e.target.reset();
            loadRooms();
        } else {
            showAlert(data.error || 'Failed to add room', 'error');
        }
    } catch (error) {
        console.error('Error adding room:', error);
        showAlert('Failed to add room: ' + error.message, 'error');
    }
}

function editRoom(id, code, name, capacity, rows, cols) {
    console.log('editRoom called with:', { id, code, name, capacity, rows, cols });
    
    try {
        document.getElementById('editRoomId').value = id;
        document.getElementById('editRoomCode').value = code;
        document.getElementById('editRoomName').value = name;
        document.getElementById('editRoomCapacity').value = capacity;
        document.getElementById('editRoomRows').value = rows;
        document.getElementById('editRoomCols').value = cols;
        console.log('Form fields populated');
        
        openModal('editRoomModal');
        console.log('Modal opened');
    } catch (error) {
        console.error('Error in editRoom:', error);
        alert('Error opening edit form: ' + error.message);
    }
}

async function handleEditRoom(e) {
    e.preventDefault();
    console.log('Edit room form submitted');
    
    const id = document.getElementById('editRoomId').value;
    const code = document.getElementById('editRoomCode').value;
    const name = document.getElementById('editRoomName').value;
    const capacity = document.getElementById('editRoomCapacity').value;
    const rows = document.getElementById('editRoomRows').value;
    const cols = document.getElementById('editRoomCols').value;
    
    console.log('Room data:', { id, code, name, capacity, rows, cols });
    
    if (!id) {
        showAlert('Room ID is missing', 'error');
        return;
    }
    
    // Try using the index.php router format
    const url = `${API_BASE}/rooms/${id}`;
    console.log('Making PUT request to:', url);
    
    try {
        const response = await fetch(url, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code, name, capacity, rows, cols })
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (response.ok && data.success) {
            showAlert('Room updated successfully!');
            closeModal('editRoomModal');
            loadRooms();
        } else {
            showAlert(data.error || 'Failed to update room', 'error');
        }
    } catch (error) {
        console.error('Error updating room:', error);
        showAlert('Failed to update room: ' + error.message, 'error');
    }
}

async function deleteRoom(id) {
    if (!confirm('Are you sure you want to delete this room?')) return;
    try {
        await fetch(`${API_BASE}/rooms/${id}`, { method: 'DELETE' });
        showAlert('Room deleted successfully!');
        loadRooms();
    } catch {
        showAlert('Failed to delete room', 'error');
    }
}