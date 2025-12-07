// Uses global API_BASE from main.js
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    if (!checkAuth()) return;
    
    // Load dashboard data
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        // Fetch all data in parallel
        const [deptsRes, semsRes, studentsRes, roomsRes, plansRes] = await Promise.all([
            fetch(`${API_BASE}/departments.php`),
            fetch(`${API_BASE}/semesters.php`),
            fetch(`${API_BASE}/students.php`),
            fetch(`${API_BASE}/rooms.php`),
            fetch(`${API_BASE}/plans.php`)
        ]);
        
        // Parse responses
        const deptsData = await deptsRes.json();
        const semsData = await semsRes.json();
        const studentsData = await studentsRes.json();
        const roomsData = await roomsRes.json();
        const plansData = await plansRes.json();
        
        // Update stats with real data
        const deptCount = deptsData.success ? deptsData.departments.length : 0;
        const semCount = semsData.success ? semsData.semesters.length : 0;
        const studentCount = studentsData.success ? studentsData.students.length : 0;
        const roomCount = roomsData.success ? roomsData.rooms.length : 0;
        const planCount = plansData.success ? plansData.plans.length : 0;
        
        document.getElementById('dept-count').textContent = deptCount;
        document.getElementById('semester-count').textContent = semCount;
        document.getElementById('student-count').textContent = studentCount;
        document.getElementById('room-count').textContent = roomCount;
        document.getElementById('plan-count').textContent = planCount;
        
        // Load recent plans from database
        const tableBody = document.querySelector('#recent-plans-table tbody');
        tableBody.innerHTML = '';
        
        if (plansData.success && plansData.plans.length > 0) {
            // Show only the 5 most recent plans
            const recentPlans = plansData.plans.slice(0, 5);
            
            recentPlans.forEach(plan => {
                const row = document.createElement('tr');
                const planDate = plan.plan_date || plan.created_at || 'N/A';
                const status = plan.status || 'draft';
                row.innerHTML = `
                    <td>${plan.title || 'Untitled Plan'}</td>
                    <td>${planDate}</td>
                    <td><span class="status-badge status-${status.toLowerCase()}">${status}</span></td>
                    <td>
                        <button class="btn btn-info" onclick="viewPlan(${plan.id})">View</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        } else {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="4" style="text-align: center; color: #666;">No plans found. Create your first plan!</td>';
            tableBody.appendChild(row);
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        // Set all counts to 0 on error
        document.getElementById('dept-count').textContent = '0';
        document.getElementById('semester-count').textContent = '0';
        document.getElementById('student-count').textContent = '0';
        document.getElementById('room-count').textContent = '0';
        document.getElementById('plan-count').textContent = '0';
    }
}

function viewPlan(planId) {
    window.location.href = `seatings.html?plan=${planId}`;
}