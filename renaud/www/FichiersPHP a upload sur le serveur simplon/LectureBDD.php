<?php

// Modifier le PDO
$bdd = new PDO('mysql:host=rvivancoztcarca.mysql.db;dbname=rvivancoztcarca;charset=utf8','rvivancoztcarca','Simplon11');

$requete = $bdd->query('SELECT * FROM coureurs');

while ($donnees=$requete->fetch())
{
  echo $donnees['name'];
}
?>
