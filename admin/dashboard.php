<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Get stats
$userCount = $db->query("SELECT COUNT(*) FROM users WHERE email != 'admin@unspend.com'")->fetchColumn();
$verifiedCount = $db->query("SELECT COUNT(*) FROM users WHERE email_verified = 1 AND email != 'admin@unspend.com'")->fetchColumn();
$referralCount = $db->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
$uploadCount = $db->query("SELECT COUNT(*) FROM uploads")->fetchColumn();
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main id="main-content" class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <span class="navbar-text">
                            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <h2><?php echo $userCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Verified Users</h5>
                            <h2><?php echo $verifiedCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Referrals</h5>
                            <h2><?php echo $referralCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Uploads</h5>
                            <h2><?php echo $uploadCount; ?></h2>
                        </div>
                    </div>
                </div>
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