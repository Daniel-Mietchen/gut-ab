<?php

require_once('korrekturen.php');

function renameAndFix($fields)
{
	$renames = array(
		'Autor' => 'author',
		'Hrsg' => 'editor',
		'Titel' => 'title',
		'Verlag' => 'publisher',
		'Zeitschrift' => 'journal',
		'Sammlung' => 'booktitle',
		'Reihe' => 'series',
		'Ort' => 'address',
		'Jahr' => 'year',
		'Monat' => 'month',
		'Tag' => FALSE, // Wird mit in Monat eingebaut
		'Ausgabe' => 'volume',
		'Seiten' => 'pages',
		'URL' => 'url',
		'ISBN' => 'ISBN',
		'ISSN' => 'ISSN',
		'Anmerkung' => 'note',
		'InLit' => false, // nicht uebernehmen
		'InFN' => false, // nicht uebernehmen
	);

	foreach($fields as $key => $val) {
		if(in_array($key, array_keys($renames))) {
			if($renames[$key] && $val)
				$ret[$renames[$key]] = $val;
		} else {
			print "Fehler, kann $key nicht uebersetzen.  Titel: ".$fields['Titel']."\n";
		}
	}

	// Tag einbauen
	if(isset($fields['Tag']) && $fields['Tag'])
		$ret['month'] = $fields['Tag'].'. '.$ret['month'];

	if(isset($ret['pages']))
		$ret['pages'] = korrBereich($ret['pages']);

	// Titel korrigieren, Kapitaele exportieren
	foreach(array('title', 'booktitle') as $key) {
		if(isset($ret[$key])) {
			$ret[$key] = korrString($ret[$key]);
			$ret[$key] = preg_replace('/([A-Z])/', '{$1}', $ret[$key]);
		}
	}

	// - durch -- ersetzen, wenn es passt
	foreach(array('title', 'publisher') as $key)
		if(isset($ret[$key]))
			$ret[$key] = korrDash($ret[$key]);

	// , durch 'and' ersetzen bei den autoren
	foreach(array('author', 'editor') as $key)
		if(isset($ret[$key]))
			$ret[$key] = str_replace(',', ' and ', $ret[$key]);

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

$s = unserialize(file_get_contents('http://de.guttenplag.wikia.com/api.php?action=query&list=categorymembers&cmtitle=Kategorie:Quelle&format=php&cmlimit=500'));

$s = $s['query']['categorymembers'];

$i = 0;
$pageids = '';
$entries = array();
foreach($s as $cat) {
	$pageids .= $cat['pageid'].'|';
	if(++$i === 49) {
		$i = 0;
		$e = unserialize(file_get_contents('http://de.guttenplag.wikia.com/api.php?action=query&prop=revisions&rvprop=content&format=php&pageids='.$pageids));
		$entries = array_merge($entries, $e['query']['pages']);
		$pageids = '';
	}
}
$e = unserialize(file_get_contents('http://de.guttenplag.wikia.com/api.php?action=query&prop=revisions&rvprop=content&format=php&pageids='.$pageids));
if(isset($e['query']['pages']))
	$entries = array_merge($entries, $e['query']['pages']);

$catFile = fopen('categories.php', 'w');
fwrite($catFile, '<?php $categories = array(');

foreach($entries as $entry) {
	if(preg_match('/{{Quelle/', $entry['revisions'][0]['*']) === 1) {
		preg_match('/{{Quelle(.*)}}/s', $entry['revisions'][0]['*'], $matches);

		if(!isset($matches[1])) {
			print 'Probleme mit '.$entry['title']."\n";
			continue;
		}

		$text = $matches[1];
		preg_match_all('/|\s*(\w+)\s*=\s*([^|]+)/', $text, $matches);
		$i = 0;
		$fields = array();
		while(isset($matches[1][$i])) {
			if($matches[1][$i])
				$fields[$matches[1][$i]] = trim($matches[2][$i]);
			$i++;
		}

		$fields = renameAndFix($fields);

		if((!isset($fields['author']) && !isset($fields['editor']))
				|| !isset($fields['title'])) {
			print 'Fehlender Autor/Titel: '.$entry['title']."\n";
			continue;
		}

		$type = decideType($fields);

		echo '@'.$type.'{'.titleToKey($entry['title']).",\n";
		foreach($fields as $key => $val) {
			echo "	$key = {".$val."},\n";
		}
		echo "}\n";

		fwrite($catFile, '\''.titleToKey($entry['title']).'\',');
	} else {
		print 'XXX: Ignoriere Quelle: '.$entry['title']."\n";
	}
}
fwrite($catFile, '); ?>');
fclose($catFile);
