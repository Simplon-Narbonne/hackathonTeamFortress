function creerInstance(){
  if(window.XMLHttpRequest){
    /* Firefox, Opera, Google Chrome */
    return new XMLHttpRequest();
  }else if(window.ActiveXObject){
    /* Internet Explorer */
    var names = [
      "Msxml2.XMLHTTP.6.0",
      "Msxml2.XMLHTTP.3.0",
      "Msxml2.XMLHTTP",
      "Microsoft.XMLHTTP"
    ];
    for(var i in names){
      /* On test les différentes versions */
      try{ return new ActiveXObject(names[i]); }
      catch(e){}
    }
    alert("Non supporte");
    return null; // non supporté
  }
};

function envoyerDonnees (){
  var req =  creerInstance();
 /* On récupère les données du formulaire */

  var formulaire = document.getElementById('nomCoureur');
  var coureur = formulaire.value;



  req.onreadystatechange = function(){
  /* Si l'état = terminé */
  if(req.readyState == 4){
    /* Si le statut = OK */
    if(req.status == 200){
      /* On affiche la réponse */
      alert(req.responseText);
    }else{
      alert("Error: returned status code " + req.status + " " + req.statusText);
    }
  }
}
  
  req.open("GET", "http://www.rvivancos.fr/hackathon/EcritureBDD.php?donnees="+coureur, true); //Modifier l'addresse
  req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  /* On met à null car c’est une commande GET*/
   req.send(null);
}