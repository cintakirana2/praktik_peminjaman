<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 'Peminjam') {
    header("Location: ../../index.php");
    exit;
}
include '../../config/koneksi.php';

$aksi = $_GET['aksi'] ?? 'tampil';
$role = $_SESSION['role'];
$id_usr = $_SESSION['id_user'];

if ($aksi == 'proses_kembali' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_peminjaman'];
    $tgl_kembali = $_POST['tanggal_kembali'];
    $kondisi = $_POST['status_kondisi'];

    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, d.id_alat, d.jumlah_pinjam FROM peminjaman p JOIN detail_peminjaman d ON p.id_peminjaman=d.id_peminjaman WHERE p.id_peminjaman='$id'"));

    if ($p && $p['status_peminjaman'] == 'Disetujui') {
        $telat = max(0, floor((strtotime($tgl_kembali) - strtotime($p['tanggal_kembali'])) / 86400));
        $denda = ($telat * 2000) + ($kondisi == 'Rusak' ? 25000 : ($kondisi == 'Hilang' ? 75000 : 0));

        if ($kondisi != 'Hilang') {
            mysqli_query($conn, "UPDATE alat SET stok = stok + {$p['jumlah_pinjam']} WHERE id_alat='{$p['id_alat']}'");
        }

        mysqli_query($conn, "UPDATE peminjaman SET status_peminjaman='Selesai', tanggal_kembali='$tgl_kembali', denda='$denda', status_kondisi='$kondisi' WHERE id_peminjaman='$id'");
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_usr', 'Pengembalian ID #$id ($kondisi, Denda: Rp " . number_format($denda, 0, ',', '.') . ")', NOW())");
    }
    header("Location: pengembalian.php");
    exit;
}

if ($aksi == 'hapus' && $role == 'Admin' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM detail_peminjaman WHERE id_peminjaman='{$_GET['id']}'");
    mysqli_query($conn, "DELETE FROM peminjaman WHERE id_peminjaman='{$_GET['id']}' AND status_peminjaman='Selesai'");
    header("Location: pengembalian.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengembalian</title>
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
        <?php if ($aksi == 'tampil'): ?>
            <h3 class="mb-3">Kelola Pengembalian Alat</h3>

            <!-- form cari -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="cari" class="form-control" placeholder="Cari data..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-dark">Cari</button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <a href="?_blank" onclick="window.location.href=window.location.pathname; return false;" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- tbl sdg pinjam -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <h5 class="text-warning fw-bold mb-3">📋 Sedang Dipinjam</h5>
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Alat</th>
                            <th>Pinjam</th>
                            <th>Batas</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $batas = 10;
                        $hal = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
                        $awal = ($hal > 1) ? ($hal * $batas) - $batas : 0;

                        //filter pencarian
                        $where_cari = "";
                        if (isset($_GET['cari']) && !empty($_GET['cari'])) {
                            $c = mysqli_real_escape_string($conn, $_GET['cari']);
                            $where_cari = "AND (u.nama_lengkap LIKE '%$c%' OR p.id_peminjaman IN (SELECT dp.id_peminjaman FROM detail_peminjaman dp JOIN alat a ON dp.id_alat=a.id_alat WHERE a.nama_alat LIKE '%$c%'))";
                        }

                        // hitung total data utk tbl sdg pinjam
                        $jml_data_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT p.id_peminjaman) as total FROM peminjaman p JOIN user u ON p.id_user=u.id_user WHERE p.status_peminjaman='Disetujui' $where_cari"))['total'];
                        $jml_hal_pinjam = ceil($jml_data_pinjam / $batas);

                        $no = $awal + 1;
                        $q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN user u ON p.id_user=u.id_user WHERE p.status_peminjaman='Disetujui' $where_cari ORDER BY p.id_peminjaman DESC LIMIT $awal, $batas");

                        if (mysqli_num_rows($q) > 0):
                            while ($r = mysqli_fetch_assoc($q)):
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><strong><?= htmlspecialchars($r['nama_lengkap']); ?></strong></td>
                                    <td><?php $qd = mysqli_query($conn, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat=a.id_alat WHERE d.id_peminjaman='{$r['id_peminjaman']}'");
                                        while ($d = mysqli_fetch_assoc($qd)) echo "<div>• {$d['nama_alat']} ({$d['jumlah_pinjam']})</div>"; ?></td>
                                    <td><?= $r['tanggal_pinjam']; ?></td>
                                    <td><span class="badge bg-danger"><?= $r['tanggal_kembali']; ?></span></td>
                                    <td class="text-center"><a href="pengembalian.php?aksi=proses&id=<?= $r['id_peminjaman']; ?>" class="btn btn-success btn-sm">Proses</a></td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Tidak ada data peminjaman aktif.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- tbl riwayat selesai -->
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h5 class="text-success fw-bold mb-3">✅ Riwayat Selesai</h5>
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Alat</th>
                            <th>Dikembalikan</th>
                            <th>Kondisi</th>
                            <th>Denda</th>
                            <?php if ($role == 'Admin'): ?><th class="text-center">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // hitung total data utk riwayat selesai
                        $jml_data_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT p.id_peminjaman) as total FROM peminjaman p JOIN user u ON p.id_user=u.id_user WHERE p.status_peminjaman='Selesai' $where_cari"))['total'];
                        $jml_hal_selesai = ceil($jml_data_selesai / $batas);

                        $no_s = $awal + 1;
                        $qs = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN user u ON p.id_user=u.id_user WHERE p.status_peminjaman='Selesai' $where_cari ORDER BY p.id_peminjaman DESC LIMIT $awal, $batas");

                        if (mysqli_num_rows($qs) > 0):
                            while ($r = mysqli_fetch_assoc($qs)):
                                $kondisi = $r['status_kondisi'] ?? 'Normal';
                                $jumlah_denda = $r['denda'] ?? 0;
                                $badge = ($kondisi == 'Rusak') ? 'bg-warning text-dark' : (($kondisi == 'Hilang') ? 'bg-danger' : 'bg-success');
                        ?>
                                <tr>
                                    <td><?= $no_s++; ?></td>
                                    <td><strong><?= htmlspecialchars($r['nama_lengkap']); ?></strong></td>
                                    <td><?php $qd = mysqli_query($conn, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat=a.id_alat WHERE d.id_peminjaman='{$r['id_peminjaman']}'");
                                        while ($d = mysqli_fetch_assoc($qd)) echo "<div>• {$d['nama_alat']} ({$d['jumlah_pinjam']})</div>"; ?></td>
                                    <td><?= $r['tanggal_kembali']; ?></td>
                                    <td><span class="badge <?= $badge; ?>"><?= $kondisi; ?></span></td>
                                    <td><strong class="text-danger"><?= ($jumlah_denda > 0) ? 'Rp ' . number_format($jumlah_denda, 0, ',', '.') : 'Rp 0'; ?></strong></td>
                                    <?php if ($role == 'Admin'): ?><td class="text-center"><a href="pengembalian.php?aksi=hapus&id=<?= $r['id_peminjaman']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">Hapus</a></td><?php endif; ?>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="<?= $role == 'Admin' ? 7 : 6; ?>" class="text-center text-muted py-3">Tidak ada riwayat pengembalian.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($aksi == 'proses'): $id = $_GET['id'];
            $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN user u ON p.id_user=u.id_user WHERE p.id_peminjaman='$id'"));
        ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 rounded-4">
                        <h4 class="mb-3">Konfirmasi Pengembalian</h4>
                        <form method="POST" action="pengembalian.php?aksi=proses_kembali">
                            <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman']; ?>">
                            <div class="mb-3"><label>Peminjam</label><input type="text" class="form-control" value="<?= $p['nama_lengkap']; ?>" readonly></div>
                            <div class="mb-3"><label>Batas Tanggal</label><input type="text" class="form-control bg-light" value="<?= $p['tanggal_kembali']; ?>" readonly></div>
                            <div class="mb-3"><label>Tanggal Kembali</label><input type="date" name="tanggal_kembali" class="form-control" value="<?= date('Y-m-d'); ?>" required></div>
                            <div class="mb-3">
                                <label>Kondisi Alat</label>
                                <select name="status_kondisi" class="form-select" required>
                                    <option value="Normal">Normal (Tanpa Denda)</option>
                                    <option value="Rusak">Rusak (+ Denda Rp 25.000)</option>
                                    <option value="Hilang">Hilang (+ Denda Rp 75.000 & Stok Tetap)</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between"><a href="pengembalian.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-success">Selesai</button></div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>