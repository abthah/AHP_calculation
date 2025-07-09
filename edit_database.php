<?php
require_once 'karyawan_model.php';

$model = new KaryawanModel($pdo);
$message = '';

// --- PILIH SALAH SATU: ---
// $bobot = $model->getBobotKriteriaManual(); // Bobot manual (default aktif)
// $bobot = $model->getBobotKriteriaManual();

$bobot = $model->getBobotAHP(); // Bobot AHP (aktifkan dengan hapus tanda komen)
// $bobot = $model->getBobotAHP();

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $data = [
        'kehadiran' => floatval($_POST['kehadiran']),
        'komunikasi' => floatval($_POST['komunikasi']),
        'tanggung_jawab' => floatval($_POST['tanggung_jawab']),
        'kerja_sama' => floatval($_POST['kerja_sama']),
        'prestasi' => floatval($_POST['prestasi']),
        'inisiatif' => floatval($_POST['inisiatif']),
        'nilai_akhir' => 0 // Akan dihitung ulang
    ];
    
    $kriteria = [
        'kehadiran' => ['rata_rata' => $data['kehadiran'], 'bobot' => $bobot['kehadiran']],
        'komunikasi' => ['rata_rata' => $data['komunikasi'], 'bobot' => $bobot['komunikasi']],
        'tanggung_jawab' => ['rata_rata' => $data['tanggung_jawab'], 'bobot' => $bobot['tanggung_jawab']],
        'kerja_sama' => ['rata_rata' => $data['kerja_sama'], 'bobot' => $bobot['kerja_sama']],
        'prestasi' => ['rata_rata' => $data['prestasi'], 'bobot' => $bobot['prestasi']],
        'inisiatif' => ['rata_rata' => $data['inisiatif'], 'bobot' => $bobot['inisiatif']]
    ];
    
    $data['nilai_akhir'] = hitungNilaiAkhir($kriteria);
    
    if ($model->updateKaryawan($id, $data)) {
        $message = '<div class="alert alert-success">Data berhasil diperbarui!</div>';
    } else {
        $message = '<div class="alert alert-danger">Gagal memperbarui data!</div>';
    }
}

$karyawan_list = $model->getAllKaryawanRanked(); // Menggunakan method dengan ranking
$bobot_list = $model->getBobotKriteria();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Karyawan - Database</title>
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
            padding: 10px 30px;
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
            padding: 10px 30px;
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
        .edit-form {
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .edit-form:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .nilai-preview {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 1.2em;
        }
        .ranking-badge {
            font-weight: bold;
            font-size: 1.1em;
            padding: 8px 12px;
            border-radius: 50%;
            min-width: 40px;
            display: inline-block;
            margin-bottom: 10px;
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
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="header-section text-center">
            <h1 class="text-white mb-3">
                <i class="fas fa-edit me-3"></i>
                Edit Data Karyawan
            </h1>
            <p class="text-white-50 mb-0">Ubah nilai kriteria penilaian karyawan</p>
        </div>

        <!-- Message -->
        <?= $message ?>

        <!-- Bobot Kriteria Info -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Bobot Kriteria</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($bobot_list as $bobot): ?>
                    <div class="col-md-2 mb-2">
                        <div class="badge bg-primary fs-6 p-2">
                            <?= ucfirst($bobot['nama_kriteria']) ?>: <?= $bobot['bobot'] ?>%
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Form Edit -->
        <div class="row">
            <?php foreach ($karyawan_list as $karyawan): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="edit-form">
                    <form method="POST" class="edit-karyawan-form">
                        <input type="hidden" name="id" value="<?= $karyawan['id'] ?>">
                        
                        <div class="text-center mb-3">
                            <div class="ranking-badge <?= $karyawan['ranking'] <= 3 ? 'ranking-' . $karyawan['ranking'] : 'ranking-other' ?>">
                                Ranking #<?= $karyawan['ranking'] ?>
                            </div>
                            <h5 class="text-primary">
                                <i class="fas fa-user me-2"></i>
                                <?= htmlspecialchars($karyawan['nama']) ?>
                            </h5>
                            <small class="text-muted">No. <?= $karyawan['no_urut'] ?></small>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Kehadiran</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="kehadiran" value="<?= $karyawan['kehadiran'] ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Komunikasi</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="komunikasi" value="<?= $karyawan['komunikasi'] ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Tanggung Jawab</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="tanggung_jawab" value="<?= $karyawan['tanggung_jawab'] ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Kerja Sama</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="kerja_sama" value="<?= $karyawan['kerja_sama'] ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Prestasi</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="prestasi" value="<?= $karyawan['prestasi'] ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Inisiatif</label>
                                <input type="number" step="0.01" class="form-control kriteria-input" 
                                       name="inisiatif" value="<?= $karyawan['inisiatif'] ?>" required>
                            </div>
                        </div>

                        <div class="nilai-preview mb-3">
                            <div>Nilai Akhir Saat Ini:</div>
                            <div class="nilai-akhir-display"><?= number_format($karyawan['nilai_akhir'], 2) ?></div>
                        </div>

                        <button type="submit" name="update" class="btn btn-save w-100">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="tabel_database.php" class="btn btn-back text-white">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Tabel
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview nilai akhir secara real-time
        document.querySelectorAll('.kriteria-input').forEach(input => {
            input.addEventListener('input', function() {
                const form = this.closest('form');
                const inputs = form.querySelectorAll('.kriteria-input');
                const display = form.querySelector('.nilai-akhir-display');
                
                // Ambil nilai dari input
                const kehadiran = parseFloat(inputs[0].value) || 0;
                const komunikasi = parseFloat(inputs[1].value) || 0;
                const tanggungJawab = parseFloat(inputs[2].value) || 0;
                const kerjaSama = parseFloat(inputs[3].value) || 0;
                const prestasi = parseFloat(inputs[4].value) || 0;
                const inisiatif = parseFloat(inputs[5].value) || 0;
                
                // Bobot kriteria (sesuai dengan database)
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
                
                display.textContent = nilaiAkhir.toFixed(2);
            });
        });
    </script>
</body>
</html> 