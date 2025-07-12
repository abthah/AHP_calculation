<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}
require_once 'database_config.php';

// Ambil data karyawan dengan ranking dan nilai kriteria
$karyawan_list = [];
try {
    $stmt = $pdo->query('SELECT k.*, COALESCE(rf.total_bobot, 0) as nilai_akhir, rf.ranking 
                        FROM karyawan k 
                        LEFT JOIN ranking_final rf ON k.id = rf.id_karyawan 
                        ORDER BY rf.ranking ASC, k.nama ASC');
    while ($row = $stmt->fetch()) {
        $karyawan_list[] = $row;
    }
} catch (Exception $e) {
    // Jika tabel ranking_final belum ada, ambil data karyawan saja
    $stmt = $pdo->query('SELECT k.*, 0 as nilai_akhir, NULL as ranking FROM karyawan k ORDER BY k.nama ASC');
    while ($row = $stmt->fetch()) {
        $karyawan_list[] = $row;
    }
}

// Ambil nilai kriteria untuk setiap karyawan
$kriteria_values = [];
$kriteria_list = ['kehadiran', 'komunikasi', 'tanggung_jawab', 'kerja_sama', 'prestasi', 'inisiatif'];

foreach ($karyawan_list as $karyawan) {
    $kriteria_values[$karyawan['id']] = [];
    foreach ($kriteria_list as $kriteria) {
        try {
            $stmt = $pdo->prepare('SELECT bobot FROM bobot_alternatif WHERE kriteria = ? AND id_karyawan = ?');
            $stmt->execute([$kriteria, $karyawan['id']]);
            $bobot = $stmt->fetchColumn();
            $kriteria_values[$karyawan['id']][$kriteria] = $bobot ? number_format($bobot, 4) : '-';
        } catch (Exception $e) {
            $kriteria_values[$karyawan['id']][$kriteria] = '-';
        }
    }
}

// Hitung statistik
$stats = [];
try {
    // Total karyawan
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM karyawan');
    $stats['total_karyawan'] = $stmt->fetchColumn();
    
    // Nilai tertinggi, terendah, rata-rata
    $stmt = $pdo->query('SELECT MAX(total_bobot) as max_nilai, MIN(total_bobot) as min_nilai, AVG(total_bobot) as avg_nilai FROM ranking_final');
    $nilai_stats = $stmt->fetch();
    $stats['nilai_tertinggi'] = $nilai_stats['max_nilai'] ?? 0;
    $stats['nilai_terendah'] = $nilai_stats['min_nilai'] ?? 0;
    $stats['rata_rata_nilai'] = $nilai_stats['avg_nilai'] ?? 0;
    
    // Top 3 performers
    $stmt = $pdo->query('SELECT k.nama, rf.total_bobot as nilai_akhir 
                        FROM ranking_final rf 
                        JOIN karyawan k ON rf.id_karyawan = k.id 
                        ORDER BY rf.ranking ASC 
                        LIMIT 3');
    $stats['top_3'] = [];
    while ($row = $stmt->fetch()) {
        $stats['top_3'][] = $row;
    }
} catch (Exception $e) {
    $stats['total_karyawan'] = count($karyawan_list);
    $stats['nilai_tertinggi'] = 0;
    $stats['nilai_terendah'] = 0;
    $stats['rata_rata_nilai'] = 0;
    $stats['top_3'] = [];
}

// Ambil bobot kriteria
$bobot_list = [];
try {
    $stmt = $pdo->query('SELECT nama_kriteria, bobot FROM bobot_ahp ORDER BY nama_kriteria');
    while ($row = $stmt->fetch()) {
        $bobot_list[$row['nama_kriteria']] = $row['bobot'];
    }
} catch (Exception $e) {
    // Jika tabel bobot_ahp belum ada
    $bobot_list = [];
}

// Buat array bobot untuk ditampilkan
$bobot_display = [];
foreach ($bobot_list as $nama_kriteria => $bobot) {
    $bobot_display[$nama_kriteria] = $bobot * 100; // Konversi ke persen untuk tampilan
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penilaian Karyawan - Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            text-align: center;
        }
        .table td {
            vertical-align: middle;
            text-align: center;
        }
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-action {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            color: white;
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
            color: white;
        }
        .header-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .bobot-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin: 2px;
        }
        .ranking-badge {
            font-weight: bold;
            font-size: 1.1em;
            padding: 8px 12px;
            border-radius: 50%;
            min-width: 40px;
            display: inline-block;
        }
        .ranking-1 {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #333;
        }
        .ranking-2 {
            background: linear-gradient(45deg, #c0c0c0, #e5e5e5);
            color: #333;
        }
        .ranking-3 {
            background: linear-gradient(45deg, #cd7f32, #daa520);
            color: white;
        }
        .ranking-other {
            background: linear-gradient(45deg, #6c757d, #495057);
            color: white;
        }
        .top-performer {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .top-performer h5 {
            margin-bottom: 10px;
        }
        .top-performer-item {
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
        }
        .kriteria-value {
            font-size: 0.9em;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="header-section text-center">
            <h1 class="text-white mb-3">
                <i class="fas fa-trophy me-3"></i>
                Sistem Penilaian Karyawan
            </h1>
            <p class="text-white-50 mb-0">Data diurutkan berdasarkan nilai akhir tertinggi</p>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h4><?= $stats['total_karyawan'] ?></h4>
                    <p class="mb-0">Total Karyawan</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-trophy fa-2x mb-2"></i>
                    <h4><?= number_format($stats['nilai_tertinggi'], 2) ?></h4>
                    <p class="mb-0">Nilai Tertinggi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h4><?= number_format($stats['rata_rata_nilai'], 2) ?></h4>
                    <p class="mb-0">Rata-rata Nilai</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <h4><?= number_format($stats['nilai_terendah'], 2) ?></h4>
                    <p class="mb-0">Nilai Terendah</p>
                </div>
            </div>
        </div>

        <!-- Top 3 Performers -->
        <?php if (!empty($stats['top_3'])): ?>
        <div class="top-performer">
            <h5><i class="fas fa-medal me-2"></i>Top 3 Performers</h5>
            <div class="row">
                <?php foreach ($stats['top_3'] as $index => $performer): ?>
                <div class="col-md-4">
                    <div class="top-performer-item text-center">
                        <div class="ranking-badge ranking-<?= $index + 1 ?> mb-2">
                            <?= $index + 1 ?>
                        </div>
                        <div class="fw-bold"><?= htmlspecialchars($performer['nama']) ?></div>
                        <div class="small"><?= number_format($performer['nilai_akhir'], 2) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bobot Kriteria -->
        <?php if (!empty($bobot_display)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-weight-hanging me-2"></i>Bobot Kriteria Penilaian</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($bobot_display as $kriteria => $bobot): ?>
                    <div class="col-md-2">
                        <div class="bobot-badge text-center">
                            <div class="fw-bold"><?= ucfirst(str_replace('_', ' ', $kriteria)) ?></div>
                            <div><?= number_format($bobot, 1) ?>%</div>
                    </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabel Karyawan -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Karyawan dengan Nilai Kriteria</h5>
                <a href="tambah_karyawan.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Tambah Karyawan
                    </a>
                </div>
            <div class="text-center mt-4">
            <div class="btn-group" role="group">
                <a href="ahp_kriteria.php" class="btn btn-primary me-2">
                    <i class="fas fa-balance-scale me-2"></i>AHP Kriteria
                </a>
                <a href="ahp_alternatif.php" class="btn btn-info me-2">
                    <i class="fas fa-users me-2"></i>AHP Alternatif
                </a>
                <a href="ranking_final_new.php" class="btn btn-success me-2">
                    <i class="fas fa-trophy me-2"></i>Ranking Final
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
                
            </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ranking</th>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Kehadiran</th>
                                <th>Komunikasi</th>
                                <th>Tanggung Jawab</th>
                                <th>Kerja Sama</th>
                                <th>Prestasi</th>
                                <th>Inisiatif</th>
                                <th>Nilai Akhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($karyawan_list)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Belum ada data karyawan. Silakan tambah karyawan terlebih dahulu.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($karyawan_list as $index => $karyawan): ?>
                            <tr>
                                <td>
                                    <?php if ($karyawan['ranking']): ?>
                                        <span class="ranking-badge ranking-<?= $karyawan['ranking'] <= 3 ? $karyawan['ranking'] : 'other' ?>">
                                        <?= $karyawan['ranking'] ?>
                                    </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= htmlspecialchars($karyawan['nama']) ?></strong></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['kehadiran'] ?></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['komunikasi'] ?></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['tanggung_jawab'] ?></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['kerja_sama'] ?></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['prestasi'] ?></td>
                                <td class="kriteria-value"><?= $kriteria_values[$karyawan['id']]['inisiatif'] ?></td>
                                <td>
                                    <?php if ($karyawan['nilai_akhir'] > 0): ?>
                                    <span class="badge bg-primary fs-6">
                                            <?= number_format($karyawan['nilai_akhir'], 4) ?>
                                        </span>
                                        <br>
                                        <span class="text-secondary small">
                                            (<?= number_format($karyawan['nilai_akhir'] * 100, 2) ?>%)
                                    </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_karyawan.php?id=<?= $karyawan['id'] ?>" class="btn btn-edit btn-action btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 