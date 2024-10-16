<?php
$search = $_REQUEST["query"];
if ($search == ""){
    die("");
}
$detailed = array_key_exists("detailed", $_REQUEST)?$_REQUEST["detailed"]:NULL;
$users = arraySQL("SELECT `username`, `points` FROM `users` WHERE INSTR(username, '{$search}') > 0 ORDER BY `points` DESC;");
$opened = arraySQL("SELECT `id`, `title` FROM `predictions` WHERE `ended` > NOW() AND INSTR(title, '{$search}') > 0 ORDER BY `ended` ASC;");
$closed = arraySQL("SELECT `id`, `title` FROM `predictions` WHERE `ended` <= NOW() AND INSTR(title, '{$search}') > 0 ORDER BY `ended` DESC;");
$users_count = $users?count($users):0;
$opened_count = $opened?count($opened):0;
$closed_count = $closed?count($closed):0;
echo "<h1>" . getString("search_results", [$search]) . "</h1>";
if($detailed == NULL || $detailed == "users"){
    echo "<hr>";
    echo "<h2>" . getString("users") . " (" . displayInt($users_count) . ")</h2>";
    if(!$users){
        echo "<p>" . getString("users_none") . "</p>";
    }else{
        for($i = 0; $i < count($users); $i++){
            echo "<a href='?view=profile&user=" . $users[$i]["username"] . "'>" . displayUsername($users[$i]["username"]) . "<small> (" . displayInt($users[$i]["points"]) . " " . getString("points_unit") . ")</small></a><br>";
            if($i >= 4 && $detailed == NULL){
                echo "<a href='?view=search&query=" . $search . "&detailed=users'>" . getString("show_all") . " ►</a><br>";
                break;
            }
        }
    }
}
if($detailed == NULL || $detailed == "opened"){
    echo "<hr>";
    echo "<h2>" . getString("predictions_ongoing") . " (" . displayInt($opened_count) . ")</h2>";
    if(!$opened){
        echo "<p>" . getString("predictions_none") . "</p>";
    }else{
        for($i = 0; $i < count($opened); $i++){
            echo "<a href='?view=prediction&id=" . $opened[$i]["id"] . "'>" . $opened[$i]["title"] . "</a><br>";
            if($i >= 4 && $detailed == NULL){
                echo "<a href='?view=search&query=" . $search . "&detailed=opened'>" . getString("show_all") . " ►</a><br>";
                break;
            }
        }
    }
}
if($detailed == NULL || $detailed == "closed"){
    echo "<hr>";
    echo "<h2>" . getString("predictions_closed") . " (" . displayInt($closed_count) . ")</h2>";
    if(!$closed){
        echo "<p>" . getString("predictions_none") . "</p>";
    }else{
        for($i = 0; $i < count($closed); $i++){
            echo "<a href='?view=prediction&id=" . $closed[$i]["id"] . "'>" . $closed[$i]["title"] . "</a><br>";
            if($i >= 4 && $detailed == NULL){
                echo "<a href='?view=search&query=" . $search . "&detailed=closed'>" . getString("show_all") . " ►</a><br>";
                break;
            }
        }
    }
}