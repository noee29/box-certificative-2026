<?php

// End the user session and return to the login page.
session_start();
session_unset();
session_destroy();

header("Location: auth/login.php");
exit();

?>
