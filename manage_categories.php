<?php
include_once 'db_connect.php';

$topGroups = ['Women','Men','Boys','Girls'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent = isset($_POST['parent']) ? trim($_POST['parent']) : '';
    $subname = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
    if ($parent !== '' && $subname !== '' && in_array($parent, $topGroups, true)) {
        $stmt = $conn->prepare("SELECT Categories_ID, Status FROM categories WHERE LOWER(Categories_name) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $parent);
        $stmt->execute();
        $result = $stmt->get_result();
        $parentId = null;
        if ($result && $result->num_rows) {
            $row = $result->fetch_assoc();
            $parentId = (int)$row['Categories_ID'];
            if ((int)$row['Status'] !== 1) {
                $conn->query("UPDATE categories SET Status = 1 WHERE Categories_ID = {$parentId}");
            }
        } else {
            $stmtIns = $conn->prepare("INSERT INTO categories (Categories_name, Status) VALUES (?, 1)");
            $stmtIns->bind_param("s", $parent);
            if ($stmtIns->execute()) {
                $parentId = (int)$conn->insert_id;
            }
            $stmtIns->close();
        }
        $stmt->close();

        if ($parentId) {
            $stmt2 = $conn->prepare("SELECT Subcategories_ID, Status FROM subcategories WHERE Categories_ID = ? AND LOWER(Subcategories_name) = LOWER(?) LIMIT 1");
            $stmt2->bind_param("is", $parentId, $subname);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2 && $res2->num_rows) {
                $row2 = $res2->fetch_assoc();
                $sid = (int)$row2['Subcategories_ID'];
                $conn->query("UPDATE subcategories SET Status = 1 WHERE Subcategories_ID = {$sid}");
                $msg = "Updated and activated subcategory under {$parent}";
            } else {
                $stmtIns2 = $conn->prepare("INSERT INTO subcategories (Categories_ID, Subcategories_name, Status) VALUES (?, ?, 1)");
                $stmtIns2->bind_param("is", $parentId, $subname);
                if ($stmtIns2->execute()) {
                    $msg = "Added subcategory '{$subname}' under {$parent}";
                } else {
                    $msg = "Error adding subcategory";
                }
                $stmtIns2->close();
            }
            $stmt2->close();
        } else {
            $msg = "Error ensuring parent category";
        }
    } else {
        $msg = "Please select a parent and enter subcategory name";
    }
}

$groupsData = [];
foreach ($topGroups as $g) {
    $stmt = $conn->prepare("SELECT Categories_ID FROM categories WHERE LOWER(Categories_name) = LOWER(?) AND Status = 1 LIMIT 1");
    $stmt->bind_param("s", $g);
    $stmt->execute();
    $res = $stmt->get_result();
    $cid = null;
    if ($res && $res->num_rows) {
        $cid = (int)$res->fetch_assoc()['Categories_ID'];
    }
    $stmt->close();
    $subs = [];
    if ($cid) {
        $q = $conn->query("SELECT Subcategories_ID, Subcategories_name, Status FROM subcategories WHERE Categories_ID = {$cid} ORDER BY Subcategories_name ASC");
        while ($row = $q->fetch_assoc()) {
            $subs[] = $row;
        }
    }
    $groupsData[$g] = ['id'=>$cid, 'subs'=>$subs];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; background:#f8f6f4; margin:0; padding:30px; }
        .card { background:#fff; border:1px solid #eee; border-radius:8px; padding:20px; max-width:900px; margin:0 auto 20px; }
        h2 { margin:0 0 10px; }
        form { display:flex; gap:12px; align-items:center; }
        select, input[type="text"] { padding:8px 12px; border:1px solid #ddd; border-radius:6px; }
        button { background:#f0c14b; border:1px solid #a88734; padding:8px 16px; border-radius:20px; cursor:pointer; font-weight:600; }
        .msg { margin-top:10px; font-size:14px; color:#333; }
        .grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:16px; }
        .group-card { border:1px solid #eee; border-radius:8px; padding:14px; background:#fff; }
        .group-card h3 { margin:0 0 10px; }
        .tag { display:inline-block; padding:4px 8px; border-radius:12px; background:#eef; font-size:12px; margin:0 6px 6px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Add Subcategory to Group</h2>
        <form method="POST">
            <select name="parent" required>
                <option value="">Select Group</option>
                <?php foreach ($topGroups as $g): ?>
                    <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="subcategory" placeholder="Subcategory name (e.g., Indian wear, Kurti)" required />
            <button type="submit">Add</button>
        </form>
        <?php if ($msg): ?>
            <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Current Subcategories</h2>
        <div class="grid">
            <?php foreach ($groupsData as $g => $data): ?>
                <div class="group-card">
                    <h3><?php echo $g; ?></h3>
                    <?php if (!empty($data['subs'])): ?>
                        <?php foreach ($data['subs'] as $s): ?>
                            <span class="tag"><?php echo htmlspecialchars($s['Subcategories_name']); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span>No subcategories</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
