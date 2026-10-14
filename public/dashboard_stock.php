<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$user = current_user();
if (!$user) { header('Location: ./'); exit; }

if ($user['role_name'] !== 'admin_stok') {
  switch ($user['role_name']) {
    case 'admin_utama': header('Location: dashboard_admin.php'); break;
    case 'atasan': header('Location: dashboard_boss.php'); break;
    default: header('Location: dashboard_user.php'); break;
  }
  exit;
}

$conn = get_db_connection();
$view = $_GET['view'] ?? 'stok';

// ===== Notifikasi stok kosong ===== //
$lowStockCount = 0;
$stmt = $conn->query("SELECT COUNT(*) AS c FROM items WHERE stock <= 0");
$row = $stmt->fetch();
$lowStockCount = (int)$row['c'];
$notifTotal = $lowStockCount;

// Helper function untuk safe output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin Stok</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="assets/styles.css" rel="stylesheet">
  <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  
  <nav class="navbar navbar-expand-lg app-navbar">
    <div class="container-fluid">
      <button class="navbar-toggler-btn me-2" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
      </button>
      
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="notif-wrap">
          <a href="#" class="notif-bell text-decoration-none" data-notif-trigger>
            <i class="fa-solid fa-bell"></i>
            <?php if ($notifTotal>0){ ?><span class="badge rounded-pill bg-danger" style="font-size: 0.6rem; position: absolute; top: 0; right: 0;"><?php echo $notifTotal; ?></span><?php } ?>
          </a>
          <div class="notif-panel shadow-lg">
            <div class="notif-header">Notifikasi</div>
            <div class="notif-body">
              <div class="notif-item">
                <span>Stok Kosong</span>
                <span class="badge bg-danger"><?php echo $lowStockCount; ?></span>
              </div>
              <div class="mt-3 d-grid gap-2">
                <a href="dashboard_stock.php?view=stok" class="btn btn-sm btn-primary">Lihat Stok</a>
              </div>
            </div>
          </div>
        </div>
        <div class="dropdown">
          <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
              <i class="fa-solid fa-user"></i>
            </div>
            <span class="d-none d-sm-inline fw-medium text-secondary"><?php echo htmlspecialchars($user['name']); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="handlers/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="layout-with-sidebar">
    <aside class="app-sidebar" id="appSidebar">
      <div class="sidebar-header">
        <div class="brand">
          <img src="img/logo.png" alt="Logo">
          <span>PT NHM</span>
        </div>
      </div>
      <div class="sidebar-nav">
        <a href="?view=stok" class="nav-link <?= $view==='stok'?'active':'' ?>"><i class="fa-solid fa-warehouse"></i> Stok Barang</a>
        <a href="?view=receiving" class="nav-link <?= $view==='receiving'?'active':'' ?>"><i class="fa-solid fa-truck-ramp-box"></i> Penerimaan</a>
        <a href="?view=adjust" class="nav-link <?= $view==='adjust'?'active':'' ?>"><i class="fa-solid fa-arrows-rotate"></i> Penyesuaian</a>
      </div>
      <div class="p-3 mt-auto border-top border-light border-opacity-10">
        <small class="text-white text-opacity-50">Logged in as Stock Admin</small>
      </div>
    </aside>

    <main class="main-content p-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-warehouse text-primary me-2"></i> Dashboard Stok</h1>
            <p class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($user['name']); ?>.</p>
          </div>
        </div>

<?php if ($view==='stok'): ?>
<section class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><i class="fa-solid fa-warehouse"></i> Stok Barang</h2>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Barang</th><th>SKU</th><th>Stok</th><th>Status</th></tr></thead>
        <tbody>
        <?php
          foreach ($conn->query("SELECT id, name, sku, stock FROM items ORDER BY name") as $row):
        ?>
        <tr>
          <td><?= e($row['name']) ?></td>
          <td><?= e($row['sku']) ?></td>
          <td><?= (int)$row['stock'] ?></td>
          <td><?= $row['stock']>0
            ? '<span class="badge bg-success">Tersedia</span>'
            : '<span class="badge bg-danger">Tidak Tersedia</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($view==='receiving'): ?>
<section class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><i class="fa-solid fa-truck-ramp-box"></i> Form Penerimaan Barang</h2>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success"><?= e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?= e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="handlers/submit_receiving.php">
      <div class="row g-3">
        <div class="col-md-6">
          <label>Nama Penerima</label>
          <input type="text" class="form-control" name="receiver_name" required>
        </div>
        <div class="col-md-6">
          <label>Tanggal Penerimaan</label>
          <input type="date" class="form-control" name="receiving_date" required>
        </div>
        <div class="col-md-6">
          <label>Barang</label>
          <select class="form-select" name="item_id" required>
            <?php foreach ($conn->query("SELECT id,name,sku FROM items ORDER BY name") as $it): ?>
              <option value="<?= e($it['id']) ?>"><?= e($it['sku'] . ' · ' . $it['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label>Supplier</label>
          <select class="form-select" name="supplier_id" required>
            <?php foreach ($conn->query("SELECT id,name FROM suppliers ORDER BY name") as $sp): ?>
              <option value="<?= e($sp['id']) ?>"><?= e($sp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label>Qty</label><input type="number" class="form-control" min="1" name="qty" required></div>
        <div class="col-md-6"><label>Status</label>
          <select class="form-select" name="status"><option value="received">Diterima</option><option value="pending">Pending</option></select></div>

        <div class="col-12"><label>Catatan</label><textarea class="form-control" name="note_text" rows="2"></textarea></div>

        <div class="col-12"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
      </div>
    </form>

  </div>
</section>

<section class="card shadow-sm mb-3">
  <div class="card-body">
    <h2 class="h6 mb-3"><i class="fa-solid fa-list"></i> Riwayat Penerimaan</h2>

    <div class="table-responsive">
      <table class="table table-sm">
        <thead><tr><th>Tanggal</th><th>Penerima</th><th>Barang</th><th>Supplier</th><th>Qty</th><th>Status</th></tr></thead>
        <tbody>
        <?php
          $rows = $conn->query("SELECT r.receiving_date,r.receiver_name,i.name AS iname,s.name AS sname,r.qty,r.status
            FROM receivings r
            JOIN items i ON i.id=r.item_id
            JOIN suppliers s ON s.id=r.supplier_id
            ORDER BY r.id DESC LIMIT 30");

          foreach($rows as $row):
        ?>
        <tr>
          <td><?= e($row['receiving_date']) ?></td>
          <td><?= e($row['receiver_name']) ?></td>
          <td><?= e($row['iname']) ?></td>
          <td><?= e($row['sname']) ?></td>
          <td><?= (int)$row['qty'] ?></td>
          <td><?= e($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($view==='adjust'): ?>
<section class="card shadow-sm">
  <div class="card-body">
    <h2 class="h6 mb-3"><i class="fa-solid fa-box"></i> Update Stok Consumable</h2>

    <form method="post" action="handlers/update_stock.php" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label>Pilih Barang</label>
          <select class="form-select" name="item_id" required>
            <?php foreach ($conn->query("SELECT id,name,sku FROM items ORDER BY name") as $it): ?>
              <option value="<?= e($it['id']) ?>"><?= e($it['sku'] . ' · ' . $it['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label>Stok Masuk</label><input type="number" class="form-control" min="1" name="stock_in" required></div>
        <div class="col-md-6"><label>Tanggal</label><input type="date" class="form-control" name="received_date"></div>
        <div class="col-md-6"><label>Bukti</label><input type="file" class="form-control" name="proof_file"></div>

        <div class="col-12"><label>Catatan</label><textarea class="form-control" name="note_text"></textarea></div>

        <div class="col-12"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
      </div>
    </form>
  </div>
</section>

<section class="card shadow-sm mt-3">
  <div class="card-body">
    <h2 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Stok</h2>
    <div class="table-responsive">
      <table class="table table-sm">
        <thead><tr><th>Tanggal</th><th>Barang</th><th>SKU</th><th>Jenis</th><th>Qty</th><th>Sumber</th><th>Catatan</th></tr></thead>
        <tbody>
        <?php
          $rows = $conn->query("SELECT sm.created_at,i.name AS iname,i.sku,sm.type,sm.qty,sm.source,sm.note_text
            FROM stock_movements sm
            JOIN items i ON i.id = sm.item_id
            ORDER BY sm.id DESC LIMIT 20");

          foreach($rows as $r):
        ?>
        <tr>
          <td><?= e($r['created_at']) ?></td>
          <td><?= e($r['iname']) ?></td>
          <td><?= e($r['sku']) ?></td>
          <td><?= $r['type']==='in'?'Masuk':'Keluar' ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= e($r['source']) ?></td>
          <td><?= e($r['note_text']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

</main>
</div>
</div>

<footer class="text-center text-muted small py-4">© <?= date('Y') ?> PT NHM · Asset Management System</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/main.js"></script>
</body>
</html>
