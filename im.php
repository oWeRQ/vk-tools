<?php

$users = [];
foreach (glob('ims/*') as $dir) {
	if (!is_dir($dir))
		continue;

	$name = basename($dir);
	$users[$name] = [];
	foreach (glob($dir.'/*.json') as $logFile) {
		preg_match('/\/([^\/]+)\.json$/u', $logFile, $logFileMatch);
		$users[$name][] = $logFileMatch[1];
	}
}

ksort($users);

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>im</title>
	<link href="css/style.css" rel="stylesheet" media="all" />
	<style>
		html {
			font: 13px Arial, sans-serif;
		}
		body {
			margin: 0;
		}
		q {
			background: #ccf;
		}
		.b-headerBar {
			margin-bottom: 10px;
			padding: 10px;
			background: #eee;
			border-bottom: 1px solid #ccc;
		}
		.b-headerBar-back {
			margin-right: 12px;
		}
		.b-headerBar-form {
			display: inline;
		}
		.b-messages {
			list-style: none;
			margin: 0 auto 50px;
			padding: 0;
			width: 600px;
		}
		.b-messages > li {
			padding: 4px;
			border-bottom: 1px solid #eee;
		}
		.b-messages > li:target {
			background: #fdd;
		}
		.b-messages-item_to {
			
		}
		.b-messages-item_from {
			background: #e6ffe6;
		}
		.b-messages-from {
			font-size: 11px;
			color: #666;
		}
		.b-messages-text {

		}
		li.b-messages-separator {
			margin: 20px 0 0;
			padding: 0 0 20px;
			border-top: 3px solid #DDD;
		}
		.b-users {

		}
		.b-chats {

		}
	</style>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
	<script type="text/javascript"  src="js/common.js"></script>
</head>
<body>
	<?php if (isset($_REQUEST['chat'])): ?>
		<?
		$chat = $_REQUEST['chat'];
		$chatMessages = json_decode(file_get_contents('ims/'.$chat.'.json'), true);
		
		$search = false;
		if (isset($_REQUEST['search'])) {
			$search = $_REQUEST['search'];

			$chatMessages = array_filter($chatMessages, function($chatMessage) use($search) {
				return preg_match('/'.$search.'/ui', $chatMessage['text']);
			});
		}

		$chatMessage = reset($chatMessages);
		$date = $chatMessage['date'];
		$chatMessageFrom = $chatMessage['from'];
		?>
		<div class="b-headerBar">
			<a class="b-headerBar-back" href="im.php">&laquo; back</a>

			<form class="b-headerBar-form" action="im.php">
				<input type="hidden" name="chat" value="<?=$chat?>">
				<input type="text" name="search" value="<?=$search?>">
				<input type="submit" value="Search">
			</form>
		</div>
		<ul class="b-messages">
			<?php foreach ($chatMessages as $chatMessage): ?>
				<?php

				if ($chatMessage['date'] - $date > 3600) {
					echo '<li class="b-messages-separator"></li>';
				}

				$date = $chatMessage['date'];
				?>
				<li id="<?=$chatMessage['id']?>" class="b-messages-item b-messages-item_<?=$chatMessageFrom == $chatMessage['from'] ? 'from' : 'to'?>">
					<div class="b-messages-from">
						<a href="#<?=$chatMessage['id']?>">#</a>
						<?=$chatMessage['date_format']?> <?=$chatMessage['from_name']?>
					</div>
					<div class="b-messages-text">
						<?
						$chatMessageText = $chatMessage['text'];
						//$chatMessageText= preg_replace_callback('/\/away\.php\?to=([^"]+)/', function($matches){
						//	return urldecode($matches[1]);
						//}, $chatMessageText);
						echo $chatMessageText;
						?>

						<?php if (!empty($chatMessage['images'])): ?>
							[images]
							<div class="b-messages-images">
								<?php foreach ($chatMessage['images'] as $image): ?>
									<a href="#" onclick="<?=$image['onclick']?>">
										<img src="<?=$image['thumb_src']?>" alt="">
									</a>
								<?php endforeach ?>
							</div>
						<?php elseif (!empty($chatMessage['wall_html'])): ?>
							<?=$chatMessage['wall_html']?>
							<?/*
							<q>
								<?=trim(strip_tags($chatMessage['wall_html']))?>
							</q>
							<!--<pre><?=htmlentities($chatMessage['wall_html'])?></pre>-->
							*/?>
						<?php endif ?>
					</div>
				</li>
			<?php endforeach ?>
		</ul>
	<?php else: ?>
		<ul class="b-users">
			<?php foreach ($users as $userName => $userChats): ?>
				<li>
					<?=$userName?>
					<ul class="b-chats">
						<?php foreach ($userChats as $userChat): ?>
							<li>
								<a href="?chat=<?=$userName?>/<?=$userChat?>"><?=$userChat?></a>
								<?php 
								$file = 'ims/'.$userName.'/'.$userChat.'.json';
								echo ' <small>('.round(filesize($file)/1024).'k)</small>';
								?>
							</li>
						<?php endforeach ?>
					</ul>
				</li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</body>
</html>
