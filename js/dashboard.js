// Dashboard JavaScript for referral statistics
document.addEventListener('DOMContentLoaded', function() {
    loadReferralStats();
});

async function loadReferralStats() {
    try {
        const response = await fetch('get_referral_stats.php');
        const data = await response.json();

        if (data.success) {
            updateDashboardStats(data.stats);
            updateReferralChart(data.stats);
            updateReferralTable(data.referrals);
        } else {
            console.error('Failed to load referral stats:', data.message);
        }
    } catch (error) {
        console.error('Error loading referral stats:', error);
    }
}

function updateDashboardStats(stats) {
    document.getElementById('dashTotalEarnings').textContent = `$${stats.earnings.toFixed(2)}`;
    document.getElementById('dashTotalReferrals').textContent = stats.total_clicks;
    document.getElementById('dashPendingReferrals').textContent = stats.pending;
}

function updateReferralChart(stats) {
    const ctx = document.getElementById('referralDoughnutChart').getContext('2d');

    const data = {
        labels: ['Completed Referrals', 'Pending Referrals'],
        datasets: [{
            data: [stats.completed, stats.pending],
            backgroundColor: ['#10B981', '#F59E0B'],
            hoverOffset: 15
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });
}

function updateReferralTable(referrals) {
    const tbody = document.getElementById('referralTableBody');
    tbody.innerHTML = '';

    if (referrals.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500">No referrals yet. Share your link to start earning!</td></tr>';
        return;
    }

    referrals.forEach(referral => {
        const statusClass = referral.status === 'completed' ? 'text-green-600' : 'text-yellow-600';
        const statusText = referral.status === 'completed' ? 'Completed' : 'Pending';
        const commission = referral.status === 'completed' ? '$10.00' : '-';
        const date = new Date(referral.created_at).toLocaleDateString();

        tbody.innerHTML += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${referral.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${date}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass === 'text-green-600' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${commission}</td>
            </tr>
        `;
    });
}