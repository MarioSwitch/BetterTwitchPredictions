<?php
if (basename($_SERVER["PHP_SELF"]) != "index.php")
{
    header("Location:?view=accueil");
    die("");
}
include_once "libs/maLibSQL.pdo.php";
include_once "libs/requetes.php";
$now = SQLGetChamp("SELECT NOW();");
$search = $_REQUEST["q"];
if($search != ""){
	$predictionsOuvertes = parcoursRs(SQLSelect("SELECT id, title FROM predictions WHERE endDate > '$now' AND INSTR(title, '{$search}') > 0 ORDER BY endDate ASC;"));
	$predictionsFermees = parcoursRs(SQLSelect("SELECT id, title FROM predictions WHERE endDate <= '$now' AND INSTR(title, '{$search}') > 0;"));
	echo "<h1 class='title'>Résultats de recherche pour \"" . $search . "\"</h1>";
	echo "<h2 class='category-h2'>Prédictions ouvertes</h2>";
	if(!$predictionsOuvertes){
	        echo "<p class='text2'>Aucune prédiction ouverte ne correspond à votre recherche.</p>";
	}else{
	        foreach($predictionsOuvertes as $uneLigne){
	                $typeDonnee = 1;
	                foreach($uneLigne as $uneDonnee){
	                        if($typeDonnee == 1){$id = $uneDonnee;}
	                        if($typeDonnee == 2){$title = $uneDonnee;}
	                        $typeDonnee++;
	                }
	                echo "<a class='a-text' href='?view=prediction&id=" . $id . "'>" . $title . "</a>";
	        }
	}
	echo "<h2 class='category-h2'>Prédictions fermées</h2>";
	if(!$predictionsFermees){
	        echo "<p class='text2'>Aucune prédiction fermée ne correspond à votre recherche.</p>";
	}else{
	        foreach($predictionsOuvertes as $uneLigne){
	                $typeDonnee = 1;
	                foreach($uneLigne as $uneDonnee){
	                        if($typeDonnee == 1){$id = $uneDonnee;}
	                        if($typeDonnee == 2){$title = $uneDonnee;}
	                        $typeDonnee++;
	                }
	                echo "<a class='a-text' href='?view=prediction&id=" . $id . "'>" . $title . "</a>";
	        }
	}
}
?>

