<?php
require_once 'database_config.php';
require_once 'karyawan_model.php';

$model = new KaryawanModel($pdo);
$message = '';

// --- PILIH SALAH SATU: ---
// $bobot = $model->getBobotKriteriaManual(); // Bobot manual (default aktif)
// $bobot = $model->getBobotKriteriaManual();

$bobot = $model->getBobotAHP(); // Bobot AHP (aktifkan dengan hapus tanda komen)

// Proses tambah karyawan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $data = [
        'no_urut' => intval($_POST['no_urut']),
        'nama' => trim($_POST['nama']),
        'kehadiran' => floatval($_POST['kehadiran']),
        'komunikasi' => floatval($_POST['komunikasi']),
        'tanggung_jawab' => floatval($_POST['tanggung_jawab']),
        'kerja_sama' => floatval($_POST['kerja_sama']),
        'prestasi' => floatval($_POST['prestasi']),
        'inisiatif' => floatval($_POST['inisiatif']),
        'nilai_akhir' => 0 // Akan dihitung
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
    
    if ($model->addKaryawan($data)) {
        $message = '<div class="alert alert-success">Karyawan berhasil ditambahkan!</div>';
        // Reset form
        $_POST = array();
    } else {
        $message = '<div class="alert alert-danger">Gagal menambahkan karyawan!</div>';
    }
}

$bobot_list = $model->getBobotKriteria();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan Baru - Database</title>
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
        .btn-add {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
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
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="header-section text-center">
            <h1 class="text-white mb-3">
                <i class="fas fa-user-plus me-3"></i>
                Tambah Karyawan Baru
            </h1>
            <p class="text-white-50 mb-0">Masukkan data karyawan baru</p>
        </div>

        <!-- Message -->
        <?= $message ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Form Data Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <!-- Bobot Kriteria Info -->
                        <div class="bobot-info">
                            <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>Bobot Kriteria:</h6>
                            <div class="row">
                                <?php foreach ($bobot_list as $bobot): ?>
                                <div class="col-md-2 mb-1">
                                    <span class="badge bg-primary">
                                        <?= ucfirst($bobot['nama_kriteria']) ?>: <?= $bobot['bobot'] ?>%
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <form method="POST" id="addKaryawanForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. Urut</label>
                                    <input type="number" class="form-control" name="no_urut" 
                                           value="<?= $_POST['no_urut'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Karyawan</label>
                                    <input type="text" class="form-control" name="nama" 
                                           value="<?= $_POST['nama'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kehadiran</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="kehadiran" value="<?= $_POST['kehadiran'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Komunikasi</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="komunikasi" value="<?= $_POST['komunikasi'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggung Jawab</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="tanggung_jawab" value="<?= $_POST['tanggung_jawab'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kerja Sama</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="kerja_sama" value="<?= $_POST['kerja_sama'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prestasi</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="prestasi" value="<?= $_POST['prestasi'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Inisiatif</label>
                                    <input type="number" step="0.01" class="form-control kriteria-input" 
                                           name="inisiatif" value="<?= $_POST['inisiatif'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="nilai-preview">
                                <div>Nilai Akhir yang Akan Dihitung:</div>
                                <div id="nilaiAkhirPreview">0.00</div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="add" class="btn btn-add text-white me-3">
                                    <i class="fas fa-save me-2"></i>Simpan Karyawan
                                </button>
                                <a href="tabel_database.php" class="btn btn-back text-white">
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