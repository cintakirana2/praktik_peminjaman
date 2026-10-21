<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}
include '../../config/koneksi.php';

$aksi = $_GET['aksi'] ?? 'tampil';
$role = $_SESSION['role'];
$id_usr = $_SESSION['id_user'];
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($aksi == 'tambah_keranjang' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_a = $_POST['id_alat'];
    $jml = $_POST['jumlah_pinjam'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM alat WHERE id_alat='$id_a'"));
    if ($cek && $cek['stok'] >= $jml) {
        if (isset($_SESSION['cart'][$id_a])) {
            $_SESSION['cart'][$id_a]['jumlah'] += $jml;
        } else {
            if (count($_SESSION['cart']) >= 3) {
                echo "<script>alert('Maksimal 3 jenis!');window.location='peminjaman.php?aksi=tambah';</script>";
                exit;
            }
            $_SESSION['cart'][$id_a] = ['id_alat' => $id_a, 'nama_alat' => $cek['nama_alat'], 'jumlah' => $jml];
        }
    } else {
        echo "<script>alert('Stok kurang!');window.location='peminjaman.php?aksi=tambah';</script>";
        exit;
    }
    header("Location: peminjaman.php?aksi=tambah");
    exit;
}
if ($aksi == 'hapus_keranjang' && isset($_GET['id'])) {
    unset($_SESSION['cart'][$_GET['id']]);
    header("Location: peminjaman.php?aksi=tambah");
    exit;
}

if ($aksi == 'proses_checkout' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $target = ($role == 'Admin' && isset($_POST['id_user'])) ? $_POST['id_user'] : $id_usr;
    $tgl_p = $_POST['tanggal_pinjam'];
    $tgl_k = $_POST['tanggal_kembali'];
    if (empty($_SESSION['cart'])) {
        header("Location: peminjaman.php?aksi=tambah");
        exit;
    }

    mysqli_query($conn, "INSERT INTO peminjaman (id_user, tanggal_pinjam, tanggal_kembali, status_peminjaman) VALUES ('$target', '$tgl_p', '$tgl_k', 'Pending')");
    $id_new = mysqli_insert_id($conn);

    foreach ($_SESSION['cart'] as $item) {
        mysqli_query($conn, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah_pinjam) VALUES ('$id_new', '{$item['id_alat']}', '{$item['jumlah']}')");
    }
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_usr', 'Mengajukan peminjaman (ID: #$id_new)', NOW())");
    unset($_SESSION['cart']);
    echo "<script>alert('Berhasil diajukan!');window.location='peminjaman.php';</script>";
    exit;
}

if (in_array($aksi, ['setujui', 'tolak', 'hapus']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_peminjaman='$id'"));
    $qd = mysqli_query($conn, "SELECT * FROM detail_peminjaman WHERE id_peminjaman='$id'");
    $stat = $p['status_peminjaman'] ?? 'Pending';

    if ($aksi == 'setujui' && $role == 'Petugas' && $stat == 'Pending') {
        while ($d = mysqli_fetch_assoc($qd)) {
            mysqli_query($conn, "UPDATE alat SET stok = stok - {$d['jumlah_pinjam']} WHERE id_alat='{$d['id_alat']}'");
        }
        mysqli_query($conn, "UPDATE peminjaman SET status_peminjaman='Disetujui' WHERE id_peminjaman='$id'");
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_usr', 'Menyetujui peminjaman ID #$id', NOW())");
    } elseif ($aksi == 'tolak' && $role == 'Petugas' && $stat == 'Pending') {
        mysqli_query($conn, "UPDATE peminjaman SET status_peminjaman='Ditolak' WHERE id_peminjaman='$id'");
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_usr', 'Menolak peminjaman ID #$id', NOW())");
    } elseif ($aksi == 'hapus' && $role == 'Admin') {
        if ($stat == 'Disetujui') {
            while ($d = mysqli_fetch_assoc($qd)) {
                mysqli_query($conn, "UPDATE alat SET stok = stok + {$d['jumlah_pinjam']} WHERE id_alat='{$d['id_alat']}'");
            }
        }
        mysqli_query($conn, "DELETE FROM detail_peminjaman WHERE id_peminjaman='$id'");
        mysqli_query($conn, "DELETE FROM peminjaman WHERE id_peminjaman='$id'");
    }
    header("Location: peminjaman.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Peminjaman</title>
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
                <h3>Data Peminjaman</h3>
                <?php if ($role != 'Petugas'): ?><a href="peminjaman.php?aksi=tambah" class="btn btn-primary btn-sm">+ Pengajuan Baru</a><?php endif; ?>
            </div>

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

            <div class="card border-0 shadow-sm rounded-4 p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <?php if ($role != 'Peminjam'): ?><th>Peminjam</th><?php endif; ?>
                            <th>Alat</th>
                            <th>Pinjam</th>
                            <th>Kembali</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $batas = 10;
                        $hal = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
                        $awal = ($hal > 1) ? ($hal * $batas) - $batas : 0;

                        $where_arr = [];
                        if ($role == 'Peminjam') {
                            $where_arr[] = "p.id_user = '$id_usr'";
                        }
                        if (isset($_GET['cari']) && !empty($_GET['cari'])) {
                            $c = mysqli_real_escape_string($conn, $_GET['cari']);
                            $where_arr[] = "(u.nama_lengkap LIKE '%$c%' OR p.id_peminjaman IN (SELECT dp.id_peminjaman FROM detail_peminjaman dp JOIN alat a ON dp.id_alat=a.id_alat WHERE a.nama_alat LIKE '%$c%'))";
                        }
                        $where_sql = count($where_arr) > 0 ? "WHERE " . implode(" AND ", $where_arr) : "";

                        // hitung total data utk paginasi
                        $jml_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT p.id_peminjaman) as total FROM peminjaman p JOIN user u ON p.id_user=u.id_user $where_sql"))['total'];
                        $jml_hal = ceil($jml_data / $batas);

                        $no = $awal + 1;
                        $q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN user u ON p.id_user=u.id_user $where_sql ORDER BY p.id_peminjaman DESC LIMIT $awal, $batas");

                        if (mysqli_num_rows($q) > 0):
                            while ($r = mysqli_fetch_assoc($q)):
                                $st = $r['status_peminjaman'] ?? 'Pending';
                                $bg = $st == 'Pending' ? 'warning text-dark' : ($st == 'Disetujui' ? 'primary' : ($st == 'Ditolak' ? 'danger' : 'success'));
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <?php if ($role != 'Peminjam'): ?><td><strong><?= htmlspecialchars($r['nama_lengkap']); ?></strong></td><?php endif; ?>
                                    <td><?php $qd = mysqli_query($conn, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat=a.id_alat WHERE d.id_peminjaman='{$r['id_peminjaman']}'");
                                        while ($d = mysqli_fetch_assoc($qd)) echo "<div>• {$d['nama_alat']} ({$d['jumlah_pinjam']})</div>"; ?></td>
                                    <td><?= $r['tanggal_pinjam']; ?></td>
                                    <td><?= $r['tanggal_kembali']; ?></td>
                                    <td><span class="badge bg-<?= $bg; ?>"><?= $st; ?></span></td>
                                    <td class="text-center">
                                        <?php if ($role == 'Peminjam' && $st == 'Pending'): ?>
                                            <a href="peminjaman.php?aksi=hapus&id=<?= $r['id_peminjaman']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan?')">Batal</a>
                                        <?php elseif ($role == 'Admin'): ?>
                                            <a href="peminjaman.php?aksi=hapus&id=<?= $r['id_peminjaman']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">Hapus</a>
                                        <?php elseif ($role == 'Petugas' && $st == 'Pending'): ?>
                                            <a href="peminjaman.php?aksi=setujui&id=<?= $r['id_peminjaman']; ?>" class="btn btn-success btn-sm">ACC</a>
                                            <a href="peminjaman.php?aksi=tolak&id=<?= $r['id_peminjaman']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tolak?')">Tolak</a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Data peminjaman tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- paginasi -->
                <?php if ($jml_hal > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($hal <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="peminjaman.php?halaman=<?= $hal - 1; ?><?= isset($_GET['cari']) ? '&cari=' . urlencode($_GET['cari']) : ''; ?>">Prev</a>
                            </li>
                            <?php for ($x = 1; $x <= $jml_hal; $x++): ?>
                                <li class="page-item <?= ($hal == $x) ? 'active' : ''; ?>">
                                    <a class="page-link" href="peminjaman.php?halaman=<?= $x; ?><?= isset($_GET['cari']) ? '&cari=' . urlencode($_GET['cari']) : ''; ?>"><?= $x; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($hal >= $jml_hal) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="peminjaman.php?halaman=<?= $hal + 1; ?><?= isset($_GET['cari']) ? '&cari=' . urlencode($_GET['cari']) : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php elseif ($aksi == 'tambah'): ?>
            <!-- bagian form tambah keranjang checkout -->
            <div class="row">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-3">
                        <h4>Pilih Alat</h4>
                        <form method="POST" action="peminjaman.php?aksi=tambah_keranjang">
                            <div class="mb-3"><select name="id_alat" class="form-select" required><?php $a = mysqli_query($conn, "SELECT * FROM alat WHERE stok>0");
                                                                                                    while ($row = mysqli_fetch_assoc($a)) echo "<option value='{$row['id_alat']}'>{$row['nama_alat']} (Stok: {$row['stok']})</option>"; ?></select></div>
                            <div class="mb-3"><input type="number" name="jumlah_pinjam" class="form-control" value="1" min="1" max="5" required></div>
                            <button type="submit" class="btn btn-dark w-100">+ Keranjang</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm p-4 rounded-4">
                        <h4>Keranjang (<?= count($_SESSION['cart']); ?>/3)</h4>
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Alat</th>
                                    <th>Jml</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_SESSION['cart'])): ?><tr>
                                        <td colspan="3" class="text-center text-muted">Kosong</td>
                                    </tr>
                                    <?php else: foreach ($_SESSION['cart'] as $item): ?>
                                        <tr>
                                            <td><?= $item['nama_alat']; ?></td>
                                            <td><?= $item['jumlah']; ?></td>
                                            <td><a href="peminjaman.php?aksi=hapus_keranjang&id=<?= $item['id_alat']; ?>" class="btn btn-danger btn-sm">X</a></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <form method="POST" action="peminjaman.php?aksi=proses_checkout" class="border-top pt-3">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tanggal Pinjam</label>
                                    <input type="date" name="tanggal_pinjam" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tanggal Batas Kembali</label>
                                    <input type="date" name="tanggal_kembali" class="form-control" required>
                                </div>
                                <div class="d-flex justify-content-between"><a href="peminjaman.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-success">Ajukan Peminjaman</button></div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>