<?php
require_once 'database_config.php';

class KaryawanModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Fungsi untuk menambah karyawan baru
    public function addKaryawan($data) {
        try {
            $sql = "INSERT INTO karyawan (no_urut, nama, kehadiran, komunikasi, tanggung_jawab, kerja_sama, prestasi, inisiatif, nilai_akhir) 
                    VALUES (:no_urut, :nama, :kehadiran, :komunikasi, :tanggung_jawab, :kerja_sama, :prestasi, :inisiatif, :nilai_akhir)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error adding karyawan: " . $e->getMessage());
            return false;
        }
    }
    
    // Fungsi untuk mendapatkan semua karyawan
    public function getAllKaryawan() {
        try {
            $sql = "SELECT * FROM karyawan ORDER BY no_urut";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting karyawan: " . $e->getMessage());
            return [];
        }
    }
    
    // Fungsi untuk mendapatkan karyawan berdasarkan ID
    public function getKaryawanById($id) {
        try {
            $sql = "SELECT * FROM karyawan WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting karyawan by ID: " . $e->getMessage());
            return false;
        }
    }
    
    // Fungsi untuk update karyawan
    public function updateKaryawan($id, $data) {
        try {
            $sql = "UPDATE karyawan SET 
                    no_urut = :no_urut, 
                    nama = :nama, 
                    kehadiran = :kehadiran, 
                    komunikasi = :komunikasi, 
                    tanggung_jawab = :tanggung_jawab, 
                    kerja_sama = :kerja_sama, 
                    prestasi = :prestasi, 
                    inisiatif = :inisiatif, 
                    nilai_akhir = :nilai_akhir 
                    WHERE id = :id";
            
            $data['id'] = $id;
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error updating karyawan: " . $e->getMessage());
            return false;
        }
    }
    
    // Fungsi untuk menghapus karyawan
    public function deleteKaryawan($id) {
        try {
            $sql = "DELETE FROM karyawan WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting karyawan: " . $e->getMessage());
            return false;
        }
    }
    
    // Fungsi untuk mendapatkan bobot kriteria dari database
    public function getBobotKriteria() {
        try {
            $sql = "SELECT * FROM bobot_kriteria ORDER BY id";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting bobot kriteria: " . $e->getMessage());
            return [];
        }
    }
    
    // Fungsi untuk mendapatkan bobot kriteria dalam format array
    public function getBobotKriteriaArray() {
        try {
            $sql = "SELECT nama_kriteria, bobot FROM bobot_kriteria";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bobot = [];
            foreach ($result as $row) {
                $bobot[$row['nama_kriteria']] = $row['bobot'] / 100; // Konversi ke decimal
            }
            return $bobot;
        } catch (PDOException $e) {
            error_log("Error getting bobot kriteria array: " . $e->getMessage());
            return [];
        }
    }
    
    // Fungsi untuk mendapatkan bobot AHP (dari perbandingan berpasangan)
    public function getBobotAHP() {
        try {
            // Ambil data perbandingan berpasangan
            $sql = "SELECT * FROM perbandingan_kriteria ORDER BY id";
            $stmt = $this->pdo->query($sql);
            $perbandingan = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($perbandingan)) {
                return $this->getBobotKriteriaManual();
            }
            
            // Buat matriks perbandingan
            $kriteria = ['kehadiran', 'komunikasi', 'tanggung_jawab', 'kerja_sama', 'prestasi', 'inisiatif'];
            $matriks = [];
            
            foreach ($kriteria as $i => $k1) {
                foreach ($kriteria as $j => $k2) {
                    if ($i == $j) {
                        $matriks[$k1][$k2] = 1;
                    } else {
                        $matriks[$k1][$k2] = 0;
                    }
                }
            }
            
            // Isi matriks dengan data perbandingan
            foreach ($perbandingan as $p) {
                $k1 = $p['kriteria1'];
                $k2 = $p['kriteria2'];
                $nilai = $p['nilai'];
                
                $matriks[$k1][$k2] = $nilai;
                $matriks[$k2][$k1] = 1 / $nilai;
            }
            
            // Hitung jumlah kolom
            $jumlah_kolom = [];
            foreach ($kriteria as $k) {
                $jumlah_kolom[$k] = 0;
                foreach ($kriteria as $k2) {
                    $jumlah_kolom[$k] += $matriks[$k2][$k];
                }
            }
            
            // Normalisasi matriks
            $matriks_normal = [];
            foreach ($kriteria as $k1) {
                foreach ($kriteria as $k2) {
                    $matriks_normal[$k1][$k2] = $matriks[$k1][$k2] / $jumlah_kolom[$k2];
                }
            }
            
            // Hitung rata-rata baris (bobot)
            $bobot = [];
            foreach ($kriteria as $k) {
                $total = 0;
                foreach ($kriteria as $k2) {
                    $total += $matriks_normal[$k][$k2];
                }
                $bobot[$k] = $total / count($kriteria);
            }
            
            return $bobot;
            
        } catch (PDOException $e) {
            error_log("Error calculating AHP weights: " . $e->getMessage());
            return $this->getBobotKriteriaManual();
        }
    }
    
    // Fungsi untuk mendapatkan bobot manual (default)
    public function getBobotKriteriaManual() {
        return [
            'kehadiran' => 0.20,
            'komunikasi' => 0.15,
            'tanggung_jawab' => 0.20,
            'kerja_sama' => 0.15,
            'prestasi' => 0.20,
            'inisiatif' => 0.10
        ];
    }
    
    // Fungsi untuk menyimpan nilai akhir ke database
    public function updateNilaiAkhir($id, $nilai_akhir) {
        try {
            $sql = "UPDATE karyawan SET nilai_akhir = :nilai_akhir WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'nilai_akhir' => $nilai_akhir
            ]);
        } catch (PDOException $e) {
            error_log("Error updating nilai akhir: " . $e->getMessage());
            return false;
        }
    }
    
    // Fungsi untuk mendapatkan ranking karyawan
    public function getRankingKaryawan() {
        try {
            $sql = "SELECT * FROM karyawan ORDER BY nilai_akhir DESC, nama ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting ranking: " . $e->getMessage());
            return [];
        }
    }
}
?> 