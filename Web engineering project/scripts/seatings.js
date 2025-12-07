// Uses global API_BASE from main.js

let currentPlanId = null;
let planData = null;

document.addEventListener('DOMContentLoaded', function () {
    if (!checkAuth()) return;
    
    // Get plan ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentPlanId = urlParams.get('plan');
    
    if (!currentPlanId) {
        showAlert('No plan ID provided', 'error');
        return;
    }
    
    loadPlanData();
});

async function loadPlanData() {
    try {
        const response = await fetch(`${API_BASE}/plans/${currentPlanId}`);
        const data = await response.json();
        
        if (!data.success) {
            showAlert('Failed to load plan: ' + (data.error || 'Unknown error'), 'error');
            return;
        }
        
        planData = data;
        
        // Update plan info
        document.getElementById('planTitle').textContent = data.plan.title || 'Seating Arrangement';
        document.getElementById('planName').textContent = data.plan.title || 'N/A';
        document.getElementById('planDate').textContent = data.plan.plan_date || 'Not set';
        document.getElementById('planStatus').textContent = data.plan.status || 'draft';
        document.getElementById('planStatus').className = `status-badge status-${(data.plan.status || 'draft').toLowerCase()}`;
        document.getElementById('totalStudents').textContent = data.seatings ? data.seatings.length : 0;
        document.getElementById('totalRooms').textContent = data.rooms ? data.rooms.length : 0;
        
        // Populate room selector
        const roomSelector = document.getElementById('roomSelector');
        roomSelector.innerHTML = '<option value="">Select a room</option>';
        
        if (data.rooms && data.rooms.length > 0) {
            data.rooms.forEach(room => {
                const option = document.createElement('option');
                option.value = room.room_id;
                option.textContent = `${room.name} (${room.code})`;
                roomSelector.appendChild(option);
            });
        }
        
        // Load first room by default if available
        if (data.rooms && data.rooms.length > 0) {
            loadRoomSeating(data.rooms[0].room_id);
        }
        
    } catch (error) {
        console.error('Error loading plan:', error);
        showAlert('Failed to load plan data', 'error');
    }
}

function loadRoomSeating(roomId = null) {
    if (!planData || !planData.seatings) {
        return;
    }
    
    const roomSelector = document.getElementById('roomSelector');
    const selectedRoomId = roomId || roomSelector.value;
    
    if (!selectedRoomId) {
        document.getElementById('seatingGrid').innerHTML = '<p>Please select a room</p>';
        return;
    }
    
    // Find room info
    const room = planData.rooms.find(r => r.room_id == selectedRoomId);
    if (!room) {
        document.getElementById('seatingGrid').innerHTML = '<p>Room not found</p>';
        return;
    }
    
    // Update room title
    document.getElementById('currentRoomTitle').textContent = `Seating Arrangement - ${room.name}`;
    
    // Filter seatings for this room
    const roomSeatings = planData.seatings.filter(s => s.room_id == selectedRoomId);
    
    // Get room dimensions (we'll need to fetch room details)
    fetch(`${API_BASE}/rooms/${selectedRoomId}`)
        .then(res => res.json())
        .then(roomData => {
            if (roomData.success && roomData.room) {
                const rows = roomData.room.rows || 10;
                const cols = roomData.room.cols || 10;
                renderSeatingGrid(roomSeatings, rows, cols, room.name);
            } else {
                // Fallback: calculate grid from seatings
                const maxRow = Math.max(...roomSeatings.map(s => s.seat_row), 0);
                const maxCol = Math.max(...roomSeatings.map(s => s.seat_col), 0);
                renderSeatingGrid(roomSeatings, maxRow, maxCol, room.name);
            }
        })
        .catch(() => {
            // Fallback: calculate grid from seatings
            const maxRow = Math.max(...roomSeatings.map(s => s.seat_row), 0);
            const maxCol = Math.max(...roomSeatings.map(s => s.seat_col), 0);
            renderSeatingGrid(roomSeatings, maxRow, maxCol, room.name);
        });
}

function renderSeatingGrid(seatings, rows, cols, roomName) {
    const grid = document.getElementById('seatingGrid');
    grid.innerHTML = '';
    grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
    grid.style.gridTemplateRows = `repeat(${rows}, 1fr)`;
    
    // Create a map of seatings by position
    const seatingMap = {};
    seatings.forEach(s => {
        const key = `${s.seat_row}-${s.seat_col}`;
        seatingMap[key] = s;
    });
    
    // Render grid
    for (let row = 1; row <= rows; row++) {
        for (let col = 1; col <= cols; col++) {
            const key = `${row}-${col}`;
            const seat = seatingMap[key];
            const seatDiv = document.createElement('div');
            seatDiv.className = 'seat';
            
            if (seat) {
                seatDiv.classList.add('occupied');
                seatDiv.innerHTML = `
                    <div class="seat-number">${row}-${col}</div>
                    <div class="student-name">${seat.full_name || 'N/A'}</div>
                    <div class="student-roll">${seat.roll_no || 'N/A'}</div>
                `;
            } else {
                seatDiv.classList.add('empty');
                seatDiv.innerHTML = `<div class="seat-number">${row}-${col}</div>`;
            }
            
            grid.appendChild(seatDiv);
        }
    }
}

function exportPDF() {
    if (!currentPlanId) {
        showAlert('No plan selected', 'error');
        return;
    }
    window.open(`${API_BASE}/plan_pdf.php?id=${currentPlanId}`, '_blank');
}

function validatePlan() {
    if (!planData) {
        showAlert('No plan data loaded', 'error');
        return;
    }
    
    const results = document.getElementById('validationResults');
    let issues = [];
    
    // Check if all students have seats
    if (planData.seatings.length === 0) {
        issues.push('No students assigned to seats');
    }
    
    // Check for duplicate seat assignments
    const seatKeys = planData.seatings.map(s => `${s.room_id}-${s.seat_row}-${s.seat_col}`);
    const duplicates = seatKeys.filter((key, index) => seatKeys.indexOf(key) !== index);
    if (duplicates.length > 0) {
        issues.push(`Found ${duplicates.length} duplicate seat assignments`);
    }
    
    if (issues.length === 0) {
        results.innerHTML = '<div class="alert alert-success">✓ Plan is valid! All checks passed.</div>';
    } else {
        results.innerHTML = issues.map(issue => 
            `<div class="alert alert-error">⚠ ${issue}</div>`
        ).join('');
    }
}

function swapSeats() {
    openModal('swapSeatsModal');
    // TODO: Implement seat swapping functionality
}
