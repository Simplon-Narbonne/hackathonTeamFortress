function move(marker){
  // Au début aucun checkpoint de franchi, à incrémenter dès que les coordonnées GPS de l'user rentre dans la zone du checkpoint
  var checkpoint = 0;

  var tracks = [new Array(43.205400, 2.362688), new Array(43.205663, 2.362846), new Array(43.205857, 2.363033), new Array(43.206117, 2.363249), new Array(43.206138, 2.363301), new Array(43.206492, 2.363391), new Array(43.206673, 2.363769), new Array(43.206816, 2.364094), new Array(43.207207, 2.364126), new Array(43.207432, 2.364204), new Array(43.207475, 2.364395), new Array(43.207875, 2.364369), new Array(43.208104, 2.364375)];

  var lat = '';
  var long = '';
  var i = 12;
  var checkpoint_number = tracks.length;
  console.log(checkpoint_number);

  var automate = setInterval(function() {
    console.log(i);
    lat = tracks[i][0];
    long = tracks[i][1];
    console.log('lat');
    console.log(lat);
    console.log('long');
    console.log(long);
    var newLatLng = new L.LatLng(lat, long);
    marker.setLatLng(newLatLng);
    i = i+1;
    console.log('i');
    console.log(i);
    if(i == checkpoint_number){
      clearTimeout(automate);
    }

  }, 2000);

}
