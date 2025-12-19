<?php
session_start();
require 'db_connection.php';

// ADMIN CHECK
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch all farmers
$sql = "SELECT * FROM users WHERE role='farmer' ORDER BY created_at DESC";
$farmers = $conn->query($sql);

// Function: Count crops owned by farmer
function cropCount($conn, $id){
    $s = $conn->prepare("SELECT COUNT(*) AS total FROM farmer_crops WHERE user_id=?");
    $s->bind_param("i",$id);
    $s->execute();
    $res = $s->get_result()->fetch_assoc();
    return $res['total'];
}

// Function: Count notifications
function notificationCount($conn, $id){
    $s = $conn->prepare("SELECT COUNT(*) AS total FROM crop_notifications WHERE user_id=?");
    $s->bind_param("i",$id);
    $s->execute();
    $res = $s->get_result()->fetch_assoc();
    return $res['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Farmer Management | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body{
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#00c6ff,#0072ff);
    margin:0;
    color:#fff;
}
.container{
    padding:30px;
}
h1{
    margin-bottom:20px;
}
.table-box{
    background:rgba(255,255,255,0.12);
    padding:20px;
    border-radius:15px;
    backdrop-filter:blur(10px);
}
table{
    width:100%;
    border-collapse:collapse;
}
table th, table td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.3);
}
table th{
    background:rgba(255,255,255,0.15);
}
.action-btn{
    padding:6px 12px;
    border:none;
    border-radius:6px;
    color:white;
    cursor:pointer;
}
.btn-edit{ background:#28a745; }
.btn-delete{ background:#ff4c4c; }
.btn-deactivate{ background:#ff9800; }
.btn-activate{ background:#007bff; }
#search{
    width:100%;
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    border:none;
    outline:none;
}

/* ⭐ BACK BUTTON STYLING (Added only this) */
.back-btn {
    display:inline-block;
    padding:10px 18px;
    background:rgba(255,255,255,0.18);
    backdrop-filter:blur(10px);
    color:#fff;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    margin-bottom:20px;
    transition:0.3s;
}
.back-btn:hover{
    background:rgba(255,255,255,0.3);
}
</style>
</head>

<body>

<div class="container">

    <!-- ⭐ BACK BUTTON -->
    <a href="admin_dashboard.php" class="back-btn">
        ⬅ Back to Dashboard
    </a>

    <h1><i class="fa-solid fa-users"></i> Farmer Management</h1>

    <input type="text" id="search" placeholder="Search farmers by Name, Email, Mobile, District...">

    <div class="table-box">
        <table id="farmersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>District</th>
                    <th>State</th>
                    <th>Registered</th>
                    <th>Crops</th>
                    <th>Notifications</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if($farmers->num_rows > 0): ?>
                    <?php while($f = $farmers->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo htmlspecialchars($f['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($f['email']); ?></td>
                            <td><?php echo htmlspecialchars($f['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($f['district'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($f['state'] ?? '-'); ?></td>
                            <td><?php echo $f['created_at']; ?></td>

                            <td><?php echo cropCount($conn, $f['id']); ?></td>
                            <td><?php echo notificationCount($conn, $f['id']); ?></td>

                            <td>
                                <?php 
                                    $status = ($f['password'] == 'blocked') ? 'Inactive' : 'Active';
                                    echo $status;
                                ?>
                            </td>

                            <td>
                                <a href="edit_farmer.php?id=<?php echo $f['id']; ?>">
                                    <button class="action-btn btn-edit">Edit</button>
                                </a>

                                <a href="toggle_farmer.php?id=<?php echo $f['id']; ?>">
                                    <button class="action-btn <?php echo ($status == 'Active') ? 'btn-deactivate' : 'btn-activate'; ?>">
                                        <?php echo ($status == 'Active') ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </a>

                                <a href="delete_farmer.php?id=<?php echo $f['id']; ?>" onclick="return confirm('Delete this farmer?')">
                                    <button class="action-btn btn-delete">Delete</button>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;">No farmers found.</td></tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>

<script>
document.getElementById("search").addEventListener("keyup", function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#farmersTable tbody tr");

    rows.forEach(r=>{
        let text = r.innerText.toLowerCase();
        r.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
