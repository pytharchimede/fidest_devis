<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Logiciel de Devis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style_login.css">
    <style>
        /* Global styles */
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1d2b57, #14203e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }

        .login-container {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        .login-container img {
            width: 100px;
            margin-bottom: 1rem;
        }

        .login-container h2 {
            color: #1d2b57;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .login-container form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            padding: 0.8rem;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            transition: background 0.3s ease;
        }

        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus {
            background: #f1f1f1;
            outline: none;
            border-color: #1d2b57;
        }

        .login-container button {
            background: #1d2b57;
            color: #ffffff;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.1s ease;
        }

        .login-container button:hover {
            background: #14203e;
            transform: translateY(-3px);
        }

        .login-container button:active {
            transform: translateY(0);
        }

        .login-container .forgot-password {
            color: #1d2b57;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .login-container .forgot-password:hover {
            color: #14203e;
        }

        .login-footer {
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #aaa;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Logo -->
        <img src="https://app.fidest.ci/logi/img/logo_connex.jpg" alt="Logo">

        <!-- Title -->
        <h2>Connexion à votre Espace</h2>

        <!-- Réponse du server -->
        <div class="message" style="display: none;"></div>
        <div class="spinner" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>

        <!-- Login Form -->
        <form id="login-form" action="javascript:void(0);">
            <input type="text" name="mail_pro" placeholder="Adresse e-mail professionnelle" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se Connecter</button>
        </form>

        <!-- Forgot Password Link -->
        <a href="reset_password.php" class="forgot-password">Mot de passe oublié ?</a>

        <!-- Footer -->
        <div class="login-footer">© 2024 Logiciel de Devis - FIDEST</div>
    </div>
    <script src="js/login.js"></script>
</body>

</html>