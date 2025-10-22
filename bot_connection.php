<?php
// Detail koneksi ke database
$servername = "localhost"; // Biasanya "localhost" untuk server lokal
$username = "jualkode_dikaputrarahmawan009";        // Ganti dengan username database Anda
$password = "G@m@techn0";            // Ganti dengan password database Anda
$dbname = "jualkode_mng_finance"; // Ganti dengan nama database yang ingin Anda hubungkan

// Membuat koneksi menggunakan mysqli_connect()
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Memeriksa koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "Koneksi berhasil dibuat. Variabel koneksi adalah \$conn.";

// Setelah selesai menggunakan, koneksi harus ditutup
// mysqli_close($conn); 
?>