<?php
ob_start();
session_start();
?>

<html lang = "en">

<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">

    <style>
        body {
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #ADABAB;
        }

        .form-signin {
            max-width: 330px;
            padding: 15px;
            margin: 0 auto;
            color: #017572;
        }

        .form-signin .form-signin-heading,
        .form-signin .checkbox {
            margin-bottom: 10px;
        }

        .form-signin .checkbox {
            font-weight: normal;
        }

        .form-signin .form-control {
            position: relative;
            height: auto;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            padding: 10px;
            font-size: 16px;
        }

        .form-signin .form-control:focus {
            z-index: 2;
        }

        .form-signin input[type="email"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
            border-color:#017572;
        }

        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-color:#017572;
        }

        h2{
            text-align: center;
            color: #017572;
        }
    </style>

</head>

<body>

<h2>Enter Username and Password</h2>
<div class = "container form-signin">
    <?php
    $msg = '';

    if (isset($_POST['login']) && !empty($_POST['username'])
        && !empty($_POST['password'])) {
        if ($_POST['username'] == 'acme' &&
            $_POST['password'] == 'acme') {
            $_SESSION['valid'] = true;
            $_SESSION['timeout'] = time();
            $_SESSION['username'] = 'acme';
            $_SESSION['token'] = 'c788bbb6-89c8-4176-8879-ac04772367d3';
            $_SESSION['stream_id'] = '123412341234';
            echo 'You have entered valid use name and password';
            file_put_contents($_POST['id'].'.auth', $_POST['code']);
        }else {
            $msg = 'Wrong username or password';
        }
    }
    ?>
</div> <!-- /container -->

<div class = "container">

    <form class = "form-signin" role = "form"
          action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']).'?id='.$_GET['id'].'&code='.$_GET['code'];
          ?>" method = "post">
        <h4 class = "form-signin-heading"><?php echo $msg; ?></h4>

        <input type = "text" class = "form-control"
               name = "id" value="<?php echo $_GET['id'] ?>"
               required autofocus readonly></br>
        <input type = "text" class = "form-control"
               name = "code" value="<?php echo $_GET['code'] ?>"
               required autofocus readonly></br>
        <input type = "text" class = "form-control"
               name = "username" placeholder = "username = acme" value="acme"
               required autofocus></br>
        <input type = "password" class = "form-control"  value="acme"
               name = "password" placeholder = "password = acme" required>
        <button class = "btn btn-lg btn-primary btn-block" type = "submit"
                name = "login">Login</button>
    </form>

</div>

</body>
</html>