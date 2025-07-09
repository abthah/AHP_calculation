<?php
require_once 'database_config.php';

// Daftar kriteria
$kriteria = [
    'kehadiran',
    'komunikasi', 
    'tanggung_jawab',
    'kerja_sama',
    'prestasi',
    'inisiatif'
];

$label_kriteria = [
    'kehadiran' => 'Kehadiran',
    'komunikasi' => 'Komunikasi',
    'tanggung_jawab' => 'Tanggung Jawab',
    'kerja_sama' => 'Kerja Sama',
    'prestasi' => 'Prestasi',
    'inisiatif' => 'Inisiatif'
];

// Ambil data karyawan
$karyawan = [];
$error_message = '';
$n_karyawan = 0;

try {
    $stmt = $pdo->query('SELECT * FROM karyawan ORDER BY nama');
    while ($row = $stmt->fetch()) {
        $karyawan[] = $row;
    }
    $n_karyawan = count($karyawan);
} catch (Exception $e) {
    $error_message = "Error mengambil data karyawan: " . $e->getMessage();
}

// Skala Saaty AHP
$skala = [
    1 => 'Sama baik',
    2 => 'Antara 1 dan 3',
    3 => 'Sedikit lebih baik',
    4 => 'Antara 3 dan 5',
    5 => 'Lebih baik',
    6 => 'Antara 5 dan 7',
    7 => 'Jauh lebih baik',
    8 => 'Antara 7 dan 9',
    9 => 'Sangat mutlak lebih baik'
];

$message = '';
$selected_kriteria = isset($_GET['kriteria']) ? $_GET['kriteria'] : 'kehadiran';

// Proses simpan input pairwise alternatif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pairwise_alternatif'])) {
    try {
        $kriteria_input = $_POST['kriteria'];
        $stmt = $pdo->prepare("DELETE FROM pairwise_alternatif WHERE kriteria = ?");
        $stmt->execute([$kriteria_input]);
        
        foreach ($_POST['pair'] as $key => $val) {
            $parts = explode('||', $key);
            if (count($parts) !== 2) continue;
            list($k1, $k2) = $parts;
            $nilai = floatval($val);
            
            $stmt = $pdo->prepare('INSERT INTO pairwise_alternatif (kriteria, karyawan1, karyawan2, nilai) VALUES (?, ?, ?, ?)');
            $stmt->execute([$kriteria_input, $k1, $k2, $nilai]);
            
            if ($nilai != 0) {
                $stmt = $pdo->prepare('INSERT INTO pairwise_alternatif (kriteria, karyawan1, karyawan2, nilai) VALUES (?, ?, ?, ?)');
                $stmt->execute([$kriteria_input, $k2, $k1, 1/$nilai]);
            }
        }
        $message = '<div class="alert alert-success">Data perbandingan alternatif berhasil disimpan!</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error menyimpan data: ' . $e->getMessage() . '</div>';
    }
}

// Ambil data pairwise alternatif untuk kriteria yang dipilih
$pairwise_alternatif = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM pairwise_alternatif WHERE kriteria = ?');
    $stmt->execute([$selected_kriteria]);
    while ($row = $stmt->fetch()) {
        $pairwise_alternatif[$row['karyawan1'].'||'.$row['karyawan2']] = $row['nilai'];
    }
} catch (Exception $e) {
    $message .= '<div class="alert alert-warning">Error mengambil data pairwise: ' . $e->getMessage() . '</div>';
}

// Matriks pairwise alternatif
$matriks_alternatif = array_fill(0, $n_karyawan, array_fill(0, $n_karyawan, 1));
foreach ($karyawan as $i => $k1) {
    foreach ($karyawan as $j => $k2) {
        if ($i == $j) {
            $matriks_alternatif[$i][$j] = 1;
        } else {
            $matriks_alternatif[$i][$j] = isset($pairwise_alternatif[$k1['id'].'||'.$k2['id']]) ? $pairwise_alternatif[$k1['id'].'||'.$k2['id']] : 1;
        }
    }
}

// Hitung bobot alternatif jika data lengkap
$hitung_alternatif = true;
for ($i = 0; $i < $n_karyawan; $i++) {
    for ($j = 0; $j < $n_karyawan; $j++) {
        if (!isset($matriks_alternatif[$i][$j])) $hitung_alternatif = false;
    }
}

$bobot_alternatif = array_fill(0, $n_karyawan, 0);
$lambda_max_alt = $ci_alt = $cr_alt = 0;
$ri_alt = [0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49, 1.51, 1.48, 1.56, 1.57, 1.59, 1.605, 1.61, 1.615, 1.62, 1.625, 1.63]; // RI untuk n=1-20

if ($hitung_alternatif && $n_karyawan > 1) {
    // Hitung jumlah kolom
    $col_sum_alt = array_fill(0, $n_karyawan, 0);
    for ($j = 0; $j < $n_karyawan; $j++) {
        for ($i = 0; $i < $n_karyawan; $i++) {
            $col_sum_alt[$j] += $matriks_alternatif[$i][$j];
        }
    }
    
    // Normalisasi matriks & hitung bobot
    $matriks_norm_alt = array_fill(0, $n_karyawan, array_fill(0, $n_karyawan, 0));
    for ($i = 0; $i < $n_karyawan; $i++) {
        for ($j = 0; $j < $n_karyawan; $j++) {
            $matriks_norm_alt[$i][$j] = $matriks_alternatif[$i][$j] / ($col_sum_alt[$j] ?: 1);
        }
        $bobot_alternatif[$i] = array_sum($matriks_norm_alt[$i]) / $n_karyawan;
    }
    
    // Lambda max
    $lambda_max_alt = 0;
    for ($i = 0; $i < $n_karyawan; $i++) {
        $row_sum = 0;
        for ($j = 0; $j < $n_karyawan; $j++) {
            $row_sum += $matriks_alternatif[$i][$j] * $bobot_alternatif[$j];
        }
        $lambda_max_alt += $row_sum / $bobot_alternatif[$i];
    }
    $lambda_max_alt /= $n_karyawan;
    
    $ci_alt = ($lambda_max_alt - $n_karyawan) / ($n_karyawan - 1);
    $cr_alt = isset($ri_alt[$n_karyawan]) && $ri_alt[$n_karyawan] > 0 ? $ci_alt / $ri_alt[$n_karyawan] : 0;
    
    // Simpan bobot alternatif ke database
    try {
        $stmt = $pdo->prepare("DELETE FROM bobot_alternatif WHERE kriteria = ?");
        $stmt->execute([$selected_kriteria]);
        
        for ($i = 0; $i < $n_karyawan; $i++) {
            $stmt = $pdo->prepare('INSERT INTO bobot_alternatif (kriteria, id_karyawan, bobot) VALUES (?, ?, ?)');
            $stmt->execute([$selected_kriteria, $karyawan[$i]['id'], $bobot_alternatif[$i]]);
        }
    } catch (Exception $e) {
        $message .= '<div class="alert alert-warning">Error menyimpan bobot alternatif: ' . $e->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AHP Alternatif - Perbandingan Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header-section { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 30px; margin-bottom: 30px; }
        .table { background: white; border-radius: 10px; overflow: hidden; }
        .table th { background: linear-gradient(45deg, #667eea, #764ba2); color: white; text-align: center; font-weight: 600; }
        .table td { text-align: center; vertical-align: middle; }
        .diagonal { background-color: #f8f9fa; font-weight: bold; }
        .nilai-cell { font-weight: 600; color: #495057; }
        .kriteria-selector { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-section text-center">
        <h1 class="text-white mb-3"><i class="fas fa-users me-2"></i>Analytic Hierarchy Process (AHP) - Alternatif</h1>
        <p class="text-white-50 mb-0">Perbandingan berpasangan antar karyawan berdasarkan kriteria</p>
    </div>
    
    <?= $message ?>
    
    <!-- Status Info -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Status Database:</strong><br>
        Jumlah karyawan: <?= $n_karyawan ?><br>
        Kriteria yang dipilih: <?= $label_kriteria[$selected_kriteria] ?><br>
        <?php if ($error_message): ?>
        Error: <?= $error_message ?>
        <?php endif; ?>
    </div>
    
    <?php if ($n_karyawan == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Tidak ada data karyawan!</strong><br>
        Silakan tambahkan data karyawan terlebih dahulu di halaman <a href="tambah_karyawan.php">Tambah Karyawan</a>
        <br><br>
        <a href="test_database.php" class="btn btn-info">Test Database</a>
    </div>
    <?php else: ?>
    
    <!-- Kriteria Selector -->
    <div class="kriteria-selector">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Pilih Kriteria</h5>
        <div class="row">
            <?php foreach ($kriteria as $k): ?>
            <div class="col-md-2 mb-2">
                <a href="?kriteria=<?= $k ?>" class="btn btn-<?= $selected_kriteria == $k ? 'primary' : 'outline-primary' ?> w-100">
                    <?= $label_kriteria[$k] ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h5><i class="fas fa-balance-scale me-2"></i>1. Input Perbandingan Berpasangan Karyawan - <?= $label_kriteria[$selected_kriteria] ?></h5>
        <?php if ($n_karyawan > 1): ?>
        <form method="POST">
            <input type="hidden" name="pairwise_alternatif" value="1">
            <input type="hidden" name="kriteria" value="<?= $selected_kriteria ?>">
            <table class="table table-bordered align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>Karyawan 1</th>
                        <th>Perbandingan</th>
                        <th>Karyawan 2</th>
                    </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < $n_karyawan; $i++): ?>
                    <?php for ($j = $i+1; $j < $n_karyawan; $j++): ?>
                        <?php $key = $karyawan[$i]['id'].'||'.$karyawan[$j]['id']; ?>
                        <tr>
                            <td><?= htmlspecialchars($karyawan[$i]['nama']) ?></td>
                            <td>
                                <select name="pair[<?= $key ?>]" class="form-select" required>
                                    <?php foreach ($skala as $num => $desc): ?>
                                        <option value="<?= $num ?>" <?= (isset($pairwise_alternatif[$key]) && $pairwise_alternatif[$key]==$num)?'selected':'' ?>><?= $num ?> - <?= $desc ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($karyawan[$j]['nama']) ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php endfor; ?>
                </tbody>
            </table>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary px-5 py-2">Simpan Perbandingan</button>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Belum ada data karyawan atau hanya ada 1 karyawan. Silakan tambahkan data karyawan terlebih dahulu.
            <br><br>
            <a href="tambah_karyawan.php" class="btn btn-primary">Tambah Karyawan</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($n_karyawan > 1): ?>
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-table me-2"></i>2. Matriks Perbandingan Berpasangan - <?= $label_kriteria[$selected_kriteria] ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <?php foreach ($karyawan as $index => $k): ?>
                        <th><?= htmlspecialchars($k['nama']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($karyawan as $i => $k1): ?>
                    <tr>
                        <td class="diagonal">
                            <strong><?= htmlspecialchars($k1['nama']) ?></strong>
                        </td>
                        <?php foreach ($karyawan as $j => $k2): ?>
                        <td class="<?= $i == $j ? 'diagonal' : 'nilai-cell' ?>">
                            <?php if ($i == $j): ?>
                                <strong>1</strong>
                            <?php else: ?>
                                <?= number_format($matriks_alternatif[$i][$j], 2) ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($hitung_alternatif): ?>
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-chart-bar me-2"></i>3. Hasil Perhitungan Bobot Alternatif - <?= $label_kriteria[$selected_kriteria] ?></h5>
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Ranking</th>
                    <th>Karyawan</th>
                    <th>Bobot</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $ranking = [];
                for ($i = 0; $i < $n_karyawan; $i++) {
                    $ranking[] = [
                        'index' => $i,
                        'nama' => $karyawan[$i]['nama'],
                        'bobot' => $bobot_alternatif[$i]
                    ];
                }
                // Urutkan berdasarkan bobot (descending)
                usort($ranking, function($a, $b) {
                    return $b['bobot'] <=> $a['bobot'];
                });
                ?>
                <?php foreach ($ranking as $rank => $item): ?>
                <tr>
                    <td><strong><?= $rank + 1 ?></strong></td>
                    <td><?= htmlspecialchars($item['nama']) ?></td>
                    <td><?= number_format($item['bobot'], 4) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($n_karyawan > 2): ?>
        <h5 class="mt-4">Uji Konsistensi</h5>
        <ul>
            <li><strong>Lambda Max (λmax):</strong> <?= number_format($lambda_max_alt, 4) ?></li>
            <li><strong>Consistency Index (CI):</strong> <?= number_format($ci_alt, 4) ?></li>
            <li><strong>Random Index (RI):</strong> <?= isset($ri_alt[$n_karyawan]) ? $ri_alt[$n_karyawan] : 'Tidak tersedia untuk n > 20' ?></li>
            <li><strong>Consistency Ratio (CR):</strong> <?= number_format($cr_alt, 4) ?></li>
        </ul>
        <?php if (isset($ri_alt[$n_karyawan]) && $ri_alt[$n_karyawan] > 0): ?>
            <?php if ($cr_alt < 0.10): ?>
                <div class="alert alert-success">Konsistensi baik (CR &lt; 0.10)</div>
            <?php else: ?>
                <div class="alert alert-danger">Konsistensi kurang baik (CR &ge; 0.10), mohon perbaiki perbandingan!</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning">Uji konsistensi tidak tersedia untuk jumlah karyawan > 20. Nilai CI: <?= number_format($ci_alt, 4) ?></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="ahp_kriteria.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kriteria
        </a>
        <a href="ranking_final_new.php" class="btn btn-success me-2">
            <i class="fas fa-trophy me-2"></i>Lihat Ranking Final
        </a>
        <a href="tabel_database.php" class="btn btn-primary">
            <i class="fas fa-table me-2"></i>Kembali ke Tabel
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 