<?php
include "auth.php";

// 載入公告
$announcementsFile = "announcements.json";
$announcements = file_exists($announcementsFile) ? json_decode(file_get_contents($announcementsFile), true) : [];

// 新增公告 (管理員限定)
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $new = [
        "date" => date("Y-m-d"), // ✅ 完整時間 (年月日 + 時:分:秒)
        "title" => htmlspecialchars($_POST['title']),
        "category" => $_POST['category'],
        "content" => htmlspecialchars($_POST['content'])
    ];
    $announcements[] = $new;
    file_put_contents($announcementsFile, json_encode($announcements, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

// 刪除公告 (管理員限定)
if (is_admin() && isset($_POST['delete_announcement'])) {
    $index = $_POST['delete_announcement'];
    if (isset($announcements[$index])) {
        unset($announcements[$index]);
        $announcements = array_values($announcements);
        file_put_contents($announcementsFile, json_encode($announcements, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>首頁</title>
<style>
body { font-family:"Microsoft JhengHei",sans-serif; background:#f9f6f2; margin:0; }
.navbar { background:#d9c2a9; padding:10px 20px; display:flex; justify-content:space-between; align-items:center; }
.logo { font-weight:bold; font-size:20px; color:#4e3b31; text-decoration:none; }
.menu { display:flex; }
.menu a { color:#4e3b31; margin:0 10px; text-decoration:none; font-weight:500; }
.menu a:hover { text-decoration:underline; }
.container { padding:20px; }
.news { background:white; border:1px solid #ddd; padding:15px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.news-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; gap:10px; }
.news-header h2 { margin:0; color:#4e3b31; }
.search-box { display:flex; align-items:center; gap:8px; }
.search-box input { width:180px; padding:6px 8px; font-size:14px; border:1px solid #ccc; border-radius:5px; }
.add-btn { padding:6px 12px; background:#a67c52; color:white; border:none; border-radius:5px; cursor:pointer; font-size:14px; }
.add-btn:hover { background:#8a6b42; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { border-bottom:1px solid #ddd; padding:10px; text-align:left; }
.table th { background:#f3e9dd; color:#4e3b31; }
.table tr { cursor:pointer; }
.table tr:hover { background:#f9f6f2; }
.tag { padding:4px 8px; border-radius:5px; font-size:13px; color:white; }
.tag.system { background:#a67c52; }
.tag.upgrade { background:#8a9a5b; }
.tag.school { background:#4b7b8f; }
.tag.event { background:#c94f4f; }
.tag.normal { background:#6c757d; }

/* Modal 彈窗 */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal-content { background:white; padding:20px; border-radius:10px; max-width:500px; width:90%; box-shadow:0 2px 10px rgba(0,0,0,0.2); }
.close { float:right; font-size:20px; font-weight:bold; cursor:pointer; }

/* 表單排版 */
form label { display:block; margin-top:10px; font-weight:500; }
form input[type=text], form select, form textarea {
  width:100%; padding:8px; border:1px solid #ccc; border-radius:5px; margin-top:5px;
}
form button { margin-top:15px; padding:8px 14px; background:#a67c52; color:white; border:none; border-radius:5px; cursor:pointer; }
form button:hover { background:#8a6b42; }

/* 紅色刪除按鈕 */
.delete-btn { background:#c94f4f; color:white; padding:8px 14px; border:none; border-radius:5px; cursor:pointer; margin-top:15px; }
.delete-btn:hover { background:#a63c3c; }
</style>
</head>
<body>
<div class="navbar">
  <a href="index.php" class="logo">Beauty</a>
  <div class="menu">
    <a href="index.php">首頁</a>
    <?php if (is_logged_in()): ?>
      <a href="announcements.php">會員公告</a>
      <?php if (is_admin()): ?><a href="manage.php">會員管理</a><?php endif; ?>
      <a href="profile.php">個人資料</a>
      <a href="logout.php">登出</a>
    <?php else: ?>
      <a href="login.php">登入</a>
      <a href="register.php">註冊</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div class="news">
    <div class="news-header">
      <h2>最新公告</h2>
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 搜尋公告...">
        <?php if (is_admin()): ?>
        <button class="add-btn" onclick="document.getElementById('addModal').style.display='flex'">➕ 新增公告</button>
        <?php endif; ?>
      </div>
    </div>

    <table class="table" id="announcementTable">
      <thead>
        <tr>
          <th>日期</th>
          <th>標題</th>
          <th>類別</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_reverse($announcements, true) as $i => $a): ?>
        <tr data-index="<?=$i?>" data-content="<?=htmlspecialchars($a['content'])?>">
          <td><?=htmlspecialchars($a['date'])?></td> <!-- ✅ 顯示完整年月日 + 時:分:秒 -->
          <td><?=htmlspecialchars($a['title'])?></td>
          <td>
            <?php
              $cat = $a['category'];
              $class = "normal";
              if ($cat=="系統公告") $class="system";
              elseif ($cat=="學務公告") $class="school";
              elseif ($cat=="系統升級") $class="upgrade";
              elseif ($cat=="活動公告") $class="event";
              echo "<span class='tag $class'>".htmlspecialchars($cat)."</span>";
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: 公告詳情 -->
<div class="modal" id="announcementModal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('announcementModal')">&times;</span>
    <h3 id="modalTitle"></h3>
    <p id="modalDate"></p>
    <p id="modalCategory"></p>
    <hr>
    <p id="modalContent"></p>

    <?php if (is_admin()): ?>
    <form method="post" id="deleteForm">
      <input type="hidden" name="delete_announcement" id="deleteIndex">
      <button type="submit" class="delete-btn" onclick="return confirm('確定要刪除這則公告嗎？')">🗑 刪除公告</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: 新增公告 -->
<?php if (is_admin()): ?>
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('addModal')">&times;</span>
    <h3>➕ 新增公告</h3>
    <form method="post">
      <label>標題</label>
      <input type="text" name="title" required>
      
      <label>類別</label>
      <select name="category">
        <option>一般公告</option>
        <option>學務公告</option>
        <option>系統公告</option>
        <option>活動公告</option>
        <option>緊急公告</option>
      </select>
      
      <label>內容</label>
      <textarea name="content" rows="4" required></textarea>
      
      <button type="submit" name="add_announcement">送出公告</button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// 搜尋功能
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#announcementTable tbody tr");
  rows.forEach(row => {
    let text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});

// 點擊公告列 -> 顯示內容
document.querySelectorAll("#announcementTable tbody tr").forEach(row => {
  row.addEventListener("click", function() {
    document.getElementById("modalDate").textContent = "📅 日期：" + this.cells[0].textContent;
    document.getElementById("modalTitle").textContent = this.cells[1].textContent;
    document.getElementById("modalCategory").textContent = "📌 類別：" + this.cells[2].innerText;
    document.getElementById("modalContent").textContent = this.getAttribute("data-content");

    let index = this.getAttribute("data-index");
    let deleteIndex = document.getElementById("deleteIndex");
    if (deleteIndex) deleteIndex.value = index;

    document.getElementById("announcementModal").style.display = "flex";
  });
});

// 關閉 Modal
function closeModal(id) { document.getElementById(id).style.display = "none"; }
</script>
</body>
</html>