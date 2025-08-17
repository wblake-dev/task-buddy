<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create survey_responses table
    $sql = "CREATE TABLE IF NOT EXISTS survey_responses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        survey_id INT NOT NULL,
        user_id INT NOT NULL,
        response_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (survey_id) REFERENCES surveys(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->exec($sql);
    echo "Table survey_responses created successfully";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 