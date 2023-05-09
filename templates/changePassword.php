<?php
if (basename($_SERVER["PHP_SELF"]) != "index.php" || !$_SESSION) {
    header("Location:?view=accueil");
    die("");
}
?>
<h1 class='title'>Changer le mot de passe de votre compte (<?php echo($_SESSION["user"]) ?>)</h1>
<form class='row' role='form' action='controleur.php'>
    <input type='hidden' name='username' value='<?php echo($_SESSION["user"]) ?>'>
    <input type='password' name='password' class='signup-input' placeholder='Saisissez votre mot de passe actuel' required='required'>
    <input type='password' name='newpassword' class='signup-input' placeholder='Saisissez un nouveau mot de passe' required='required'>
    <input type='password' name='newpasswordconfirmation' class='signup-input' placeholder='Confirmez le nouveau mot de passe' required='required'>
    <button class='button' type='submit' name='action' value='ChangePassword'>Confirmer le changement de mot de passe</button>
</form>
