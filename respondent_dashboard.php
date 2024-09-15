<?php
include("header.php");   

if (isset($_POST['submit_survey'])) {
    $user_id = $_SESSION['user_id'];
    $survey_id = $_POST['survey_id'];
    $responses = $_POST['responses'];
    $error_message = '';

    $check_query = "SELECT id FROM responses WHERE user_id = $user_id AND survey_id = $survey_id";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        $error_message = "You have already responded to this survey.";
    } else {
        foreach ($responses as $question_id => $response) {
            if (is_array($response)) {
                foreach ($response as $option_id) {
                    $insert_query = "INSERT INTO responses (user_id, survey_id, question_id, option_id) 
                                     VALUES ($user_id, $survey_id, $question_id, $option_id)";
                    mysqli_query($conn, $insert_query);
                }
            } else {
                $insert_query = "INSERT INTO responses (user_id, survey_id, question_id, option_id) 
                                 VALUES ($user_id, $survey_id, $question_id, $response)";
                mysqli_query($conn, $insert_query);
            }
        }
        header('Location: respondent_dashboard.php');
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$survey_query = "SELECT s.id, s.title, s.description 
                 FROM surveys s 
                 LEFT JOIN responses r ON s.id = r.survey_id AND r.user_id = $user_id 
                 WHERE r.id IS NULL";
$surveys = mysqli_query($conn, $survey_query);

function getSurveyResponses($survey_id) {
    global $conn;
    $response_query = "SELECT u.username, q.question_text, o.option_text 
                       FROM responses r 
                       JOIN users u ON r.user_id = u.id 
                       JOIN questions q ON r.question_id = q.id 
                       JOIN options o ON r.option_id = o.id 
                       WHERE r.survey_id = $survey_id";
    return mysqli_query($conn, $response_query);
}

$view_responses = false;
if (isset($_GET['view_responses']) && $_SESSION['role'] === 'admin') {
    $view_responses = true;
    $responses = getSurveyResponses($_GET['view_responses']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respondent Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: skyblue;
            color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 20px;
            max-width: 900px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #007bff;
            color: #f8f9fa;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .form-check {
            margin-bottom: 10px;
        }
        .error-message {
            color: #dc3545;
            margin-top: 20px;
        }
        .survey-section {
            background-color: #495057;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }
        .survey-section h3 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        table {
            margin-top: 20px;
        }
        table th, table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-primary">Respondent Dashboard</h2>
    <a href="?logout" class="button">Logout</a>
    <br><br>

    <?php if ($error_message) { ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php } ?>

    <?php if ($view_responses) { ?>
        <h3 class="text-primary">Survey Responses</h3>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Question</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($response = mysqli_fetch_assoc($responses)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($response['username']); ?></td>
                        <td><?php echo htmlspecialchars($response['question_text']); ?></td>
                        <td><?php echo htmlspecialchars($response['option_text']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <a href="respondent_dashboard.php" class="button">Back to Dashboard</a>
    <?php } else { ?>
        <?php while ($survey = mysqli_fetch_assoc($surveys)) { ?>
            <div class="survey-section">
                <h3><?php echo htmlspecialchars($survey['title']); ?></h3>
                <p><?php echo htmlspecialchars($survey['description']); ?></p>
                <form method="POST">
                    <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                    <?php
                    $questions = mysqli_query($conn, "SELECT * FROM questions WHERE survey_id=" . $survey['id']);
                    while ($question = mysqli_fetch_assoc($questions)) {
                        $options = mysqli_query($conn, "SELECT * FROM options WHERE question_id=" . $question['id']);
                        echo "<h4>" . htmlspecialchars($question['question_text']) . "</h4>";
                        while ($option = mysqli_fetch_assoc($options)) {
                            if ($question['question_type'] == 'Single Choice') {
                                echo "<div class='form-check'>
                                    <input class='form-check-input' type='radio' name='responses[" . $question['id'] . "]' value='" . $option['id'] . "'>
                                    <label class='form-check-label'>" . htmlspecialchars($option['option_text']) . "</label>
                                </div>";
                            } elseif ($question['question_type'] == 'Multiple Choice') {
                                echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='responses[" . $question['id'] . "][]' value='" . $option['id'] . "'>
                                    <label class='form-check-label'>" . htmlspecialchars($option['option_text']) . "</label>
                                </div>";
                            }
                        }
                    }
                    ?>
                    <button type="submit" class="btn btn-primary" name="submit_survey">Submit Survey</button>
                </form>
            </div>
        <?php } ?>

        <?php if ($_SESSION['role'] === 'admin') { ?>
            <h3 class="mt-5 text-primary">View Survey Responses</h3>
            <ul class="list-group">
                <?php
                mysqli_data_seek($surveys, 0);
                while ($survey = mysqli_fetch_assoc($surveys)) {
                    echo "<li class='list-group-item bg-dark text-light'><a href='?view_responses=" . $survey['id'] . "'>" . htmlspecialchars($survey['title']) . "</a></li>";
                }
                ?>
            </ul>
        <?php } ?>
    <?php } ?>
</div>

<?=include("footer.php");?>   
</body>
</html>
