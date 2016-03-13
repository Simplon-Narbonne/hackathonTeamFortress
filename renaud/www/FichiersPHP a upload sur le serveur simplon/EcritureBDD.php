<?php
	// Modifier le pdo et la requete
	$bdd = new PDO('mysql:host=rvivancoztcarca.mysql.db;dbname=rvivancoztcarca;charset=utf8','rvivancoztcarca','Simplon11');

	$requete = $bdd->prepare('INSERT INTO coureurs (name) VALUES (:coureur)');
	$requete->execute(array(
		'coureur'=>$_GET['donnees']
		));
?>