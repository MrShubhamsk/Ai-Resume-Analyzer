<?php
// Create uploads folder if it doesn't exist
$uploadDir = "uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_FILES["resume"])) {
        die("No file uploaded.");
    }

    $file = $_FILES["resume"];

    // Check for upload errors
    if ($file["error"] != 0) {
        die("File upload failed.");
    }

    // Allowed file types
    $allowedTypes = ["pdf", "jpg", "jpeg", "png"];

    $fileName = basename($file["name"]);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        die("Only PDF, JPG, JPEG and PNG files are allowed.");
    }

    // Maximum file size (5MB)
    if ($file["size"] > 5 * 1024 * 1024) {
        die("File size must be less than 5MB.");
    }

    // Generate unique filename
    $newFileName = time() . "_" . uniqid() . "." . $fileExt;

    $targetFile = $uploadDir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {

        // Redirect to result page
        header("Location: result.php?file=" . urlencode($newFileName));
        exit();

    } else {
        die("Unable to save uploaded file.");
    }

} else {
    die("Invalid request.");
}
?>