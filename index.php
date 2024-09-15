<?php
include("header.php");   
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            echo "<div class='alert alert-success'>Admin login successful!</div>";
        } else {
            header("location:respondent_dashboard.php");
            echo "<div class='alert alert-success'>Respondent login successful!</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Invalid username or password</div>";
    }
}

if (isset($_POST['create_survey'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $questions = $_POST['questions'];
    $question_types = $_POST['question_type'];
    $options = $_POST['options'];

    $insert_survey_query = "INSERT INTO surveys (title, description) VALUES ('$title', '$description')";
    if (mysqli_query($conn, $insert_survey_query)) {
        $survey_id = mysqli_insert_id($conn);

        foreach ($questions as $index => $question) {
            $question_type = $question_types[$index];
            $insert_question_query = "INSERT INTO questions (survey_id, question_text, question_type) VALUES ('$survey_id', '$question', '$question_type')";
            if (mysqli_query($conn, $insert_question_query)) {
                $question_id = mysqli_insert_id($conn);

                foreach ($options[$index] as $option) {
                    $insert_option_query = "INSERT INTO options (question_id, option_text) VALUES ('$question_id', '$option')";
                    mysqli_query($conn, $insert_option_query);
                }
            }
        }
        echo "<div class='alert alert-success'>Survey created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error creating survey: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Management System</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: #333;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #c82333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            border-radius: 4px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['user_id'])) { ?>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['role']); ?>!</p>
        <a href="?logout" class="button">Logout</a>
        <a style="float:right" href="survey_list.php" class="button">Survey List</a>

        <?php if ($_SESSION['role'] == 'admin') { ?>
            <h2>Create Survey</h2>
            <form method="POST">
                <div class="form-group">
                    <input type="text" class="form-control" name="title" placeholder="Survey Title" required>
                </div>
                <div class="form-group">
                    <textarea class="form-control" name="description" placeholder="Survey Description"></textarea>
                </div>
                <h3>Questions</h3>
                <div id="questions">
                    <div class="form-group question-item">
                        <input type="text" class="form-control" name="questions[]" placeholder="Question" required>
                        <select class="form-control mt-2" name="question_type[]">
                            <option value="Single Choice">Single Choice</option>
                            <option value="Multiple Choice">Multiple Choice</option>
                        </select>
                        <div class="mt-2">
                            <input type="text" class="form-control" name="options[0][]" placeholder="Option 1" required>
                            <input type="text" class="form-control mt-2" name="options[0][]" placeholder="Option 2" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="addQuestion()">Add Question</button>
                <button type="submit" class="btn btn-success" name="create_survey">Create Survey</button>
            </form>

            <script>
            let questionCount = 1;

            function addQuestion() {
                const questionHtml = `<div class="form-group question-item">
                    <input type="text" class="form-control" name="questions[]" placeholder="Question" required>
                    <select class="form-control mt-2" name="question_type[]">
                        <option value="Single Choice">Single Choice</option>
                        <option value="Multiple Choice">Multiple Choice</option>
                    </select>
                    <div class="mt-2">
                        <input type="text" class="form-control" name="options[${questionCount}][]" placeholder="Option 1" required>
                        <input type="text" class="form-control mt-2" name="options[${questionCount}][]" placeholder="Option 2" required>
                    </div>
                </div>`;
                document.getElementById('questions').insertAdjacentHTML('beforeend', questionHtml);
                questionCount++;
            }
            </script>

        <?php } else { ?>

            <?php if (isset($_GET['survey_id'])) {
                $survey_id = $_GET['survey_id'];
                $survey = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM surveys WHERE id=$survey_id"));
                $questions = mysqli_query($conn, "SELECT * FROM questions WHERE survey_id=$survey_id");
                echo "<h2>" . htmlspecialchars($survey['title']) . "</h2>";
                echo "<p>" . htmlspecialchars($survey['description']) . "</p>"; ?>
                <form method="POST" action="submit_response.php">
                    <?php while ($question = mysqli_fetch_assoc($questions)) {
                        echo "<h3>" . htmlspecialchars($question['question_text']) . "</h3>";
                        $options = mysqli_query($conn, "SELECT * FROM options WHERE question_id=" . $question['id']);
                        while ($option = mysqli_fetch_assoc($options)) {
                            if ($question['question_type'] == 'Single Choice') {
                                echo "<div class='form-check'>
                                    <input class='form-check-input' type='radio' name='question_" . $question['id'] . "' value='" . $option['id'] . "'>
                                    <label class='form-check-label'>" . htmlspecialchars($option['option_text']) . "</label>
                                </div>";
                            } elseif ($question['question_type'] == 'Multiple Choice') {
                                echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='question_" . $question['id'] . "[]' value='" . $option['id'] . "'>
                                    <label class='form-check-label'>" . htmlspecialchars($option['option_text']) . "</label>
                                </div>";
                            }
                        }
                    } ?>
                    <button type="submit" class="btn btn-info">Submit Response</button>
                </form>
            <?php } else {
                echo "<p>No survey selected.</p>";
            } ?>
        <?php } ?>
    <?php } else { ?>
        <h2>Login</h2>
        <form method="POST">
            <div class="form-group">
                <input type="text" class="form-control" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-success" name="login">Login</button>
        </form>
    <?php } ?>
</div>
</body>
</html>

<?=include("footer.php");?>   
</body>
</html>
