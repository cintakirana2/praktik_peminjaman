<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'Admin') { header("Location: ../../index.php"); exit; }
include '../../config/koneksi.php';

$batas = 10;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$mulai = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM log_aktivitas"))['jml'];
$jml_hal = ceil($total / $batas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Log Aktivitas</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../../index.php">Peminjaman Alat Sekolah</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Halo, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></span>
                <a href="../../index.php" class="btn btn-outline-light btn-sm">Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>
    <div class="container my-4">
        <h3 class="mb-3">Log Aktivitas Sistem</h3>
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>#</th><th>User</th><th>Aktivitas</th><th>Waktu</th></tr></thead>
                <tbody>
                    <?php $no = $mulai + 1;
                    $q = mysqli_query($conn, "SELECT log_aktivitas.*, user.nama_lengkap, user.role FROM log_aktivitas LEFT JOIN user ON log_aktivitas.id_user=user.id_user ORDER BY id_log DESC LIMIT $mulai, $batas");
                    while($r = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><strong><?= $r['nama_lengkap'] ?? 'Sistem'; ?></strong> <span class="badge bg-secondary"><?= $r['role'] ?? '-'; ?></span></td>
                        <td><?= $r['aktivitas']; ?></td>
                        <td><?= $r['waktu']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($jml_hal > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="log.php?halaman=<?= $halaman - 1; ?>">Prev</a></li>
                    <?php for($i=1; $i<=$jml_hal; $i++): ?>
                        <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>"><a class="page-link" href="log.php?halaman=<?= $i; ?>"><?= $i; ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($halaman >= $jml_hal) ? 'disabled' : ''; ?>"><a class="page-link" href="log.php?halaman=<?= $halaman + 1; ?>">Next</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>