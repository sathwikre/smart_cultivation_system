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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Use the same visual theme as admin_dashboard.php */
*{box-sizing:border-box;font-family:'Poppins',sans-serif}
body{margin:0;background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);background-size:400% 400%;animation:gradientBG 20s ease infinite;color:#fff}
@keyframes gradientBG{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.dashboard{display:flex;min-height:100vh}
.sidebar{width:250px;background: rgba(0,0,0,0.5);backdrop-filter: blur(10px);padding:20px;display:flex;flex-direction:column;justify-content:space-between;border-right:1px solid rgba(255,255,255,0.2)}
.sidebar h2{text-align:center;font-size:1.8rem;margin-bottom:30px}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:12px;margin:6px 0;display:block;transition:.3s}
.sidebar a:hover{background: rgba(255,255,255,0.2)}
.lang-switch{text-align:center;margin-top:20px}
.lang-switch a{padding:6px 12px;border-radius:15px;background:rgba(255,255,255,0.2);margin:0 5px}
.logout-btn{margin-top:20px;padding:10px 25px;background:#ff4c4c;border:none;border-radius:25px;font-weight:600;color:#fff;cursor:pointer}
.main{flex:1;padding:30px;overflow:auto}
.header{display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:1.8rem;margin:0}
.table-wrap{margin-top:20px;background:rgba(255,255,255,0.04);padding:15px;border-radius:12px}
table{width:100%;border-collapse:collapse;color:#fff}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:left}
th{font-weight:600;font-size:0.95rem}
.action-btn{padding:8px 10px;border-radius:10px;border:none;cursor:pointer;color:#fff;margin-right:6px}
.btn-update{background:linear-gradient(135deg,#ffb100,#ff6b00)}
.btn-delete{background:linear-gradient(135deg,#ff4c4c,#ff7f50)}
.btn-add{background:linear-gradient(135deg,#28a745,#00ff7f);color:#000;font-weight:700}
.inline-select{padding:6px;border-radius:8px;border:0;font-weight:600}
.small{font-size:0.85rem;opacity:0.9}
.date-col{white-space:nowrap}

/* Modal */
.modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);z-index:1000}
.modal .panel{background:#fff;color:#000;padding:20px;border-radius:12px;width:520px;max-width:95%}
.modal .panel h3{margin:0 0 10px}
.input,select,textarea{width:100%;padding:10px;margin:6px 0;border-radius:8px;border:1px solid #ddd;font-size:1rem}
.form-row{display:flex;gap:10px}
.form-row .col{flex:1}

/* small messages */
.success{background:#e6ffef;color:#0b6623;padding:8px;border-radius:8px;margin-bottom:10px}
.err{background:#ffe6e6;color:#7a0000;padding:8px;border-radius:8px;margin-bottom:10px}

/* responsive */
@media (max-width:800px){th,td{font-size:0.85rem}}
</style>
</head>
<body>

<!-- Sidebar (reuse links from admin_dashboard.php) -->
<div class="dashboard">
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <a href="admin_dashboard.php"><i class="fas fa-house"></i> Home</a>
        <a href="crop_management.php"><i class="fas fa-seedling"></i> Crop Management</a>
        <a href="knowledge_base_admin.php"><i class="fas fa-book"></i> Knowledge Base</a>
        <a href="notifications_admin.php"><i class="fas fa-bell"></i> Notifications</a>
        <a href="farmers_admin.php"><i class="fas fa-user"></i> Farmer Management</a>

        <div class="lang-switch">
            <a href="?lang=en">EN</a>
            <a href="?lang=te">TE</a>
        </div>

        <form action="logout.php" method="POST">
            <button class="logout-btn"><?php echo $translations['logout'] ?? 'Logout'; ?></button>
        </form>
    </div>

    <div class="main">
        <div class="header">
            <h1><?php echo $translations['crop_management'] ?? 'Crop Management'; ?></h1>
            <div>
                <button id="openAdd" class="action-btn btn-add">➕ Add Crop</button>
            </div>
        </div>
<a href="admin_dashboard.php" 
   style="display:inline-block;margin-top:15px;
          padding:10px 18px;background:#fff;color:#000;
          border-radius:10px;font-weight:600;
          text-decoration:none;">
    ⬅ Back to Dashboard
</a>


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
                                    <button class='action-btn btn-update open-update' data-id='{$pid}'>Update</button>
                                    <button class='action-btn btn-delete delete-crop' data-id='{$pid}'>Delete</button>
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
        <button id="closeUpdate" style="float:right;border:0;background:transparent;font-size:20px;cursor:pointer">&times;</button>
        <h3>Update Growth Stage</h3>
        <div id="updateMsg"></div>
        <form id="updateForm">
            <input type="hidden" name="id" id="upd_id">
            <p><strong id="upd_cropinfo"></strong></p>
            <label>New Growth Stage</label>
            <select name="growth_stage" id="upd_stage" class="input" required>
                <option value="">-- Select Stage --</option>
                <?php foreach($stages as $s) echo "<option value=\"{$s}\">{$s}</option>"; ?>
            </select>
            <div style="margin-top:10px;text-align:right">
                <button type="submit" class="action-btn btn-update">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="panel">
        <button id="closeAdd" style="float:right;border:0;background:transparent;font-size:20px;cursor:pointer">&times;</button>
        <h3>Add Crop (Admin)</h3>
        <div id="addMsg"></div>
        <form id="addForm">
            <label>Farmer</label>
            <select name="user_id" required>
                <option value="">-- Select Farmer --</option>
                <?php foreach($farmers as $f) echo "<option value=\"{$f['id']}\">".htmlspecialchars($f['fullname'])."</option>"; ?>
            </select>
            <label>Crop Name</label>
            <input type="text" name="crop_name" required />
            <label>Variety</label>
            <input type="text" name="variety" />
            <label>Field/Plot</label>
            <input type="text" name="field" />
            <div class="form-row">
                <div class="col">
                    <label>Planting Date</label>
                    <input type="date" name="planting_date" />
                </div>
                <div class="col">
                    <label>Growth Stage</label>
                    <select name="growth_stage">
                        <?php foreach($stages as $s) echo "<option value=\"{$s}\">{$s}</option>"; ?>
                    </select>
                </div>
            </div>
            <label>Yield</label>
            <input type="text" name="yield" />
            <label>Notes</label>
            <textarea name="notes" rows="3"></textarea>
            <div style="margin-top:10px;text-align:right">
                <button type="submit" class="action-btn btn-add">Add Crop</button>
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
document.getElementById('openAdd').addEventListener('click', ()=> document.getElementById('addModal').style.display='flex');
document.getElementById('closeAdd').addEventListener('click', ()=> document.getElementById('addModal').style.display='none');
document.getElementById('closeUpdate').addEventListener('click', ()=> document.getElementById('updateModal').style.display='none');

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
        document.getElementById('updateModal').style.display = 'flex';
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
            setTimeout(()=> { document.getElementById('updateModal').style.display='none'; }, 900);
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
