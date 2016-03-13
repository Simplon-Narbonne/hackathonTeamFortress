function request(callback) {

    var xhr = getXMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
            callback(xhr.responseText);
        }
    };
   
    xhr.open("GET", "http://www.rvivancos.fr/hackathon/LectureBDD.php", true); //Modifier l'addresse
    xhr.send(null);
    }

function readData(sData) {

    document.getElementById('test').innerHTML = sData;
}
