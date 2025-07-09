<?php
require_once 'database_config.php';

// Daftar kriteria
$kriteria = ['kehadiran', 'komunikasi', 'tanggung_jawab', 'kerja_sama', 'prestasi', 'inisiatif'];
$label_kriteria = [
    'kehadiran' => 'Kehadiran',
    'komunikasi' => 'Komunikasi',
    'tanggung_jawab' => 'Tanggung Jawab',
    'kerja_sama' => 'Kerja Sama',
    'prestasi' => 'Prestasi',
    'inisiatif' => 'Inisiatif'
];

$message = '';

// Ambil bobot kriteria dari hasil AHP
$bobot_kriteria = [];
try {
    $stmt = $pdo->query('SELECT * FROM bobot_ahp');
    while ($row = $stmt->fetch()) {
        $bobot_kriteria[$row['nama_kriteria']] = $row['bobot'];
    }
} catch (Exception $e) {
    $message = '<div class="alert alert-danger">Gagal mengambil bobot kriteria: ' . $e->getMessage() . '</div>';
}

// Ambil data karyawan dan nilai per kriteria
$karyawan = [];
try {
    $stmt = $pdo->query('SELECT * FROM karyawan ORDER BY nama');
    while ($row = $stmt->fetch()) {
        $karyawan[] = $row;
    }
} catch (Exception $e) {
    $message = '<div class="alert alert-danger">Gagal mengambil data karyawan: ' . $e->getMessage() . '</div>';
}

// Hitung nilai akhir hybrid
$ranking_data = [];
foreach ($karyawan as $k) {
    $total = 0;
    $detail = [];
    foreach ($kriteria as $kr) {
        $nilai = isset($k[$kr]) ? $k[$kr] : 0;
        $bobot = isset($bobot_kriteria[$kr]) ? $bobot_kriteria[$kr] : 0;
        $total += $bobot * $nilai;
        $detail[$kr] = [
            'nilai' => $nilai,
            'bobot' => $bobot
        ];
    }
    $ranking_data[] = [
        'id_karyawan' => $k['id'],
        'nama' => $k['nama'],
        'total' => $total,
        'detail' => $detail
    ];
}

// Urutkan ranking
usort($ranking_data, function($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Tambahkan ranking
foreach ($ranking_data as $i => &$d) {
    $d['ranking'] = $i + 1;
}
unset($d);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Hybrid - Sistem AHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header-section { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 30px; margin-bottom: 30px; }
        .table { background: white; border-radius: 10px; overflow: hidden; }
        .table th { background: linear-gradient(45deg, #667eea, #764ba2); color: white; text-align: center; font-weight: 600; }
        .table td { text-align: center; vertical-align: middle; }
        .ranking-1 { background-color: #ffd700; font-weight: bold; }
        .ranking-2 { background-color: #c0c0c0; font-weight: bold; }
        .ranking-3 { background-color: #cd7f32; font-weight: bold; }
        .trophy-icon { font-size: 1.2em; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="header-section text-center">
        <h1 class="text-white mb-3"><i class="fas fa-trophy me-2"></i>Ranking Hybrid - Sistem AHP</h1>
        <p class="text-white-50 mb-0">Perhitungan ranking berdasarkan bobot kriteria (AHP) dan nilai karyawan pada setiap kriteria</p>
    </div>
    <?= $message ?>
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-list-ol me-2"></i>Ranking Karyawan (Hybrid)</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ranking</th>
                        <th>Nama Karyawan</th>
                        <?php foreach ($kriteria as $kr): ?>
                        <th><?= $label_kriteria[$kr] ?><br><span class="text-secondary small">(Bobot: <?= isset($bobot_kriteria[$kr]) ? number_format($bobot_kriteria[$kr], 4) : '-' ?>)</span></th>
                        <?php endforeach; ?>
                        <th>Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking_data as $d): ?>
                    <tr class="<?= $d['ranking'] == 1 ? 'ranking-1' : ($d['ranking'] == 2 ? 'ranking-2' : ($d['ranking'] == 3 ? 'ranking-3' : '')) ?>">
                        <td>
                            <strong><?= $d['ranking'] ?></strong>
                            <?php if ($d['ranking'] <= 3): ?>
                            <i class="fas fa-trophy trophy-icon ms-2"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($d['nama']) ?></td>
                        <?php foreach ($kriteria as $kr): ?>
                        <td><?= $d['detail'][$kr]['nilai'] ?></td>
                        <?php endforeach; ?>
                        <td>
                            <span class="badge bg-primary fs-6">
                                <?= number_format($d['total'], 4) ?>
                            </span>
                            <br>
                            <span class="text-secondary small">
                                (<?= number_format($d['total'] * 100, 2) ?>%)
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="tabel_database.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Database
        </a>
        <a href="ranking_final_new.php" class="btn btn-success">
            <i class="fas fa-trophy me-2"></i>Ranking AHP Murni
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 