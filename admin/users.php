<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    // Prevent deleting main admin
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['email'] !== 'admin@unspend.com') {

        // Begin transaction for safe multiple deletions
        $db->beginTransaction();

        try {
            // 1️⃣ Delete related uploads
            $deleteUploads = $db->prepare("DELETE FROM uploads WHERE user_id = ?");
            $deleteUploads->execute([$userId]);

            // 2️⃣ Delete referrals where user is referrer or referred
            $deleteReferrals = $db->prepare("
                DELETE FROM referrals 
                WHERE referrer_id = ? OR referred_user_id = ?
            ");
            $deleteReferrals->execute([$userId, $userId]);

            // 3️⃣ Delete user from users table
            $deleteUser = $db->prepare("DELETE FROM users WHERE id = ?");
            $deleteUser->execute([$userId]);

            // Commit transaction
            $db->commit();

        } catch (Exception $e) {
            // Rollback in case of any failure
            $db->rollBack();
            error_log("Delete error: " . $e->getMessage());
        }
    }

    header('Location: users.php');
    exit;
}


// Get all users except admin
$stmt = $db->prepare("SELECT * FROM users WHERE email != 'admin@unspend.com' ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Users Management</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Verified</th>
                        <th>Created</th>
                        <th>Used Credits</th>
                        <th>Total Purchased Credits</th>
                        <th>Avilalbe Credits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-primary"></i>
                                </div>
                                <span><?php echo htmlspecialchars($user['name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success-subtle text-success px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Verified
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                        <!-- 
                        <td>
                            <?php if ($user['last_login']): ?>
                                <span class="text-muted" title="<?php echo date('Y-m-d H:i:s', strtotime($user['last_login'])); ?>">
                                    <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-danger">Never</span>
                            <?php endif; ?>
                        </td>
                        -->
                        <td><?php echo $user['analysis_count']; ?></td>
                        <td><?php echo $user['additional_credits_total']; ?></td>
                        <td><?php echo $user['additional_credits']; ?></td> 
                        <td>
                            <div class="d-flex gap-1">
                                <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout/app.php';
?>

<style>
/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length select {
    min-width: 80px;
    display: inline-block;
    margin: 0 10px;
}

.dataTables_wrapper .dataTables_filter input {
    margin-left: 10px;
    min-width: 250px;
}

.dataTables_wrapper .dataTables_info {
    padding-top: 1rem;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 0.75rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background-color: #fff;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff !important;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search users...",
            lengthMenu: "_MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
            infoFiltered: "(filtered from _MAX_ total users)"
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        order: [[0, 'desc']], // Sort by ID in descending order
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function(row) {
                        var data = row.data();
                        return 'Details for ' + data[1];
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll()
            }
        },
        columnDefs: [
            {
                targets: -1,
                orderable: false
            }
        ],
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'excel',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'pdf',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'print',
                className: 'btn btn-sm btn-primary'
            }
        ],
        initComplete: function () {
            // Add Bootstrap classes to DataTables elements
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            
            // Create button container
            var buttonContainer = $('<div class="text-end mb-3"></div>');
            table.buttons().container().appendTo(buttonContainer);
            $('.dataTables_length').parent().after(buttonContainer);
        }
    });
});

function deleteUser(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'users.php?delete=' + userId;
        }
    });
}
</script>