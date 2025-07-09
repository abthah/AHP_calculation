    <?php
    require_once 'database_config.php';

    // Daftar kriteria
    $kriteria_list = ['kehadiran', 'komunikasi', 'tanggung_jawab', 'kerja_sama', 'prestasi', 'inisiatif'];
    $label_kriteria = [
        'kehadiran' => 'Kehadiran',
        'komunikasi' => 'Komunikasi',
        'tanggung_jawab' => 'Tanggung Jawab',
        'kerja_sama' => 'Kerja Sama',
        'prestasi' => 'Prestasi',
        'inisiatif' => 'Inisiatif'
    ];

    // Ambil bobot kriteria dari hasil AHP
    $bobot_kriteria = [];
    try {
        $stmt = $pdo->query('SELECT * FROM bobot_ahp');
        while ($row = $stmt->fetch()) {
            $bobot_kriteria[$row['nama_kriteria']] = $row['bobot'];
        }
    } catch (Exception $e) {
        $bobot_kriteria = [];
    }

    // Ambil data karyawan dan nilai per kriteria
    $karyawan_list = [];
    try {
        $stmt = $pdo->query('SELECT * FROM karyawan ORDER BY nama');
        while ($row = $stmt->fetch()) {
            $karyawan_list[] = $row;
        }
    } catch (Exception $e) {
        $karyawan_list = [];
    }

    // Hitung nilai akhir hybrid dan ranking
    $ranking_data = [];
    foreach ($karyawan_list as $k) {
        $total = 0;
        foreach ($kriteria_list as $kr) {
            $nilai = isset($k[$kr]) ? $k[$kr] : 0;
            $bobot = isset($bobot_kriteria[$kr]) ? $bobot_kriteria[$kr] : 0;
            $total += $bobot * $nilai;
        }
        $ranking_data[] = [
            'id' => $k['id'],
            'nama' => $k['nama'],
            'nilai_akhir' => $total,
            'kriteria' => $k,
        ];
    }
    // Urutkan ranking
    usort($ranking_data, function($a, $b) {
        return $b['nilai_akhir'] <=> $a['nilai_akhir'];
    });
    // Tambahkan ranking
    foreach ($ranking_data as $i => &$d) {
        $d['ranking'] = $i + 1;
    }
    unset($d);

    // Statistik
    $stats = [];
    $stats['total_karyawan'] = count($ranking_data);
    $stats['nilai_tertinggi'] = count($ranking_data) ? $ranking_data[0]['nilai_akhir'] : 0;
    $stats['nilai_terendah'] = count($ranking_data) ? $ranking_data[count($ranking_data)-1]['nilai_akhir'] : 0;
    $stats['rata_rata_nilai'] = count($ranking_data) ? array_sum(array_column($ranking_data, 'nilai_akhir'))/count($ranking_data) : 0;
    $stats['top_3'] = array_slice($ranking_data, 0, 3);

    // Buat array bobot untuk ditampilkan
    $bobot_display = [];
    foreach ($bobot_kriteria as $nama_kriteria => $bobot) {
        $bobot_display[$nama_kriteria] = $bobot * 100; // persen
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_hybrid'])) {
        foreach ($ranking_data as $karyawan) {
            $stmt = $pdo->prepare('UPDATE karyawan SET nilai_akhir = ? WHERE id = ?');
            $stmt->execute([$karyawan['nilai_akhir'], $karyawan['id']]);
        }
        $message = '<div class="alert alert-success">Nilai akhir hybrid berhasil disimpan ke database!</div>';
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistem Penilaian Karyawan Hybrid - Database</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); backdrop-filter: blur(10px); background: rgba(255,255,255,0.95); }
            .table { border-radius: 10px; overflow: hidden; }
            .table th { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; font-weight: 600; text-align: center; }
            .table td { vertical-align: middle; text-align: center; }
            .stats-card { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
            .btn-action { border-radius: 25px; padding: 8px 20px; font-weight: 500; transition: all 0.3s ease; }
            .btn-edit { background: linear-gradient(45deg, #ff6b6b, #ee5a24); border: none; color: white; }
            .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,107,107,0.4); color: white; }
            .header-section { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 30px; margin-bottom: 30px; }
            .bobot-badge { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8em; margin: 2px; }
            .ranking-badge { font-weight: bold; font-size: 1.1em; padding: 8px 12px; border-radius: 50%; min-width: 40px; display: inline-block; }
            .ranking-1 { background: linear-gradient(45deg, #ffd700, #ffed4e); color: #333; }
            .ranking-2 { background: linear-gradient(45deg, #c0c0c0, #e5e5e5); color: #333; }
            .ranking-3 { background: linear-gradient(45deg, #cd7f32, #daa520); color: white; }
            .ranking-other { background: linear-gradient(45deg, #6c757d, #495057); color: white; }
            .top-performer { background: linear-gradient(45deg, #28a745, #20c997); color: white; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
            .top-performer h5 { margin-bottom: 10px; }
            .top-performer-item { background: rgba(255,255,255,0.2); border-radius: 8px; padding: 10px; margin: 5px 0; }
            .kriteria-value { font-size: 0.9em; font-weight: 500; }
        </style>
    </head>
    <body>
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="header-section text-center">
                <h1 class="text-white mb-3">
                    <i class="fas fa-trophy me-3"></i>
                    Sistem Penilaian Karyawan (Hybrid)
                </h1>
                <p class="text-white-50 mb-0">Data diurutkan berdasarkan nilai akhir tertinggi (Hybrid)</p>
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
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Karyawan dengan Nilai Kriteria (Hybrid)</h5>
                    <a href="tambah_karyawan.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Tambah Karyawan
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <form method="POST" class="mb-3">
                            <button type="submit" name="simpan_hybrid" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Simpan Nilai Akhir ke Database
                            </button>
                        </form>
                        <?= $message ?? '' ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <?php foreach ($kriteria_list as $kr): ?>
                                    <th><?= $label_kriteria[$kr] ?></th>
                                    <?php endforeach; ?>
                                    <th>Nilai Akhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ranking_data)): ?>
                                <tr>
                                    <td colspan="<?= 5 + count($kriteria_list) ?>" class="text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Belum ada data karyawan. Silakan tambah karyawan terlebih dahulu.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ranking_data as $index => $karyawan): ?>
                                <tr>
                                    <td>
                                        <?php if ($karyawan['ranking'] <= 3): ?>
                                            <span class="ranking-badge ranking-<?= $karyawan['ranking'] ?>">
                                                <?= $karyawan['ranking'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="ranking-badge ranking-other">
                                                <?= $karyawan['ranking'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($karyawan['nama']) ?></strong></td>
                                    <?php foreach ($kriteria_list as $kr): ?>
                                    <td class="kriteria-value">
                                        <?= isset($karyawan['kriteria'][$kr]) ? number_format($karyawan['kriteria'][$kr], 2) : '-' ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <span class="badge bg-primary fs-6">
                                            <?= number_format($karyawan['nilai_akhir'], 4) ?>
                                        </span>
                                        <br>
                                        <span class="text-secondary small">
                                            (<?= number_format($karyawan['nilai_akhir'] * 100, 2) ?>%)
                                        </span>
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

            <!-- Menu Navigasi -->
        
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html> 