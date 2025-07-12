<?php
// Konfigurasi Database
$host = 'localhost';
$dbname = 'sistem_penilaian';
$username = 'root';
$password = '';
$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk menghitung nilai akhir berdasarkan kriteria dengan bobot
function hitungNilaiAkhir($kriteria) {
    $total_nilai_terbobot = 0;
    $total_bobot = 0;
    
    foreach ($kriteria as $nama_kriteria => $data) {
        $nilai = $data['rata_rata'];
        $bobot = $data['bobot'];
        
        $total_nilai_terbobot += ($nilai * $bobot);
        $total_bobot += $bobot;
    }
    
    return $total_bobot > 0 ? $total_nilai_terbobot / $total_bobot : 0;
}
?> 