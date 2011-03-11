<?php

require_once('korrekturen.php');

function renameAndFix($source)
{
	if($source['title'] == 'Kategorie:Adams 1992') {
		$source['Hrsg'] = 'Willi Paul Adams';
		$source['Redakteur'] = 'Holger Ehmke';
	} else if($source['title'] == 'Kategorie:Shell 1992') {
		$source['Hrsg'] = 'Willi Paul Adams';
		$source['Redakteur'] = 'Holger Ehmke';
	} else if($source['title'] == 'Kategorie:LZB NDS 2004') {
		$source['Hrsg'] = '{Niedersächsische Landeszentrale für politische Bildung}';
		$source['Schluessel'] = 'LZB NDS';
	} else if($source['title'] == 'Kategorie:Examen Europaeum Consortium o.J.') {
		$source['Hrsg'] = '{Examen Europaeum Consortium}';
	} else if($source['title'] == 'Kategorie:CRS Annotated Constitution 1992') {
		$source['Hrsg'] = '{Congressional Research Service}';
	} else if($source['title'] == 'Kategorie:U.S. Diplomatic Mission to Germany') {
		$source['Hrsg'] = '{U.S. Diplomatic Mission to Germany}';
	} else if($source['title'] == 'Kategorie:U.S. Diplomatic Mission to Germany 2004') {
		$source['Hrsg'] = '{U.S. Diplomatic Mission to Germany}';
	}

	// bei inkompatiblen Eintraegen warnen
	$categoryname = $source['title'];
	if(!isset($source['Titel']))
		print "WARNUNG: Quelle ohne \"Titel\": $categoryname\n";
	if(!isset($source['Jahr']))
		print "WARNUNG: Quelle ohne \"Jahr\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Verlag']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Verlag\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Ort']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Ort\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Reihe']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Reihe\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Ausgabe']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Ausgabe\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Sammlung']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Sammlung\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Hrsg']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Hrsg\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['Redakteur']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"Redakteur\": $categoryname\n";
	if(isset($source['Zeitschrift']) && isset($source['ISBN']))
		print "WARNUNG: Quelle mit \"Zeitschrift\" und \"ISBN\": $categoryname\n";
	if(!isset($source['Zeitschrift']) && isset($source['ISSN']))
		print "WARNUNG: Quelle ohne \"Zeitschrift\", aber mit \"ISSN\": $categoryname\n";


	$renames = array(
		'Autor' => 'author',
		'Titel' => 'title',
		'Jahr' => 'year',
		'Monat' => 'month',
		'Tag' => false, // Wird mit in Monat eingebaut
		'Zeitschrift' => 'journal',
		'Jahrgang' => 'volume',
		'Nummer' => 'number',
		'Verlag' => 'publisher',
		'Ort' => 'address',
		'Reihe' => 'series',
		'Ausgabe' => 'edition',
		'Sammlung' => 'booktitle',
		'Hrsg' => 'editor',
		'Redakteur' => 'copyed',
		'Seiten' => 'pages',
		'ISBN' => 'ISBN',
		'ISSN' => 'ISSN',
		'URL' => 'url',
		'Schluessel' => 'key',
		'Anmerkung' => 'note',
		'InLit' => false, // nicht uebernehmen
		'InFN' => false, // nicht uebernehmen
		'title' => false, // Wikititel nicht uebernehmen
	);

	foreach($source as $key => $val) {
		if(in_array($key, array_keys($renames))) {
			if($renames[$key] && $val)
				$ret[$renames[$key]] = $val;
		} else {
			print "Fehler, kann $key nicht uebersetzen! Quelle: {$source['title']}\n";
		}
	}

	// Tag einbauen
	if(isset($source['Tag']) && $source['Tag'])
		$ret['month'] = $source['Tag'].'. '.$ret['month'];

	// Weitere Korrekturen
	$korrs = array(
		'title' => array('korrString', 'korrVersalien', 'korrDash'),
		'booktitle' => array('korrString', 'korrVersalien'),
		'author' => array('korrBracket', 'korrAnd'),
		'editor' => array('korrBracket', 'korrAnd'),
		'copyed' => array('korrBracket', 'korrAnd'),
		'publisher' => array('korrAmpersand', 'korrDash'),
		'pages' => array('korrBereich'),
		'note' => array('korrStringWithLinks'),
		'year' => array('korrBracket'),
	);
	foreach($korrs as $key => $korrFunctions) {
		if(isset($ret[$key]))
			foreach($korrFunctions as $korrFunction)
				$ret[$key] = $korrFunction($ret[$key]);
	}

	return $ret;
}


function decideType($f)
{
	if(isset($f['journal']))
		return 'article';

	if(isset($f['booktitle']))
		return 'incollection';

	if(isset($f['publisher']))
		return 'book';

	return 'misc';
}


if(!file_exists('cache')) {
	print "Fehler: Cache existiert nicht! 'make cache' ausgefuehrt?\n";
	exit(1);
}
$cache = unserialize(file_get_contents('cache'));

foreach($cache['sources'] as $source) {
	if(count($source) >= 2) {
		$fields = renameAndFix($source);

		if(!isset($fields['title'])) {
			print 'Fehlender Titel: '.$source['title']."\n";
			continue;
		}

		$type = decideType($fields);

		echo '@'.$type.'{'.titleToKey($source['title']).",\n";
		foreach($fields as $key => $val) {
			echo "	$key = {".$val."},\n";
		}
		echo "}\n";

	} else {
		print 'XXX: Ignoriere Quelle: '.$source['title']."\n";
	}
}
