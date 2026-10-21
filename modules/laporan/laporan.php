<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 'Peminjam') {
    header("Location: ../../index.php");
    exit;
}
include '../../config/koneksi.php';

function tanggal_indo($tanggal)
{
    if (!$tanggal) return '-';
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Peminjaman Alat</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }

            .table {
                border-color: #000 !important;
            }

            .table thead th {
                background-color: #343a40 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 shadow-sm no-print">
        <a class="navbar-brand fw-bold" href="../../index.php">Peminjaman Alat Sekolah</a>
        <div>
            <a href="../../index.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
            <button onclick="window.print()" class="btn btn-success btn-sm">🖨️ Cetak Laporan</button>
        </div>
    </nav>

    <div class="container my-5">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-uppercase m-0">Laporan Rekapitulasi Peminjaman Alat</h4>
            <p class="text-muted m-0">Sistem Informasi Peminjaman Sarana dan Prasarana Sekolah</p>
            <hr class="w-50 mx-auto mt-2">
        </div>

        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th width="22%">Nama Peminjam</th>
                            <th width="33%">Daftar Alat & Jumlah</th>
                            <th width="13%">Tgl Pinjam</th>
                            <th width="13%">Tgl Kembali</th>
                            <th width="14%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN user u ON p.id_user=u.id_user ORDER BY p.id_peminjaman DESC");
                        while ($r = mysqli_fetch_assoc($q)):
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($r['nama_lengkap']); ?></strong></td>
                                <td>
                                    <ul class="mb-0 ps-3">
                                        <?php
                                        $qd = mysqli_query($conn, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat=a.id_alat WHERE d.id_peminjaman='{$r['id_peminjaman']}'");
                                        while ($d = mysqli_fetch_assoc($qd)) {
                                            echo "<li>" . htmlspecialchars($d['nama_alat']) . " <span class='text-muted'>(" . $d['jumlah_pinjam'] . " unit)</span></li>";
                                        }
                                        ?>
                                    </ul>
                                </td>
                                <td class="text-center"><?= tanggal_indo($r['tanggal_pinjam']); ?></td>
                                <td class="text-center"><?= tanggal_indo($r['tanggal_kembali']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $status = $r['status_peminjaman'] ?? ($r['status'] ?? '-');
                                    if ($status == 'Pending') {
                                        $badge_color = 'bg-warning text-dark';
                                    } elseif ($status == 'Disetujui') {
                                        $badge_color = 'bg-primary';
                                    } elseif ($status == 'Ditolak') {
                                        $badge_color = 'bg-danger';
                                    } else {
                                        $badge_color = 'bg-success';
                                    }
                                    ?>
                                    <span class="badge <?= $badge_color; ?> px-2 py-1"><?= $status; ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5">
                <div class="col-8"></div>
                <div class="col-4 text-center">
                    <p class="mb-1">Kendal, <?= tanggal_indo(date('Y-m-d')); ?></p>
                    <p class="mb-5">Petugas Sarpras,</p>
                    <br><br>
                    <p class="fw-bold text-decoration-underline m-0"><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>