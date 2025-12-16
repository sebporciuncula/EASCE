<?php
require_once '../config.php';
// checkAuth(['admin']); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';

    // --- ADD SINGLE STUDENT (UPDATED) ---
    if ($action === 'add_single') {
        $student_id = trim($_POST['student_id']);
        $fullname = trim($_POST['fullname']);
        $department = trim($_POST['department']);
        $program = trim($_POST['program']);
        $year_level = $_POST['year_level'];
        // KUNIN ANG STATUS
        $status = $_POST['status'] ?? 'active'; 

        if (empty($student_id) || empty($fullname)) {
            header("Location: students.php?error=Student ID and Name are required");
            exit;
        }

        try {
            // Updated SQL to include status
            $stmt = $conn->prepare("INSERT INTO masterlist_students (student_id, fullname, department, program, year_level, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $fullname, $department, $program, $year_level, $status]);

            header("Location: students.php?success=Student added successfully");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                header("Location: students.php?error=Student ID already exists");
            } else {
                header("Location: students.php?error=Database error: " . $e->getMessage());
            }
        }
    }

    // --- IMPORT CSV (UPDATED ERROR CHECKING) ---
    elseif ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
            $fileName = $_FILES['csv_file']['tmp_name'];
            $originalName = $_FILES['csv_file']['name'];
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // STRICT CHECK
            if ($fileExtension !== 'csv') {
                $errorMsg = "Invalid format! You uploaded a .$fileExtension file. Please open Excel and Save As 'CSV (Comma delimited)'.";
                header("Location: students.php?error=" . urlencode($errorMsg));
                exit;
            }
            
            $file = fopen($fileName, "r");
            $count = 0;
            $duplicates = 0;

            // Check if file opened successfully
            if ($file === FALSE) {
                header("Location: students.php?error=Could not open the file.");
                exit;
            }

            fgetcsv($file); // Skip header row

            $stmt = $conn->prepare("INSERT INTO masterlist_students (student_id, fullname, department, program, year_level, status) VALUES (?, ?, ?, ?, ?, ?)");

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                // ... (Rest of the loop logic remains the same) ...
                $sid = isset($column[0]) ? trim($column[0]) : '';
                $name = isset($column[1]) ? trim($column[1]) : '';
                $dept = isset($column[2]) ? trim($column[2]) : '';
                $prog = isset($column[3]) ? trim($column[3]) : '';
                $year = isset($column[4]) ? trim($column[4]) : '';
                $stat = isset($column[5]) ? strtolower(trim($column[5])) : 'active';
                
                if(!in_array($stat, ['active', 'inactive', 'graduated'])) { $stat = 'active'; }

                if (!empty($sid) && !empty($name)) {
                    try {
                        $stmt->execute([$sid, $name, $dept, $prog, $year, $stat]);
                        $count++;
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062) { $duplicates++; }
                    }
                }
            }
            fclose($file);
            
            $msg = "Success! Imported $count students. ($duplicates duplicates skipped)";
            header("Location: students.php?success=" . urlencode($msg));
        } else {
            header("Location: students.php?error=File upload failed.");
        }
    }
} else {
    header("Location: students.php");
}
?>