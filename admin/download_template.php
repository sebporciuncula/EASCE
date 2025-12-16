<?php
// File: admin/download_template.php

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_import_template.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// 1. Add the Column Headers (Must match your database/import logic exactly)
fputcsv($output, array('StudentID', 'FullName', 'Department', 'Program', 'YearLevel', 'Status'));

// 2. Add a Sample Row (Optional, to show the format)
fputcsv($output, array('2023-00001', 'Dela Cruz, Juan M.', 'College of Computer Studies', 'BS Information Technology', '1st Year', 'active'));
fputcsv($output, array('2023-00002', 'Santos, Maria A.', 'College of Accountancy', 'BS Accountancy', '2nd Year', 'active'));

// Close the file pointer
fclose($output);
exit();
?>