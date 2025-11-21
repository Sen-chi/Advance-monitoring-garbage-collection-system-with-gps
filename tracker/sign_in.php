<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <!-- Font Awesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sign_in_up.css">
    <style>
        .password-container {
        position: relative; /* This is crucial for positioning the icon */
        display: flex; /* Helps align items */
        align-items: center; /* Vertically aligns the icon with the input */
        }

        /* Target the password input specifically */
        #password {
            width: 100%; /* Make the input take the full width of its container */
            padding-right: 40px; /* Add space on the right for the icon! */
            box-sizing: border-box; /* Ensures padding is included in the total width */
        }

        /* This is your existing icon style, which is almost perfect */
        .toggle-password {
            position: absolute; /* Positions the icon relative to the container */
            right: 15px; /* Position it 15px from the right edge */
            cursor: pointer;
            color: #555;
            /* The top: 50% and transform were good, but flexbox alignment is often simpler */
        }

    </style>
</head>
<body>
    <form id="loginForm" autocomplete="off">
        <div>
            <img src="images/gso.png" style="height: 90px; display: block; margin: 0 auto;">
            <h2>Sign In</h2>

            <label for="username_email">Username or Email</label>
            <input type="text" id="username_email" name="username_email" placeholder="Enter Username or Email" required>
            <br><br>

            <label for="password">Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility()"></i>
            </div>
            <br><br>

            <input type="submit" value="Login">
        </div>
    </form>

    <script>
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        toggleIcon.classList.toggle('fa-eye-slash');
    }

    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('process_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
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