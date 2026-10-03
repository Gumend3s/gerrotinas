<?php

declare(strict_types=1);

class Database
{
    private static ?self $instance = null;

    private string $host;
    private string $user;
    private string $password;
    private string $database;
    private int    $port;

    private ?PDO $dbh = null;
    private ?PDOStatement $stmt = null;

    private string $logStatement   = '';
    private array $logParameters = [];

    /**
     * Opens a PDO connection using the DB constant configuration.
     *
     * @throws PDOException If the connection fails.
     */
    public function __construct()
    {
        $this->host     = DB['HOST'];
        $this->user     = DB['USER'];
        $this->password = DB['PASSWORD'];
        $this->database = DB['DATABASE'];
        $this->port     = (int) DB['PORT'];

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->database);

        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->password, $options);
            //LOG CONNECTION CAN BE DONE HERE
        } catch (PDOException $e) {
            errorHandler(1, $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * Releases the statement and closes the PDO connection.
     */
    public function __destruct()
    {
        $this->stmt = null;
        $this->dbh  = null;
    }

    /**
     * Returns the single instance of the class (Singleton).
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Returns the available ENUM values defined for a table column.
     *
     * Retrieves the ENUM definition from the database schema and returns
     * its values as a comma-separated string.
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     *
     * @return string Comma-separated ENUM values.
     */
    public function getEnumValues(string $table, string $column): string
    {
        $this->query(
            "SELECT 
                REPLACE(COLUMN_TYPE, '''', '') AS enum_values
            FROM information_schema.COLUMNS 
            WHERE 
                TABLE_NAME = :table AND 
                COLUMN_NAME = :column"
        );

        $this->bind('table', $table);
        $this->bind('column', $column);

        return $this->fetchSingleRow()->enum_values;
    }

    /**
     * Prepares a SQL statement and resets log parameters.
     *
     * @param  string $sql  SQL with named (`:param`) or positional (`?`) placeholders.
     * @return void
     */
    public function query(string $sql): void
    {
        $this->logStatement = $sql;
        $this->logParameters = [];
        $this->stmt = $this->dbh->prepare($sql);
    }

    /**
     * Binds a value to a statement parameter, inferring the PDO type when omitted.
     *
     * @param  int|string $parameter  Placeholder name (`:param`) or position index.
     * @param  mixed      $value      Value to bind.
     * @param  int|null   $type       PDO::PARAM_* constant; auto-detected if null.
     * @return void
     */
    public function bind(int|string $parameter, mixed $value, ?int $type = null): void
    {
        $type ??= match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };

        $this->stmt->bindValue($parameter, $value, $type);
        $this->logParameters[$parameter] = $value;
    }

    /**
     * Binds multiple query parameters at once.
     *
     * Each array key is treated as the parameter name and its
     * corresponding value is bound to the prepared statement.
     *
     * @param array $params Parameters to bind.
     *
     * @return void
     */
    public function bindAll(array $params): void
    {
        foreach ($params as $key => $value) $this->bind($key, $value);
    }

    /**
     * Executes the prepared statement and logs write operations.
     *
     * @return bool True on success.
     * @throws PDOException If execution fails.
     */
    public function execute(): bool
    {
        $result = $this->stmt->execute();
        $this->log();

        return $result;
    }

    /**
     * Executes the statement and returns the first result row.
     *
     * @return object|false Row as stdClass, or false if no results.
     */
    public function fetchSingleRow(): object|false
    {
        $this->execute();

        return $this->stmt->fetch();
    }

    /**
     * Executes the statement and returns all result rows.
     *
     * @return object[] Array of stdClass objects, empty if no results.
     */
    public function fetchAllRows(): array
    {
        $this->execute();

        return $this->stmt->fetchAll();
    }

    /**
     * Returns the ID generated by the last INSERT.
     *
     * @return string Last inserted ID, or '0' if not applicable.
     */
    public function getLastInsertedId(): string
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Returns the number of rows affected by the last executed statement.
     * 
     * @return int Number of affected rows.
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Begins a database transaction.
     *
     * @return bool True on success.
     * @throws PDOException If a transaction is already active.
     */
    public function beginTransaction(): bool
    {
        return $this->dbh->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return bool True on success.
     * @throws PDOException If no transaction is active.
     */
    public function commit(): bool
    {
        return $this->dbh->commit();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool True on success.
     * @throws PDOException If no transaction is active.
     */
    public function rollback(): bool
    {
        return $this->dbh->rollBack();
    }

    /**
     * Adds a parameter to the write operation log without binding it to the statement.
     *
     * @param string $parameter Log parameter name.
     * @param mixed  $value     Log parameter value.
     *
     * @return void
     */
    public function addLogParameter(string $parameter, mixed $value): void
    {
        $this->logParameters[$parameter] = $value;
    }

    /**
     * Logs write operations (INSERT, UPDATE, DELETE); skips SELECT statements.
     * Appends the generated insert_id for INSERT operations.
     *
     * @return void
     */
    private function log(): void
    {
        $sql = trim($this->logStatement);

        if (stripos($sql, 'SELECT') === 0) return;

        $tokens = preg_split('/\s+/', $sql);
        $action = strtoupper($tokens[0] ?? '');
        $table  = ($action === 'INSERT') ? ($tokens[2] ?? '') : ($tokens[1] ?? '');

        if ($action === 'INSERT') $this->logParameters['insert_id'] = $this->dbh->lastInsertId();

        Helper::log($action, $table, $this->logParameters);
    }
}