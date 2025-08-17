<?php
$password = '5';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";

if (password_verify($password, $hash)) {
    echo "Password verification successful.\n";
} else {
    echo "Password verification failed.\n";
}
?>
