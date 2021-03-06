<?php

/**
 * RadioDJ Library Assistant
 * @link https://github.com/stefanopedrinazzi/RadioDJ-Library-Assistant
 * Version: 1.0.0
 *
 * Copyright 2017-2018 Stefano Pedrinazzi & Paolo Camozzi
 * Released under the MIT license
 * @link https://github.com/stefanopedrinazzi/RadioDJ-Library-Assistant/blob/master/LICENSE.md
 */

	include("FunctionNew.php");

	include("languages/".$_SESSION['language']);

	//connesione ai database
	$connectionrd=DBrd_connection();
	$connectionap=DBap_connection();

	global $db_namerd;
	global $db_nameap;

	mysqli_set_charset($connectionrd,"utf8");
	mysqli_set_charset($connectionap,"utf8");

	mysqli_select_db($connectionrd,$db_namerd);
	mysqli_select_db($connectionap,$db_nameap);

		//variabili ottenute dal $_POST di tracks_manager.php
		$ID_cat = $_POST['ID_cat'];

		$ID_subcat = $_POST['ID_subcat'];

		$ID_genre = $_POST['ID_genre'];

		$search = $_POST['Search'];

		$draw = $_POST['draw'];

		$length = $_POST['length'];

		$start = $_POST['start'];

		$old = $_POST['old'];

		$order=$_POST['order'];

		$explode = explode('~', $ID_cat);

		$category_ID=$explode[0];

		if($category_ID!=0){
		$category=$explode[1];
		}

		$song ="";

		$count="";

		//calcolo del valore di limit e di order by della query
		$limit=" LIMIT ".$length . " OFFSET " . $start;

		$array=Number_exception();

		$column=$order[0]['column'];

		$dir=$order[0]['dir'];

		switch ($column) {
			case '0':
				$ord="ORDER BY songs.title ".$dir;
				break;
			case '1':
				$ord="ORDER BY songs.artist ".$dir;
				break;
			case '2':
				$ord="ORDER BY songs.enabled ".$dir;
				break;
			
			default:
				$ord="ORDER BY songs.title ";
				break;
		}

		//utilizzo della vecchia query per creare la tabella
		if($old!="0"){

		$song=$_SESSION["song"];
		$count=$_SESSION["count"];

		//creazione nuova query in base ai parametri inseriti nelle select box dall'utente
		} else {

		if($category_ID=="0" && $ID_genre=="0" && $ID_subcat=="0"){

		$song="SELECT songs.ID, songs.artist, songs.title, songs.enabled FROM songs ";

		$count="SELECT COUNT(*) AS total FROM songs";

		}elseif ($ID_subcat=="0" && $ID_genre=="0"){
		
			$song="SELECT songs.ID, songs.artist, songs.title, songs.enabled FROM category JOIN subcategory ON category.ID=subcategory.parentid JOIN songs ON songs.id_subcat=subcategory.ID WHERE category.ID='$category_ID' ";
			
			$count="SELECT COUNT(*) AS total FROM category JOIN subcategory ON category.ID=subcategory.parentid JOIN songs ON songs.id_subcat=subcategory.ID WHERE category.ID='$category_ID'";

		}elseif($ID_subcat=="0" && $ID_genre!="0"){
			
			$song="SELECT songs.ID, songs.artist, songs.title, songs.enabled FROM category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_genre='$ID_genre' AND category.ID='$category_ID' ";

			$count="SELECT COUNT(*) AS total FROM category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_genre='$ID_genre' AND category.ID='$category_ID' ";
		
		}elseif($ID_subcat!="0" && $ID_genre=="0"){
			
			$song="SELECT songs.ID, songs.artist, songs.title, songs.enabled FROM category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_subcat='$ID_subcat' AND category.ID='$category_ID' ";

			$count="SELECT COUNT(*) AS total FROM category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_subcat='$ID_subcat' AND category.ID='$category_ID'";

		
		}elseif($ID_subcat!="0" && $ID_genre!="0"){
			
			$song="SELECT songs.ID, songs.artist, songs.title, songs.enabled FROM category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_genre='$ID_genre' AND songs.id_subcat='$ID_subcat' AND category.ID='$category_ID' ";

			$count="SELECT COUNT(*) AS total FROM  category JOIN subcategory ON subcategory.parentid=category.ID JOIN songs ON songs.id_subcat=subcategory.ID WHERE songs.id_genre='$ID_genre' AND songs.id_subcat='$ID_subcat' AND category.ID='$category_ID'";
		
		}

		//inserimento di LIKE nella query se valore di ricerca se presente
		if($search!="" && $category_ID=="0"){
			
			$app="";

			$app=" WHERE (songs.title LIKE '%$search%' OR songs.artist LIKE '%$search%') ";

			$song = $song."".$app;
		
			$count = $count."".$app;

		}

		if($search!="" && $category_ID!="0"){

			$app="";

			$app=" AND (songs.title LIKE '%$search%' OR songs.artist LIKE '%$search%') ";

			$song = $song."".$app;

			$count = $count."".$app;

		}

		$song=$song.$ord.$limit;

		}

		//impostazione delle variabili di sessione per ricordare il valore della query per il refresh 
		$_SESSION["song"]=$song;
		$_SESSION["count"]=$count;
		$_SESSION["ID_cat"]=$ID_cat;
		$_SESSION["ID_subcat"]=$ID_subcat;
		$_SESSION["ID_genre"]=$ID_genre;

		//creazione dell'oggetto da restituire al tracks manager per la creazione della tabella
		if($countquery=$connectionrd->query($count)){

			$query=$countquery->fetch_assoc();
	
			$total=$query['total'];
		}

		$elenco_songs=array('draw'=> $draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>array( ));

		if($songquery=$connectionrd->query($song)){

			while($riga=$songquery->fetch_assoc()){

			error_reporting(E_ERROR | E_WARNING | E_PARSE);
			$number=is_null($array[$riga['ID']]) ? 0 : $array[$riga['ID']];
			error_reporting(E_ALL);
				

				array_push($elenco_songs['data'], array('Titolo' => $riga['title'], 'Artista' => $riga['artist'], 'Abilitata' => $riga['enabled'], 'Eccezioni' => $number, 'Info' => $riga['ID'] , 'Azione'=>$riga['ID']));

			}
		}		

		echo json_encode($elenco_songs);

?>
