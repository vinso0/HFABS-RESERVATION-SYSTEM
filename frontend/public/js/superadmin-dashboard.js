const API = '../../backend/public/index.php?url=superadmin/';

function renderDashboardStats(data) {
    document.getElementById('stat-total-admins').textContent    = data.total_admins;
    document.getElementById('stat-active-admins').textContent   = data.active_admins;
    document.getElementById('stat-total-branches').textContent  = data.total_branches;
    document.getElementById('ov-active-branches').textContent   = data.active_branches;
    document.getElementById('ov-inactive-branches').textContent = data.inactive_branches;
    document.getElementById('ov-total-users').textContent       = data.total_users;
}

function renderRecentAdmins(admins) {
    const tbody = document.getElementById('recent-admins-tbody');
    if (!admins.length) {
        tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No admin accounts found.</p></div></td></tr>';
        return;
    }
    tbody.innerHTML = admins.map(function(admin) {
        const activeClass = parseInt(admin.is_active) === 1 ? 'badge-active' : 'badge-inactive';
        const activeLabel = parseInt(admin.is_active) === 1 ? 'Active' : 'Inactive';
        const roleClass   = admin.role === 'admin' ? 'badge-admin' : 'badge-cashier';
        return '<tr>' +
            '<td><strong>' + admin.username + '</strong></td>' +
            '<td><span class="badge ' + roleClass + '">' + admin.role + '</span></td>' +
            '<td>' + (admin.branch_name || '—') + '</td>' +
            '<td><span class="badge ' + activeClass + '">' + activeLabel + '</span></td>' +
        '</tr>';
    }).join('');
}

function renderRecentBranches(branches) {
    const tbody = document.getElementById('recent-branches-tbody');
    if (!branches || !branches.length) {
        tbody.innerHTML = '<tr><td colspan="3"><div class="empty-state"><i class="fas fa-store-slash"></i><p>No branches found.</p></div></td></tr>';
        return;
    }
    function fmtTime(t) {
        if (!t) return '—';
        var p = t.split(':'), hr = parseInt(p[0]), mn = p[1];
        return (hr % 12 || 12) + ':' + mn + ' ' + (hr < 12 ? 'AM' : 'PM');
    }
    tbody.innerHTML = branches.map(function(b) {
        const statusClass = b.status === 'active' ? 'badge-active' : 'badge-inactive';
        return '<tr>' +
            '<td><strong style="text-transform:capitalize;">' + b.branch_name + '</strong></td>' +
            '<td style="font-size:12px;">' + fmtTime(b.opening_time) + ' – ' + fmtTime(b.closing_time) + '</td>' +
            '<td><span class="badge ' + statusClass + '">' + b.status + '</span></td>' +
        '</tr>';
    }).join('');
}

function loadDashboard() {
    // Stats
    fetch(API + 'dashboardStats')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) renderDashboardStats(json.data);
        })
        .catch(function(err) { console.error('Stats error:', err); });

    // Recent admins
    fetch(API + 'recentAdmins')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) renderRecentAdmins(json.data);
        })
        .catch(function(err) { console.error('Recent admins error:', err); });

    // Branches for status table
    fetch(API + 'branches')
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) renderRecentBranches(json.data);
        })
        .catch(function(err) { console.error('Branches error:', err); });
}

document.addEventListener('DOMContentLoaded', loadDashboard);
