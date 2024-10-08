<?php
if(array_key_exists("error",$_REQUEST)){
    echo "<p class='error'>";
    switch($_REQUEST["error"]){
        case "vote":
        case "answer":
        case "delete":
            echo getString("error_data");
            break;
        case "closed":
            echo getString("error_prediction_closed");
            break;
        case "unauthorized":
            echo getString("error_forbidden_manage_prediction");
            break;
        case "too_early":
            echo getString("error_prediction_answer");
            break;
        case "points":
            echo getString("error_prediction_points");
            break;
        default:
            echo getString("error_default") . "<br>" . getString("error_try_again");
            break;
    }
    echo "</p>";
}
$prediExists = intSQL("SELECT COUNT(*) FROM `predictions` WHERE `id` = ?;", [$_REQUEST["id"]]);
if (!$prediExists){
    echo "<h1>" . getString("prediction_not_found_info1") . "</h1><br><p>" . getString("prediction_not_found_info2") . "</p>";
    die("");
}
$prediction =  arraySQL("SELECT * FROM `predictions` WHERE `id` = ?;", [$_REQUEST["id"]]);
$prediTitle = $prediction[0]["title"];
$prediDesc = $prediction[0]["description"];
$prediCreator = $prediction[0]["user"];
$prediCreated = $prediction[0]["created"];
$prediEnd = $prediction[0]["ended"];
$prediAnswered = $prediction[0]["answered"];
$prediAnswer = $prediction[0]["answer"];
if ($prediAnswer != NULL){
    $prediAnswerTitle = stringSQL("SELECT `name` FROM `choices` WHERE `id` = ?;", [$prediAnswer]);
}
$prediNumberOfAnswers = intSQL("SELECT COUNT(*) FROM `choices` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
$prediChoices = arraySQL("SELECT * FROM `choices` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
$svgVotes = "<abbr title='" . getString("prediction_table_bet_count") . "'><img src='svg/people.svg'></abbr>";
$svgPoints = "<abbr title='" . getString("points_spent") . "'><img src='svg/points.svg'></abbr>";
$svgRatio = "<abbr title='" . getString("prediction_table_bet_multiplier") . "'><img src='svg/cup.svg'></abbr>";
$svgMax = "<abbr title='" . getString("prediction_table_bet_record") . "'><img src='svg/podium.svg'></abbr>";
$prediChoicesText = "<span id='table'><table><tr><th>" . getString("choices") . "</th><th>" . $svgVotes . "</th><th>" . $svgPoints . "</th><th>" . $svgRatio . "</th><th>" . $svgMax . "</th></tr>";
for($i = 0; $i < count($prediChoices); $i++){
    $choiceID = $prediChoices[$i]["id"];
    $votesChoice = intSQL("SELECT COUNT(*) FROM `votes` WHERE `prediction` = ? AND `choice` = ?;", [$_REQUEST["id"], $choiceID]);
    $votesTotal = intSQL("SELECT COUNT(*) FROM `votes` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
    $pointsChoice = intSQL("SELECT SUM(points) FROM `votes` WHERE `prediction` = ? AND `choice` = ?;", [$_REQUEST["id"], $choiceID]);
    $pointsTotal = intSQL("SELECT SUM(points) FROM `votes` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
    if($pointsTotal != 0 && $pointsChoice != 0){
        $pointsPercentage = "<br><small>" . getString("percentage", [displayFloat(($pointsChoice / $pointsTotal) * 100)]) . "</small>";
    }else{
        $pointsPercentage = "";
    }
    if($votesTotal != 0 && $votesChoice != 0){
        $votesPercentage = "<br><small>" . getString("percentage", [displayFloat(($votesChoice / $votesTotal) * 100)]) . "</small>";
    }else{
        $votesPercentage = "";
    }
    if($pointsPercentage != ""){
        $winRate = "×" . displayFloat($pointsTotal / $pointsChoice);
    }else{
        $winRate = "—";
    }
    $pointsMaxChoice = intSQL("SELECT MAX(points) FROM `votes` WHERE `prediction` = ? AND `choice` = ?;", [$_REQUEST["id"], $choiceID]);
    $pointsMaxChoiceUsersText = "";
    if($pointsMaxChoice){
        $pointsMaxChoiceUsers = arraySQL("SELECT `user` FROM `votes` WHERE `prediction` = ? AND `choice` = ? AND `points` = ?;", [$_REQUEST["id"], $choiceID, $pointsMaxChoice]);
        $pointsMaxChoiceUsersText = "<br><small>";
        for($j = 0; $j < count($pointsMaxChoiceUsers); $j++){
            $pointsMaxChoiceUsersText = $pointsMaxChoiceUsersText . "<a href='?view=profile&user=" . $pointsMaxChoiceUsers[$j]["user"] . "'>" . displayUsername($pointsMaxChoiceUsers[$j]["user"]) . "</a>";
        }
        $pointsMaxChoiceUsersText = $pointsMaxChoiceUsersText . "</small>";
    }
    
    $choiceName = $prediChoices[$i]["name"];
    $selectedChoice = isConnected()?intSQL("SELECT `choice` FROM `votes` WHERE `user` = ? AND `prediction` = ?;", [$_COOKIE["username"], $_REQUEST["id"]]):NULL;
    $choiceClass = ($choiceID == $prediAnswer)?" class='green'":(($choiceID == $selectedChoice)?" class='blue'":"");
    $prediChoicesText = $prediChoicesText . "<tr" . $choiceClass . "><td>" . $choiceName . "</td><td>" . displayInt($votesChoice) . $votesPercentage . "</td><td>" . displayInt($pointsChoice) . $pointsPercentage .  "</td><td>" . $winRate . "</td><td>" . displayInt($pointsMaxChoice) . $pointsMaxChoiceUsersText . "</td></tr>";
}
$pointsMaxTotal = intSQL("SELECT MAX(points) FROM `votes` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
$pointsMaxTotalUsersText = "<br><small>";
if($pointsMaxTotal){
    $pointsMaxTotalUsers = arraySQL("SELECT `user` FROM `votes` WHERE `prediction` = ? AND `points` = ?;", [$_REQUEST["id"], $pointsMaxTotal]);
    for($j = 0; $j < count($pointsMaxTotalUsers); $j++){
        $pointsMaxTotalUsersText = $pointsMaxTotalUsersText . "<a href='?view=profile&user=" . $pointsMaxTotalUsers[$j]["user"] . "'>" . displayUsername($pointsMaxTotalUsers[$j]["user"]) . "</a>";
    }
    $pointsMaxTotalUsersText = $pointsMaxTotalUsersText . "</small>";
}
$prediChoicesText = $prediChoicesText . "<tr><th>" . getString("total") . "</th><th>" . displayInt($votesTotal) . "</th><th>" . displayInt($pointsTotal) . "</th><th>" . getString("n_a") . "</th><th>" . displayInt($pointsMaxTotal) . $pointsMaxTotalUsersText . "</th></tr></table></span>";

//Dynamic content
if(array_key_exists("username",$_COOKIE)){
    $creator = $prediCreator == $_COOKIE["username"];
}else{
    $creator = false;
}

if (!isConnected()){
    $mode = "disconnected";
} elseif (intSQL("SELECT COUNT(*) FROM `votes` WHERE `user` = ? AND `prediction` = ?;", [$_COOKIE["username"], $_REQUEST["id"]]) == 1){
    $mode = "alreadyVoted";
} elseif ($prediEnd < stringSQL("SELECT NOW();")){
    $mode = "waitingAnswer";
} else{
    $mode = "normal";
}
$dropdownMenu = "<select name='choice'>";
for($i = 0; $i < count($prediChoices); $i++){
    $dropdownMenu = $dropdownMenu . "<option value=" . $prediChoices[$i]["id"] . ">" . $prediChoices[$i]["name"] . "</option>";
}
$dropdownMenu = $dropdownMenu . "</select>";

//Display
echo "
<h1>" . $prediTitle . "</h1>
" . ($prediDesc != NULL ? ("<p>" . $prediDesc . "</p><br><br>") : "") . "
<p>" . getString("created_by") . " <a href='?view=profile&user=" . $prediCreator . "'>" . displayUsername($prediCreator) . "</a> <abbr id='createdCountdown'>$prediCreated</abbr></p>
<p>" . getString("bets_end") . " <abbr id='endedCountdown'>$prediEnd</abbr></p>";
if($prediAnswered != NULL){
    echo "<p>" . getString("prediction_answered") . " <abbr id='answeredCountdown'>$prediAnswered</abbr></p>";
}else if($prediAnswer == NULL && $prediEnd < stringSQL("SELECT NOW();")){
    echo "<p>" . getString("prediction_waiting_answer") . "</p>";
}
echo "
<h2>" . getString("prediction_answer_count", [$prediNumberOfAnswers]) . "</h2>
" . $prediChoicesText . "
<hr>
<h2>" . getString("prediction_bet") . "</h2>
";
switch($mode){
    case "disconnected" :
        echo displayInvite(getString("invite_action_bet"));
    break;

    case "alreadyVoted" :
        $choice = stringSQL("SELECT `choices`.`name` FROM `choices` JOIN `votes` ON `choices`.`id` = `votes`.`choice` WHERE `votes`.`user` = ? AND `votes`.`prediction` = ?;", [$_COOKIE["username"], $_REQUEST["id"]]);
        $pointsSpent = intSQL("SELECT `points` FROM `votes` WHERE `user` = ? AND `prediction` = ?;", [$_COOKIE["username"], $_REQUEST["id"]]);
        $pointsMax = intSQL("SELECT `points` FROM `users` WHERE `username` = ?;", [$_COOKIE["username"]]);
        echo "<p>" . getString("prediction_bet_info", [$choice, displayInt($pointsSpent)]) . "</p>";
        if($prediEnd >= stringSQL("SELECT NOW();")){
            echo "<form role='form' action='controller.php'>
                <input type='hidden' name='prediction' value='" . $_REQUEST["id"] . "'>
                <p>" . getString("prediction_bet_add", ["<input type='number' name='points' min='1' max='" . $pointsMax . "' required='required'>"]) . "</p>
                <button type='submit' name='action' value='addPoints'>" . getString("prediction_bet_add_confirm") . "</button>
            </form>";
        }
    break;

    case "waitingAnswer" :
        echo "<p>" . getString("prediction_bet_closed") . "</p>";
    break;

    case "normal" :
    default :
        $pointsMax = intSQL("SELECT `points` FROM `users` WHERE `username` = ?;", [$_COOKIE["username"]]);
        echo "<form role='form' action='controller.php'>
            <input type='hidden' name='prediction' value='" . $_REQUEST["id"] . "'>
            <p>" . getString("prediction_bet_bet", [$dropdownMenu, "<input type='number' name='points' min='1' max='" . $pointsMax . "' required='required'>"]) . "</p>
            <button type='submit' name='action' value='vote'>" . getString("prediction_bet") . "</button>
        </form>";
    break;
}
if ($creator || (isMod() && !isMod($prediCreator))){
    echo "<hr><h2>" . getString("prediction_manage") . "</h2>";
    if ($prediAnswer == NULL){
        if ($prediEnd < stringSQL("SELECT NOW();")){
            echo "<form role='form' action='controller.php'>
                <input type='hidden' name='prediction' value='" . $_REQUEST["id"] . "'>
                <p>" . getString("prediction_manage_answer", [$dropdownMenu]) . "</p>
                <button type='submit' name='action' value='answer'>" . getString("prediction_manage_answer_confirm") . "</button>
            </form>";
        } else {
            echo "<p>" . getString("prediction_manage_answer_early") . "</p>";
        }
        echo "<form role='form' action='controller.php'>
            <input type='hidden' name='prediction' value='" . $_REQUEST["id"] . "'>
            <button type='submit' name='action' value='deletePrediction'>" . getString("prediction_manage_delete_points") . "</button>
        </form>";
    } else {
        echo "<form role='form' action='controller.php'>
            <input type='hidden' name='prediction' value='" . $_REQUEST["id"] . "'>
            <button type='submit' name='action' value='deletePrediction'>" . getString("prediction_manage_delete") . "</button>
        </form>";
    }
}
if ($prediAnswer != NULL){
    echo "<hr><h3>" . getString("prediction_answer", [$prediAnswerTitle]) . "</h3>";
    if(isConnected()){
        $choiceID = intSQL("SELECT `choice` FROM `votes` WHERE `user` = ? AND `prediction` = ?;", [$_COOKIE["username"], $_REQUEST["id"]]);
        if($choiceID){
            if($choiceID == $prediAnswer){
                $pointsChoiceVictory = intSQL("SELECT SUM(points) FROM `votes` WHERE `prediction` = ? AND `choice` = ?;", [$_REQUEST["id"], $prediAnswer]);
                $winRateVictory = $pointsTotal / $pointsChoiceVictory;
                $earnedPoints = floor($pointsSpent * $winRateVictory);
                $balance = $earnedPoints - $pointsSpent;
                echo "<p>" . getString("prediction_answer_win", [displayInt($earnedPoints), displayInt($balance)]) . "</p>";
            }else{
                echo "<p>" . getString("prediction_answer_lose", [displayInt($pointsSpent)]) . "</p>";
            }
        }
    }
}

//JavaScript
include_once "time.js.php";
echo "<script>displayDateTime(\"$prediCreated\",\"createdCountdown\");</script>";
echo "<script>displayDateTime(\"$prediEnd\",\"endedCountdown\");</script>";
if($prediAnswered != NULL){
    echo "<script>displayDateTime(\"$prediAnswered\",\"answeredCountdown\");</script>";
}
include_once "refreshTable.js.php";
echo "<script>refreshTable(" . $_REQUEST["id"] . ");</script>";