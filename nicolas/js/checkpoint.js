var trajUn = [// Latitude | Longitude
[43.205338, 2.362688],
[43.205663, 2.362846],
[43.205857, 2.363033],
[43.206117, 2.363249],
[43.206138, 2.363301],
[43.206492, 2.363391],
[43.206673, 2.363769],
[43.206816, 2.364094],
[43.207207, 2.364126],
[43.207432, 2.364204],
[43.207475, 2.364395],
[43.207875, 2.364369],
[43.205466, 2.362723
]];
var unTronc = [];

for (var i = 0; i < trajUn.length; i++) { // on tronque les checkpoint
	unTronc.push([parseFloat(trajUn[i][0].toString().substr(0, 6), 10), parseFloat(trajUn[i][1].toString().substr(0, 6), 10)]);
}

function maPosition(position) {
	if (trajUn.length > 0) { // si il y a un truc dans le tableu c'est que tout les checkpoint ne son pas passer
		var lati = position.coords.latitude; // latitude
		var longi = position.coords.longitude; // longitude

		var latiTronc = parseFloat(lati.toString().substr(0, 6), 10); // latitude tronquer de la position exace de l'user'
		var longiTronc = parseFloat(longi.toString().substr(0, 6), 10); // longitude tronquer 

		for (var i = 0; i < trajUn.length; i++) {
			if (unTronc[i][0] == latiTronc && unTronc[i][1] == longiTronc) {
				trajUn.splice([i], 1); // supr d'une ligne dans le tableau
				console.log('succès '+[i]);
			}
		}
} else { // on arette timeaout quant tout les chekpoint son passer
		clearTimeout(refresh);
		var ajax = minu + ":" + secon + ":" + centi; // cration d'une variable pour envois ajax
	}
}
var refresh = setInterval(function() {
	navigator.geolocation.getCurrentPosition(maPosition);
}, 1000); 