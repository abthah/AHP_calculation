# Employee Performance Evaluation Decision Support System

This web application is designed to assist in the employee evaluation and ranking process using the AHP (Analytical Hierarchy Process) and Hybrid methods. The system helps HR or managers make objective and structured data-driven decisions.

## Main Features
- Employee data management
- Evaluation criteria management
- Employee ranking calculation using AHP and Hybrid methods
- Automatic display of ranking results

## System Requirements
- Web server (recommended: XAMPP, Laragon, or similar)
- PHP 7.x or newer
- MySQL/MariaDB

## Installation & Local Setup

1. **Clone or Download the Repository**
   - Clone this repository or download the ZIP file, then extract it to your web server folder (e.g., `htdocs` in XAMPP or `www` in Laragon).

2. **Import the Database**
   - Open phpMyAdmin or your preferred database tool.
   - Create a new database, e.g., `sistem_penilaian`.
   - Import the `sistem_penilaian.sql` file from the project folder into the new database.

3. **Configure Database Connection**
   - Open the `database_config.php` file.
   - Adjust the database configuration (host, username, password, database name) according to your local setup.

   ```php
   // Example configuration
   $host = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'sistem_penilaian';
   ```

4. **Run the Application**
   - Open your browser and go to `http://localhost/your_project_folder/index.php`
   - You can now use the application to manage employee data and perform evaluations.

## Main File Structure
- `index.php` : Main application page
- `ahp_kriteria.php` : AHP criteria management
- `ahp_alternatif.php` : Employee/alternative management
- `ranking_final_new.php` : Final ranking results
- `ranking_hybrid.php` : Hybrid method ranking results
- `tambah_karyawan.php` : Add employee form
- `edit_karyawan.php` : Edit employee data
- `karyawan_model.php` : Employee data model
- `database_config.php` : Database configuration
- `sistem_penilaian.sql` : SQL file for the database

## Notes
- Make sure your web server and database are running before accessing the application.
- If you encounter database connection errors, double-check your settings in `database_config.php`.

## License
This project is created for learning and portfolio purposes.

---

If you have any questions or issues, please contact the author via this repository page. 