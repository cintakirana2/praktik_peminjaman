<?php
session_start();
include '../../config/koneksi.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Cek password dengan enkripsi MD5
        if (md5($password) == $row['password']) {
            $_SESSION['id_user']      = $row['id_user'];
            $_SESSION['username']     = $row['username'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role']         = $row['role'];

            $id_u = $_SESSION['id_user'];
            $ket = "User dengan ID " . $id_u . " berhasil login";
            mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_u', '$ket', NOW())");

            header("Location: ../../index.php");
            exit;
        } else {
            $error = "Password yang kamu masukkan salah!";
        }
    } else {
        $error = "Username tidak terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman</title>
    <link rel="stylesheet" href="/praktik_peminjaman/assets/css/bootstrap.min.css">
</head>

<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-bold text-center mb-1">Peminjaman Alat Sekolah</h4>
                        <p class="text-muted text-center small mb-4">Silakan masuk ke akun Anda</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show py-2 small"><?= $error ?>
                                <button type="button" class="btn-close small" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>