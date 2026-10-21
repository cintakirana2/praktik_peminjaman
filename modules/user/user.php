<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../index.php"); exit;
}
include '../../config/koneksi.php';

$aksi = $_GET['aksi'] ?? 'tampil';
$id_u = $_SESSION['id_user'];

// PROSES TAMBAH & EDIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    
    if ($aksi == 'proses_tambah') {
        $pass = md5($_POST['password']);
        mysqli_query($conn, "INSERT INTO user VALUES (NULL, '$username', '$pass', '$nama', '$role')");
    } elseif ($aksi == 'proses_edit') {
        $id   = $_POST['id_user'];
        $pass = !empty($_POST['password']) ? ", password='".md5($_POST['password'])."'" : "";
        mysqli_query($conn, "UPDATE user SET nama_lengkap='$nama', username='$username', role='$role' $pass WHERE id_user='$id'");
    }
    header("Location: user.php"); exit;
}

// PROSES HAPUS
if ($aksi == 'hapus') {
    $id = $_GET['id'];
    if ($id != $_SESSION['id_user']) mysqli_query($conn, "DELETE FROM user WHERE id_user='$id'");
    header("Location: user.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Peminjaman</title>
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

    <div class="container my-5">

        <?php if ($aksi == 'tampil'): ?>
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h2>Manajemen User</h2>
                    <p class="text-muted">Kelola akun Admin, Petugas, dan Peminjam (Siswa).</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="user.php?aksi=tambah" class="btn btn-primary shadow-sm">+ Tambah User Baru</a>
                </div>
            </div>

            <!-- form cari -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                <form method="GET" action="user.php" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama atau username..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-dark">Cari</button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <a href="user.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>NO</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role / Hak Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $batas = 10;
                                $hal = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
                                $awal = ($hal > 1) ? ($hal * $batas) - $batas : 0;

                                $where = "";
                                if (isset($_GET['cari']) && !empty($_GET['cari'])) {
                                    $c = mysqli_real_escape_string($conn, $_GET['cari']);
                                    $where = "WHERE nama_lengkap LIKE '%$c%' OR username LIKE '%$c%'";
                                }

                                $jml_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM user $where"))['total'];
                                $jml_hal = ceil($jml_data / $batas);

                                $no = $awal + 1;
                                $q = mysqli_query($conn, "SELECT * FROM user $where ORDER BY id_user DESC LIMIT $awal, $batas");
                                
                                if (mysqli_num_rows($q) > 0):
                                    while ($r = mysqli_fetch_assoc($q)):
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['nama_lengkap']); ?></td>
                                    <td><?= htmlspecialchars($r['username']); ?></td>
                                    <td>
                                        <span class="badge bg-<?= $r['role']=='Admin'?'primary':($r['role']=='Petugas'?'warning text-dark':'success') ?>">
                                            <?= $r['role']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="user.php?aksi=edit&id=<?= $r['id_user']; ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                        <?php if ($r['id_user'] != $_SESSION['id_user']): ?>
                                            <a href="user.php?aksi=hapus&id=<?= $r['id_user']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">Hapus</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">Data user tidak ditemukan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- paginasi -->
                    <?php if ($jml_hal > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($hal <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="user.php?halaman=<?= $hal - 1; ?><?= isset($_GET['cari']) ? '&cari='.urlencode($_GET['cari']) : ''; ?>">Prev</a>
                                </li>
                                <?php for ($x = 1; $x <= $jml_hal; $x++): ?>
                                    <li class="page-item <?= ($hal == $x) ? 'active' : ''; ?>">
                                        <a class="page-link" href="user.php?halaman=<?= $x; ?><?= isset($_GET['cari']) ? '&cari='.urlencode($_GET['cari']) : ''; ?>"><?= $x; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($hal >= $jml_hal) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="user.php?halaman=<?= $hal + 1; ?><?= isset($_GET['cari']) ? '&cari='.urlencode($_GET['cari']) : ''; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                </div>
            </div>

        <?php elseif ($aksi == 'tambah' || $aksi == 'edit'): ?>
            <?php
            $is_edit = ($aksi == 'edit');
            $d = $is_edit ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user WHERE id_user='".$_GET['id']."'")) : [];
            ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h3 class="fw-bold mb-3"><?= $is_edit ? '✏️ Edit Data User' : '+ Tambah User Baru'; ?></h3>
                            <form action="user.php?aksi=<?= $is_edit ? 'proses_edit' : 'proses_tambah'; ?>" method="POST">
                                <?php if ($is_edit): ?>
                                    <input type="hidden" name="id_user" value="<?= $d['id_user']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" class="form-control" value="<?= $d['nama_lengkap'] ?? ''; ?>" required autocomplete="off">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= $d['username'] ?? ''; ?>" required autocomplete="off">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password <?= $is_edit ? '<small class="text-muted">(Kosongkan jika tidak diubah)</small>' : ''; ?></label>
                                    <input type="password" name="password" class="form-control" <?= $is_edit ? '' : 'required'; ?>>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Role / Hak Akses</label>
                                    <select name="role" class="form-select" required>
                                        <option value="Admin" <?= ($d['role']??'')=='Admin'?'selected':''; ?>>Admin</option>
                                        <option value="Petugas" <?= ($d['role']??'')=='Petugas'?'selected':''; ?>>Petugas</option>
                                        <option value="Peminjam" <?= ($d['role']??'')=='Peminjam'?'selected':''; ?>>Peminjam</option>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="user.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4"><?= $is_edit ? 'Perbarui' : 'Simpan'; ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>