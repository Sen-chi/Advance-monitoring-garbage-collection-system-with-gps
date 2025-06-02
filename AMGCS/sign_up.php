<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="css/sign_in_up.css">
</head>
<body>
    <form id="signupForm">
        <div>
            <img src="images/gso.png" style="height: 90px; display: block; margin: 0 auto;">
            <h2>Sign Up</h2>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required>
            <br><br>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
            <br><br>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <br><br>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Retype Password" required>
            <br><br>

            <input type="submit" value="Sign Up">
            <br><br>
            <div class="noAcc">
                <h5>Already have an account? <a href="sign_in.php" class="linky">Sign In</a></h5>
            </div>
        </div>
    </form>

    <script>
    document.getElementById('signupForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch('register.php', { // ⬅ Change this to the new PHP file
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert("Registration successful! Redirecting to login...");
                window.location.href = 'sign_in.php'; // ⬅ Correct redirect to the login page
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    </script>
</body>
</html>
