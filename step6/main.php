<?php
  // Load required libraries for User Service
  require_once 'google/appengine/api/users/User.php';
  require_once 'google/appengine/api/users/UserService.php';

  use google\appengine\api\users\User;
  use google\appengine\api\users\UserService;

  session_start();

  $instance = ":/cloudsql/gcecodelab83:gcecodelab";
//  $instance = "localhost";
  $db = "guestbook";
  $user = "root";
  $passwd = "";
  $mysql = mysql_connect($instance, $user, $passwd);
  $db_select = mysql_select_db($db);

  if (mysql_errno()) {
    printf("Connect failed: %s\n", mysql_error($mysql));
    exit();
  }

  // Check for user login
  $user = UserService::getCurrentUser();
  if($user) {
    $url = UserService::createLogoutURL('', '');
    $url_linktext = "Logout";
    $author = $user->getNickname();
  }
  else {
    $url = UserService::createLoginURL('');
    $url_linktext = "Login";
    $author = "";
  }

  // Check if we're submitting something
  if(isset($_POST['guestbook_form_submit'])) {
    // XSRF protection: confirm XSRF token is present
    if (isset($_SESSION['token']) && ($_POST['token'] === $_SESSION['token'])) {
      // It's here, continue with submission
      $content = mysql_real_escape_string($_POST['content']);
      // Use prepared statements in your real code!
      // TODO: Modify the next line to save the author's name.
      $sql = "INSERT INTO greeting (author, content) VALUES ('Anonymous', '$content')";
      $query = mysql_query($sql);
    } else if (!isset($_SESSION['token'])) {
      // The session is missing, throw an error.
      syslog(LOG_ERR, 'Missing session token');
      echo "Session token missing - Please reset your session.";
    } else {
      // The session is present but the token is invalid/missing, throw an error.
      syslog(LOG_ERR, 'Mismatch session token.');
      echo "Invalid session token - Please reset your session.";
    }
  }
?>
<html>
  <body>
    <?php
      // XSRF protection: creating a unique value stored in our session
      $salt = sprintf("%s%d", getenv("HTTP_X_APPENGINE_CITY"), mt_rand());
      $token = md5(uniqid($salt, true));
      $_SESSION['token'] = $token;

      $sql = "SELECT * FROM greeting ORDER BY id DESC LIMIT 10";
      $query = mysql_query($sql);
      if($query) {
        while($row = mysql_fetch_assoc($query)) {
          if($row['author']) {
            echo '<b>'.$row['author'].'</b> wrote:';
          } else {
            echo 'An anonymous person wrote:';
          }
          echo '<blockquote>'.$row['content'].'</blockquote>';
        }
      }
    ?>
    <form method="post" name="guestbook_form">
      <div><textarea name="content" rows="3" cols="60"></textarea></div>
      <input type="hidden" name="token" value="<?php echo $token; ?>" />
      <div><input type="submit" name="guestbook_form_submit" value="Sign Guestbook"></div>
    </form>

    <a href="<?php echo $url; ?>"><?php echo $url_linktext; ?></a>
  </body>
</html>
