<?php
$user = $_REQUEST["user"];
$detailed = array_key_exists("detailed", $_REQUEST)?($_REQUEST["detailed"] == "true"):false;
$userExists = intSQL("SELECT COUNT(*) FROM `users` WHERE `username` = ?;", [$user]);
if(!$userExists){
    echo "<h1>" . getString("profile_not_found", [$user]) . "</h1>";
    die("");
}

//Timestamps
$created = stringSQL("SELECT `created` FROM `users` WHERE `username` = ?;", [$user]);
$online = stringSQL("SELECT `updated` FROM `users` WHERE `username` = ?;", [$user]);

//Values
$streak = intSQL("SELECT `streak` FROM `users` WHERE `username` = ?;", [$user]);
$points = intSQL("SELECT `points` FROM `users` WHERE `username` = ?;", [$user]);
$totalCreated = intSQL("SELECT COUNT(*) FROM `predictions` WHERE `user` = ?;", [$user]);
$totalBets = intSQL("SELECT COUNT(*) FROM `votes` WHERE `user` = ?;", [$user]);
$totalBetsPoints = intSQL("SELECT SUM(points) FROM `votes` WHERE `user` = ?;", [$user]);
$correctBets = intSQL("SELECT COUNT(*) FROM `votes` JOIN `predictions` ON votes.prediction = predictions.id WHERE votes.user = ? AND choice = answer;", [$user]);
$correctBetsPoints = intSQL("SELECT SUM(points) FROM `votes` JOIN `predictions` ON votes.prediction = predictions.id WHERE votes.user = ? AND choice = answer;", [$user]);
$earned = intSQL("WITH prediction_yields AS (SELECT p.id AS prediction_id, SUM(v.points) / SUM(CASE WHEN v.choice = p.answer THEN v.points ELSE 0 END) AS yield FROM predictions p JOIN votes v ON v.prediction = p.id WHERE p.answer IS NOT NULL GROUP BY p.id), user_points AS (SELECT v.user AS username, v.prediction AS prediction_id, FLOOR(v.points * py.yield) AS points_gained FROM votes v JOIN predictions p ON v.prediction = p.id JOIN prediction_yields py ON v.prediction = py.prediction_id WHERE v.choice = p.answer AND v.user = ?) SELECT COALESCE(SUM(up.points_gained), 0) AS total_points_gained FROM user_points up;", [$user]);

$answerBets = intSQL("SELECT COUNT(*) FROM `votes` JOIN `predictions` ON votes.prediction = predictions.id WHERE votes.user = ? AND predictions.answer IS NOT NULL;", [$user]);
$answerBetsPoints = intSQL("SELECT SUM(points) FROM `votes` JOIN `predictions` ON votes.prediction = predictions.id WHERE votes.user = ? AND predictions.answer IS NOT NULL;", [$user]);
$correctBetsPercentage = $answerBets?($correctBets/$answerBets*100):getString("n_a");
$correctBetsPercentagePoints = $answerBetsPoints?($correctBetsPoints/$answerBetsPoints*100):getString("n_a");
$accounts = intSQL("SELECT COUNT(*) FROM `users`");

//Ranks
$rankStreak = intSQL("SELECT COUNT(*) FROM `users` WHERE `streak` > " . $streak) + 1;
$rankPoints = intSQL("SELECT COUNT(*) FROM `users` WHERE `points` > " . $points) + 1;
$rankCreated = intSQL("SELECT COUNT(*) FROM `users` LEFT JOIN (SELECT `user`, COUNT(*) AS `totalCreated` FROM `predictions` GROUP BY `user`) `predictions2` ON `users`.`username` = `predictions2`.`user` WHERE `totalCreated` > " . $totalCreated) + 1;
$rankBets = intSQL("SELECT COUNT(*) FROM `users` LEFT JOIN (SELECT `user`, COUNT(*) AS `totalBets` FROM `votes` GROUP BY `user`) `votes2` ON `users`.`username` = `votes2`.`user` WHERE `totalBets` > " . $totalBets) + 1;
$rankBetsPoints = intSQL("SELECT COUNT(*) FROM `users` LEFT JOIN (SELECT `user`, SUM(`points`) AS `pointsSpent` FROM `votes` GROUP BY `user`) `votes2` ON `users`.`username` = `votes2`.`user` WHERE `pointsSpent` > " . $totalBetsPoints) + 1;
$rankCorrectBets = intSQL("SELECT COUNT(*) FROM `users` LEFT JOIN (SELECT `votes`.`user`, COUNT(*) AS `correct_vote_count` FROM `votes` JOIN `predictions` ON `votes`.`prediction` = `predictions`.`id` WHERE `votes`.`choice` = `predictions`.`answer` GROUP BY `votes`.`user`) `correct_votes` ON `users`.`username` = `correct_votes`.`user` WHERE `correct_vote_count` > " . $correctBets) + 1;
$rankCorrectBetsPoints = intSQL("SELECT COUNT(*) FROM `users` LEFT JOIN (SELECT `votes`.`user`, SUM(`points`) AS `pointsSpentWins` FROM `votes` JOIN `predictions` ON `votes`.`prediction` = `predictions`.`id` WHERE `votes`.`choice` = `predictions`.`answer` GROUP BY `votes`.`user`) `correct_votes` ON `users`.`username` = `correct_votes`.`user` WHERE `pointsSpentWins` > " . $correctBetsPoints) + 1;
$rankEarned = intSQL("WITH prediction_yields AS (SELECT p.id AS prediction_id, SUM(v.points) / SUM(CASE WHEN v.choice = p.answer THEN v.points ELSE 0 END) AS yield FROM predictions p JOIN votes v ON v.prediction = p.id WHERE p.answer IS NOT NULL GROUP BY p.id), user_points AS (SELECT v.user AS username, v.prediction AS prediction_id, FLOOR(v.points * py.yield) AS points_gained FROM votes v JOIN predictions p ON v.prediction = p.id JOIN prediction_yields py ON v.prediction = py.prediction_id WHERE v.choice = p.answer) SELECT COUNT(*) FROM (SELECT username, COALESCE(SUM(up.points_gained), 0) AS total_points_gained FROM user_points up GROUP BY username) `earned` WHERE `total_points_gained` > " . $earned) + 1;

//Predictions created
$predictionsCreatedText = "";
$predictionsCreated = arraySQL("SELECT `id`, `title` FROM `predictions` WHERE `user` = ? AND NOW() < `ended` AND `answer` IS NULL ORDER BY `ended` ASC;", [$user]);
$predictionsCreatedCount = $predictionsCreated?count($predictionsCreated):0;
$predictionsCreatedText = $predictionsCreatedText . "<h3>" . getString("predictions_ongoing") . " (" . displayInt($predictionsCreatedCount) . ")</h3>";
if(!$predictionsCreated){
    $predictionsCreatedText = $predictionsCreatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    for ($i=0; $i < count($predictionsCreated); $i++){
        $link = "index.php?view=prediction&id=" . $predictionsCreated[$i]["id"];
        $predictionsCreatedText = $predictionsCreatedText . "<a href=\"$link\">" . $predictionsCreated[$i]["title"] . "</a><br/>";
    }
}
$predictionsCreatedText = $predictionsCreatedText . "<hr class='mini'>";

$predictionsCreated = arraySQL("SELECT `id`, `title` FROM `predictions` WHERE `user` = ? AND NOW() > `ended` AND `answer` IS NULL ORDER BY `ended` ASC;", [$user]);
$predictionsCreatedCount = $predictionsCreated?count($predictionsCreated):0;
$predictionsCreatedText = $predictionsCreatedText . "<h3>" . getString("predictions_waiting") . " (" . displayInt($predictionsCreatedCount) . ")</h3>";
if(!$predictionsCreated){
    $predictionsCreatedText = $predictionsCreatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    for ($i=0; $i < count($predictionsCreated); $i++){
        $link = "index.php?view=prediction&id=" . $predictionsCreated[$i]["id"];
        $predictionsCreatedText = $predictionsCreatedText . "<a href=\"$link\">" . $predictionsCreated[$i]["title"] . "</a><br/>";
    }
}
$predictionsCreatedText = $predictionsCreatedText . "<hr class='mini'>";

$predictionsCreated = arraySQL("SELECT `id`, `title` FROM `predictions` WHERE `user` = ? AND `answer` IS NOT NULL ORDER BY `ended` DESC;", [$user]);
$predictionsCreatedCount = $predictionsCreated?count($predictionsCreated):0;
$predictionsCreatedText = $predictionsCreatedText . "<h3>" . getString("predictions_ended") . " (" . displayInt($predictionsCreatedCount) . ")</h3>";
if(!$predictionsCreated){
    $predictionsCreatedText = $predictionsCreatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    for ($i=0; $i < count($predictionsCreated); $i++){
        $link = "index.php?view=prediction&id=" . $predictionsCreated[$i]["id"];
        $predictionsCreatedText = $predictionsCreatedText . "<a href=\"$link\">" . $predictionsCreated[$i]["title"] . "</a><br/>";
        if(!$detailed && $i >= 4){
            $predictionsCreatedText .= "<a href=\"" . $_SERVER['REQUEST_URI'] . "&detailed=true\">" . getString("show_all") . " ►</a><br>";
            break;
        }
    }
}

//Predictions participated
$predictionsParticipatedText = "";
$predictionsParticipated = arraySQL("SELECT `predictions`.`id`, `predictions`.`title`, `choices`.`name`, `votes`.`points` FROM `predictions` JOIN `choices` ON `choices`.`prediction` = `predictions`.`id` JOIN `votes` ON `votes`.`choice` = `choices`.`id` WHERE `votes`.`user` = ? AND NOW() < `ended` AND `answer` IS NULL ORDER BY `ended` ASC;", [$user]);
$predictionsParticipatedCount = $predictionsParticipated?count($predictionsParticipated):0;
$predictionsParticipatedText = $predictionsParticipatedText . "<h3>" . getString("predictions_ongoing") . " (" . displayInt($predictionsParticipatedCount) . ")</h3>";
if(!$predictionsParticipated){
    $predictionsParticipatedText = $predictionsParticipatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    for ($i=0; $i < count($predictionsParticipated); $i++){
        $link = "index.php?view=prediction&id=" . $predictionsParticipated[$i]["id"];
        $predictionsParticipatedText = $predictionsParticipatedText . "<a href=\"$link\">" . $predictionsParticipated[$i]["title"] . "</a><p>" . getString("profile_prediction_bet", [$predictionsParticipated[$i]["name"], displayInt($predictionsParticipated[$i]["points"])]) . "</p><br/>";
    }
}
$predictionsParticipatedText = $predictionsParticipatedText . "<hr class='mini'>";

$predictionsParticipated = arraySQL("SELECT `predictions`.`id`, `predictions`.`title`, `choices`.`name`, `votes`.`points` FROM `predictions` JOIN `choices` ON `choices`.`prediction` = `predictions`.`id` JOIN `votes` ON `votes`.`choice` = `choices`.`id` WHERE `votes`.`user` = ? AND NOW() > `ended` AND `answer` IS NULL ORDER BY `ended` ASC;", [$user]);
$predictionsParticipatedCount = $predictionsParticipated?count($predictionsParticipated):0;
$predictionsParticipatedText = $predictionsParticipatedText . "<h3>" . getString("predictions_waiting") . " (" . displayInt($predictionsParticipatedCount) . ")</h3>";
if(!$predictionsParticipated){
    $predictionsParticipatedText = $predictionsParticipatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    for ($i=0; $i < count($predictionsParticipated); $i++){
        $link = "index.php?view=prediction&id=" . $predictionsParticipated[$i]["id"];
        $predictionsParticipatedText = $predictionsParticipatedText . "<a href=\"$link\">" . $predictionsParticipated[$i]["title"] . "</a><p>" . getString("profile_prediction_bet", [$predictionsParticipated[$i]["name"], displayInt($predictionsParticipated[$i]["points"])]) . "</p><br/>";
    }
}
$predictionsParticipatedText = $predictionsParticipatedText . "<hr class='mini'>";

$predictionsParticipated = arraySQL("SELECT `predictions`.`id`, `predictions`.`title`, `predictions`.`answer`, `choices`.`name`, `votes`.`points` FROM `predictions` JOIN `choices` ON `choices`.`prediction` = `predictions`.`id` JOIN `votes` ON `votes`.`choice` = `choices`.`id` WHERE `votes`.`user` = ? AND `answer` IS NOT NULL ORDER BY `ended` DESC;", [$user]);
$predictionsParticipatedCount = $predictionsParticipated?count($predictionsParticipated):0;
if(!$predictionsParticipated){
    $predictionsParticipatedText = $predictionsParticipatedText . "<h3>" . getString("predictions_ended") . " (" . displayInt($predictionsParticipatedCount) . ")</h3>";
    $predictionsParticipatedText = $predictionsParticipatedText . "<p>" . getString("predictions_none") . "</p>";
}else{
    if($detailed){
        $predictionsWon = arraySQL("SELECT `predictions`.`id`, `predictions`.`title`, `predictions`.`answer`, `choices`.`name`, `votes`.`points` FROM `predictions` JOIN `choices` ON `choices`.`prediction` = `predictions`.`id` JOIN `votes` ON `votes`.`choice` = `choices`.`id` WHERE `votes`.`user` = ? AND `answer` = `votes`.`choice` ORDER BY `ended` DESC;", [$user]);
        $predictionsWonCount = $predictionsWon?count($predictionsWon):0;
        $predictionsParticipatedText .= "<h3>" . getString("bets_won") . " (" . displayInt($predictionsWonCount) . ")</h3>";
        for ($i=0; $i < $predictionsWonCount; $i++){
            $link = "index.php?view=prediction&id=" . $predictionsWon[$i]["id"];
            $predictionsParticipatedText = $predictionsParticipatedText . "<a href=\"$link\">" . $predictionsWon[$i]["title"] . "</a><p>" . getString("profile_prediction_bet", [$predictionsWon[$i]["name"], displayInt($predictionsWon[$i]["points"])]) . "</p><br/>";
        }
        $predictionsParticipatedText .= "<hr class='mini'>";
        $predictionsLost = arraySQL("SELECT `predictions`.`id`, `predictions`.`title`, `predictions`.`answer`, `choices`.`name`, `votes`.`points` FROM `predictions` JOIN `choices` ON `choices`.`prediction` = `predictions`.`id` JOIN `votes` ON `votes`.`choice` = `choices`.`id` WHERE `votes`.`user` = ? AND `answer` != `votes`.`choice` ORDER BY `ended` DESC;", [$user]);
        $predictionsLostCount = $predictionsLost?count($predictionsLost):0;
        $predictionsParticipatedText .= "<h3>" . getString("bets_lost") . " (" . displayInt($predictionsLostCount) . ")</h3>";
        for ($i=0; $i < $predictionsLostCount; $i++){
            $link = "index.php?view=prediction&id=" . $predictionsLost[$i]["id"];
            $answer = stringSQL("SELECT `name` FROM `choices` WHERE `id`=?;", [$predictionsLost[$i]["answer"]]);
            $predictionsParticipatedText = $predictionsParticipatedText . "<a href=\"$link\">" . $predictionsLost[$i]["title"] . "</a><p>" . getString("profile_prediction_bet", [$predictionsLost[$i]["name"], displayInt($predictionsLost[$i]["points"])]) . "<br/>" . getString("prediction_answer", [$answer]) . "</p><br/>";
        }
    }else{
        $predictionsParticipatedText = $predictionsParticipatedText . "<h3>" . getString("predictions_ended") . " (" . displayInt($predictionsParticipatedCount) . ")</h3>";
        for ($i=0; $i < count($predictionsParticipated); $i++){
            $link = "index.php?view=prediction&id=" . $predictionsParticipated[$i]["id"];
            $answer = stringSQL("SELECT `name` FROM `choices` WHERE `id`=?;", [$predictionsParticipated[$i]["answer"]]);
            $predictionsParticipatedText = $predictionsParticipatedText . "<a href=\"$link\">" . $predictionsParticipated[$i]["title"] . "</a><p>" . getString("profile_prediction_bet", [$predictionsParticipated[$i]["name"], displayInt($predictionsParticipated[$i]["points"])]) . "<br/>". getString("prediction_answer", [$answer]) . "</p><br/>";
            if($i >= 4){
                $predictionsParticipatedText .= "<a href=\"" . $_SERVER['REQUEST_URI'] . "&detailed=true\">" . getString("show_all") . " ►</a><br>";
                break;
            }
        }
    }
}

//Display
echo "
    <h1>" . displayUsername($user) . "</h1>
    <p>" . getString("profile_created") . " <abbr id='createdCountdown'>$created</abbr></p>
    <p>" . getString("online") . " <abbr id='onlineCountdown'>$online</abbr></p>
    <hr>
    <h2>" . getString("stats") . "</h2>
    <table>
        <tr>
            <th>" . getString("stat") . "</th>
            <th>" . getString("value") . "</th>
            <th>" . getString("rank") . "</th>
        </tr>
        <tr>
            <td>" . getString("streak") . "</td>
            <td>" . displayInt($streak) . "</td>
            <td><p><a href=\"?view=allUsers&order=streakHigh\">" . displayOrdinal($rankStreak) . "</a></p></td>
        </tr>
        <tr>
            <td>" . getString("points") . "</td>
            <td>" . displayInt($points) . "</td>
            <td><p><a href=\"?view=allUsers&order=pointsHigh\">" . displayOrdinal($rankPoints) . "</a></p></td>
        </tr>
        <tr>
            <td>" . getString("predictions_created") . "</td>
            <td>" . displayInt($totalCreated) . "</td>
            <td><p><a href=\"?view=allUsers&order=predictionsHigh\">" . displayOrdinal($rankCreated) . "</a></p></td>
        </tr>
        <tr>
            <td>" . getString("predictions_participated") . "</td>
            <td>" . displayInt($totalBets) . " " . getString("bets_unit") . "<br>" . displayInt($totalBetsPoints) . " " . getString("points_unit") . "</td>
            <td><p><a href=\"?view=allUsers&order=votesHigh\">" . displayOrdinal($rankBets) . "</a><br><a href=\"?view=allUsers&order=spentHigh\">" . displayOrdinal($rankBetsPoints) . "</p></td>
        </tr>
        <tr>
            <td>" . getString("bets_won") . "</td>
            <td>" . getString("of", [displayInt($correctBets), displayInt($answerBets)]) . ($answerBets?("<br><small>" . getString("percentage", [displayFloat($correctBetsPercentage)]) . "</small>"):"") . "</td>
            <td><p><a href=\"?view=allUsers&order=winsHigh\">" . displayOrdinal($rankCorrectBets) . "</a></p></td>
        </tr>
        <tr>
            <td>" . getString("bets_won") . " (" . getString("points_unit") . ")</td>
            <td>" . getString("of", [displayInt($correctBetsPoints), displayInt($answerBetsPoints)]) . ($answerBetsPoints?("<br><small>" . getString("percentage", [displayFloat($correctBetsPercentagePoints)]) . "</small>"):"") . "</td>
            <td><p><a href=\"?view=allUsers&order=psowHigh\">" . displayOrdinal($rankCorrectBetsPoints) . "</a></p></td>
        </tr>
        <tr>
            <td>" . getString("points_earned") . "</td>
            <td>" . displayInt($earned) . "</td>
            <td><p><a href=\"?view=allUsers&order=earnedHigh\">" . displayOrdinal($rankEarned) . "</a></p></td>
        </tr>
    </table>
    <hr>
	<h2>" . getString("predictions_created") . " (" . displayInt($totalCreated) . ")</h2>
	<p>" . $predictionsCreatedText . "</p>
    <hr>
	<h2>" . getString("predictions_participated") . " (" . displayInt($totalBets) . ")</h2>
	<p>" . $predictionsParticipatedText . "</p>
";
if(isConnected() && $user == $_COOKIE["username"]){
    echo "
        <hr>
        <h2>" . getString("profile_manage") . "</h2>
        <p>" . getString("profile_manage_info", ["<a href='?view=changePassword'>" . getString("profile_manage_info_change_password") . "</a>", "<a href='?view=deleteAccount&user=$user'>" . getString("profile_manage_info_delete_account") . "</a>"]) . "</p>
    ";
} else if (isMod() && !isMod($user)){
    echo "
        <hr>
        <h2>" . getString("profile_manage_mod") . "</h2>
        <p>" . getString("profile_manage_info_mod", ["<a href='?view=deleteAccount&user=$user'>" . getString("profile_manage_info_delete_account_mod") . "</a>"]) . "</p>
    ";
}

//JavaScript
include_once "time.js.php";
echo "<script>displayDateTime(\"$created\",\"createdCountdown\");</script>";
echo "<script>displayDateTime(\"$online\",\"onlineCountdown\");</script>";