<?php

$shortOptions = 's:h';
$longOptions = [
    'surname:',
    'help'
];
$options = getopt($shortOptions, $longOptions);

if (empty($options)) {
    echo 'Usage: php search.php [options]' . PHP_EOL .
        '-s <surname> Search in DB by beginning of surname' . PHP_EOL;

    exit;
}

if (isset($options['h']) || isset($options['help'])) {
    echo 'Script searches all surnames in DB by the beginning of the surname passed as parameter.' . PHP_EOL .
        'Search results will be stored in SearchResults.html.' . PHP_EOL;

    exit;
}

$database = [
    'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=name',
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
];

$searchParameter = '';

if (isset($options['s'])) {
    $searchParameter = $options['s'];
}

if (isset($options['surname'])) {
    $searchParameter = $options['surname'];
}

$pdo = new PDO($database['dsn'], $database['username'], $database['password']);
$sql = 'SELECT last_name
        FROM name
        WHERE last_name LIKE ?
        ORDER BY last_name';

$statement = $pdo->prepare($sql);
$statement->execute([$searchParameter . '%']);

$head = '
<html>
	<head>
		<title>Search results</title>
	</head>

	<body>
	<h4>Found surnames:</h4>
	<ul>
	';

$footer = '
</ul>
</body>
</html>
';

$results = '';

while($result = $statement->fetch()) {
    $results .= '<li>' . $result['last_name'] . '</li>';
}

if(!$results) {
    echo 'No matches found in DB.' . PHP_EOL;

    exit;
}

$html = $head . $results . $footer;

if(file_put_contents(__DIR__ . '/SearchResults.html', $html)) {
    echo 'Success! Check your SearchResults.html.' . PHP_EOL;
};