<?php

declare (strict_types = 1);

interface SearcherInterface
{
    /**
     * @param string $surname
     */
    public function search(string $surname);
}

abstract class Searcher implements SearcherInterface
{
    /**
     * @var DbUtil
     */
    protected $db;
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(\DbUtil $db, \OutputInterface $output)
    {
        $this->db = $db;
        $this->output = $output;
    }

    /**
     * @param string $result
     * @param string $format
     */
    protected function saveToFile(string $result, string $format)
    {
        try {
            file_put_contents(__DIR__ . '/SearchResults.' . $format, $result);
            echo 'Success! Check your SearchResults.' . $format . PHP_EOL;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;

            exit;
        }
    }
}

class NameSearcher extends Searcher
{
    /**
     * @param string $surname
     */
    public function search(string $surname)
    {
        $nameList = $this->db->searchBySurname($surname);
        $results = $this->output->format($nameList);

        $this->saveToFile($results, $this->output->getFormat());
    }
}


interface OutputInterface
{
    /**
     * @param array $nameList
     * @return string
     */
    public function format(array $nameList): string;

    /**
     * @return string
     */
    public function getFormat(): string;
}

abstract class Output implements OutputInterface
{
    /**
     * @var string $format
     */
    protected $format = '';

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }
}

class HtmlOutput extends Output
{
    /**
     * @var string $format
     */
    protected $format = 'html';

    /**
     * @param array $nameList
     * @return string
     */
    public function format(array $nameList): string
    {
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
        foreach ($nameList as $result) {
            $body .= '<li>' . $result . '</li>';
        }

        $html = $head . $body . $footer;

        return $html;
    }
}

class JsonOutput extends Output
{
    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @param array $nameList
     * @return string
     */
    public function format(array $nameList): string
    {
        $json = json_encode($nameList);

        if (!$json) {
            echo 'JSON encoding failed.';
        }

        return $json;
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

    public function initDb()
    {
        $sql = 'CREATE TABLE name (
        id int NOT NULL AUTO_INCREMENT,
        last_name varchar(255) NOT NULL,
        first_name varchar(255),
        age int,
        PRIMARY KEY (ID)
        );
        INSERT INTO table_name (last_name, first_name, age)
        VALUES (`Skinner`, `Walter`, `43`);
        INSERT INTO table_name (last_name, first_name, age)
        VALUES (`Skin`, `William`, `12`);
        INSERT INTO table_name (last_name, first_name, age)
        VALUES (`Scully`, `Dana`, `28`);
        INSERT INTO table_name (last_name, first_name, age)
        VALUES (`Scull`, `Kyle`, `8`);
        INSERT INTO table_name (last_name, first_name, age)
        VALUES (`Mulder`, `Fox`, `31`);
        ';

        $statement = $this->getPDO()->prepare($sql);
        $statement->execute();
    }

    /**
     * @param string $surname
     * @return array
     */
    public function searchBySurname(string $surname): array
    {
        $sql = 'SELECT last_name
        FROM name
        WHERE last_name LIKE ?
        ORDER BY last_name';

        $statement = $this->getPDO()->prepare($sql);
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
$searchWord = $cli->processOptions();
$dbUtil = new DbUtil();
$dbUtil->initDb();

$htmlSearch = new NameSearcher($dbUtil, new \HtmlOutput());
$htmlSearch->search($searchWord);

$jsonSearch = new NameSearcher($dbUtil, new \JsonOutput());
$jsonSearch->search($searchWord);