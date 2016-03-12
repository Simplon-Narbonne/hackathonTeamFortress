<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

// Ce fichier permet de recuperer les flux de syndication de Tourinsoft et insere ou met a jour les articles en base
// Log du plugin dans /tmp/prive_import_syndication.log

//Librairie qui permet de traiter les documents
include_spip('inc/distant');


//
// Lancement du script avec comme argument la description de tous les flux
//
function lancement_traitement_flux($desciption_des_flux){
    /*
    $desciption_des_flux = description de tous les flux a recuperer
    */
    spip_log("***** Dans la fonction lancement_traitement_flux() *****\n Demarrage traitement import",'import_syndication');
    spip_log("Variable recu par lancement_traitement_flux \$desciption_des_flux : $desciption_des_flux",'import_syndication');
    spip_log($desciption_des_flux,'import_syndication');


    //Pour chaque item , affichage des cles valeur
    foreach($desciption_des_flux as $cle=>$balise)
    {
        $tableau_description_flux = $desciption_des_flux[$cle];
        spip_log("Un type de desciption_flux :",'import_syndication');
        //spip_log($tableau_description_flux,'import_syndication');
       
        // Traitement d un flux en particulier lecture et parsage
        @traiter_flux($tableau_description_flux);
    }

    //retour lancement_traitement_flux false
    return $retour;
//fin function lancement_traitement_flux
    spip_log("***** FIN fonction lancement_traitement_flux() *****",'import_syndication');
}



//
// Point d'entree du script determine si flux doit etre mis a jour ou creer suivant la date contenu dans le tableau $tableau_description_flux
//
function traiter_flux($tableau_description_flux){
    /*
    $tableau_description_flux = description entiere du flux
    */
    spip_log("***** Dans la fonction traiter_flux() *****",'import_syndication');
    spip_log("Variable recu par traiter_flux: Tableau \$tableau_desciption_flux :",'import_syndication');
    //spip_log($tableau_description_flux,'import_syndication');
   
    // recuperer type et id_module pour determiner le type de flux a recuperer
    $type = $tableau_description_flux['type'];
    $id_syndication = $tableau_description_flux['id_syndication'];
    spip_log("Traitement du flux: type: $type - id_syndication: $id_syndication :",'import_syndication');


    // TODO
    // Si pas de date en base pour ce flux
    $fonction = "getDetailBordereau";
   
    //Sinon date existante en base pour ce flux, on fait un get Diff
    //$fonction = "getDetailBordereauDiff";
    //Puis supprimer le reste
    //$fonction = "getDetailBordereauSuppr";


    // Lecture du flux
    $xml = @lire_flux($fonction, $type, $id_syndication,$date=0);
    //spip_log("xml : $xml",'import_syndication');
   
    // Parsage et traitement du flux
    $retour = @traiter_xml($xml, $tableau_description_flux);
    //spip_log("xml : $retour",'import_syndication');
   
    if($retour == true){
    //spip_log("----- traiter_flux() $fonction, $type, $id_syndication retour == OK -----",'import_syndication');   
    }
    else{
    //spip_log("----- traiter_flux() $fonction, $type, $id_syndication retour == NOK -----",'import_syndication');   
    }

    //retour traiter_flux false
    return $retour;
//fin function traiter_flux
    spip_log("***** FIN fonction traiter_flux() *****",'import_syndication');    
}

//
// Recupere et commence a lire le flux et retourne le xml du flux
//
function lire_flux($fonction, $type, $id_syndication,$date=0){
    /*
    $fonction = Type de requete get a effectuer
    $type = type de bordereau DEG, RES, HOT...
    $id_syndication = numero de l id syndication
    $date = date de derniere mise a jour
    */
    spip_log("***** Dans la fonction lire_flux() *****\nVariable recu par lire_flux:\n fonction: $fonction\n type: $type\n id_syndication: $id_syndication\n date: $date",'import_syndication');

    //construction de l url du flux a recuperer = url + argument du flux type_get, type de flux, id_syndication
    $flux = "http://cdt34.tourinsoft.com/soft/RechercheDynamique/Syndication/controle/syndication2.asmx/".$fonction."?idModule=".$id_syndication."&OBJETTOUR_CODE=".$type;

    // Ouverture du flux distant en lecture avec fopen
    $flux_lus = @fopen($flux, "r");
 
    // Test si le flux a ete recuperer
    if (!$flux_lus) {
    spip_log(" Impossible de lire le flux $flux",'import_syndication');
    $retour = false;
    break;
    }
    // si flux lisible et correct
    else{
    // Parcours du flux et stockage dans la varaible de retour
    while (!feof ($flux_lus)) {
        $retour .= fgets($flux_lus, 1024);
    }
    }
    //spip_log($retour,'import_syndication');
   
    //retour lire_flux, soit le flux $xml soit false
    return $retour;   
//fin function lire_flux
    spip_log("***** FIN fonction lire_flux() *****",'import_syndication');   
}


//
// Traite le flux pour determiner s il contient un nouvel article ou si c 'est une mise a jour d un article existant, supprime les documents de l article si c est une mise a jour
//
function traiter_xml($xml, $tableau_description_flux){
/*
$xml = xml a parser
$tableau_description_flux = description entiere du flux
*/ 
    spip_log("***** Dans la fonction traiter_xml() *****\nTraitement du tableau recu - Variable \$xml et le tableau de description des flux",'import_syndication');
    //spip_log($xml,'import_syndication');   
    //spip_log($tableau_description_flux,'import_syndication');
   
    // Inclusion des Methodes SPIP pour traiter les xml//
    $retour=true;
    include_spip('inc/xml');
   
    // Parsage du flux
    $tableau_xml_parser = spip_xml_parse($xml);
    // Test si le flux a pu etre parser
    if (!$tableau_xml_parser) {
          spip_log(" Impossible de parser ce flux $xml",'import_syndication');
      $retour = false;
    continue;
    }
   
    // Nombre d element a parser dans le flux
    $nb_detail = count($tableau_xml_parser['DataSet xmlns="http://www.faire-savoir.com/webservices/"'][0]['diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"'][0]['Listing xmlns=""'][0]);
    spip_log("Nb element detail: $nb_detail \n traitement du tableau pour le type $tableau_description_flux[type]",'import_syndication');

    // Pour chaque element detail du flux, traiter les donnees
    foreach($tableau_xml_parser['DataSet xmlns="http://www.faire-savoir.com/webservices/"'][0]['diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"'][0]['Listing xmlns=""'][0] as $item_tableau)
    {

        // Recuperation de l id de item
        $id = $item_tableau[0]['ID'][0];

        // Recuperation de l id article en base de donnee       
        $id_article = sql_getfetsel('id_article','spip_articles', 'extra_id="'.$id.'"');
       
            spip_log("id de l item dans le flux $id et son id_article en base: $id_article ",'import_syndication');   
   
        // Si id_article present on supprime tous les docs de l article sinon on creer l article
        if(!empty($id_article)){
        spip_log("On supprime les documents de l article $id_article\n L id item : $id correspond a l id article $id_article en base" ,'import_syndication');
            supprimer_doc_joint($id_article);
        // TODO desaffecter_logo()
        }
        else{
        spip_log("Pas d article avec extra_id $id, on le creer" ,'import_syndication');
        sql_insertq('spip_articles', array('extra_id'=>$id));
        }


         //spip_log($tableau_description_flux ,'import_syndication');

        //Pour chaque destination de $tableau_description_flux['destinations'] as $destination
        foreach($tableau_description_flux['destinations'] as $cle=>$valeur_destination)
        {
            //spip_log("Cle destination $cle" ,'import_syndication');
            //spip_log("Valeur destination",'import_syndication');
            //spip_log($valeur_destination ,'import_syndication');
            //
            //spip_log("On teste la condition" ,'import_syndication');
            $condition = $tableau_description_flux['destinations'];
            //spip_log($condition, 'import_syndication');

           
            // On teste si les conditions dans la description sont les meme que celle de item_tableau
                if(tester_condition($item_tableau, $condition) == 1){
                $id_rubrique=$cle;
                spip_log("Valeur id_rubrique_cible: $id_rubrique_cible",'import_syndication');
                //$retour=$retour ET traiter_article($Item_tableau, ,$flux,$id_rubrique_cible);
                traiter_article($item_tableau, $tableau_description_flux, $id_article, $id_rubrique);
                }
        }
    }
   
    //spip_log(" flux parser : $tableau_xml_parser",'import_syndication');

    //retour traiter_xml
    return $retour;   
//fin function traiter_xml
    spip_log("***** FIN fonction traiter_xml() *****",'import_syndication');   
}

//
// Traite et recupere les champs et valeurs de l item pour les stocker en base dans l article $id_article
//
function traiter_article($item_tableau, $tableau_description_flux, $id_article, $id_rubrique){
/*
$item_tableau = un element complet du flux xml
$tableau_description_flux = description du flux
$id_article = id de l'article a traiter
$id_rubrique = rubrique ou stocker l article
*/
    spip_log("***** Dans la fonction traiter_article() *****\nVariable recu par traiter_article:\n item_tableau: $item_tableau\n id_article: $id_article\n id_rubrique: $id_rubrique\n",'import_syndication');
    //spip_log($tableau_description_flux ,'import_syndication');

    // creation des variables pour la requete de mise a jour
    // Mise a jour de l id_rubrique de l article $id_article
    $table_requete = "spip_articles";
    // Tableau stockant les champs et valeurs a mettre a jour
    $champs_valeurs_requete['id_rubrique'] = $id_rubrique;
    // condition WHERE pour la requete de mise a jour pour bien mettre a jour le bon article
    $condition_requete = "id_article=$id_article";

    // initialisation de la variable qui va contenir les elements sql pour la mise a jour champs=>valeurs
    $update = "";

    spip_log("requete initialisation: $table_requete\n update: $update\n condition_requete: $condition_requete\n",'import_syndication');
   
   
    // Pour chaque item du tableau on recuperer les balises
    foreach($item_tableau as $balises)
    {
        // Pour chaque balise recupere on stocke la valeur de la balise , le nom de la balise etant $balise
        foreach($balises as $balise => $tableau_valeur_balise)
        {
        spip_log("Pour la balise: $balise",'import_syndication');
       
        if(array_key_exists($balise, $tableau_description_flux['liste_champs'])){
            spip_log("Balise existante dans la description du flux",'import_syndication');
           
            // Valeur des donnees de la balise , donnee a mettre en base
            $valeur_balise = $tableau_valeur_balise[0];
            spip_log("Valeur balise",'import_syndication');
            spip_log($valeur_balise ,'import_syndication');
           
            // Type du champs, en fonction du champs, traitement cas par cas voir switch ci-dessous
            $champs = $tableau_description_flux['liste_champs'][$balise]['type'];
            spip_log("Type de champ $champs",'import_syndication');

            // Tableau contenant le descriptif total du champs_extra
            $valeurs_champs = $tableau_description_flux['liste_champs'][$balise]['champs_cibles'];
            spip_log("Valeurs champs en tableau",'import_syndication');
            spip_log($valeurs_champs ,'import_syndication');

            switch ($champs) {
                // Traite tous les champs extra contenu dans le flux
                case "champ_extra":
                spip_log(" Switch, type: $champs",'import_syndication');
                spip_log("\n Cas de type: $champs \n les valeurs des extras: $valeur_balise \n valeurs_champs:",'import_syndication');
                spip_log( $valeurs_champs,'import_syndication');
                traiter_champ_extra($update , $tableau_description_flux['liste_champs'][$balise], $valeur_balise);
                //$update = traiter_champ_extra($update , $valeurs_champs, $valeur_balise);
                break;
                // Traite les photos contenus dans le flux
                case "photo":
                spip_log("\nCas de type: $champs \n les photos: $valeur_balise et id_article $id_article",'import_syndication');
                traiter_champ_photo($champs,$valeur_balise, $id_article);           
                break;
                // Traite les documents du flux
                case "document":
                spip_log(" Switch, type :$champs ",'import_syndication');
                spip_log("\nCas de type: $champs \n les documents: $valeur_balise et id_article $id_article",'import_syndication');
                //traiter_champ_document($champ,$valeur_balise, $id_article);
                break;
                // Traite les coordonnees GPS du flux
                case "google_map":
                spip_log(" Switch, type :$champs ",'import_syndication');
                spip_log("\nCas de type: $champs \n les valeurs GPS: $valeur_balise et id_article $id_article",'import_syndication');
                //traiter_champ_google_map($champ,$valeur_balise, $id_article);
                break;
            // Fin switch suivant le type du champ
            }
        // Fin si balise persente dans $tableau_description_flux
        }
        // Fin Pour chaque balise
        }
    // Fin Pour chaque item du tableau on recuperer les balises
    }


    // S'il y a des champs a mettre a jour on utilise le contenu d update
    // $update = variable qui contient les elements sql pour la mise a jour champs=>valeurs
    if($update!=''){
    // Execution de la requete update creer en fonction du retour de traiter_champ_extra
    sql_updateq($table_requete,$update,$condition_requete);   
    // Execution de la requete pour mettre a jour le numero de rubrique de l article id_article
    sql_updateq($table_requete,$champs_valeurs_requete,$condition_requete);
    // TODO traiter valeur retour true ou false
    }
    else{
    spip_log("***** pas de mise a jour *****",'import_syndication');
    $requete_cond = "UPDATE spip_articles SET id_rubrique= '$id_rubrique' WHERE id_article='666875'";
    spip_log($requete_cond,'import_syndication');   
    }

    spip_log("MAJ terminee, requete finale executee sur la table: $table_requete\n Mise a jour des champs/valeurs: $update\n WHERE: $condition_requete\n",'import_syndication');


    spip_log("***** FIN fonction traiter_article() *****",'import_syndication');
    //retour traiter_article
    return $retour;
}


//
// Permet de recuperer les extras contenu dans le flux, chaque couple champs_extra valeur est ajouter au tableau $update qui sera utilise en parametre pour la requete de mise a jour sql_updateq de traiter_article()
//
function  traiter_champ_extra($update ,$valeurs_champs, $valeur_balise){
/*
$update = variable qui va contenir les elements sql pour la mise a jour champs=>valeurs
$valeurs_champs = nom des extras dans la description du flux = chacun doit correspondre a un champs extra un base
$valeur_balise = valeur contenu dans la balise du champ xml
*/
    spip_log("***** Dans la fonction traiter_champ_extra() *****",'import_syndication');
    spip_log("Valeur transmise a traiter_champ_extra: \n valeur_balise : $valeur_balise \n update :",'import_syndication');
    spip_log($update,'import_syndication');
    spip_log("\n valeurs_champs nom des extras dans la description :",'import_syndication');   
    spip_log($valeurs_champs,'import_syndication');
    $separateur = $valeurs_champs['separateur'];
    spip_log("valeur separateur :",'import_syndication');   
    spip_log($separateur,'import_syndication');
   
   
    if(empty($separateur)){
    spip_log("pas de separateur",'import_syndication');
    spip_log($valeurs_champs['champs_cibles'],'import_syndication');
   
    // Recuperation du nom du champs
    $champs = key($valeurs_champs['champs_cibles']);
    spip_log("cle du champ",'import_syndication');
    spip_log($champs,'import_syndication');   
    // Tableau stockant les champs et valeurs a mettre a jour

    // construction du tableau requete avec champs => valeur de la balise du flux
    $champs_valeurs_requete[$champs] = $valeur_balise;
    $update = $champs_valeurs_requete;
    spip_log("update a faire SI PAS de separateur",'import_syndication');
    spip_log($update,'import_syndication');
    //$valeurs_champs['separateur']
    //$update .= $valeur_champs[‘champ_cibles’]clé=> $valeur_champs[‘champ_cibles’][‘traitement’]($valeur_balise)   
    }
    else{
    spip_log("separateur present",'import_syndication');
    spip_log("valeur des champs cibles :",'import_syndication'); 
    spip_log($valeurs_champs['champs_cibles'],'import_syndication');

    // Recuperation de tous les champs presents cle du tableau $valeurs_champs
    $champs = array_keys($valeurs_champs['champs_cibles']);
    spip_log("valeur des champs a importer en base :",'import_syndication');   
    spip_log($champs,'import_syndication');   
    // Recuperation de chaque valeur de la balise suivant le separateur $separateur
        $valeurs_balise_split = explode($separateur, $valeur_balise);
    spip_log("valeurs de la balise :",'import_syndication');   
    spip_log($valeurs_balise_split,'import_syndication');
    $update = array_combine($champs, $valeurs_balise_split);
    spip_log("update a faire SI separateur",'import_syndication');
    spip_log($update,'import_syndication');
    }


    spip_log("Separateur: $separateur\nvaleurs de l UPDATE :",'import_syndication');   
    spip_log($update,'import_syndication');
//
//print_r($update);

    spip_log("***** FIN fonction traiter_champ_extra() *****",'import_syndication');
    //retour traiter_champ_extra
    return $update;
}

//
// Permet de recuperer les photos contenu dans le flux, la premiere est inseree en logo, les autres dans le portfolio
//
function traiter_champ_photo($champ, $valeur_balise, $id_article){
/*
$champ = definition du champ xml
$valeur_balise = valeur contenu dans la balise du champ xml
$id_article = id de l'article a traiter
*/
    spip_log("***** Dans la fonction traiter_champ_photo() *****",'import_syndication');
    spip_log("Valeur transmise a traiter_champ_photo: \n champ : $champ\r\n valeur_balise : $valeur_balise \r\n id_article : $id_article",'import_syndication');

    //Tableau contenant pour chaque enregistrement du document libelle|url_photo|credit
    $liste_photo = preg_split('/#/', $valeur_balise);
////print_r($liste_photo);
    spip_log("Nombre de photos presente: ".sizeof($liste_photo),'import_syndication');
    // pour chaque photo recuperer: libelle, url de la photo, credit
    //initialisation compteur de photos:
    $i=1;
    spip_log("Pour chaque element photos",'import_syndication');
    foreach($liste_photo as $cle=>$valeur_photo)
        {
            $tableau_photo = preg_split('/\|/', $valeur_photo);
            $titre = utf8_encode($tableau_photo[0]);
            $url_doc = $tableau_photo[1];
            $credit = utf8_encode($tableau_photo[2]);
    spip_log("Photo $i : \r\n Titre: $titre\r\n URL: $url_doc\r\n Credit: $credit",'import_syndication');
////print_r($tableau_photo);

            if($i == 1){
        spip_log("Première photo, appel affecter_logo_article a l'article $id_article",'import_syndication');
                affecter_logo_article($url_doc, $id_article);
            }
            else{
        spip_log("Photo en tant que document, appel joindre_document_distant a l'article $id_article",'import_syndication');
                joindre_document_distant($titre,$url_doc,$credit,$id_article);      
            }
    //incrementation compteur de photos:   
    $i++;
    }

//fin function traiter_champ_photo
    spip_log("***** FIN fonction traiter_champ_photo() *****",'import_syndication');
}

//
// Affecte la premiere image contenu dans le flux en tant que logo d'article
//
function affecter_logo_article($url_doc, $id_article){
    spip_log("***** Dans la fonction affecter_logo_article() ***** \n URL distante a recuperer: $url_doc \n Mettre la photo recuperer en logo de l'article: $id_article",'import_syndication');   

    $infos_photo = recuperer_infos_distantes($url_doc);
    $extension_photo = $infos_photo['extension'];
    $logo_article = 'arton'.$id_article.".".$extension_photo;
    spip_log("Type de photo(extension): $extension_photo \n La photo prendra un nouveau nom: $logo_article",'import_syndication');   

    if (!@copy($url_doc, _DIR_IMG.$logo_article)) {
        spip_log("La copie $file du fichier a echoue...\n",'import_syndication');
    }
    else{
        spip_log("fichier copier avec succes",'import_syndication');
    }
    spip_log("***** FIN fonction affecter_logo_article() *****",'import_syndication');       
}

//
// Inserer les photos dans le porfolio de l article sauf la premiere qui est traite par affecter_logo_article
//
function joindre_document_distant($titre, $url_doc, $credit, $id_article){
    spip_log("***** Dans la fonction joindre_document_distant() *****",'import_syndication');
    $type_lien = "article";
        if ($a = recuperer_infos_distantes($url_doc)) {
            # NB: dans les bonnes conditions (fichier autorise et pas trop gros)
            # $a['fichier'] est une copie locale du fichier

            $type_image = $a['type_image'];

            unset($a['type_image']);
            unset($a['body']);

            $a['date'] = date('Y-m-d H:i:s');
            $a['distant'] = 'oui';
            $a['mode'] = 'document';
            $a['fichier'] = $url_doc;
            }
        else {
            spip_log("Echec du lien vers le document $url_doc, abandon");
            return;
        }
                // On prepare le titre et le credit pour la requete
                $a['titre']= $titre;
                $a['credits']= $credit;
    spip_log("Information concernant l image distante: $a",'import_syndication');
    // Installer le document dans la base
    // attention piege semantique : les images s'installent en mode 'vignette'
    // note : la fonction peut "mettre a jour un document" si on lui
    // passe "mode=document" et "id_document=.." (pas utilise)
        $id = sql_insertq("spip_documents", $a);

        if ($id_article AND $id
        AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
        ) {
            sql_insertq('spip_documents_liens',
                    array('id_document' => $id,
                      'id_objet' => $id_article,
                      'objet' => $type_lien));
        } else spip_log("Pb d'insertion $id_article $type_lien");
    spip_log("***** FIN fonction joindre_document_distant() *****",'import_syndication');
    //retour joindre_document_distant
    return $id;
}

//
// Supprimer les documents de spip_documents et de spip_documents_liens en fonction de id_article donnee
//
function supprimer_doc_joint($id_article){
    spip_log("***** Dans la fonction supprimer_doc_joint() ***** \n Supprimer les documents de l'article: $id_article",'import_syndication');

    //On recupere tous les documents de cet article et on les effaces un par un
        $requete = sql_select('id_document','spip_documents_liens','id_objet='.$id_article);
        while ($documents_en_base = sql_fetch($requete)) {
                    $id_document = $documents_en_base['id_document'];
                    spip_log("on supprime".$documents_en_base['id_document'],'import_syndication');
                    sql_delete('spip_documents', 'id_document='.$id_document);
                    sql_delete('spip_documents_liens', 'id_document='.$id_document);
        }
    spip_log("Suppression des documents de l'article: $id_article terminee \n ***** FIN fonction supprimer_doc_joint() *****",'import_syndication');
}

//
// Stocke les informations GPS en base de donnees
//
function traiter_champ_google_map($champ, $valeur_balise, $id_article){
/*
$champ = definition du champ xml
$valeur_balise = valeur contenu dans la balise du champ xml Exemple : 3.4351176|43.6269781
$id_article = id de l'article a traiter
*/
    spip_log("***** Dans la fonction traiter_champ_google_map() *****\n enregistrement localisation, id_article: $id_article",'import_syndication');
    // Recuperation des coordonnees dans un tableau, [0] longitude, [1] latitude
    $tableau_gps = preg_split('/\|/', $valeur_balise);
    spip_log("Coordonnees GPS: $tableau_gps ",'import_syndication');
    $longitude = $tableau_gps[0];
    $latitude = $tableau_gps[1];
    spip_log("Coordonnees GPS: longitude: $longitude , latitude: $latitude ",'import_syndication');  
    // recuperation de id_gis de l article id_article
    $requete = sql_getfetsel('id_gis','spip_gis','id_article='.$id_article);
    $id_gis = $requete[id_gis];
    spip_log("Identifiant id_gis: $id_gis de l article id_article: $id_article ",'import_syndication');
       
    //update si id_gis existe
    if (!empty($id_gis)){
    spip_log("Mise a jour des coordonnees GPS",'import_syndication');
    $retour = sql_updateq("spip_gis", array(
        "id_article" => $id_article,
        "lonx" => $longitude,
        "lat" => $latitude,
        ),'id_gis = '.$id_gis);   
    }
    else{
    //insertion
    spip_log("Insertion des coordonnees GPS",'import_syndication');
    $retour = sql_insertq("spip_gis", array(
        "id_article" => $id_article,
        "lonx" => $longitude,
        "lat" => $latitude,
        ));
    }
   
    spip_log("***** FIN fonction traiter_champ_google_map() *****",'import_syndication');
    //retour traiter_champ_google_map()
    return $retour;
}

//
// Tester la valeur $condition et renvoie son evaluation qui retourne true ou false
//
function tester_condition($item_tableau, $condition){
/*
$item_tableau = Contenu xml de l article a importer
$condition = $condition = Remplacer dans la chaine condition les ‘[‘ par ‘$item_tableau[‘ + remplacer ] pr ‘]
$retour = eval $condition
*/
    spip_log("***** Dans la fonction teste_condition() *****\n Variable recu par tester_condition:",'import_syndication');
    //spip_log("Item_tableau:",'import_syndication');
    //spip_log($item_tableau,'import_syndication');   
    spip_log("La condition",'import_syndication');   
    // Ce log $condition provoque un bug...
    //spip_log($condition,'import_syndication');

  
    $recherche = array();
    $recherche[0] = '/\[/i';
    $recherche[1] = '/\] =/i';
    $remplace = array();
    $remplace[0] = '$item_tableau[0][';
    $remplace[1] = '][0] =';
    $condition_a_tester = preg_replace($recherche, $remplace, $condition);
   
    $cle=key($condition_a_tester);
    $condition = $condition_a_tester[$cle];
    $condition = "return(".$condition .");" ;

    spip_log("Resultat: condition egale: $condition",'import_syndication');
    //spip_log($condition,'import_syndication');
   
    spip_log("***** FIN fonction teste_condition() *****",'import_syndication');
    //retour teste_condition()
return eval($condition);
}

//
// Filtre la valeur est renvoi un texte formater soit en liste en en ligne
//
function filtre_donnees_en_texte($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_en_texte() *****\n Valeur egale: $valeur",'import_syndication');
    // Tester la presence de # et de | dans la valeur
    if (preg_match("/#/i", $valeur) && preg_match("/\|/i", $valeur)) {
    // Si present presentation en ligne des valeurs avec la premiere en gras en syntaxe spip {{ valeur }}
    $presentation = "en_ligne";
    } elseif (!preg_match("/\|/i", $valeur)) {
    // Si pas la presence de | presentation en liste des valeurs
    $presentation = "en_liste";
    }
    spip_log("Presentation: $presentation",'import_syndication');
    $retour ='';

    //Tableau contenant pour chaque enregistrement du document libelle|url_photo|credit
    $liste_item = preg_split('/#/', $valeur);
////print_r($liste_item);
    $nb_item = sizeof($liste_item);
    spip_log("Nombre d item present: ".$nb_item,'import_syndication');
    // pour chaque item recuperer: type, valeur complement

    spip_log("Pour chaque element format le contenu en fonction du nombre d item et du contenu",'import_syndication');
    if ($presentation == 'en_ligne'){
    foreach($liste_item as $cle=>$valeur_item)
        { 
        spip_log($valeur_item,'import_syndication');
        $tableau_item = preg_split('/\|/', $valeur_item);
        $titre_ligne = utf8_encode($tableau_item[0]);
        $contenu_ligne = $tableau_item[1];
        $complement = utf8_encode($tableau_item[2]);
        if (!empty($complement)){
        $contenu_ligne = $contenu_ligne."-".$complement;
        }
        $retour.="{{".$titre_ligne."}}:".$contenu_ligne;
        spip_log("Type: $titre_ligne\r\n valeur_type: $contenu_ligne\r\n complement: $complement",'import_syndication');
    }
    }
    else{
    foreach($liste_item as $cle=>$valeur_item)
        {
        echo $valeur_item."<br>";
    }
    }
    spip_log("Valeur: $retour\n***** FIN fonction filtre_donnees_en_texte() *****",'import_syndication');
    //retour filtre_donnees_en_texte()
return $retour;
}

//
// Recupere une valeur pouvant contenir plusieurs elements et optionnellement une liste de titre qui se placera en haut des valeurs
//
function filtre_donnees_en_tableau($valeur, $liste_titre=''){
/*
$valeur = valeur contenu dans une balise xml
$liste_titre = optionnel, une liste de titre qui se placera en haut des valeurs
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_en_tableau() *****\n valeur: $valeur , liste_titre:",'import_syndication');
    spip_log($liste_titre,'import_syndication');

    // Tester la presence de liste_titre
    if (is_array($liste_titre) && !empty($liste_titre) ) {
    $nb_titre= sizeof($liste_titre);
    spip_log("Nombre de titre: ".$nb_titre,'import_syndication');
    // Si presence de la variable liste_titre dans l'appel, alors presentation avec un titre au dessus des valeurs avec la premiere ligne en gras en syntaxe spip {{ valeur }}
    $presentation = "titre";
    spip_log("Presentation: ".$presentation,'import_syndication');   
    }
   
    //initialisation de la variable retour
    $retour ="";
    spip_log("Pour chaque element format le titre",'import_syndication');
    // Si besoin d afficher les titres
    if ($presentation == 'titre'){
    // Pour chaque element format le titre
    foreach($liste_titre as $cle=>$titre)
        { 
    spip_log($valeur,'import_syndication');
    //Recuperation des valeurs pour chaque enregistrement
    $retour .="|{{".$titre."}}";
    spip_log("retour ".$retour,'import_syndication');
    }
    // ajout du | final
    $retour .= "|";
    }

    //Tableau contenant pour chaque enregistrement du document contenu1|contenu2|contenu3....
    $liste_item = preg_split('/#/', $valeur);
    //Nombre d enregistrement
    $nb_item = sizeof($liste_item);
    spip_log("Nombre d item present: ".$nb_item,'import_syndication');
   
    // pour chaque item recuperer: les valeurs du contenu
    foreach($liste_item as $cle=>$valeur_item)
        {
    spip_log($valeur_item,'import_syndication');
    // Recuperer chaque valeur separement
    $tableau_item = preg_split('/\|/', $valeur_item);
    spip_log($tableau_item, 'import_syndication');
    spip_log("titre numero $i : \r\n Titre: $tableau_item[$i]\r\n",'import_syndication');
    // Mettre un pipe avant chaque valeur
    foreach($tableau_item as $cle=>$valeur_contenu)
        {
        $retour .= "|".$valeur_contenu;
        spip_log($valeur_contenu, 'import_syndication');
        }
    // ajout du | final
    $retour .= "|";
    }
    spip_log("***** FIN fonction filtre_donnees_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_en_tableau()
return $retour;
}

//
// Filtre les donnees pour les acces
//
function filtre_donnees_acces_en_tableau($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_acces_en_tableau() *****",'import_syndication');
   
    $retour = filtre_donnees_en_tableau($valeur, array("Point d'accès", "Nom", "Distance"));
                        
    spip_log("***** FIN fonction filtre_donnees_acces_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_acces_en_tableau()
return $retour;
}

//
// Filtre les donnees pour les tarifs
//
function filtre_donnees_tarifs_en_tableau($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_tarifs_en_tableau() *****",'import_syndication');
   
    $retour = filtre_donnees_en_tableau($valeur, array("Intitule Tarifs", "Saisonnalité", "Minimum", "Maximum", "Remarque"));
    spip_log("***** FIN fonction filtre_donnees_tarifs_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_tarifs_en_tableau()
return $retour;
}

?><?php
if (!defined('_ECRIRE_INC_VERSION')) return;

// Ce fichier permet de recuperer les flux de syndication de Tourinsoft et insere ou met a jour les articles en base
// Log du plugin dans /tmp/prive_import_syndication.log

//Librairie qui permet de traiter les documents
include_spip('inc/distant');


//
// Lancement du script avec comme argument la description de tous les flux
//
function lancement_traitement_flux($desciption_des_flux){
    /*
    $desciption_des_flux = description de tous les flux a recuperer
    */
    spip_log("***** Dans la fonction lancement_traitement_flux() *****\n Demarrage traitement import",'import_syndication');
    spip_log("Variable recu par lancement_traitement_flux \$desciption_des_flux : $desciption_des_flux",'import_syndication');
    spip_log($desciption_des_flux,'import_syndication');


    //Pour chaque item , affichage des cles valeur
    foreach($desciption_des_flux as $cle=>$balise)
    {
        $tableau_description_flux = $desciption_des_flux[$cle];
        spip_log("Un type de desciption_flux :",'import_syndication');
        //spip_log($tableau_description_flux,'import_syndication');
       
        // Traitement d un flux en particulier lecture et parsage
        @traiter_flux($tableau_description_flux);
    }

    //retour lancement_traitement_flux false
    return $retour;
//fin function lancement_traitement_flux
    spip_log("***** FIN fonction lancement_traitement_flux() *****",'import_syndication');
}



//
// Point d'entree du script determine si flux doit etre mis a jour ou creer suivant la date contenu dans le tableau $tableau_description_flux
//
function traiter_flux($tableau_description_flux){
    /*
    $tableau_description_flux = description entiere du flux
    */
    spip_log("***** Dans la fonction traiter_flux() *****",'import_syndication');
    spip_log("Variable recu par traiter_flux: Tableau \$tableau_desciption_flux :",'import_syndication');
    //spip_log($tableau_description_flux,'import_syndication');
   
    // recuperer type et id_module pour determiner le type de flux a recuperer
    $type = $tableau_description_flux['type'];
    $id_syndication = $tableau_description_flux['id_syndication'];
    spip_log("Traitement du flux: type: $type - id_syndication: $id_syndication :",'import_syndication');


    // TODO
    // Si pas de date en base pour ce flux
    $fonction = "getDetailBordereau";
   
    //Sinon date existante en base pour ce flux, on fait un get Diff
    //$fonction = "getDetailBordereauDiff";
    //Puis supprimer le reste
    //$fonction = "getDetailBordereauSuppr";


    // Lecture du flux
    $xml = @lire_flux($fonction, $type, $id_syndication,$date=0);
    //spip_log("xml : $xml",'import_syndication');
   
    // Parsage et traitement du flux
    $retour = @traiter_xml($xml, $tableau_description_flux);
    //spip_log("xml : $retour",'import_syndication');
   
    if($retour == true){
    //spip_log("----- traiter_flux() $fonction, $type, $id_syndication retour == OK -----",'import_syndication');   
    }
    else{
    //spip_log("----- traiter_flux() $fonction, $type, $id_syndication retour == NOK -----",'import_syndication');   
    }

    //retour traiter_flux false
    return $retour;
//fin function traiter_flux
    spip_log("***** FIN fonction traiter_flux() *****",'import_syndication');    
}

//
// Recupere et commence a lire le flux et retourne le xml du flux
//
function lire_flux($fonction, $type, $id_syndication,$date=0){
    /*
    $fonction = Type de requete get a effectuer
    $type = type de bordereau DEG, RES, HOT...
    $id_syndication = numero de l id syndication
    $date = date de derniere mise a jour
    */
    spip_log("***** Dans la fonction lire_flux() *****\nVariable recu par lire_flux:\n fonction: $fonction\n type: $type\n id_syndication: $id_syndication\n date: $date",'import_syndication');

    //construction de l url du flux a recuperer = url + argument du flux type_get, type de flux, id_syndication
    $flux = "http://cdt34.tourinsoft.com/soft/RechercheDynamique/Syndication/controle/syndication2.asmx/".$fonction."?idModule=".$id_syndication."&OBJETTOUR_CODE=".$type;

    // Ouverture du flux distant en lecture avec fopen
    $flux_lus = @fopen($flux, "r");
 
    // Test si le flux a ete recuperer
    if (!$flux_lus) {
    spip_log(" Impossible de lire le flux $flux",'import_syndication');
    $retour = false;
    break;
    }
    // si flux lisible et correct
    else{
    // Parcours du flux et stockage dans la varaible de retour
    while (!feof ($flux_lus)) {
        $retour .= fgets($flux_lus, 1024);
    }
    }
    //spip_log($retour,'import_syndication');
   
    //retour lire_flux, soit le flux $xml soit false
    return $retour;   
//fin function lire_flux
    spip_log("***** FIN fonction lire_flux() *****",'import_syndication');   
}


//
// Traite le flux pour determiner s il contient un nouvel article ou si c 'est une mise a jour d un article existant, supprime les documents de l article si c est une mise a jour
//
function traiter_xml($xml, $tableau_description_flux){
/*
$xml = xml a parser
$tableau_description_flux = description entiere du flux
*/ 
    spip_log("***** Dans la fonction traiter_xml() *****\nTraitement du tableau recu - Variable \$xml et le tableau de description des flux",'import_syndication');
    //spip_log($xml,'import_syndication');   
    //spip_log($tableau_description_flux,'import_syndication');
   
    // Inclusion des Methodes SPIP pour traiter les xml//
    $retour=true;
    include_spip('inc/xml');
   
    // Parsage du flux
    $tableau_xml_parser = spip_xml_parse($xml);
    // Test si le flux a pu etre parser
    if (!$tableau_xml_parser) {
          spip_log(" Impossible de parser ce flux $xml",'import_syndication');
      $retour = false;
    continue;
    }
   
    // Nombre d element a parser dans le flux
    $nb_detail = count($tableau_xml_parser['DataSet xmlns="http://www.faire-savoir.com/webservices/"'][0]['diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"'][0]['Listing xmlns=""'][0]);
    spip_log("Nb element detail: $nb_detail \n traitement du tableau pour le type $tableau_description_flux[type]",'import_syndication');

    // Pour chaque element detail du flux, traiter les donnees
    foreach($tableau_xml_parser['DataSet xmlns="http://www.faire-savoir.com/webservices/"'][0]['diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1"'][0]['Listing xmlns=""'][0] as $item_tableau)
    {

        // Recuperation de l id de item
        $id = $item_tableau[0]['ID'][0];

        // Recuperation de l id article en base de donnee       
        $id_article = sql_getfetsel('id_article','spip_articles', 'extra_id="'.$id.'"');
       
            spip_log("id de l item dans le flux $id et son id_article en base: $id_article ",'import_syndication');   
   
        // Si id_article present on supprime tous les docs de l article sinon on creer l article
        if(!empty($id_article)){
        spip_log("On supprime les documents de l article $id_article\n L id item : $id correspond a l id article $id_article en base" ,'import_syndication');
            supprimer_doc_joint($id_article);
        // TODO desaffecter_logo()
        }
        else{
        spip_log("Pas d article avec extra_id $id, on le creer" ,'import_syndication');
        sql_insertq('spip_articles', array('extra_id'=>$id));
        }


         //spip_log($tableau_description_flux ,'import_syndication');

        //Pour chaque destination de $tableau_description_flux['destinations'] as $destination
        foreach($tableau_description_flux['destinations'] as $cle=>$valeur_destination)
        {
            //spip_log("Cle destination $cle" ,'import_syndication');
            //spip_log("Valeur destination",'import_syndication');
            //spip_log($valeur_destination ,'import_syndication');
            //
            //spip_log("On teste la condition" ,'import_syndication');
            $condition = $tableau_description_flux['destinations'];
            //spip_log($condition, 'import_syndication');

           
            // On teste si les conditions dans la description sont les meme que celle de item_tableau
                if(tester_condition($item_tableau, $condition) == 1){
                $id_rubrique=$cle;
                spip_log("Valeur id_rubrique_cible: $id_rubrique_cible",'import_syndication');
                //$retour=$retour ET traiter_article($Item_tableau, ,$flux,$id_rubrique_cible);
                traiter_article($item_tableau, $tableau_description_flux, $id_article, $id_rubrique);
                }
        }
    }
   
    //spip_log(" flux parser : $tableau_xml_parser",'import_syndication');

    //retour traiter_xml
    return $retour;   
//fin function traiter_xml
    spip_log("***** FIN fonction traiter_xml() *****",'import_syndication');   
}

//
// Traite et recupere les champs et valeurs de l item pour les stocker en base dans l article $id_article
//
function traiter_article($item_tableau, $tableau_description_flux, $id_article, $id_rubrique){
/*
$item_tableau = un element complet du flux xml
$tableau_description_flux = description du flux
$id_article = id de l'article a traiter
$id_rubrique = rubrique ou stocker l article
*/
    spip_log("***** Dans la fonction traiter_article() *****\nVariable recu par traiter_article:\n item_tableau: $item_tableau\n id_article: $id_article\n id_rubrique: $id_rubrique\n",'import_syndication');
    //spip_log($tableau_description_flux ,'import_syndication');

    // creation des variables pour la requete de mise a jour
    // Mise a jour de l id_rubrique de l article $id_article
    $table_requete = "spip_articles";
    // Tableau stockant les champs et valeurs a mettre a jour
    $champs_valeurs_requete['id_rubrique'] = $id_rubrique;
    // condition WHERE pour la requete de mise a jour pour bien mettre a jour le bon article
    $condition_requete = "id_article=$id_article";

    // initialisation de la variable qui va contenir les elements sql pour la mise a jour champs=>valeurs
    $update = "";

    spip_log("requete initialisation: $table_requete\n update: $update\n condition_requete: $condition_requete\n",'import_syndication');
   
   
    // Pour chaque item du tableau on recuperer les balises
    foreach($item_tableau as $balises)
    {
        // Pour chaque balise recupere on stocke la valeur de la balise , le nom de la balise etant $balise
        foreach($balises as $balise => $tableau_valeur_balise)
        {
        spip_log("Pour la balise: $balise",'import_syndication');
       
        if(array_key_exists($balise, $tableau_description_flux['liste_champs'])){
            spip_log("Balise existante dans la description du flux",'import_syndication');
           
            // Valeur des donnees de la balise , donnee a mettre en base
            $valeur_balise = $tableau_valeur_balise[0];
            spip_log("Valeur balise",'import_syndication');
            spip_log($valeur_balise ,'import_syndication');
           
            // Type du champs, en fonction du champs, traitement cas par cas voir switch ci-dessous
            $champs = $tableau_description_flux['liste_champs'][$balise]['type'];
            spip_log("Type de champ $champs",'import_syndication');

            // Tableau contenant le descriptif total du champs_extra
            $valeurs_champs = $tableau_description_flux['liste_champs'][$balise]['champs_cibles'];
            spip_log("Valeurs champs en tableau",'import_syndication');
            spip_log($valeurs_champs ,'import_syndication');

            switch ($champs) {
                // Traite tous les champs extra contenu dans le flux
                case "champ_extra":
                spip_log(" Switch, type: $champs",'import_syndication');
                spip_log("\n Cas de type: $champs \n les valeurs des extras: $valeur_balise \n valeurs_champs:",'import_syndication');
                spip_log( $valeurs_champs,'import_syndication');
                traiter_champ_extra($update , $tableau_description_flux['liste_champs'][$balise], $valeur_balise);
                //$update = traiter_champ_extra($update , $valeurs_champs, $valeur_balise);
                break;
                // Traite les photos contenus dans le flux
                case "photo":
                spip_log("\nCas de type: $champs \n les photos: $valeur_balise et id_article $id_article",'import_syndication');
                traiter_champ_photo($champs,$valeur_balise, $id_article);           
                break;
                // Traite les documents du flux
                case "document":
                spip_log(" Switch, type :$champs ",'import_syndication');
                spip_log("\nCas de type: $champs \n les documents: $valeur_balise et id_article $id_article",'import_syndication');
                //traiter_champ_document($champ,$valeur_balise, $id_article);
                break;
                // Traite les coordonnees GPS du flux
                case "google_map":
                spip_log(" Switch, type :$champs ",'import_syndication');
                spip_log("\nCas de type: $champs \n les valeurs GPS: $valeur_balise et id_article $id_article",'import_syndication');
                //traiter_champ_google_map($champ,$valeur_balise, $id_article);
                break;
            // Fin switch suivant le type du champ
            }
        // Fin si balise persente dans $tableau_description_flux
        }
        // Fin Pour chaque balise
        }
    // Fin Pour chaque item du tableau on recuperer les balises
    }


    // S'il y a des champs a mettre a jour on utilise le contenu d update
    // $update = variable qui contient les elements sql pour la mise a jour champs=>valeurs
    if($update!=''){
    // Execution de la requete update creer en fonction du retour de traiter_champ_extra
    sql_updateq($table_requete,$update,$condition_requete);   
    // Execution de la requete pour mettre a jour le numero de rubrique de l article id_article
    sql_updateq($table_requete,$champs_valeurs_requete,$condition_requete);
    // TODO traiter valeur retour true ou false
    }
    else{
    spip_log("***** pas de mise a jour *****",'import_syndication');
    $requete_cond = "UPDATE spip_articles SET id_rubrique= '$id_rubrique' WHERE id_article='666875'";
    spip_log($requete_cond,'import_syndication');   
    }

    spip_log("MAJ terminee, requete finale executee sur la table: $table_requete\n Mise a jour des champs/valeurs: $update\n WHERE: $condition_requete\n",'import_syndication');


    spip_log("***** FIN fonction traiter_article() *****",'import_syndication');
    //retour traiter_article
    return $retour;
}


//
// Permet de recuperer les extras contenu dans le flux, chaque couple champs_extra valeur est ajouter au tableau $update qui sera utilise en parametre pour la requete de mise a jour sql_updateq de traiter_article()
//
function  traiter_champ_extra($update ,$valeurs_champs, $valeur_balise){
/*
$update = variable qui va contenir les elements sql pour la mise a jour champs=>valeurs
$valeurs_champs = nom des extras dans la description du flux = chacun doit correspondre a un champs extra un base
$valeur_balise = valeur contenu dans la balise du champ xml
*/
    spip_log("***** Dans la fonction traiter_champ_extra() *****",'import_syndication');
    spip_log("Valeur transmise a traiter_champ_extra: \n valeur_balise : $valeur_balise \n update :",'import_syndication');
    spip_log($update,'import_syndication');
    spip_log("\n valeurs_champs nom des extras dans la description :",'import_syndication');   
    spip_log($valeurs_champs,'import_syndication');
    $separateur = $valeurs_champs['separateur'];
    spip_log("valeur separateur :",'import_syndication');   
    spip_log($separateur,'import_syndication');
   
   
    if(empty($separateur)){
    spip_log("pas de separateur",'import_syndication');
    spip_log($valeurs_champs['champs_cibles'],'import_syndication');
   
    // Recuperation du nom du champs
    $champs = key($valeurs_champs['champs_cibles']);
    spip_log("cle du champ",'import_syndication');
    spip_log($champs,'import_syndication');   
    // Tableau stockant les champs et valeurs a mettre a jour

    // construction du tableau requete avec champs => valeur de la balise du flux
    $champs_valeurs_requete[$champs] = $valeur_balise;
    $update = $champs_valeurs_requete;
    spip_log("update a faire SI PAS de separateur",'import_syndication');
    spip_log($update,'import_syndication');
    //$valeurs_champs['separateur']
    //$update .= $valeur_champs[‘champ_cibles’]clé=> $valeur_champs[‘champ_cibles’][‘traitement’]($valeur_balise)   
    }
    else{
    spip_log("separateur present",'import_syndication');
    spip_log("valeur des champs cibles :",'import_syndication'); 
    spip_log($valeurs_champs['champs_cibles'],'import_syndication');

    // Recuperation de tous les champs presents cle du tableau $valeurs_champs
    $champs = array_keys($valeurs_champs['champs_cibles']);
    spip_log("valeur des champs a importer en base :",'import_syndication');   
    spip_log($champs,'import_syndication');   
    // Recuperation de chaque valeur de la balise suivant le separateur $separateur
        $valeurs_balise_split = explode($separateur, $valeur_balise);
    spip_log("valeurs de la balise :",'import_syndication');   
    spip_log($valeurs_balise_split,'import_syndication');
    $update = array_combine($champs, $valeurs_balise_split);
    spip_log("update a faire SI separateur",'import_syndication');
    spip_log($update,'import_syndication');
    }


    spip_log("Separateur: $separateur\nvaleurs de l UPDATE :",'import_syndication');   
    spip_log($update,'import_syndication');
//
//print_r($update);

    spip_log("***** FIN fonction traiter_champ_extra() *****",'import_syndication');
    //retour traiter_champ_extra
    return $update;
}

//
// Permet de recuperer les photos contenu dans le flux, la premiere est inseree en logo, les autres dans le portfolio
//
function traiter_champ_photo($champ, $valeur_balise, $id_article){
/*
$champ = definition du champ xml
$valeur_balise = valeur contenu dans la balise du champ xml
$id_article = id de l'article a traiter
*/
    spip_log("***** Dans la fonction traiter_champ_photo() *****",'import_syndication');
    spip_log("Valeur transmise a traiter_champ_photo: \n champ : $champ\r\n valeur_balise : $valeur_balise \r\n id_article : $id_article",'import_syndication');

    //Tableau contenant pour chaque enregistrement du document libelle|url_photo|credit
    $liste_photo = preg_split('/#/', $valeur_balise);
////print_r($liste_photo);
    spip_log("Nombre de photos presente: ".sizeof($liste_photo),'import_syndication');
    // pour chaque photo recuperer: libelle, url de la photo, credit
    //initialisation compteur de photos:
    $i=1;
    spip_log("Pour chaque element photos",'import_syndication');
    foreach($liste_photo as $cle=>$valeur_photo)
        {
            $tableau_photo = preg_split('/\|/', $valeur_photo);
            $titre = utf8_encode($tableau_photo[0]);
            $url_doc = $tableau_photo[1];
            $credit = utf8_encode($tableau_photo[2]);
    spip_log("Photo $i : \r\n Titre: $titre\r\n URL: $url_doc\r\n Credit: $credit",'import_syndication');
////print_r($tableau_photo);

            if($i == 1){
        spip_log("Première photo, appel affecter_logo_article a l'article $id_article",'import_syndication');
                affecter_logo_article($url_doc, $id_article);
            }
            else{
        spip_log("Photo en tant que document, appel joindre_document_distant a l'article $id_article",'import_syndication');
                joindre_document_distant($titre,$url_doc,$credit,$id_article);      
            }
    //incrementation compteur de photos:   
    $i++;
    }

//fin function traiter_champ_photo
    spip_log("***** FIN fonction traiter_champ_photo() *****",'import_syndication');
}

//
// Affecte la premiere image contenu dans le flux en tant que logo d'article
//
function affecter_logo_article($url_doc, $id_article){
    spip_log("***** Dans la fonction affecter_logo_article() ***** \n URL distante a recuperer: $url_doc \n Mettre la photo recuperer en logo de l'article: $id_article",'import_syndication');   

    $infos_photo = recuperer_infos_distantes($url_doc);
    $extension_photo = $infos_photo['extension'];
    $logo_article = 'arton'.$id_article.".".$extension_photo;
    spip_log("Type de photo(extension): $extension_photo \n La photo prendra un nouveau nom: $logo_article",'import_syndication');   

    if (!@copy($url_doc, _DIR_IMG.$logo_article)) {
        spip_log("La copie $file du fichier a echoue...\n",'import_syndication');
    }
    else{
        spip_log("fichier copier avec succes",'import_syndication');
    }
    spip_log("***** FIN fonction affecter_logo_article() *****",'import_syndication');       
}

//
// Inserer les photos dans le porfolio de l article sauf la premiere qui est traite par affecter_logo_article
//
function joindre_document_distant($titre, $url_doc, $credit, $id_article){
    spip_log("***** Dans la fonction joindre_document_distant() *****",'import_syndication');
    $type_lien = "article";
        if ($a = recuperer_infos_distantes($url_doc)) {
            # NB: dans les bonnes conditions (fichier autorise et pas trop gros)
            # $a['fichier'] est une copie locale du fichier

            $type_image = $a['type_image'];

            unset($a['type_image']);
            unset($a['body']);

            $a['date'] = date('Y-m-d H:i:s');
            $a['distant'] = 'oui';
            $a['mode'] = 'document';
            $a['fichier'] = $url_doc;
            }
        else {
            spip_log("Echec du lien vers le document $url_doc, abandon");
            return;
        }
                // On prepare le titre et le credit pour la requete
                $a['titre']= $titre;
                $a['credits']= $credit;
    spip_log("Information concernant l image distante: $a",'import_syndication');
    // Installer le document dans la base
    // attention piege semantique : les images s'installent en mode 'vignette'
    // note : la fonction peut "mettre a jour un document" si on lui
    // passe "mode=document" et "id_document=.." (pas utilise)
        $id = sql_insertq("spip_documents", $a);

        if ($id_article AND $id
        AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
        ) {
            sql_insertq('spip_documents_liens',
                    array('id_document' => $id,
                      'id_objet' => $id_article,
                      'objet' => $type_lien));
        } else spip_log("Pb d'insertion $id_article $type_lien");
    spip_log("***** FIN fonction joindre_document_distant() *****",'import_syndication');
    //retour joindre_document_distant
    return $id;
}

//
// Supprimer les documents de spip_documents et de spip_documents_liens en fonction de id_article donnee
//
function supprimer_doc_joint($id_article){
    spip_log("***** Dans la fonction supprimer_doc_joint() ***** \n Supprimer les documents de l'article: $id_article",'import_syndication');

    //On recupere tous les documents de cet article et on les effaces un par un
        $requete = sql_select('id_document','spip_documents_liens','id_objet='.$id_article);
        while ($documents_en_base = sql_fetch($requete)) {
                    $id_document = $documents_en_base['id_document'];
                    spip_log("on supprime".$documents_en_base['id_document'],'import_syndication');
                    sql_delete('spip_documents', 'id_document='.$id_document);
                    sql_delete('spip_documents_liens', 'id_document='.$id_document);
        }
    spip_log("Suppression des documents de l'article: $id_article terminee \n ***** FIN fonction supprimer_doc_joint() *****",'import_syndication');
}

//
// Stocke les informations GPS en base de donnees
//
function traiter_champ_google_map($champ, $valeur_balise, $id_article){
/*
$champ = definition du champ xml
$valeur_balise = valeur contenu dans la balise du champ xml Exemple : 3.4351176|43.6269781
$id_article = id de l'article a traiter
*/
    spip_log("***** Dans la fonction traiter_champ_google_map() *****\n enregistrement localisation, id_article: $id_article",'import_syndication');
    // Recuperation des coordonnees dans un tableau, [0] longitude, [1] latitude
    $tableau_gps = preg_split('/\|/', $valeur_balise);
    spip_log("Coordonnees GPS: $tableau_gps ",'import_syndication');
    $longitude = $tableau_gps[0];
    $latitude = $tableau_gps[1];
    spip_log("Coordonnees GPS: longitude: $longitude , latitude: $latitude ",'import_syndication');  
    // recuperation de id_gis de l article id_article
    $requete = sql_getfetsel('id_gis','spip_gis','id_article='.$id_article);
    $id_gis = $requete[id_gis];
    spip_log("Identifiant id_gis: $id_gis de l article id_article: $id_article ",'import_syndication');
       
    //update si id_gis existe
    if (!empty($id_gis)){
    spip_log("Mise a jour des coordonnees GPS",'import_syndication');
    $retour = sql_updateq("spip_gis", array(
        "id_article" => $id_article,
        "lonx" => $longitude,
        "lat" => $latitude,
        ),'id_gis = '.$id_gis);   
    }
    else{
    //insertion
    spip_log("Insertion des coordonnees GPS",'import_syndication');
    $retour = sql_insertq("spip_gis", array(
        "id_article" => $id_article,
        "lonx" => $longitude,
        "lat" => $latitude,
        ));
    }
   
    spip_log("***** FIN fonction traiter_champ_google_map() *****",'import_syndication');
    //retour traiter_champ_google_map()
    return $retour;
}

//
// Tester la valeur $condition et renvoie son evaluation qui retourne true ou false
//
function tester_condition($item_tableau, $condition){
/*
$item_tableau = Contenu xml de l article a importer
$condition = $condition = Remplacer dans la chaine condition les ‘[‘ par ‘$item_tableau[‘ + remplacer ] pr ‘]
$retour = eval $condition
*/
    spip_log("***** Dans la fonction teste_condition() *****\n Variable recu par tester_condition:",'import_syndication');
    //spip_log("Item_tableau:",'import_syndication');
    //spip_log($item_tableau,'import_syndication');   
    spip_log("La condition",'import_syndication');   
    // Ce log $condition provoque un bug...
    //spip_log($condition,'import_syndication');

  
    $recherche = array();
    $recherche[0] = '/\[/i';
    $recherche[1] = '/\] =/i';
    $remplace = array();
    $remplace[0] = '$item_tableau[0][';
    $remplace[1] = '][0] =';
    $condition_a_tester = preg_replace($recherche, $remplace, $condition);
   
    $cle=key($condition_a_tester);
    $condition = $condition_a_tester[$cle];
    $condition = "return(".$condition .");" ;

    spip_log("Resultat: condition egale: $condition",'import_syndication');
    //spip_log($condition,'import_syndication');
   
    spip_log("***** FIN fonction teste_condition() *****",'import_syndication');
    //retour teste_condition()
return eval($condition);
}

//
// Filtre la valeur est renvoi un texte formater soit en liste en en ligne
//
function filtre_donnees_en_texte($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_en_texte() *****\n Valeur egale: $valeur",'import_syndication');
    // Tester la presence de # et de | dans la valeur
    if (preg_match("/#/i", $valeur) && preg_match("/\|/i", $valeur)) {
    // Si present presentation en ligne des valeurs avec la premiere en gras en syntaxe spip {{ valeur }}
    $presentation = "en_ligne";
    } elseif (!preg_match("/\|/i", $valeur)) {
    // Si pas la presence de | presentation en liste des valeurs
    $presentation = "en_liste";
    }
    spip_log("Presentation: $presentation",'import_syndication');
    $retour ='';

    //Tableau contenant pour chaque enregistrement du document libelle|url_photo|credit
    $liste_item = preg_split('/#/', $valeur);
////print_r($liste_item);
    $nb_item = sizeof($liste_item);
    spip_log("Nombre d item present: ".$nb_item,'import_syndication');
    // pour chaque item recuperer: type, valeur complement

    spip_log("Pour chaque element format le contenu en fonction du nombre d item et du contenu",'import_syndication');
    if ($presentation == 'en_ligne'){
    foreach($liste_item as $cle=>$valeur_item)
        { 
        spip_log($valeur_item,'import_syndication');
        $tableau_item = preg_split('/\|/', $valeur_item);
        $titre_ligne = utf8_encode($tableau_item[0]);
        $contenu_ligne = $tableau_item[1];
        $complement = utf8_encode($tableau_item[2]);
        if (!empty($complement)){
        $contenu_ligne = $contenu_ligne."-".$complement;
        }
        $retour.="{{".$titre_ligne."}}:".$contenu_ligne;
        spip_log("Type: $titre_ligne\r\n valeur_type: $contenu_ligne\r\n complement: $complement",'import_syndication');
    }
    }
    else{
    foreach($liste_item as $cle=>$valeur_item)
        {
        echo $valeur_item."<br>";
    }
    }
    spip_log("Valeur: $retour\n***** FIN fonction filtre_donnees_en_texte() *****",'import_syndication');
    //retour filtre_donnees_en_texte()
return $retour;
}

//
// Recupere une valeur pouvant contenir plusieurs elements et optionnellement une liste de titre qui se placera en haut des valeurs
//
function filtre_donnees_en_tableau($valeur, $liste_titre=''){
/*
$valeur = valeur contenu dans une balise xml
$liste_titre = optionnel, une liste de titre qui se placera en haut des valeurs
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_en_tableau() *****\n valeur: $valeur , liste_titre:",'import_syndication');
    spip_log($liste_titre,'import_syndication');

    // Tester la presence de liste_titre
    if (is_array($liste_titre) && !empty($liste_titre) ) {
    $nb_titre= sizeof($liste_titre);
    spip_log("Nombre de titre: ".$nb_titre,'import_syndication');
    // Si presence de la variable liste_titre dans l'appel, alors presentation avec un titre au dessus des valeurs avec la premiere ligne en gras en syntaxe spip {{ valeur }}
    $presentation = "titre";
    spip_log("Presentation: ".$presentation,'import_syndication');   
    }
   
    //initialisation de la variable retour
    $retour ="";
    spip_log("Pour chaque element format le titre",'import_syndication');
    // Si besoin d afficher les titres
    if ($presentation == 'titre'){
    // Pour chaque element format le titre
    foreach($liste_titre as $cle=>$titre)
        { 
    spip_log($valeur,'import_syndication');
    //Recuperation des valeurs pour chaque enregistrement
    $retour .="|{{".$titre."}}";
    spip_log("retour ".$retour,'import_syndication');
    }
    // ajout du | final
    $retour .= "|";
    }

    //Tableau contenant pour chaque enregistrement du document contenu1|contenu2|contenu3....
    $liste_item = preg_split('/#/', $valeur);
    //Nombre d enregistrement
    $nb_item = sizeof($liste_item);
    spip_log("Nombre d item present: ".$nb_item,'import_syndication');
   
    // pour chaque item recuperer: les valeurs du contenu
    foreach($liste_item as $cle=>$valeur_item)
        {
    spip_log($valeur_item,'import_syndication');
    // Recuperer chaque valeur separement
    $tableau_item = preg_split('/\|/', $valeur_item);
    spip_log($tableau_item, 'import_syndication');
    spip_log("titre numero $i : \r\n Titre: $tableau_item[$i]\r\n",'import_syndication');
    // Mettre un pipe avant chaque valeur
    foreach($tableau_item as $cle=>$valeur_contenu)
        {
        $retour .= "|".$valeur_contenu;
        spip_log($valeur_contenu, 'import_syndication');
        }
    // ajout du | final
    $retour .= "|";
    }
    spip_log("***** FIN fonction filtre_donnees_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_en_tableau()
return $retour;
}

//
// Filtre les donnees pour les acces
//
function filtre_donnees_acces_en_tableau($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_acces_en_tableau() *****",'import_syndication');
   
    $retour = filtre_donnees_en_tableau($valeur, array("Point d'accès", "Nom", "Distance"));
                        
    spip_log("***** FIN fonction filtre_donnees_acces_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_acces_en_tableau()
return $retour;
}

//
// Filtre les donnees pour les tarifs
//
function filtre_donnees_tarifs_en_tableau($valeur){
/*
$valeur = valeur contenu dans une balise xml
$retour = $valeur
*/
    spip_log("***** Dans la fonction filtre_donnees_tarifs_en_tableau() *****",'import_syndication');
   
    $retour = filtre_donnees_en_tableau($valeur, array("Intitule Tarifs", "Saisonnalité", "Minimum", "Maximum", "Remarque"));
    spip_log("***** FIN fonction filtre_donnees_tarifs_en_tableau() *****",'import_syndication');
    //retour filtre_donnees_tarifs_en_tableau()
return $retour;
}

?> 
