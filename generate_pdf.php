<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

$conn = mysqli_connect("localhost", "root", "", "logindetails_db");

if (!isset($_GET['project_id'])) {
    die("No project selected.");
}

$project_id = intval($_GET['project_id']);

// Fetch project info
$project = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM project WHERE project_number=$project_id"));

// Fetch reports
$reports = mysqli_query($conn, "
    SELECT pr.*, lg.username 
    FROM project_reports pr 
    JOIN lgntable lg ON pr.employee_id = lg.id 
    WHERE pr.project_id = $project_id
");

$html = "<h2>Project Report Summary</h2>";
$html .= "<strong>Project #:</strong> {$project['project_number']}<br>";
$html .= "<strong>Objective:</strong> {$project['project_objective']}<br><hr>";

if (mysqli_num_rows($reports) > 0) {
    while ($r = mysqli_fetch_assoc($reports)) {
        $html .= "<p><strong>By:</strong> {$r['username']}<br>";
        $html .= "<strong>Report:</strong><br>".nl2br($r['report_text'])."</p><hr>";
    }
} else {
    $html .= "<p>No reports available for this project.</p>";
}

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("project_{$project_id}_report.pdf", array("Attachment" => 1));
