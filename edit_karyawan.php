<?php
require_once 'database_config.php';

$kriteria_list = ['kehadiran', 'komunikasi', 'tanggung_jawab', 'kerja_sama', 'prestasi', 'inisiatif'];
$label_kriteria = [
    'kehadiran' => 'Kehadiran',
    'komunikasi' => 'Komunikasi',
    'tanggung_jawab' => 'Tanggung Jawab',
    'kerja_sama' => 'Kerja Sama',
    'prestasi' => 'Prestasi',
    'inisiatif' => 'Inisiatif'
];

$karyawan_list = [];
try {
    $stmt = $pdo->query('SELECT * FROM karyawan ORDER BY nama');
    while ($row = $stmt->fetch()) {
        $karyawan_list[] = $row;
    }
} catch (Exception $e) {
    $karyawan_list = [];
}


$message = '';

// Ambil data karyawan berdasarkan ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: tabel_database_hybrid.php');
    exit;
}

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


// Ambil data karyawan
$stmt = $pdo->prepare('SELECT * FROM karyawan WHERE id = ?');
$stmt->execute([$id]);
$karyawan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$karyawan) {
    header('Location: tabel_database_hybrid.php');
    exit;
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $data = [
        'kehadiran' => floatval($_POST['kehadiran']),
        'komunikasi' => floatval($_POST['komunikasi']),
        'tanggung_jawab' => floatval($_POST['tanggung_jawab']),
        'kerja_sama' => floatval($_POST['kerja_sama']),
        'prestasi' => floatval($_POST['prestasi']),
        'inisiatif' => floatval($_POST['inisiatif'])
    ];
    // Update data ke database
    $stmt = $pdo->prepare('UPDATE karyawan SET kehadiran=?, komunikasi=?, tanggung_jawab=?, kerja_sama=?, prestasi=?, inisiatif=? WHERE id=?');
    $success = $stmt->execute([
        $data['kehadiran'],
        $data['komunikasi'],
        $data['tanggung_jawab'],
        $data['kerja_sama'],
        $data['prestasi'],
        $data['inisiatif'],
        $id
    ]);
    if ($success) {
        $message = '<div class="alert alert-success">Data berhasil diperbarui!</div>';
        // Refresh data karyawan
        $stmt = $pdo->prepare('SELECT * FROM karyawan WHERE id = ?');
        $stmt->execute([$id]);
        $karyawan = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = '<div class="alert alert-danger">Gagal memperbarui data!</div>';
    }
}

// Ambil bobot kriteria untuk info
$bobot_kriteria = [];
try {
    $stmt = $pdo->query('SELECT * FROM bobot_ahp');
    while ($row = $stmt->fetch()) {
        $bobot_kriteria[$row['nama_kriteria']] = $row['bobot'];
    }
} catch (Exception $e) {
    $bobot_kriteria = [];
}

$bobot_display = [];
foreach ($bobot_kriteria as $nama_kriteria => $bobot) {
    $bobot_display[$nama_kriteria] = $bobot * 100; // persen
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan - <?= htmlspecialchars($karyawan['nama']) ?></title>
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
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-save {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-back {
            background: linear-gradient(45deg, #6c757d, #495057);
            border: none;
            border-radius: 25px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        .header-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .nilai-preview {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 1.3em;
            margin-top: 20px;
        }
        .bobot-info {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .karyawan-info {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="header-section text-center">
            <h1 class="text-white mb-3">
                <i class="fas fa-edit me-3"></i>
                Edit Data Karyawan
            </h1>
            <p class="text-white-50 mb-0">Ubah nilai kriteria penilaian</p>
        </div>

        <!-- Message -->
        <?= $message ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Form Edit Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <!-- Info Karyawan -->
                        <div class="karyawan-info">
                            <h4><i class="fas fa-user me-2"></i><?= htmlspecialchars($karyawan['nama']) ?></h4>
                            <p class="mb-0">No. Urut: <?= $karyawan['no_urut'] ?></p>
                        </div>

                        <!-- Bobot Kriteria Info -->
                        <div class="bobot-info">
                            <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>Bobot Kriteria:</h6>
                            <div class="row">
                                <?php foreach ($bobot_display as $kriteria => $bobot): ?>
                                <div class="col-md-3 mb-2">
                                    <span class="badge bg-primary">
                                        <?= ucfirst(str_replace('_', ' ', $kriteria)) ?>: <?= number_format($bobot, 1) ?>%
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <form method="POST" id="editKaryawanForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kehadiran</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="kehadiran" value="<?= $karyawan['kehadiran'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Komunikasi</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="komunikasi" value="<?= $karyawan['komunikasi'] ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggung Jawab</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="tanggung_jawab" value="<?= $karyawan['tanggung_jawab'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kerja Sama</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="kerja_sama" value="<?= $karyawan['kerja_sama'] ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prestasi</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="prestasi" value="<?= $karyawan['prestasi'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Inisiatif</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="inisiatif" value="<?= $karyawan['inisiatif'] ?>" required>
                                </div>
                            </div>

                            <div class="nilai-preview">
                                <div>Nilai Akhir (Hybrid):</div>
                                <?php
                                // Hitung nilai akhir hybrid real-time (menggunakan bobot dari tabel bobot_ahp)
                                $total_hybrid = 0;
                                foreach ($kriteria_list as $kr) {
                                    $nilai = isset($karyawan[$kr]) ? floatval($karyawan[$kr]) : 0;
                                    $bobot = isset($bobot_kriteria[$kr]) ? floatval($bobot_kriteria[$kr]) : 0;
                                    $total_hybrid += $bobot * $nilai;
                                }
                                ?>
                                <div><span class="badge bg-primary fs-6"><?= number_format($total_hybrid, 4) ?></span></div>
                                <div class="text-secondary small">(<?= number_format($total_hybrid * 100, 2) ?>%)</div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="update" class="btn btn-save text-white me-3">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                                <a href="tabel_database_hybrid.php" class="btn btn-back text-white">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview nilai akhir secara real-time
        function updateNilaiAkhir() {
            const inputs = document.querySelectorAll('.kriteria-input');
            const preview = document.getElementById('nilaiAkhirPreview');
            
            const kehadiran = parseFloat(inputs[0].value) || 0;
            const komunikasi = parseFloat(inputs[1].value) || 0;
            const tanggungJawab = parseFloat(inputs[2].value) || 0;
            const kerjaSama = parseFloat(inputs[3].value) || 0;
            const prestasi = parseFloat(inputs[4].value) || 0;
            const inisiatif = parseFloat(inputs[5].value) || 0;
            
            // Bobot kriteria
            const bobot = {
                kehadiran: 30,
                komunikasi: 10,
                tanggungJawab: 20,
                kerjaSama: 10,
                prestasi: 20,
                inisiatif: 10
            };
            
            // Hitung nilai akhir
            const totalNilaiTerbobot = (kehadiran * bobot.kehadiran) + 
                                     (komunikasi * bobot.komunikasi) + 
                                     (tanggungJawab * bobot.tanggungJawab) + 
                                     (kerjaSama * bobot.kerjaSama) + 
                                     (prestasi * bobot.prestasi) + 
                                     (inisiatif * bobot.inisiatif);
            
            const totalBobot = bobot.kehadiran + bobot.komunikasi + bobot.tanggungJawab + 
                              bobot.kerjaSama + bobot.prestasi + bobot.inisiatif;
            
            const nilaiAkhir = totalBobot > 0 ? totalNilaiTerbobot / totalBobot : 0;
            
            preview.textContent = nilaiAkhir.toFixed(2);
        }

        // Event listener untuk input
        document.querySelectorAll('.kriteria-input').forEach(input => {
            input.addEventListener('input', updateNilaiAkhir);
        });

        // Update saat halaman dimuat
        updateNilaiAkhir();
    </script>
</body>
</html> 