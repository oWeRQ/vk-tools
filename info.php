<?php

$genresMap = array(
	1 => 'Rock',
	2 => 'Pop',
	3 => 'Rap & Hip-Hop',
	4 => 'Easy Listening',
	5 => 'Dance & House',
	6 => 'Instrumental',
	7 => 'Metal',
	21 => 'Alternative',
	8 => 'Dubstep',
	9 => 'Jazz & Blues',
	10 => 'Drum & Bass',
	11 => 'Trance',
	12 => 'Chanson',
	13 => 'Ethnic',
	14 => 'Acoustic & Vocal',
	15 => 'Reggae',
	16 => 'Classical',
	17 => 'Indie Pop',
	19 => 'Speech',
	22 => 'Electropop & Disco',
	18 => 'Other',
);

$response = json_decode(file_get_contents('audio.json'), true);

$duration = 0;
$genres = array();

foreach ($response['response']['items'] as $audio) {
	if (isset($audio['artist'])) {
		$duration += $audio['duration'];

		if (!isset($audio['genre_id']))
			$audio['genre_id'] = 18;

		@$genres[$audio['genre_id']]++;
	}
}

$hours = floor($duration / 3600);
$minuts = floor($duration / 60) % 60;
$seconds = $duration % 60;

arsort($genres);

printf("total time: %02d:%02d:%02d\n", $hours, $minuts, $seconds);
echo "genres: \n";
$i = 1;
foreach ($genres as $genre_id => $genre_count) {
	echo "  ".($i++).". ".$genresMap[$genre_id].": $genre_count\n";
}
