<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Get all referrals with user details
$stmt = $db->prepare("
    SELECT r.id, r.status, r.created_at, r.completed_at,
           ru.name as referrer_name, ru.email as referrer_email,
           reu.name as referred_name, reu.email as referred_email
    FROM referrals r
    JOIN users ru ON r.referrer_id = ru.id
    JOIN users reu ON r.referred_user_id = reu.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main id="main-content" class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Referral Management</h1>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Referrer</th>
                            <th>Referred User</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo $referral['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($referral['referrer_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($referral['referrer_email']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($referral['referred_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($referral['referred_email']); ?></small>
                            </td>
                            <td>
                                <?php if ($referral['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($referral['created_at'])); ?></td>
                            <td><?php echo $referral['completed_at'] ? date('Y-m-d H:i', strtotime($referral['completed_at'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    width: 250px;
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

#main-content {
    margin-left: 250px;
    padding-top: 20px;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #007bff;
}

.sidebar .nav-link:hover {
    color: #007bff;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
    }
    #main-content {
        margin-left: 0;
    }
}
</style>

<?php include 'includes/footer.php'; ?>