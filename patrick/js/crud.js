$('.submit_login').click(function(e){
  e.preventDefault();
  var pseudo_session = encodeURIComponent( $('.pseudo_session').val() ); // on sécurise les données
console.log(pseudo_session);
  $.ajax({
     url : 'http://iness.simplon.co/crud.php',
     type : 'POST', // Le type de la requête HTTP, ici devenu POST
     data : 'pseudo_session=' + pseudo_session , // On fait passer nos variables, exactement comme en GET, au script more_com.php
     dataType : 'html'
  });
});

/*function charger(){

var meilleurTemps = $('#scores p:first').attr('temps_session'); // on récupère le meilleur temps

$.ajax({
  url : "crud.php?temps_sesions=" + meilleurTemps // on passe le meilleur temps au fichier de chargement
  type : GET,
  success : function(html){
  $('#scores').prepend(html);
  }
});

charger();
*/
