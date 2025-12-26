<?php
session_start();
require 'db_connection.php';

// Admin session check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// translations (if any)
$lang = $_SESSION['lang'] ?? 'en';
$translations = file_exists("languages/$lang.php") ? include "languages/$lang.php" : [];

// Growth stages (from your farmer update_stage.php)
$stages = ['Seed','Germination','Vegetative','Flowering','Harvest'];

// Fetch crops with farmer info
$sql = "SELECT fc.id, fc.user_id, u.fullname AS farmer_name, fc.crop_name, fc.variety, fc.field, fc.planting_date, fc.growth_stage, fc.yield, fc.notes, fc.last_updated
        FROM farmer_crops fc
        JOIN users u ON fc.user_id = u.id
        ORDER BY fc.last_updated DESC";
$result = $conn->query($sql);

// Fetch farmer list for Add Crop dropdown
$farmer_stmt = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'farmer' ORDER BY fullname ASC");
$farmer_stmt->execute();
$farmers = $farmer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$farmer_stmt->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?php echo $translations['crop_management'] ?? 'Crop Management'; ?> - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Reset & Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-green: #2d8659;
    --primary-green-dark: #1f5d3f;
    --primary-green-light: #3da372;
    --secondary-green: #4caf50;
    --accent-orange: #ff9800;
    --accent-yellow: #ffc107;
    --text-dark: #2c3e50;
    --text-light: #5a6c7d;
    --bg-light: #f8f9fa;
    --bg-white: #ffffff;
    --border-color: #e0e0e0;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: 'Inter', 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
    min-height: 100vh;
    color: var(--text-dark);
    line-height: 1.6;
    overflow-x: hidden;
}

/* Background Pattern */
body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(45, 134, 89, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(76, 175, 80, 0.03) 0%, transparent 50%);
    z-index: 0;
    pointer-events: none;
}

/* Dashboard Layout */
.dashboard {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: white;
    box-shadow: var(--shadow-md);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 2px solid var(--bg-light);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    animation: slideInLeft 0.4s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar h2 {
    text-align: center;
    font-size: 24px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 3px solid var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.sidebar h2 i {
    color: var(--primary-green);
    font-size: 28px;
}

.sidebar a {
    color: var(--text-dark);
    text-decoration: none;
    padding: 14px 18px;
    border-radius: 12px;
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: var(--transition);
    font-weight: 500;
    font-size: 15px;
    position: relative;
}

.sidebar a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--primary-green);
    border-radius: 0 4px 4px 0;
    transform: scaleY(0);
    transition: var(--transition);
}

.sidebar a:hover {
    background: var(--bg-light);
    color: var(--primary-green);
    padding-left: 22px;
}

.sidebar a:hover::before {
    transform: scaleY(1);
}

.sidebar a i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

/* Language Switch */
.lang-switch {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid var(--bg-light);
}

.lang-switch a {
    padding: 8px 16px;
    border-radius: 8px;
    background: var(--bg-light);
    margin: 0 4px;
    color: var(--text-dark);
    font-weight: 600;
    font-size: 13px;
    display: inline-block;
    transition: var(--transition);
}

.lang-switch a:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
}

/* Logout Button */
.logout-btn {
    width: 100%;
    margin-top: 20px;
    padding: 14px 25px;
    background: linear-gradient(135deg, #f56565, #e53e3e);
    border: none;
    border-radius: 12px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: var(--transition);
    font-size: 15px;
    box-shadow: var(--shadow-sm);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Main Content */
.main {
    flex: 1;
    padding: 40px;
    background: transparent;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.header h1 i {
    color: var(--primary-green);
    font-size: 36px;
}

/* Back Button */
a[href="admin_dashboard.php"] {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 24px;
    background: white;
    color: var(--primary-green-dark);
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

a[href="admin_dashboard.php"]:hover {
    background: var(--primary-green);
    color: white;
    transform: translateX(-4px);
    box-shadow: var(--shadow-md);
}

/* Table Wrapper */
.table-wrap {
    margin-top: 20px;
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    overflow-x: auto;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

table {
    width: 100%;
    border-collapse: collapse;
    color: var(--text-dark);
}

th {
    font-weight: 700;
    font-size: 14px;
    color: var(--primary-green-dark);
    padding: 16px 12px;
    text-align: left;
    border-bottom: 3px solid var(--bg-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    background: white;
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--bg-light);
    font-size: 14px;
    transition: var(--transition);
}

tbody tr {
    transition: var(--transition);
    animation: slideInRight 0.4s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.1s; }
tbody tr:nth-child(2) { animation-delay: 0.2s; }
tbody tr:nth-child(3) { animation-delay: 0.3s; }
tbody tr:nth-child(4) { animation-delay: 0.4s; }
tbody tr:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

tbody tr:hover {
    background: var(--bg-light);
    transform: translateX(4px);
}

.small {
    font-size: 13px;
    color: var(--text-light);
}

.date-col {
    white-space: nowrap;
}

/* Action Buttons */
.action-btn {
    padding: 10px 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    color: white;
    margin-right: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.action-btn:hover::before {
    width: 300px;
    height: 300px;
}

.action-btn span {
    position: relative;
    z-index: 1;
}

.btn-update {
    background: linear-gradient(135deg, #ff9800, #f57c00);
}

.btn-update:hover {
    background: linear-gradient(135deg, #f57c00, #e65100);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-delete {
    background: linear-gradient(135deg, #f56565, #e53e3e);
}

.btn-delete:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-add {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    font-weight: 700;
    padding: 14px 28px;
    font-size: 15px;
    box-shadow: var(--shadow-md);
}

.btn-add:hover {
    background: linear-gradient(135deg, var(--primary-green-light), var(--primary-green));
    transform: translateY(-3px) scale(1.05);
    box-shadow: var(--shadow-lg);
}

/* Modal */
.modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    animation: fadeIn 0.3s ease-out;
}

.modal.show {
    display: flex;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal .panel {
    background: white;
    color: var(--text-dark);
    padding: 32px;
    border-radius: 20px;
    width: 520px;
    max-width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    animation: scaleIn 0.3s ease-out;
    position: relative;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal .panel h3 {
    margin: 0 0 24px;
    font-size: 24px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal .panel h3 i {
    color: var(--primary-green);
}

.modal .panel button[style*="float:right"] {
    position: absolute;
    top: 20px;
    right: 20px;
    border: none;
    background: transparent;
    font-size: 28px;
    cursor: pointer;
    color: var(--text-light);
    transition: var(--transition);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.modal .panel button[style*="float:right"]:hover {
    background: var(--bg-light);
    color: var(--text-dark);
    transform: rotate(90deg);
}

.input, select, textarea {
    width: 100%;
    padding: 12px 16px;
    margin: 8px 0;
    border-radius: 10px;
    border: 2px solid var(--border-color);
    font-size: 15px;
    font-family: inherit;
    transition: var(--transition);
    background: white;
    color: var(--text-dark);
}

.input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

label {
    display: block;
    margin-top: 16px;
    margin-bottom: 6px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
}

.form-row {
    display: flex;
    gap: 16px;
}

.form-row .col {
    flex: 1;
}

/* Messages */
.success {
    background: #e6ffef;
    color: #0b6623;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    border-left: 4px solid var(--secondary-green);
    animation: slideInLeft 0.3s ease-out;
}

.err {
    background: #ffe6e6;
    color: #7a0000;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    border-left: 4px solid #e53e3e;
    animation: slideInLeft 0.3s ease-out;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 2px solid var(--bg-light);
    }

    .main {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
        padding: 24px;
    }

    .header h1 {
        font-size: 24px;
    }

    .table-wrap {
        padding: 16px;
        overflow-x: auto;
    }

    th, td {
        font-size: 13px;
        padding: 12px 8px;
    }

    .action-btn {
        padding: 8px 12px;
        font-size: 12px;
        margin-right: 4px;
    }

    .modal .panel {
        padding: 24px;
        width: 95%;
    }
}

@media (max-width: 480px) {
    .main {
        padding: 16px;
    }

    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="dashboard">
    <div class="sidebar">
        <h2>
            <i class="fas fa-shield-alt"></i>
            Admin Dashboard
        </h2>
        <nav>
            <a href="admin_dashboard.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="crop_management.php">
                <i class="fas fa-seedling"></i>
                <span>Crop Management</span>
            </a>
            <a href="knowledge_base_admin.php">
                <i class="fas fa-book"></i>
                <span>Knowledge Base</span>
            </a>
            <a href="notifications_admin.php">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="farmers_admin.php">
                <i class="fas fa-users"></i>
                <span>Farmer Management</span>
            </a>
        </nav>

        <div>
            <div class="lang-switch">
                <a href="?lang=en">EN</a>
                <a href="?lang=te">TE</a>
            </div>

            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <?php echo $translations['logout'] ?? 'Logout'; ?>
                </button>
            </form>
        </div>
    </div>

    <div class="main">
        <a href="admin_dashboard.php">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="header">
            <h1>
                <i class="fas fa-seedling"></i>
                <?php echo $translations['crop_management'] ?? 'Crop Management'; ?>
            </h1>
            <div>
                <button id="openAdd" class="action-btn btn-add">
                    <i class="fas fa-plus"></i> Add Crop
                </button>
            </div>
        </div>


        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Farmer</th>
                        <th>Crop</th>
                        <th>Variety</th>
                        <th>Field / Plot</th>
                        <th>Planting Date</th>
                        <th>Growth Stage</th>
                        <th>Yield</th>
                        <th>Last Updated</th>
                        <th class="small">Actions</th>
                    </tr>
                </thead>
                <tbody id="cropBody">
                <?php
                if($result && $result->num_rows>0){
                    $i = 1;
                    while($row = $result->fetch_assoc()){
                        $pid = (int)$row['id'];
                        $planting = $row['planting_date'] ? date("d M Y", strtotime($row['planting_date'])) : '-';
                        $last = $row['last_updated'] ? date("d M Y H:i", strtotime($row['last_updated'])) : '-';
                        $stage = htmlspecialchars($row['growth_stage']);
                        echo "<tr data-id='{$pid}'>
                                <td>{$i}</td>
                                <td>".htmlspecialchars($row['farmer_name'])."</td>
                                <td>".htmlspecialchars($row['crop_name'])."</td>
                                <td>".htmlspecialchars($row['variety'])."</td>
                                <td>".htmlspecialchars($row['field'])."</td>
                                <td class='date-col'>{$planting}</td>
                                <td><span class='small'>".htmlspecialchars($stage)."</span></td>
                                <td>".htmlspecialchars($row['yield'] ?? '-')."</td>
                                <td class='date-col'>{$last}</td>
                                <td>
                                    <button class='action-btn btn-update open-update' data-id='{$pid}'><span>Update</span></button>
                                    <button class='action-btn btn-delete delete-crop' data-id='{$pid}'><span>Delete</span></button>
                                </td>
                              </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='10' class='small'>No crops found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal" id="updateModal">
    <div class="panel">
        <button id="closeUpdate" style="float:right;border:0;background:transparent;font-size:20px;cursor:pointer">
            <i class="fas fa-times"></i>
        </button>
        <h3>
            <i class="fas fa-edit"></i>
            Update Growth Stage
        </h3>
        <div id="updateMsg"></div>
        <form id="updateForm">
            <input type="hidden" name="id" id="upd_id">
            <p style="background: var(--bg-light); padding: 12px; border-radius: 10px; margin-bottom: 16px;">
                <strong id="upd_cropinfo"></strong>
            </p>
            <label>New Growth Stage</label>
            <select name="growth_stage" id="upd_stage" class="input" required>
                <option value="">-- Select Stage --</option>
                <?php foreach($stages as $s) echo "<option value=\"{$s}\">{$s}</option>"; ?>
            </select>
            <div style="margin-top:20px;text-align:right">
                <button type="submit" class="action-btn btn-update">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="panel">
        <button id="closeAdd" style="float:right;border:0;background:transparent;font-size:20px;cursor:pointer">
            <i class="fas fa-times"></i>
        </button>
        <h3>
            <i class="fas fa-plus-circle"></i>
            Add Crop (Admin)
        </h3>
        <div id="addMsg"></div>
        <form id="addForm">
            <label>Farmer</label>
            <select name="user_id" class="input" required>
                <option value="">-- Select Farmer --</option>
                <?php foreach($farmers as $f) echo "<option value=\"{$f['id']}\">".htmlspecialchars($f['fullname'])."</option>"; ?>
            </select>
            <label>Crop Name</label>
            <input type="text" name="crop_name" class="input" required />
            <label>Variety</label>
            <input type="text" name="variety" class="input" />
            <label>Field/Plot</label>
            <input type="text" name="field" class="input" />
            <div class="form-row">
                <div class="col">
                    <label>Planting Date</label>
                    <input type="date" name="planting_date" class="input" />
                </div>
                <div class="col">
                    <label>Growth Stage</label>
                    <select name="growth_stage" class="input">
                        <?php foreach($stages as $s) echo "<option value=\"{$s}\">{$s}</option>"; ?>
                    </select>
                </div>
            </div>
            <label>Yield</label>
            <input type="text" name="yield" class="input" />
            <label>Notes</label>
            <textarea name="notes" rows="3" class="input"></textarea>
            <div style="margin-top:20px;text-align:right">
                <button type="submit" class="action-btn btn-add">
                    <i class="fas fa-plus"></i> Add Crop
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Helpers
function ajaxPost(url, body) {
    return fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(body) })
           .then(r=>r.json());
}

// Open/close modals
document.getElementById('openAdd').addEventListener('click', ()=> {
    document.getElementById('addModal').classList.add('show');
});
document.getElementById('closeAdd').addEventListener('click', ()=> {
    document.getElementById('addModal').classList.remove('show');
});
document.getElementById('closeUpdate').addEventListener('click', ()=> {
    document.getElementById('updateModal').classList.remove('show');
});

// Close modals on background click
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('show');
    }
});
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('show');
    }
});

// Open update modal and populate
document.querySelectorAll('.open-update').forEach(btn=>{
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        // find row and extract info
        const row = document.querySelector('tr[data-id="'+id+'"]');
        const crop = row.children[2].textContent.trim();
        const farmer = row.children[1].textContent.trim();
        const stage = row.children[6].textContent.trim();
        document.getElementById('upd_id').value = id;
        document.getElementById('upd_cropinfo').textContent = `${crop} — ${farmer} (current: ${stage})`;
        document.getElementById('upd_stage').value = '';
        document.getElementById('updateMsg').innerHTML = '';
        document.getElementById('updateModal').classList.add('show');
    });
});

// Update stage (AJAX)
document.getElementById('updateForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const id = document.getElementById('upd_id').value;
    const growth_stage = document.getElementById('upd_stage').value;
    if(!growth_stage) { alert('Select a stage'); return; }
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        const res = await ajaxPost('admin_update_stage.php', { id, growth_stage });
        if(res.status === 'success'){
            document.getElementById('updateMsg').innerHTML = '<div class="success">Stage updated & notification sent.</div>';
            // update table row stage and last_updated (if provided)
            const row = document.querySelector('tr[data-id="'+id+'"]');
            if(row){
                row.children[6].textContent = growth_stage;
                if(res.last_updated) row.children[8].textContent = res.last_updated;
            }
            setTimeout(()=> { document.getElementById('updateModal').classList.remove('show'); }, 900);
        } else {
            document.getElementById('updateMsg').innerHTML = '<div class="err">'+(res.message||'Update failed')+'</div>';
        }
    } catch(e){
        document.getElementById('updateMsg').innerHTML = '<div class="err">Network error</div>';
    } finally { btn.disabled = false; btn.textContent = 'Save'; }
});

// Delete crop
document.querySelectorAll('.delete-crop').forEach(btn=>{
    btn.addEventListener('click', async function(){
        if(!confirm('Delete this crop? This cannot be undone.')) return;
        const id = this.dataset.id;
        this.disabled = true;
        try {
            const res = await ajaxPost('admin_delete_crop.php', { id });
            if(res.status === 'success'){
                const row = document.querySelector('tr[data-id="'+id+'"]');
                if(row) row.remove();
            } else {
                alert(res.message || 'Delete failed');
            }
        } catch(e){
            alert('Network error');
        } finally { this.disabled = false; }
    });
});

// Add crop
document.getElementById('addForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = new FormData(this);
    const body = {};
    for(const [k,v] of form.entries()) body[k]=v;
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Adding...';
    document.getElementById('addMsg').innerHTML = '';
    try {
        const res = await ajaxPost('admin_add_crop.php', body);
        if(res.status === 'success'){
            document.getElementById('addMsg').innerHTML = '<div class="success">Crop added successfully.</div>';
            setTimeout(()=> location.reload(), 800);
        } else {
            document.getElementById('addMsg').innerHTML = '<div class="err">'+(res.message||'Failed to add')+'</div>';
        }
    } catch(e){
        document.getElementById('addMsg').innerHTML = '<div class="err">Network error</div>';
    } finally { btn.disabled = false; btn.textContent = 'Add Crop'; }
});
</script>

</body>
</html>
