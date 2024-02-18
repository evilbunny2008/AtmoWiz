<?php
	$error = null;

	require_once('mariadb.php');

	if(isset($_SESSION['authenticated']) && $_SESSION['authenticated'] == true)
	{
		header('Location: graphs.php');
	} else if($error == null) {
		if(!empty($_POST))
		{
			$username = empty($_POST['username']) ? null : $_POST['username'];
			$password = empty($_POST['password']) ? null : $_POST['password'];

			if($username == $rwusername && $password == $rwpassword)
			{
				$_SESSION['authenticated'] = true;
				$_SESSION['rw'] = true;
				header('Location: graphs.php');
				exit;
			} else if($username == $rousername && $password == $ropassword) {
				$_SESSION['authenticated'] = true;
				$_SESSION['rw'] = false;
				header('Location: graphs.php');
				exit;
			} else {
				$error = 'Incorrect username or password';
			}
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="favicon.svg">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  overflow: hidden;
}

body {
  font-family: Arial, Helvetica, sans-serif;
}

input[type=text], input[type=password] {
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

button {
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}

button:hover {
  opacity: 0.8;
}

.imgcontainer {
  text-align: center;
  margin: 24px 0 12px 0;
  position: relative;
}

img.avatar {
  width: 20%;
  border-radius: 50%;
}

.container {
  padding: 16px;
}

span.password {
  float: right;
  padding-top: 16px;
}

.modal {
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 80%;
  height: 80vh;
  overflow: auto;
  background-color: rgb(0,0,0);
  background-color: rgba(0,0,0,0.4);
  padding-top: 60px;
}

.modal-content {
  background-color: #fefefe;
  margin: 5% auto 15% auto;
  border: 1px solid #888;
  width: 40%;
}

#footer {
  width: 100vw;
  height: 32px;
  background: #ccc;
  position: absolute;
  bottom: 0;
}

#footer-content {
  text-align: center;
  height: 32px;
  padding: 8px;
  width:100%;
}

#footer a {
  color: #085f24;
}

.animate {
  -webkit-animation: animatezoom 0.6s;
  animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom {
  from {-webkit-transform: scale(0)}
  to {-webkit-transform: scale(1)}
}

@keyframes animatezoom {
  from {transform: scale(0)}
  to {transform: scale(1)}
}

@media screen and (max-width: 300px) {
  span.password {
     display: block;
     float: none;
  }
}
</style>
</head>
<body>
  <form class="modal-content animate" action="index.php" method="post">
    <div class="imgcontainer">
      <img src="img_avatar2.png" alt="Avatar" class="avatar">
    </div>
<?php
	if($error != null)
	{
?>
    <div style='width:100%;text-align:center;'>
	<font style='color:red'><?=$error?></font>
    </div>
<?php
	}
?>
    <div class="container">
      <label for="username"><b>Username</b></label>
      <input type="text" placeholder="Enter Username" name="username" required>

      <label for="password"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" name="password" required>

      <button type="submit">Login</button>
    </div>
  </form>
<footer id="footer">
  <div id="footer-content"><a target='_blank' href='https://github.com/evilbunny2008/AtmoWiz'>&copy; 2024 by </a><a target='_blank' href='https://evilbunny.org'>evilbunny</a></div>
</footer>
</body>
</html>
