<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ./'); exit; }
// Hanya user biasa yang boleh akses
if ($user['role_name'] !== 'user') {
  switch ($user['role_name']) {
    case 'admin_utama': header('Location: dashboard_admin.php'); break;
    case 'admin_stok': header('Location: dashboard_stock.php'); break;
    case 'atasan': header('Location: dashboard_boss.php'); break;
  }
  exit;
}

$conn = get_db_connection();
$view = isset($_GET['view']) ? $_GET['view'] : 'ajukan';
$uid = (int)$user['id'];

$reqPendingCount = $reqApprovedCount = $reqRejectedCount = 0;
$reimPendingCount = $reimApprovedCount = $reimRejectedCount = 0;
$pickupPendingCount = $pickupCompletedCount = 0;

try {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE user_id = :uid AND status = :status");
  $stmt->execute([':uid' => $uid, ':status' => 'pending']);
  $reqPendingCount = (int)$stmt->fetchColumn();

  $stmt->execute([':uid' => $uid, ':status' => 'approved']);
  $reqApprovedCount = (int)$stmt->fetchColumn();

  $stmt->execute([':uid' => $uid, ':status' => 'rejected']);
  $reqRejectedCount = (int)$stmt->fetchColumn();

  $stmt2 = $conn->prepare("SELECT COUNT(*) AS c FROM reimbursements WHERE user_id = :uid AND status = :status");
  $stmt2->execute([':uid' => $uid, ':status' => 'pending']);
  $reimPendingCount = (int)$stmt2->fetchColumn();

  $stmt2->execute([':uid' => $uid, ':status' => 'approved']);
  $reimApprovedCount = (int)$stmt2->fetchColumn();

  $stmt2->execute([':uid' => $uid, ':status' => 'rejected']);
  $reimRejectedCount = (int)$stmt2->fetchColumn();

  $stmt3 = $conn->prepare("SELECT COUNT(*) AS c FROM item_takings WHERE user_id = :uid AND status = :status");
  $stmt3->execute([':uid' => $uid, ':status' => 'pending']);
  $pickupPendingCount = (int)$stmt3->fetchColumn();

  $stmt3->execute([':uid' => $uid, ':status' => 'approved']);
  $pickupCompletedCount = (int)$stmt3->fetchColumn();
} catch (PDOException $e) {}

$notifTotal = $reqPendingCount + $reimPendingCount + $reqApprovedCount + $reqRejectedCount + $reimApprovedCount + $reimRejectedCount + $pickupPendingCount + $pickupCompletedCount;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
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
              <div class="notif-item"><span>Pending</span><span class="badge bg-warning text-dark"><?php echo $reqPendingCount + $reimPendingCount + $pickupPendingCount; ?></span></div>
              <div class="notif-item"><span>Approved</span><span class="badge bg-success"><?php echo $reqApprovedCount + $reimApprovedCount + $pickupCompletedCount; ?></span></div>
              <div class="mt-3 d-grid gap-2">
                <a href="dashboard_user.php?view=ajukan" class="btn btn-sm btn-primary">Lihat Aktivitas</a>
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
        <a href="dashboard_user.php?view=ajukan" class="nav-link <?php echo $view==='ajukan'?'active':''; ?>"><i class="fa-solid fa-boxes-stacked"></i> Ajukan Barang</a>
        <a href="dashboard_user.php?view=stok" class="nav-link <?php echo $view==='stok'?'active':''; ?>"><i class="fa-solid fa-warehouse"></i> Stok Barang</a>
        <a href="dashboard_user.php?view=reimburse" class="nav-link <?php echo $view==='reimburse'?'active':''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Reimbursement</a>
        <a href="dashboard_user.php?view=pengambilan" class="nav-link <?php echo $view==='pengambilan'?'active':''; ?>"><i class="fa-solid fa-box-open"></i> Pengambilan</a>
      </div>
      <div class="p-3 mt-auto border-top border-light border-opacity-10">
        <small class="text-white text-opacity-50">Logged in as User</small>
      </div>
    </aside>

    <main class="main-content p-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-gauge text-primary me-2"></i> Dashboard User</h1>
            <p class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($user['name']); ?>.</p>
          </div>
        </div>

        <?php if (!empty($_GET['msg'])): ?>
          <div class="alert alert-info py-2 my-2 border-0 shadow-sm"><i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <?php if ($view==='ajukan'): ?>
        <section class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-4 fw-bold"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Form Pengajuan Barang</h2>
            <form method="post" action="handlers/submit_request.php" enctype="multipart/form-data">
              <div class="row g-4">
                <div class="col-md-6">
                  <label class="form-label fw-medium">Nama User</label>
                  <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Departemen</label>
                  <input type="text" class="form-control" name="department" placeholder="Contoh: IT, Finance" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Pilih Barang</label>
                  <select class="form-select" name="item_id" required>
                    <option value="" disabled selected>Pilih Barang...</option>
                    <?php
                      foreach ($conn->query("SELECT id, name, stock FROM items ORDER BY name") as $row) {
                        echo "<option value='".(int)$row['id']."'>".htmlspecialchars($row['name'])." (Stok: ".(int)$row['stock'].")</option>";
                      }
                    ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-medium">Jumlah</label>
                  <input type="number" class="form-control" name="qty" min="1" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-medium">Tanggal</label>
                  <input type="date" class="form-control" name="request_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Catatan / Alasan</label>
                  <textarea class="form-control" name="note" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Upload Foto (Opsional)</label>
                  <input type="file" class="form-control" name="photo" accept="image/*">
                </div>
                <div class="col-12">
                  <button class="btn btn-primary px-4" type="submit"><i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan</button>
                </div>
              </div>
            </form>
          </div>
        </section>

        <section class="card shadow-sm">
          <div class="card-body p-4">
            <h3 class="h6 mb-4 fw-bold text-secondary"><i class="fa-solid fa-clock-rotate-left me-2"></i> Riwayat Pengajuan</h3>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead><tr><th>Tanggal</th><th>Barang</th><th>Qty</th><th>Status</th><th>Bukti</th></tr></thead>
                <tbody>
                  <?php
                    $stmt = $conn->prepare("SELECT r.request_date, r.created_at, i.name AS iname, r.qty, r.status, r.photo_file FROM requests r JOIN items i ON i.id = r.item_id WHERE r.user_id = :uid ORDER BY r.id DESC LIMIT 10");
                    $stmt->execute([':uid' => $uid]);
                    while ($r = $stmt->fetch()) {
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['request_date'] ?: date('Y-m-d', strtotime($r['created_at']))); ?></td>
                    <td class="fw-medium"><?php echo htmlspecialchars($r['iname']); ?></td>
                    <td><span class="badge bg-light text-dark"><?php echo (int)$r['qty']; ?></span></td>
                    <td>
                      <?php if ($r['status']==='approved'): ?>
                        <span class="badge bg-success">Disetujui</span>
                      <?php elseif ($r['status']==='rejected'): ?>
                        <span class="badge bg-danger">Ditolak</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($r['photo_file'])): ?>
                        <a href="../<?php echo htmlspecialchars($r['photo_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                      <?php else: ?>
                        <span class="text-muted small">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='stok'): ?>
        <section class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="h5 mb-4 fw-bold"><i class="fa-solid fa-warehouse text-primary me-2"></i> Ketersediaan Stok</h2>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead><tr><th>Nama Barang</th><th>SKU</th><th>Stok Tersedia</th><th>Status</th></tr></thead>
                <tbody>
                  <?php
                    foreach ($conn->query("SELECT name, sku, stock FROM items ORDER BY name") as $row) {
                  ?>
                  <tr>
                    <td class="fw-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><code><?php echo htmlspecialchars($row['sku']); ?></code></td>
                    <td><?php echo (int)$row['stock']; ?></td>
                    <td>
                      <?php if ($row['stock']>0): ?>
                        <span class="badge bg-success">Tersedia</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Habis</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='reimburse'): ?>
        <section class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-4 fw-bold"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Form Reimbursement</h2>
            <form method="post" action="handlers/submit_reimbursement.php" enctype="multipart/form-data">
              <div class="row g-4">
                <div class="col-md-8">
                  <label class="form-label fw-medium">Nama Barang / Keperluan</label>
                  <input type="text" class="form-control" name="description" placeholder="Contoh: Pembelian Tinta Printer" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Departemen</label>
                  <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Qty</label>
                  <input type="number" class="form-control" name="qty" id="reim_qty" min="1" value="1">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Harga Satuan (Rp)</label>
                  <input type="number" class="form-control" name="unit_price" id="reim_price" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Total (Rp)</label>
                  <input type="number" class="form-control bg-light" name="amount" id="reim_total" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Upload Bukti / Nota</label>
                  <input type="file" class="form-control" name="receipt" required>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary px-4" type="submit">Kirim Reimbursement</button>
                </div>
              </div>
            </form>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='pengambilan'): ?>
        <section class="card shadow-sm mb-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-4 fw-bold"><i class="fa-solid fa-box-open text-primary me-2"></i> Form Pengambilan Stok</h2>
            <form method="post" action="handlers/submit_pickup.php">
              <div class="row g-4">
                <div class="col-md-6">
                  <label class="form-label fw-medium">Barang</label>
                  <select class="form-select" name="item_id" required>
                    <option value="">Pilih...</option>
                    <?php
                      foreach ($conn->query("SELECT id, name, stock FROM items WHERE stock > 0 ORDER BY name") as $row) {
                        echo "<option value='".(int)$row['id']."'>".htmlspecialchars($row['name'])." (Tersedia: ".(int)$row['stock'].")</option>";
                      }
                    ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-medium">Jumlah</label>
                  <input type="number" class="form-control" name="qty" min="1" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-medium">Tanggal Ambil</label>
                  <input type="date" class="form-control" name="pickup_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Tujuan / Alasan</label>
                  <input type="text" class="form-control" name="purpose" placeholder="Contoh: Digunakan untuk Project IT-22" required>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary" type="submit">Ajukan Pengambilan</button>
                </div>
              </div>
            </form>
          </div>
        </section>

        <section class="card shadow-sm mt-4">
          <div class="card-body p-4">
            <h2 class="h5 mb-4 fw-bold"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Riwayat Pengambilan Saya</h2>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Tanggal Ambil</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Disetujui Oleh</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $uid = current_user()['id'];
                  $stmt = $conn->prepare("
                    SELECT tk.*, i.name as item_name, admin.name as admin_name
                    FROM item_takings tk
                    JOIN items i ON i.id = tk.item_id
                    LEFT JOIN users admin ON admin.id = tk.approved_by
                    WHERE tk.user_id = ?
                    ORDER BY tk.id DESC
                  ");
                  $stmt->execute([$uid]);
                  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  if ($rows): foreach ($rows as $r):
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['taking_date']); ?></td>
                    <td><?php echo htmlspecialchars($r['item_name']); ?></td>
                    <td><?php echo (int)$r['quantity']; ?></td>
                    <td>
                      <?php if ($r['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Menunggu</span>
                      <?php elseif ($r['status'] === 'approved'): ?>
                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Disetujui</span>
                      <?php else: ?>
                        <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i> Ditolak</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['admin_name'] ?? '-'); ?></td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat pengambilan.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

      </div>
    </main>
  </div>

  <footer class="text-center text-muted small py-4">© <?php echo date('Y'); ?> PT NHM · Asset Management System</footer>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/main.js"></script>
  <script>
    // Local script for reimbursement auto-calc if not in main.js
    const q = document.getElementById('reim_qty');
    const p = document.getElementById('reim_price');
    const t = document.getElementById('reim_total');
    if(q && p && t) {
      const calc = () => { t.value = (parseFloat(q.value)||0) * (parseFloat(p.value)||0); };
      q.addEventListener('input', calc);
      p.addEventListener('input', calc);
    }
  </script>
</body>
</html>
