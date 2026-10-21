<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}
include '../../config/koneksi.php';

$aksi = $_GET['aksi'] ?? 'tampil';
$id_u = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kat = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_alat']);
    $merk = mysqli_real_escape_string($conn, $_POST['merk']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $kond = mysqli_real_escape_string($conn, $_POST['kondisi']);

    if ($aksi == 'proses_tambah') {
        mysqli_query($conn, "INSERT INTO alat VALUES (NULL, '$kat', '$nama', '$merk', '$stok', '$kond')");
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_u', 'Tambah alat: $nama', NOW())");
    } elseif ($aksi == 'proses_edit') {
        $id = $_POST['id_alat'];
        mysqli_query($conn, "UPDATE alat SET id_kategori='$kat', nama_alat='$nama', merk='$merk', stok='$stok', kondisi='$kond' WHERE id_alat='$id'");
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_u', 'Edit alat: $nama', NOW())");
    }
    header("Location: alat.php");
    exit;
}

if ($aksi == 'hapus') {
    $id = $_GET['id'];
    $nm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_alat FROM alat WHERE id_alat='$id'"))['nama_alat'] ?? 'Alat';
    mysqli_query($conn, "DELETE FROM alat WHERE id_alat='$id'");
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_u', 'Hapus alat: $nm', NOW())");
    header("Location: alat.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Alat</title>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Kelola Data Alat</h3>
                <a href="alat.php?aksi=tambah" class="btn btn-primary btn-sm">+ Tambah Alat</a>
            </div>
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
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>NO</th>
                            <th>Nama Alat</th>
                            <th>Merk</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Kondisi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $batas = 10;
                        $hal = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
                        $awal = ($hal > 1) ? ($hal * $batas) - $batas : 0;
                        $where = isset($_GET['cari']) ? "WHERE alat.nama_alat LIKE '%{$_GET['cari']}%' OR alat.merk LIKE '%{$_GET['cari']}%'" : "";

                        $jml_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM alat $where"))['total'];
                        $jml_hal = ceil($jml_data / $batas);

                        $no = $awal + 1;
                        $q = mysqli_query($conn, "SELECT alat.*, kategori.nama_kategori FROM alat LEFT JOIN kategori ON alat.id_kategori = kategori.id_kategori $where ORDER BY alat.id_alat DESC LIMIT $awal, $batas");
                        if (mysqli_num_rows($q) > 0):
                            while ($r = mysqli_fetch_assoc($q)):
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['nama_alat']); ?></td>
                                    <td><?= htmlspecialchars($r['merk']); ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['nama_kategori'] ?? '-'); ?></span></td>
                                    <td><?= $r['stok']; ?></td>
                                    <td><span class="badge bg-<?= $r['kondisi'] == 'Baik' ? 'success' : 'danger'; ?>"><?= $r['kondisi']; ?></span></td>
                                    <td class="text-center">
                                        <a href="alat.php?aksi=edit&id=<?= $r['id_alat']; ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                        <a href="alat.php?aksi=hapus&id=<?= $r['id_alat']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?><tr>
                                <td colspan="7" class="text-center text-muted">Data kosong.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
                <?php if ($jml_hal > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($hal <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="alat.php?halaman=<?= $hal - 1; ?><?= isset($_GET['cari']) ? '&cari=' . $_GET['cari'] : ''; ?>">Prev</a></li>
                            <?php for ($x = 1; $x <= $jml_hal; $x++): ?>
                                <li class="page-item <?= ($hal == $x) ? 'active' : ''; ?>"><a class="page-link" href="alat.php?halaman=<?= $x; ?><?= isset($_GET['cari']) ? '&cari=' . $_GET['cari'] : ''; ?>"><?= $x; ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($hal >= $jml_hal) ? 'disabled' : ''; ?>"><a class="page-link" href="alat.php?halaman=<?= $hal + 1; ?><?= isset($_GET['cari']) ? '&cari=' . $_GET['cari'] : ''; ?>">Next</a></li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php elseif ($aksi == 'tambah' || $aksi == 'edit'): $ed = ($aksi == 'edit');
            $d = $ed ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM alat WHERE id_alat='{$_GET['id']}'")) : []; ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 rounded-4">
                        <h4 class="mb-3"><?= $ed ? 'Edit Alat' : 'Tambah Alat'; ?></h4>
                        <form action="alat.php?aksi=<?= $ed ? 'proses_edit' : 'proses_tambah'; ?>" method="POST">
                            <?php if ($ed): ?><input type="hidden" name="id_alat" value="<?= $d['id_alat']; ?>"><?php endif; ?>
                            <div class="mb-3"><label>Nama Alat</label><input type="text" name="nama_alat" class="form-control" value="<?= $d['nama_alat'] ?? ''; ?>" required></div>
                            <div class="mb-3"><label>Merk</label><input type="text" name="merk" class="form-control" value="<?= $d['merk'] ?? ''; ?>" required></div>
                            <div class="mb-3"><label>Kategori</label><select name="id_kategori" class="form-select" required>
                                    <option value="" disabled selected>Pilih</option>
                                    <?php $kat = mysqli_query($conn, "SELECT * FROM kategori");
                                    while ($k = mysqli_fetch_assoc($kat)): ?>
                                        <option value="<?= $k['id_kategori']; ?>" <?= (($d['id_kategori'] ?? '') == $k['id_kategori']) ? 'selected' : ''; ?>><?= $k['nama_kategori']; ?></option>
                                    <?php endwhile; ?>
                                </select></div>
                            <div class="mb-3"><label>Stok</label><input type="number" name="stok" class="form-control" value="<?= $d['stok'] ?? ''; ?>" min="1" required></div>
                            <div class="mb-3"><label>Kondisi</label><select name="kondisi" class="form-select" required>
                                    <option value="Baik" <?= ($d['kondisi'] ?? '') == 'Baik' ? 'selected' : ''; ?>>Baik</option>
                                    <option value="Rusak" <?= ($d['kondisi'] ?? '') == 'Rusak' ? 'selected' : ''; ?>>Rusak</option>
                                </select></div>
                            <div class="d-flex justify-content-between"><a href="alat.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-primary">Simpan</button></div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>