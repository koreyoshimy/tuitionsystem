<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('img/background.png') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            width: 100%;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .logo img {
            max-width: 150px;
            margin: 20px auto;
        }

        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        form {
            margin-top: 20px;
        }

        .input-group {
            margin: 10px 0;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
        }

        .input-group input,
        .input-group select {
            width: 80%;
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin: 0 auto;
            display: block;
        }

        .register-btn {
            width: 80%;
            padding: 10px;
            background-color: #4a9cf0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .register-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .register-btn:hover:enabled {
            background-color: #3a8ad0;
        }

        .login {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .login a {
            color: #4a9cf0;
            text-decoration: none;
        }

        .login a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function validateForm() {
            const username = document.getElementById("username").value.trim();
            const name = document.getElementById("name").value.trim();
            const password = document.getElementById("password").value.trim();
            const email = document.getElementById("email").value.trim();
            const gender = document.getElementById("gender").value;

            const registerButton = document.getElementById("register-btn");
            if (username && name && password && email && gender) {
                registerButton.disabled = false;
            } else {
                registerButton.disabled = true;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const inputs = document.querySelectorAll("input, select");
            inputs.forEach(input => {
                input.addEventListener("input", validateForm);
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo">
        </div>
        <div class="header">
            Register
        </div>
        <form method="POST" action="insert.php">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="" disabled selected>Choose gender</option>
                    <option value="female">Female</option>
                    <option value="male">Male</option>
                </select>
            </div>
            <button type="submit" id="register-btn" class="register-btn" disabled>Register</button>
            <div class="login">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>
</body>
</html>
