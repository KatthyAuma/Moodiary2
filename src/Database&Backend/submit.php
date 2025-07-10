<?php
$host = "localhost";
$dbname = "mood_journal";
$username = "root"; 
$password = "";     

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$entryDate = $_POST['entryDate'];
$mood = $_POST['mood'];
$journalEntry = $_POST['journalEntry'];

$stmt = $conn->prepare("INSERT INTO entries (entry_date, mood, journal_entry) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $entryDate, $mood, $journalEntry);

if ($stmt->execute()) {
    echo "Jornal posted.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
