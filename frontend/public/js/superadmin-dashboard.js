// ── Mock Data — mirrors `users` table (role: admin/cashier) ──
const mockAdmins = [
    {
        user_id: 1,
        username: 'cal branch admin',
        email: 'admin@admin',
        contact_number: '',
        password: '',
        role: 'admin',
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        is_active: 1,
        deleted_at: null,
        created_at: '2026-01-09 11:42:55'
    },
    {
        user_id: 7,
        username: 'cal branch cashier',
        email: 'cashier@gmail.com',
        contact_number: '12342141',
        password: '',
        role: 'cashier',
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        is_active: 1,
        deleted_at: null,
        created_at: '2026-02-20 14:00:32'
    }
];

// ── Mock Data — mirrors `branch` table ──
const mockBranches = [
    {
        branch_id: 1,
        branch_name: 'Caloocan Branch',
        branch_location: '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place',
        contact_number: '09054543104',
        opening_time: '10:00:00',
        closing_time: '21:00:00',
        down_payment_rate: 0.5,
        email: 'hfabscal@gmail.com',
        status: 'active'
    },
    {
        branch_id: 2,
        branch_name: 'Quezon City Branch',
        branch_location: '850 Atherton, Quezon City',
        contact_number: '0946 178 23',
        opening_time: '08:00:00',
        closing_time: '20:00:00',
        down_payment_rate: 0.5,
        email: 'hfabsqc@gmail.com',
        status: 'active'
    }
];

const MOCK_TOTAL_USERS = 7;

function renderDashboardStats() {
    const adminStaff   = mockAdmins.filter(u => u.role === 'admin' || u.role === 'cashier');
    const activeAdmins = adminStaff.filter(u => u.is_active === 1);
    const activeBranches = mockBranches.filter(b => b.status === 'active');
    const inactiveBranches = mockBranches.filter(b => b.status === 'inactive');

    document.getElementById('stat-total-admins').textContent   = adminStaff.length;
    document.getElementById('stat-active-admins').textContent  = activeAdmins.length;
    document.getElementById('stat-total-branches').textContent = mockBranches.length;
    document.getElementById('ov-active-branches').textContent  = activeBranches.length;
    document.getElementById('ov-inactive-branches').textContent = inactiveBranches.length;
    document.getElementById('ov-total-users').textContent      = MOCK_TOTAL_USERS;
}

function renderRecentAdmins() {
    const tbody = document.getElementById('recent-admins-tbody');
    const adminStaff = mockAdmins.filter(u => u.role === 'admin' || u.role === 'cashier');

    if (!adminStaff.length) {
        tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No admin accounts found.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = adminStaff.map(function (admin) {
        const date = new Date(admin.created_at).toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        const roleBadgeClass = admin.role === 'admin' ? 'badge-admin' : 'badge-cashier';
        return '<tr>' +
            '<td><strong>' + admin.username + '</strong></td>' +
            '<td>' + admin.email + '</td>' +
            '<td>' + (admin.branch_name || '—') + '</td>' +
            '<td><span class="badge ' + (admin.is_active ? 'badge-active' : 'badge-inactive') + '">' + (admin.is_active ? 'Active' : 'Inactive') + '</span></td>' +
            '<td>' + date + '</td>' +
        '</tr>';
    }).join('');
}

document.addEventListener('DOMContentLoaded', function () {
    renderDashboardStats();
    renderRecentAdmins();
});
