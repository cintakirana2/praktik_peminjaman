<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: modules/auth/login.php");
    exit;
}

include 'config/koneksi.php';

$role = $_SESSION['role'];
$nama = $_SESSION['nama_lengkap'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Peminjaman Alat</title>
    <link rel="stylesheet" href="/praktik_peminjaman/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Peminjaman Alat Sekolah</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Halo, <strong><?= htmlspecialchars($nama); ?></strong> <span class="badge bg-primary"><?= $role; ?></span></span>
                <a href="modules/auth/logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin logout dari sistem?');">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <?php if ($role == 'Admin'): ?>
            <!-- DASHBOARD ADMIN -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="p-4 bg-white rounded-4 shadow-sm border-start border-4 border-primary">
                        <h2>Dashboard Admin</h2>
                        <p class="text-muted mb-0">Kelola master data sistem, pengguna, kategori, alat, dan peminjaman/pengembalian.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Kelola Peminjaman</h5>
                            <p class="card-text">Tambah, edit, atau hapus data peminjaman alat.</p>
                            <a href="modules/peminjaman/peminjaman.php" class="btn btn-primary btn-sm">Buka Menu Peminjaman</a>
                        </div>
                    </div>
                </div>
                <!-- TAMBAHAN TOMBOL KELOLA PENGEMBALIAN UNTUK ADMIN -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Kelola Pengembalian</h5>
                            <p class="card-text">Pantau dan kelola riwayat pengembalian alat.</p>
                            <a href="modules/pengembalian/pengembalian.php" class="btn btn-primary btn-sm">Buka Menu Pengembalian</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Kelola Alat & Kategori</h5>
                            <p class="card-text">Tambah, edit, atau hapus data inventaris alat sekolah.</p>
                            <a href="modules/alat/alat.php" class="btn btn-primary btn-sm">Buka Menu Alat</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Manajemen User</h5>
                            <p class="card-text">Atur akun Admin, Petugas, dan Peminjam.</p>
                            <a href="modules/user/user.php" class="btn btn-primary btn-sm">Buka Menu User</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Log Aktivitas</h5>
                            <p class="card-text">Pantau riwayat aksi yang dilakukan user.</p>
                            <a href="modules/log/log.php" class="btn btn-primary btn-sm">Lihat Log</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($role == 'Petugas'): ?>
            <!-- DASHBOARD PETUGAS -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="p-4 bg-white rounded-4 shadow-sm border-start border-4 border-warning">
                        <h2>Dashboard Petugas</h2>
                        <p class="text-muted mb-0">Validasi peminjaman masuk, pantau pengembalian, dan cetak laporan.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Validasi Peminjaman</h5>
                            <p class="card-text">Setujui (ACC) atau tolak permohonan pinjam alat dari siswa.</p>
                            <a href="modules/peminjaman/peminjaman.php" class="btn btn-warning btn-sm text-white">Kelola Peminjaman</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Pengembalian Alat</h5>
                            <p class="card-text">Terima pengembalian alat dan pulihkan stok inventaris.</p>
                            <a href="modules/pengembalian/pengembalian.php" class="btn btn-warning btn-sm text-white">Proses Pengembalian</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Cetak Laporan</h5>
                            <p class="card-text">Buat dan cetak laporan rekapitulasi peminjaman alat.</p>
                            <a href="modules/laporan/laporan.php" class="btn btn-success btn-sm">Buka Menu Laporan</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- DASHBOARD PEMINJAM (SISWA) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="p-4 bg-white rounded-4 shadow-sm border-start border-4 border-success">
                        <h2>Dashboard Peminjam</h2>
                        <p class="text-muted mb-0">Lihat daftar alat, ajukan peminjaman, dan pantau status riwayatmu di sini.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Ajukan Peminjaman</h5>
                            <p class="card-text">Pilih alat yang tersedia dan ajukan form peminjaman baru.</p>
                            <a href="modules/peminjaman/peminjaman.php?aksi=tambah" class="btn btn-success btn-sm">+ Buat Pengajuan Pinjam</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="card-title text-muted">Riwayat Status Peminjaman</h5>
                            <p class="card-text">Cek status pengajuanmu (apakah masih Pending, Dipinjam, atau Selesai).</p>
                            <a href="modules/peminjaman/peminjaman.php" class="btn btn-success btn-sm">Lihat Riwayat & Daftar Pinjaman</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>