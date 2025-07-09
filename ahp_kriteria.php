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
$n = count($kriteria);

// Skala Saaty AHP
$skala = [
    1 => 'Sama penting',
    2 => 'Antara 1 dan 3',
    3 => 'Sedikit lebih penting',
    4 => 'Antara 3 dan 5',
    5 => 'Lebih penting',
    6 => 'Antara 5 dan 7',
    7 => 'Jauh lebih penting',
    8 => 'Antara 7 dan 9',
    9 => 'Sangat mutlak lebih penting'
];

$message = '';

// Proses simpan input pairwise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pairwise'])) {
    $pdo->exec('DELETE FROM pairwise_kriteria');
    foreach ($_POST['pair'] as $key => $val) {
        $parts = explode('||', $key);
        if (count($parts) !== 2) continue; // Lewati jika key tidak valid
        list($k1, $k2) = $parts;
        $nilai = floatval($val);
        $stmt = $pdo->prepare('INSERT INTO pairwise_kriteria (kriteria1, kriteria2, nilai) VALUES (?, ?, ?)');
        $stmt->execute([$k1, $k2, $nilai]);
        if ($nilai != 0) {
            $stmt = $pdo->prepare('INSERT INTO pairwise_kriteria (kriteria1, kriteria2, nilai) VALUES (?, ?, ?)');
            $stmt->execute([$k2, $k1, 1/$nilai]);
        }
    }
    $message = '<div class="alert alert-success">Data perbandingan berhasil disimpan!</div>';
}

// Ambil data pairwise
$pairwise = [];
$stmt = $pdo->query('SELECT * FROM pairwise_kriteria');
while ($row = $stmt->fetch()) {
    $pairwise[$row['kriteria1'].'||'.$row['kriteria2']] = $row['nilai'];
}

// Matriks pairwise
$matriks = array_fill(0, $n, array_fill(0, $n, 1));
foreach ($kriteria as $i => $k1) {
    foreach ($kriteria as $j => $k2) {
        if ($i == $j) {
            $matriks[$i][$j] = 1;
        } else {
            $matriks[$i][$j] = isset($pairwise[$k1.'||'.$k2]) ? $pairwise[$k1.'||'.$k2] : 1;
        }
    }
}

// Hitung bobot AHP jika data lengkap
$hitung_ahp = true;
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $n; $j++) {
        if (!isset($matriks[$i][$j])) $hitung_ahp = false;
    }
}

$bobot = array_fill(0, $n, 0);
$lambda_max = $ci = $cr = 0;
$ri = 1.24; // Untuk n=6
if ($hitung_ahp) {
    // Hitung jumlah kolom
    $col_sum = array_fill(0, $n, 0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $col_sum[$j] += $matriks[$i][$j];
        }
    }
    // Normalisasi matriks & hitung bobot
    $matriks_norm = array_fill(0, $n, array_fill(0, $n, 0));
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $matriks_norm[$i][$j] = $matriks[$i][$j] / ($col_sum[$j] ?: 1);
        }
        $bobot[$i] = array_sum($matriks_norm[$i]) / $n;
    }
    // Lambda max
    $lambda_max = 0;
    for ($i = 0; $i < $n; $i++) {
        $row_sum = 0;
        for ($j = 0; $j < $n; $j++) {
            $row_sum += $matriks[$i][$j] * $bobot[$j];
        }
        $lambda_max += $row_sum / $bobot[$i];
    }
    $lambda_max /= $n;
    $ci = ($lambda_max - $n) / ($n - 1);
    $cr = $ri > 0 ? $ci / $ri : 0;
    // Simpan bobot ke tabel bobot_ahp
    $pdo->exec('DELETE FROM bobot_ahp');
    for ($i = 0; $i < $n; $i++) {
        $stmt = $pdo->prepare('INSERT INTO bobot_ahp (nama_kriteria, bobot) VALUES (?, ?)');
        $stmt->execute([$kriteria[$i], $bobot[$i]]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AHP Kriteria</title>
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
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-section text-center">
        <h1 class="text-white mb-3"><i class="fas fa-balance-scale me-2"></i>Analytic Hierarchy Process (AHP) - Kriteria</h1>
        <p class="text-white-50 mb-0">Input, matriks, dan hasil AHP dalam satu halaman</p>
    </div>
    <?= $message ?>
    <div class="card p-4 mb-4">
        <h5>1. Input Perbandingan Berpasangan</h5>
        <form method="POST">
            <input type="hidden" name="pairwise" value="1">
            <table class="table table-bordered align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>Kriteria 1</th>
                        <th>Perbandingan</th>
                        <th>Kriteria 2</th>
                    </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < count($kriteria); $i++): ?>
                    <?php for ($j = $i+1; $j < count($kriteria); $j++): ?>
                        <?php $key = $kriteria[$i].'||'.$kriteria[$j]; ?>
                        <tr>
                            <td><?= $label_kriteria[$kriteria[$i]] ?></td>
                            <td>
                                <select name="pair[<?= $key ?>]" class="form-select" required>
                                    <?php foreach ($skala as $num => $desc): ?>
                                        <option value="<?= $num ?>" <?= (isset($pairwise[$key]) && $pairwise[$key]==$num)?'selected':'' ?>><?= $num ?> - <?= $desc ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= $label_kriteria[$kriteria[$j]] ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php endfor; ?>
                </tbody>
            </table>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary px-5 py-2">Simpan Perbandingan</button>
            </div>
        </form>
    </div>

    <div class="card p-4 mb-4">
        <h5>2. Matriks Perbandingan Berpasangan</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kriteria</th>
                        <?php foreach ($kriteria as $index => $k): ?>
                        <th><?= $label_kriteria[$k] ?> (C<?= $index + 1 ?>)</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria as $i => $k1): ?>
                    <tr>
                        <td class="diagonal">
                            <strong><?= $label_kriteria[$k1] ?> (C<?= $i + 1 ?>)</strong>
                        </td>
                        <?php foreach ($kriteria as $j => $k2): ?>
                        <td class="<?= $i == $j ? 'diagonal' : 'nilai-cell' ?>">
                            <?php if ($i == $j): ?>
                                <strong>1</strong>
                            <?php else: ?>
                                <?= number_format($matriks[$i][$j], 2) ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <h6><i class="fas fa-info-circle me-2"></i>Keterangan:</h6>
            <ul class="list-unstyled">
                <li><strong>C1</strong> = Kehadiran</li>
                <li><strong>C2</strong> = Komunikasi</li>
                <li><strong>C3</strong> = Tanggung Jawab</li>
                <li><strong>C4</strong> = Kerja Sama</li>
                <li><strong>C5</strong> = Prestasi</li>
                <li><strong>C6</strong> = Inisiatif</li>
            </ul>
        </div>
    </div>

    <?php if ($hitung_ahp): ?>
    <div class="card p-4 mb-4">
        <h5>3. Hasil Perhitungan Bobot & Konsistensi</h5>
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kriteria</th>
                    <th>Bobot</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < $n; $i++): ?>
                <tr>
                    <td><?= $label_kriteria[$kriteria[$i]] ?></td>
                    <td><?= number_format($bobot[$i], 4) ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <h5 class="mt-4">Uji Konsistensi</h5>
        <ul>
            <li><strong>Lambda Max (λmax):</strong> <?= number_format($lambda_max, 4) ?></li>
            <li><strong>Consistency Index (CI):</strong> <?= number_format($ci, 4) ?></li>
            <li><strong>Random Index (RI):</strong> <?= $ri ?></li>
            <li><strong>Consistency Ratio (CR):</strong> <?= number_format($cr, 4) ?></li>
        </ul>
        <?php if ($cr < 0.10): ?>
            <div class="alert alert-success">Konsistensi baik (CR &lt; 0.10)</div>
        <?php else: ?>
            <div class="alert alert-danger">Konsistensi kurang baik (CR &ge; 0.10), mohon perbaiki perbandingan!</div>
        <?php endif;?>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="ahp_alternatif.php" class="btn btn-success me-2">
            <i class="fas fa-users me-2"></i>Perbandingan Alternatif
        </a>
        <a href="tabel_database.php" class="btn btn-primary me-2">
            <i class="fas fa-table me-2"></i>Kembali ke Tabel
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 