<?php
include("header.php");        
function getSurveyResponses($survey_id) {
    global $conn;
    $query = "SELECT u.username, q.question_text, o.option_text 
              FROM responses r 
              JOIN users u ON r.user_id = u.id 
              JOIN questions q ON r.question_id = q.id 
              JOIN options o ON r.option_id = o.id 
              WHERE r.survey_id = $survey_id";
    return mysqli_query($conn, $query);
}

if (isset($_POST['export'])) {
    $survey_id = intval($_POST['survey_id']);
    $responses = getSurveyResponses($survey_id);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="survey_responses.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Username', 'Question', 'Response']);

    while ($response = mysqli_fetch_assoc($responses)) {
        fputcsv($output, [
            $response['username'],
            $response['question_text'],
            $response['option_text']
        ]);
    }

    fclose($output);
    exit();
}

if (isset($_POST['survey_id']) && !isset($_POST['export'])) {
    $survey_id = intval($_POST['survey_id']);
    $responses = getSurveyResponses($survey_id);

    echo '<table class="table table-bordered">';
    echo '<thead><tr><th>Username</th><th>Question</th><th>Response</th></tr></thead>';
    echo '<tbody>';

    while ($response = mysqli_fetch_assoc($responses)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($response['username']) . '</td>';
        echo '<td>' . htmlspecialchars($response['question_text']) . '</td>';
        echo '<td>' . htmlspecialchars($response['option_text']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<form method="post" action="load_responses.php" style="margin-top: 20px;">';
    echo '<input type="hidden" name="survey_id" value="' . htmlspecialchars($survey_id) . '">';
    echo '<button type="submit" name="export" class="btn btn-primary">Export to CSV</button>';
    echo '</form>';

    exit();
}
?>
