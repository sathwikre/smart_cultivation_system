<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch all knowledge base items
$kb_result = $conn->query("SELECT * FROM knowledge_base ORDER BY crop_name, FIELD(section,'Growth Stages','Problems & Solutions','Fertilizer Schedule','Watering Schedule'), id");

// Handle Add New Guide
if(isset($_POST['add_guide'])){
    $crop_name = $_POST['crop_name'];
    $section = $_POST['section'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO knowledge_base (crop_name, section, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $crop_name, $section, $title, $description);
    $stmt->execute();
    $stmt->close();

    header("Location: knowledge_base_admin.php");
    exit;
}

// Handle Edit Guide
if(isset($_POST['edit_guide'])){
    $id = $_POST['id'];
    $crop_name = $_POST['crop_name'];
    $section = $_POST['section'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE knowledge_base SET crop_name=?, section=?, title=?, description=?, created_at=NOW() WHERE id=?");
    $stmt->bind_param("ssssi", $crop_name, $section, $title, $description, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: knowledge_base_admin.php");
    exit;
}

// Handle Delete Guide
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM knowledge_base WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: knowledge_base_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Knowledge Base Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Reset & base */
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif}
html,body{height:100%}

/* Gradient background (matches farmer_dashboard) */
body{
    background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;
    animation: gradientBG 20s ease infinite;
    color:#ffffff;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    padding:30px;
}
@keyframes gradientBG{
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/* Floating icons */
.icon-bg{position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.12);animation: floatBg 20s linear infinite;z-index:0}
.icon-bg:nth-child(1){top:8%; left:4%}
.icon-bg:nth-child(2){top:28%; right:6%}
.icon-bg:nth-child(3){bottom:12%; left:8%}
.icon-bg:nth-child(4){top:50%; left:50%}
.icon-bg:nth-child(5){bottom:10%; right:10%}
@keyframes floatBg{
    0%{transform: translateY(0) rotate(0deg)}
    50%{transform: translateY(-30px) rotate(180deg)}
    100%{transform: translateY(0) rotate(360deg)}
}

/* Page container */
.container {
    max-width:1200px;
    margin: 0 auto;
    position:relative;
    z-index:1; /* above floating icons */
}

/* Back button (top-left) */
.back-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:10px 18px;
    border-radius:12px;
    background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.06));
    color:#fff;
    text-decoration:none;
    border:1px solid rgba(255,255,255,0.12);
    box-shadow:0 6px 18px rgba(0,0,0,0.25);
    transition:transform .18s ease, box-shadow .18s ease;
    margin-bottom:20px;
}
.back-btn:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(0,0,0,0.35)}

/* Header */
.header {
    text-align:center;
    margin-bottom:22px;
}
.header h1{
    font-size:1.6rem;
    font-weight:700;
    color:#fff;
    text-shadow:0 6px 18px rgba(0,0,0,0.25);
}
.header p{
    color:rgba(255,255,255,0.9);
    margin-top:8px;
    opacity:0.95;
}

/* Card (glass) */
.card {
    background: rgba(255,255,255,0.06);
    border-radius:18px;
    padding:22px;
    backdrop-filter: blur(10px) saturate(120%);
    -webkit-backdrop-filter: blur(10px) saturate(120%);
    border:1px solid rgba(255,255,255,0.08);
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
    margin-bottom:22px;
    color:#fff;
}

/* Form controls (styled to match) */
.form-row{display:flex;gap:16px;flex-wrap:wrap}
.form-group{flex:1;min-width:220px}
label{display:block;margin-bottom:8px;font-weight:600;opacity:0.95}
input[type="text"], select, textarea{
    width:100%;
    padding:10px 12px;
    border-radius:12px;
    border:1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.03);
    color:#fff;
    outline:none;
    transition: box-shadow .15s ease, transform .12s ease;
    resize:vertical;
}
input[type="text"]::placeholder, textarea::placeholder{color:rgba(255,255,255,0.6)}
input[type="text"]:focus, select:focus, textarea:focus{
    box-shadow: 0 6px 18px rgba(0,0,0,0.5);
    transform: translateY(-2px);
}

/* Buttons */
.btn {
    display:inline-block;padding:10px 18px;border-radius:12px;border:none;cursor:pointer;font-weight:700;
    transition:transform .14s ease, box-shadow .14s ease;
}
.btn-success {
    background: linear-gradient(135deg,#28a745,#00ff7f);
    color:#041b0b;
    box-shadow: 0 8px 22px rgba(0,255,127,0.12);
}
.btn-success:hover{transform:translateY(-3px)}
.btn-danger {
    background: linear-gradient(135deg,#ff4c4c,#ff7f50);
    color:#fff;
    box-shadow: 0 8px 22px rgba(255,76,76,0.12);
}
.btn-danger:hover{transform:translateY(-3px)}
.btn-warning {
    background: linear-gradient(135deg,#ffb100,#ff6b00);
    color:#131100;
    box-shadow:0 8px 22px rgba(255,177,0,0.12);
}
.btn-warning:hover{transform:translateY(-3px)}

/* Table - glassy */
.table-wrap{
    overflow:auto;
    border-radius:12px;
    border:1px solid rgba(255,255,255,0.06);
}
table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}
thead th{
    text-align:left;
    padding:12px 14px;
    background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border-bottom:1px solid rgba(255,255,255,0.06);
    font-weight:700;
    color:#fff;
}
tbody td{
    padding:12px 14px;
    border-bottom:1px solid rgba(255,255,255,0.04);
    color:rgba(255,255,255,0.95);
}
tbody tr:hover td{
    background: rgba(255,255,255,0.02);
}

/* Small helpers */
.text-center{ text-align:center }
.mb-2{ margin-bottom:12px }
.mt-2{ margin-top:12px }

/* Modal (centered glass) */
#editModal {
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    width:92%;
    max-width:680px;
    z-index:2000;
    border-radius:16px;
    padding:22px;
    background: rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    box-shadow:0 20px 60px rgba(0,0,0,0.6);
}
#overlay {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.45);
    z-index:1990;
}

/* Responsive tweaks */
@media (max-width:900px){
    .form-row{flex-direction:column}
    table{min-width:700px}
}
</style>
</head>
<body>

<!-- floating icons -->
<i class="fas fa-leaf icon-bg"></i>
<i class="fas fa-seedling icon-bg"></i>
<i class="fas fa-tractor icon-bg"></i>
<i class="fas fa-water icon-bg"></i>
<i class="fas fa-sun icon-bg"></i>

<div class="container">

    <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <div class="header">
        <h1>📘 Knowledge Base Management (Admin)</h1>
        <p>Manage crop guides — add, edit or remove knowledge base items</p>
    </div>

    <!-- ADD NEW GUIDE -->
    <div class="card" role="region" aria-label="Add New Guide">
        <h3 style="margin-bottom:14px">Add New Guide</h3>

        <form method="POST" autocomplete="off">
            <div class="form-row">
                <div class="form-group">
                    <label for="crop_name">Crop Name</label>
                    <input id="crop_name" name="crop_name" type="text" required placeholder="e.g., Tomato">
                </div>

                <div class="form-group">
                    <label for="section">Section</label>
                    <select id="section" name="section" required>
                        <option value="">Select Section</option>
                        <option value="Growth Stages">Growth Stages</option>
                        <option value="Problems & Solutions">Problems & Solutions</option>
                        <option value="Fertilizer Schedule">Fertilizer Schedule</option>
                        <option value="Watering Schedule">Watering Schedule</option>
                    </select>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="form-group" style="flex:1 1 100%">
                    <label for="title">Title / Topic</label>
                    <input id="title" name="title" type="text" required placeholder="Short title for guide">
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="form-group" style="flex:1 1 100%">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required placeholder="Write guide content..."></textarea>
                </div>
            </div>

            <div style="margin-top:14px">
                <button type="submit" name="add_guide" class="btn btn-success">Add Guide</button>
            </div>
        </form>
    </div>

    <!-- KNOWLEDGE BASE TABLE -->
    <div class="card" role="region" aria-label="All Guides">
        <h3 style="margin-bottom:14px">All Guides</h3>

        <div class="table-wrap">
            <table aria-describedby="All knowledge base guides">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Crop Name</th>
                        <th>Section</th>
                        <th>Title / Topic</th>
                        <th>Description</th>
                        <th>Created / Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($kb_result->num_rows > 0): ?>
                    <?php while($row = $kb_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['crop_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['section']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                onclick="openEditModal(
                                    <?php echo $row['id']; ?>,
                                    <?php echo json_encode($row['crop_name']); ?>,
                                    <?php echo json_encode($row['section']); ?>,
                                    <?php echo json_encode($row['title']); ?>,
                                    <?php echo json_encode($row['description']); ?>
                                )">
                                Edit
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this guide?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No guides found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- EDIT MODAL + OVERLAY -->
<div id="overlay" aria-hidden="true" onclick="closeEditModal()"></div>

<div id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <h3 id="editModalTitle" style="margin-bottom:12px">Edit Guide</h3>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="id" id="edit_id">

        <div class="form-row">
            <div class="form-group">
                <label for="edit_crop_name">Crop Name</label>
                <input id="edit_crop_name" name="crop_name" type="text" required>
            </div>

            <div class="form-group">
                <label for="edit_section">Section</label>
                <select id="edit_section" name="section" required>
                    <option value="">Select Section</option>
                    <option value="Growth Stages">Growth Stages</option>
                    <option value="Problems & Solutions">Problems & Solutions</option>
                    <option value="Fertilizer Schedule">Fertilizer Schedule</option>
                    <option value="Watering Schedule">Watering Schedule</option>
                </select>
            </div>
        </div>

        <div class="form-row mt-2">
            <div class="form-group" style="flex:1 1 100%">
                <label for="edit_title">Title / Topic</label>
                <input id="edit_title" name="title" type="text" required>
            </div>
        </div>

        <div class="form-row mt-2">
            <div class="form-group" style="flex:1 1 100%">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="6" required></textarea>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:12px; flex-wrap:wrap">
            <button type="submit" name="edit_guide" class="btn btn-success">Update Guide</button>
            <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
        </div>
    </form>
</div>

<script>
function openEditModal(id, crop, section, title, desc){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_crop_name').value = crop;
    document.getElementById('edit_section').value = section;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = desc;

    document.getElementById('overlay').style.display = 'block';
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('overlay').setAttribute('aria-hidden','false');
}

function closeEditModal(){
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('overlay').setAttribute('aria-hidden','true');
}
</script>

</body>
</html>
