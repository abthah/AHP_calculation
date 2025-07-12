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

$message = '';

// Proses hitung ranking final
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hitung_ranking'])) {
    try {
        // Ambil bobot kriteria
        $bobot_kriteria = [];
        $stmt = $pdo->query('SELECT * FROM bobot_ahp');
        while ($row = $stmt->fetch()) {
            $bobot_kriteria[$row['nama_kriteria']] = $row['bobot'];
        }
        
        // Ambil data karyawan
        $karyawan = [];
        $stmt = $pdo->query('SELECT * FROM karyawan ORDER BY nama');
        while ($row = $stmt->fetch()) {
            $karyawan[] = $row;
        }
        
        // Hitung total bobot untuk setiap karyawan
        $ranking_data = [];
        foreach ($karyawan as $k) {
            $total_bobot = 0;
            
            // Ambil bobot alternatif untuk setiap kriteria
            foreach ($kriteria as $kr) {
                $stmt = $pdo->prepare('SELECT bobot FROM bobot_alternatif WHERE kriteria = ? AND id_karyawan = ?');
                $stmt->execute([$kr, $k['id']]);
                $bobot_alt = $stmt->fetchColumn();
                
                if ($bobot_alt && isset($bobot_kriteria[$kr])) {
                    $total_bobot += $bobot_kriteria[$kr] * $bobot_alt;
                }
            }
            
            $ranking_data[] = [
                'id_karyawan' => $k['id'],
                'nama' => $k['nama'],
                'total_bobot' => $total_bobot
            ];
        }
        
        // Urutkan berdasarkan total bobot (descending)
        usort($ranking_data, function($a, $b) {
            return $b['total_bobot'] <=> $a['total_bobot'];
        });
        
        // Simpan ranking ke database
        $pdo->exec('DELETE FROM ranking_final');
        for ($i = 0; $i < count($ranking_data); $i++) {
            $stmt = $pdo->prepare('INSERT INTO ranking_final (id_karyawan, total_bobot, ranking) VALUES (?, ?, ?)');
            $stmt->execute([$ranking_data[$i]['id_karyawan'], $ranking_data[$i]['total_bobot'], $i + 1]);
        }
        
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Ranking final berhasil dihitung dan disimpan!</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error menghitung ranking: ' . $e->getMessage() . '</div>';
    }
}

// Ambil data ranking final
$ranking_final = [];
try {
    $stmt = $pdo->query('SELECT rf.*, k.nama FROM ranking_final rf JOIN karyawan k ON rf.id_karyawan = k.id ORDER BY rf.ranking');
    while ($row = $stmt->fetch()) {
        $ranking_final[] = $row;
    }
} catch (Exception $e) {
    // Tabel ranking_final mungkin belum ada
}

// Ambil bobot kriteria untuk ditampilkan
$bobot_kriteria_display = [];
try {
    $stmt = $pdo->query('SELECT * FROM bobot_ahp ORDER BY nama_kriteria');
    while ($row = $stmt->fetch()) {
        $bobot_kriteria_display[] = $row;
    }
} catch (Exception $e) {
    // Tabel bobot_ahp mungkin belum ada
}

// Cek apakah data lengkap
$data_lengkap = true;
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM bobot_ahp');
    $total_bobot_kriteria = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(DISTINCT kriteria) as total FROM bobot_alternatif');
    $total_bobot_alternatif = $stmt->fetchColumn();
    
    if ($total_bobot_kriteria < 6 || $total_bobot_alternatif < 6) {
        $data_lengkap = false;
    }
} catch (Exception $e) {
    $data_lengkap = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Final - Sistem AHP</title>
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
        <h1 class="text-white mb-3"><i class="fas fa-trophy me-2"></i>Ranking Final - Sistem AHP</h1>
        <p class="text-white-50 mb-0">Hasil akhir perhitungan bobot kriteria dan alternatif</p>
    </div>
    
    <?= $message ?>
    
    <!-- Status Info -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Status Data:</strong><br>
        Bobot kriteria: <?= $total_bobot_kriteria ?? 0 ?>/6<br>
        Kriteria dengan bobot alternatif: <?= $total_bobot_alternatif ?? 0 ?>/6<br>
        Data lengkap: <?= $data_lengkap ? 'Ya' : 'Tidak' ?>
    </div>
    
    <!-- Bobot Kriteria -->
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-balance-scale me-2"></i>Bobot Kriteria</h5>
        <?php if (!empty($bobot_kriteria_display)): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kriteria</th>
                        <th>Bobot</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bobot_kriteria_display as $index => $bobot): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $label_kriteria[$bobot['nama_kriteria']] ?></td>
                        <td><?= number_format($bobot['bobot'], 4) ?></td>
                        <td><?= number_format($bobot['bobot'] * 100, 2) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Belum ada data bobot kriteria. Silakan isi data di halaman <a href="ahp_kriteria.php">AHP Kriteria</a> terlebih dahulu.
        </div>
        <?php endif; ?>
    </div>

    <!-- Tombol Hitung Ranking -->
    <?php if ($data_lengkap): ?>
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-calculator me-2"></i>Hitung Ranking Final</h5>
        <p class="text-muted">Klik tombol di bawah untuk menghitung ranking final berdasarkan bobot kriteria dan bobot alternatif.</p>
        <form method="POST">
            <input type="hidden" name="hitung_ranking" value="1">
            <div class="text-center">
                <button type="submit" class="btn btn-primary px-5 py-2">
                    <i class="fas fa-calculator me-2"></i>Hitung Ranking Final
                </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card p-4 mb-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Data belum lengkap!</strong><br>
            Pastikan semua data berikut sudah diisi:
            <ul class="mb-0 mt-2">
                <li>Bobot kriteria (6 kriteria) - <a href="ahp_kriteria.php">Klik di sini</a></li>
                <li>Bobot alternatif untuk setiap kriteria (6 kriteria) - <a href="ahp_alternatif.php">Klik di sini</a></li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ranking Final -->
    <?php if (!empty($ranking_final)): ?>
    <div class="card p-4 mb-4">
        <h5><i class="fas fa-medal me-2"></i>Ranking Final Karyawan</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ranking</th>
                        <th>Nama Karyawan</th>
                        <th>Total Bobot</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking_final as $index => $rank): ?>
                    <tr class="<?= $rank['ranking'] == 1 ? 'ranking-1' : ($rank['ranking'] == 2 ? 'ranking-2' : ($rank['ranking'] == 3 ? 'ranking-3' : '')) ?>">
                        <td>
                            <strong><?= $rank['ranking'] ?></strong>
                            <?php if ($rank['ranking'] <= 3): ?>
                            <i class="fas fa-trophy trophy-icon ms-2"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($rank['nama']) ?></td>
                        <td><?= number_format($rank['total_bobot'], 4) ?></td>
                        <td>
                            <?php if ($rank['ranking'] == 1): ?>
                                <span class="badge bg-warning text-dark">Juara 1</span>
                            <?php elseif ($rank['ranking'] == 2): ?>
                                <span class="badge bg-secondary">Juara 2</span>
                            <?php elseif ($rank['ranking'] == 3): ?>
                                <span class="badge bg-danger">Juara 3</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Ranking <?= $rank['ranking'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Keterangan -->
        <div class="mt-4">
            <h6><i class="fas fa-info-circle me-2"></i>Keterangan:</h6>
            <ul class="mb-0">
                <li><strong>Juara 1 (Ranking 1):</strong> Karyawan dengan total bobot tertinggi</li>
                <li><strong>Juara 2 (Ranking 2):</strong> Karyawan dengan total bobot kedua tertinggi</li>
                <li><strong>Juara 3 (Ranking 3):</strong> Karyawan dengan total bobot ketiga tertinggi</li>
            </ul>
        </div>
    </div>
    <?php else: ?>
    <div class="card p-4 mb-4">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Belum ada data ranking final!</strong><br>
            Silakan klik tombol "Hitung Ranking Final" di atas untuk menghitung dan menampilkan ranking karyawan.
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="ahp_kriteria.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kriteria
        </a>
        <a href="ahp_alternatif.php" class="btn btn-info me-2">
            <i class="fas fa-users me-2"></i>Ke Alternatif
        </a>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-table me-2"></i>Kembali ke Tabel
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 