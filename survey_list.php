<?php
include("header.php");     

if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);    
    $role = $_POST['role'];
    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    mysqli_query($conn, $query);
    header('Location: survey_list.php');
    exit();
}

if (isset($_GET['delete_user_id'])) {
    $user_id = $_GET['delete_user_id'];
    if ($_SESSION['user_id'] == $user_id) {
        echo "<script>alert('You cannot delete your own account.'); window.location.href='survey_list.php';</script>";
        exit();
    }
    mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
    header('Location: survey_list.php');
    exit();
}

if (isset($_GET['delete_survey_id'])) {
    $survey_id = $_GET['delete_survey_id'];
    mysqli_query($conn, "DELETE FROM surveys WHERE id=$survey_id");
    header('Location: survey_list.php');
    exit();
}

$surveys = mysqli_query($conn, "SELECT * FROM surveys");
$users = mysqli_query($conn, "SELECT * FROM users");

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey List and User Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            margin-top: 30px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-danger {
            margin-right: 10px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
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
        .btn-primary {
            margin: 5px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        #addUserForm, #userList, #responsesSection {
            display: none;
            margin-top: 20px;
        }
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Survey List</h2>
    <a href="?logout" class="button">Logout</a>
    <a href="index.php" class="button">Admin Dashboard</a>

    <button class="btn btn-primary" onclick="toggleSection('addUserForm')">Toggle Add New User Form</button>
    <button class="btn btn-primary" onclick="toggleSection('userList')">Toggle User List</button>

    <table class="table table-bordered table-striped mt-4">
        <thead>
            <tr>
                <th>Survey Title</th>
                <th>Description</th>
                <th>Responses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($survey = mysqli_fetch_assoc($surveys)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($survey['title']); ?></td>
                    <td><?php echo htmlspecialchars($survey['description']); ?></td>
                    <td>
                        <button class="btn btn-info" onclick="loadResponses(<?php echo $survey['id']; ?>)">View Responses</button>
                    </td>
                    <td>
                        <a href="?delete_survey_id=<?php echo $survey['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this survey?')">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div id="addUserForm">
        <h2 class="mt-5 mb-4">Add New User</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="respondent">Respondent</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success" name="add_user">Add User</button>
        </form>
    </div>

    <div id="userList">
        <h2 class="mt-5 mb-4">User List</h2>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($users)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <?php if ($_SESSION['user_id'] != $user['id']) { ?>
                                <a href="?delete_user_id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="responsesSection">
        <h2 class="mt-5 mb-4">Survey Responses</h2>
        <div id="responsesContent"></div>
    </div>
</div>
<?=include("footer.php");?>    

<script>
function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    section.style.display = (section.style.display === 'none' || section.style.display === '') ? 'block' : 'none';
}

function loadResponses(surveyId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'load_responses.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('responsesContent').innerHTML = xhr.responseText;
            document.getElementById('responsesSection').style.display = 'block';
        }
    };
    xhr.send('survey_id=' + surveyId);
}
</script>
</body>
</html>
