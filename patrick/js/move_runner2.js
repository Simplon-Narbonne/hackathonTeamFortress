function move(marker){
  // Au début aucun checkpoint de franchi, à incrémenter dès que les coordonnées GPS de l'user rentre dans la zone du checkpoint
  var checkpoint = 0;

  var tracks = [
				new Array(43.206567, 2.365934),
				new Array(43.206858, 2.365577),
				new Array(43.206893, 2.365396),
				new Array(43.206943, 2.365008),
				new Array(43.207159, 2.36507),
				new Array(43.20733, 2.365149),
				new Array(43.20756, 2.36501),
				new Array(43.20753, 2.364684),
				new Array(43.207648, 2.364685),
				new Array(43.207729, 2.364775),
				new Array(43.208057, 2.364694),
				new Array(43.208117, 2.364534),
				new Array(43.208128, 2.364373),
				new Array(43.208088, 2.364153),
				new Array(43.207934, 2.363831),
				new Array(43.207816, 2.363843),
				new Array(43.207478, 2.364141),
				new Array(43.207373, 2.364146),
				new Array(43.207134, 2.364126),
				new Array(43.206819, 2.364084),
				new Array(43.206672, 2.363749),
				new Array(43.206516, 2.363432),
				new Array(43.206437, 2.36286),
				new Array(43.206277, 2.362724),
				new Array(43.206117, 2.362683),
				new Array(43.206062, 2.362684),
				new Array(43.206062, 2.362684),
				new Array(43.205989, 2.362791),
				new Array(43.205733, 2.36281),
				new Array(43.205663, 2.362853),
				new Array(43.205539, 2.362828),
				new Array(43.205482, 2.363007),
				new Array(43.205455, 2.363217),
				new Array(43.205282, 2.363234),
				new Array(43.205078, 2.363255),
				new Array(43.205119, 2.363687),
				new Array(43.205162, 2.364059),
				new Array(43.205234, 2.36422),
				new Array(43.205407, 2.364475),
				new Array(43.205497, 2.364559),
				new Array(43.20559, 2.364589),
				new Array(43.205935, 2.364497),
				new Array(43.206228, 2.364591),
				new Array(43.206469, 2.364657),
				new Array(43.20665, 2.364957),
				new Array(43.206912, 2.365002)
				];
  var lat = '';
  var long = '';
  var i = 0;
  var checkpoint_number = tracks.length;

  var automate = setInterval(function() {
  	console.log('tada');
    lat = tracks[i][0];
    long = tracks[i][1];
    var newLatLng = new L.LatLng(lat, long);
    marker.setLatLng(newLatLng);
    i = i+1;

    if(i==31){
      console.log('Vous etes à la basilique');
      $('.infotourist').show();
    }

    if(i==32){
      console.log('Vous etes à la basilique');
      $('.infotourist').hide();
    }

    if(i == checkpoint_number){
      clearTimeout(automate);
      // Scores
      window.location.replace("score.html");
    }

  }, 800);

}
