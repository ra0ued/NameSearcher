<?php

abstract class NameSearcher
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var string
     */
    protected $format;

    public function __construct(\PDO $PDO)
    {
        $this->pdo = $PDO;
    }

    /**
     * @param string $surname
     * @return array
     */
    protected function searchBySurname(string $surname): array
    {
        $sql = 'SELECT last_name
        FROM name
        WHERE last_name LIKE ?
        ORDER BY last_name';

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$surname . '%']);

        $results = [];

        while ($result = $statement->fetch()) {
            $results[] = $result['last_name'];
        }

        if (!$results) {
            echo 'No matches found in DB.' . PHP_EOL;

            exit;
        }

        return $results;
    }

    /**
     * @param string $result
     */
    protected function saveToFile(string $result)
    {
        try {
            file_put_contents(__DIR__ . '/SearchResults.' . $this->format, $result);
            echo 'Success! Check your SearchResults.' . $this->format . PHP_EOL;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;

            exit;
        }
    }
}

class HtmlResult extends NameSearcher
{
    /**
     * @var string
     */
    protected $format = 'html';

    /**
     * @param string $surname
     */
    public function saveResult(string $surname)
    {
        $results = $this->searchBySurname($surname);

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
        $body = '';
        foreach ($results as $result) {
            $body .= '<li>' . $result . '</li>';
        }

        $html = $head . $body . $footer;

        $this->saveToFile($html);
    }
}

class JsonResult extends NameSearcher
{
    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @param string $surname
     */
    public function saveResult(string $surname)
    {
        $results = $this->searchBySurname($surname);
        $json = json_encode($results);

        if (!$json) {
            echo 'JSON encoding failed.';
        }

        $this->saveToFile($json);
    }
}

class CommandLineUtil
{
    /**
     * @var string
     */
    private $shortOptions = 's:h';

    /**
     * @var array
     */
    private $longOptions = [
        'surname:',
        'help'
    ];

    /**
     * @return string
     */
    public function processOptions(): string
    {
        $options = getopt($this->shortOptions, $this->longOptions);

        if (empty($options)) {
            echo 'Usage: php search.php [options]' . PHP_EOL .
                '-s <surname> Search in DB by beginning of surname' . PHP_EOL;

            exit;
        }

        if (isset($options['h']) || isset($options['help'])) {
            echo 'Script searches all surnames in DB by the beginning of the surname passed as parameter.' . PHP_EOL .
                'Search results will be stored in SearchResults.' . PHP_EOL;

            exit;
        }

        $searchParameter = '';

        if (isset($options['s'])) {
            $searchParameter = $options['s'];
        }

        if (isset($options['surname'])) {
            $searchParameter = $options['surname'];
        }

        return $searchParameter;
    }
}

class DbUtil
{
    /**
     * @return PDO
     */
    public function getPDO(): \PDO
    {
        try {
            $pdo = new PDO($this->getConfig()['dsn'], $this->getConfig()['username'], $this->getConfig()['password']);

            return $pdo;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;

            exit;
        }
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return [
            'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=name',
            'username' => getenv('DB_USERNAME') ?: '',
            'password' => getenv('DB_PASSWORD') ?: '',
        ];
    }
}

$cli = new CommandLineUtil();
$dbUtil = new DbUtil();

$pdo = $dbUtil->getPDO();
$searchWord = $cli->processOptions();

$htmlResult = new HtmlResult($pdo);
$htmlResult->saveResult($searchWord);

$jsonResult = new JsonResult($pdo);
$jsonResult->saveResult($searchWord);