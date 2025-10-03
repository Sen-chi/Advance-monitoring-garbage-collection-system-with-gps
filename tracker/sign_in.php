<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="css/sign_in_up.css">
</head>
<body>
    <form id="loginForm">
        <div>
            <img src="images/gso.png" style="height: 90px; display: block; margin: 0 auto;">
            <h2>Sign In</h2>

            <label for="username_email">Username or Email</label>
            <input type="text" id="username_email" name="username_email" placeholder="Enter Username or Email" required>
            <br><br>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <br><br>

            <input type="submit" value="Login">
            <br><br>

            <div class="noAcc">
                <h5>No Account? <a href="sign_up.php" class="linky">Sign Up</a></h5>
            </div>
        </div>
    </form>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch('process_login.php', { // Call process_login.php instead of sign_in.php
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert("Login successful! Redirecting...");
                window.location.href = 'dashboard.php';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred. Please try again.");
        });
    });
    </script>
</body>
</html>
