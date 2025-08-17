<?php
// Database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'task_buddy_db';

// Establish database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    // If connection fails, stop script execution and display error
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful.<br>";

// SQL query to create the 'admins' table if it doesn't already exist.
// The 'password' field is VARCHAR(255) to accommodate hashed passwords.
$sql_create_table = "CREATE TABLE IF NOT EXISTS admins (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_name VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Execute the table creation query
if ($conn->query($sql_create_table) === TRUE) {
    echo "Table 'admins' checked/created successfully.<br>";
} else {
    // If there's an error creating the table, display it
    echo "Error checking/creating admins table: " . $conn->error . "<br>";
}

// --- Section to insert the first admin user ('H') ---
$admin_name_h = 'H';
$admin_password_plain_h = '8';
// Hash the password using PHP's recommended password_hash() function.
// PASSWORD_DEFAULT ensures a strong, modern hashing algorithm (currently bcrypt).
$admin_password_hashed_h = password_hash($admin_password_plain_h, PASSWORD_DEFAULT);

// Prepare a statement to check if 'H' already exists to prevent duplicate entries
$check_h_stmt = $conn->prepare("SELECT id FROM admins WHERE admin_name = ?");
$check_h_stmt->bind_param("s", $admin_name_h);
$check_h_stmt->execute();
$check_h_stmt->store_result(); // Store the result so we can check num_rows

// If 'H' does not exist, insert them
if ($check_h_stmt->num_rows == 0) {
    // Prepare a statement to insert the admin user securely
    $stmt_h = $conn->prepare("INSERT INTO admins (admin_name, password) VALUES (?, ?)");
    // 'ss' indicates that both parameters are strings
    $stmt_h->bind_param("ss", $admin_name_h, $admin_password_hashed_h);

    // Execute the insert statement
    if ($stmt_h->execute()) {
        echo "Admin user '{$admin_name_h}' inserted successfully.<br>";
    } else {
        echo "Error inserting admin user '{$admin_name_h}': " . $stmt_h->error . "<br>";
    }
    $stmt_h->close(); // Close the statement after use
} else {
    echo "Admin user '{$admin_name_h}' already exists, skipping insertion.<br>";
}
$check_h_stmt->close(); // Close the check statement

// --- Section to insert a NEW admin user ---
// Define the new admin's name and a strong plain-text password
$new_admin_name = 'admin1'; // ✨ You can change this to any desired unique username
$new_admin_password_plain = 'admin123!'; // 🔒 IMPORTANT: Change this to a strong, unique password

// Hash the new admin's password
$new_admin_password_hashed = password_hash($new_admin_password_plain, PASSWORD_DEFAULT);

// Prepare a statement to check if the new admin user already exists
$check_new_admin_stmt = $conn->prepare("SELECT id FROM admins WHERE admin_name = ?");
$check_new_admin_stmt->bind_param("s", $new_admin_name);
$check_new_admin_stmt->execute();
$check_new_admin_stmt->store_result();

// If the new admin does not exist, insert them
if ($check_new_admin_stmt->num_rows == 0) {
    // Prepare a statement to insert the new admin user securely
    $stmt_new_admin = $conn->prepare("INSERT INTO admins (admin_name, password) VALUES (?, ?)");
    $stmt_new_admin->bind_param("ss", $new_admin_name, $new_admin_password_hashed);

    // Execute the insert statement
    if ($stmt_new_admin->execute()) {
        echo "New admin user '{$new_admin_name}' inserted successfully.<br>";
    } else {
        echo "Error inserting new admin user '{$new_admin_name}': " . $stmt_new_admin->error . "<br>";
    }
    $stmt_new_admin->close(); // Close the statement
} else {
    echo "Admin user '{$new_admin_name}' already exists, skipping insertion.<br>";
}
$check_new_admin_stmt->close(); // Close the check statement

// Close the database connection when all operations are complete
$conn->close();
echo "Database connection closed.<br>";
?>