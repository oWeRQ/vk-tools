<?php

$response = json_decode(file_get_contents('audio.json'), true);

$playlist = "#EXTM3U\n";

foreach ($response['response']['items'] as $audio) {
	if (isset($audio['artist'])) {
		$name = $audio['artist'].' - '.$audio['title'];
		$name = preg_replace('/http:\/\/[\w.-_]+/i', '', $name);
		$name = preg_replace('/[^-_.() \w]/i', '', $name);
		$name = trim($name);
		$filename = $name.'.mp3';
		$filepath = "download/".$filename;
		if (!file_exists($filepath)) {
			echo "$filename\n";
			exec('wget "'.$audio['url'].'" -c -O "'.$filepath.'"');
		}

		$playlist .= "#EXTINF: ".$audio['duration'].", ".$name."\n";
		$playlist .= "$filename\n";
	}
}

file_put_contents('download/VKPlaylist.m3u', $playlist);