<?php
include_once "functions.php";

$prediction =  arraySQL("SELECT * FROM `predictions` WHERE `id` = ?;", [$_REQUEST["id"]]);
$prediAnswer = $prediction[0]["answer"];
if ($prediAnswer != NULL){
    $prediAnswerTitle = stringSQL("SELECT `name` FROM `choices` WHERE `id` = ?;", [$prediAnswer]);
}
$prediChoices = arraySQL("SELECT * FROM `choices` WHERE `prediction` = ?;", [$_REQUEST["id"]]);
$svgVotes = "<abbr title='" . getString("prediction_table_bet_count") . "'><img src='svg/people.svg'></abbr>";
$svgPoints = "<abbr title='" . getString("points_spent") . "'><img src='svg/points.svg'></abbr>";
$svgRatio = "<abbr title='" . getString("prediction_table_bet_multiplier") . "'><img src='svg/cup.svg'></abbr>";
$svgMax = "<abbr title='" . getString("prediction_table_bet_record") . "'><img src='svg/podium.svg'></abbr>";
$prediChoicesText = "<table><tr><th>" . getString("choices") . "</th><th>" . $svgVotes . "</th><th>" . $svgPoints . "</th><th>" . $svgRatio . "</th><th>" . $svgMax . "</th></tr>";
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
$prediChoicesText = $prediChoicesText . "<tr><th>" . getString("total") . "</th><th>" . displayInt($votesTotal) . "</th><th>" . displayInt($pointsTotal) . "</th><th>" . getString("n_a") . "</th><th>" . displayInt($pointsMaxTotal) . $pointsMaxTotalUsersText . "</th></tr></table>";

//Display
echo $prediChoicesText;