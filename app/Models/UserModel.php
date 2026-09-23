<?php

class UserModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'name',
        'cpf',
        'password',
        'department',
        'level'
    ];
    private const UPDATABLE_FIELDS = [
        ...self::CREATABLE_FIELDS,
        'status'
    ];
    private const SEARCHABLE_FIELDS = [
        'id',
        'cpf'
    ];

    /**
     * Returns the available user departments defined in the database schema.
     *
     * @return string Comma-separated enum values.
     */
    public function getDepartments(): string
    {
        return $this->db->getEnumValues('users', 'department');
    }

    /**
     * Returns the available user access levels defined in the database schema.
     *
     * @return string Comma-separated enum values.
     */
    public function getLevels(): string
    {
        return $this->db->getEnumValues('users', 'level');
    }

    /**
     * Returns a user by the specified searchable field.
     *
     * @param int|string $value      Value to search.
     * @param string     $field      Search field.
     * @param bool       $activeOnly Whether to return only active users.
     *
     * @return object|false User object if found, otherwise false.
     */
    public function findBy(int|string $value, string $field = 'id', bool $activeOnly = false): object|false
    {
        if (!in_array($field, self::SEARCHABLE_FIELDS, true)) return false;

        $this->db->query(
            "
            SELECT 
                U.id, 
                U.name, 
                U.cpf, 
                U.department, 
                U.level, 
                U.status, 
                U.password 
            FROM users U 
            WHERE 
                U.{$field} = :value
                " . ($activeOnly ? ' AND U.status = :status' : '')
        );

        $this->db->bind("value", $value);
        if ($activeOnly) $this->db->bind('status', 'ACTIVE');

        return $this->db->fetchSingleRow();
    }

    /**
     * Returns all active users ordered by access level.
     *
     * @return array|false List of users or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query("SELECT U.id, U.name, U.cpf, U.department, U.level FROM users U WHERE U.status = 'ACTIVE' ORDER BY FIELD(U.level, 'BASIC', 'INTERMEDIATE', 'ADVANCED', 'ADMINISTRATOR') DESC");
        
        return $this->db->fetchAllRows();
    }

    /**
     * Updates one or more user fields.
     *
     * Only the provided updatable fields are included in the generated
     * UPDATE statement.
     *
     * @param string $id     User ID.
     * @param array  $fields Fields to update.
     *
     * @return bool True if the update was executed successfully, otherwise false.
     */
    public function update(int|string $id, array $fields): bool
    {
        $fields = array_intersect_key($fields, array_flip(self::UPDATABLE_FIELDS));

        if (empty($fields)) return false;

        $keys = array_keys($fields);
        $queryFields = implode(', ', array_map(fn($k) => "$k = :$k", $keys));

        $this->db->query("
            UPDATE users
            SET 
            " . $queryFields . "
            WHERE id = :id
        ");

        $this->db->bindAll($fields);

        $this->db->bind('id', $id);

        return $this->db->execute();
    }

    /**
     * Creates a new user.
     *
     * The provided array must contain all required user fields.
     * Additional fields are ignored.
     *
     * @param array $fields User data.
     *
     * @return int|false Id of the new user if it was created successfully,
     *                   otherwise false.
     */
    public function create(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        $this->db->query("
            INSERT INTO users(". implode(', ', self::CREATABLE_FIELDS) .") 
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")
        ");

        $this->db->bindAll($fields);

        if (!$this->db->execute()) return false;

        return $this->db->getLastInsertedId();
    }

    /**
     * Marks a user as deleted without permanently removing the record.
     *
     * @param string $id User ID.
     *
     * @return bool True if the user was marked as deleted, otherwise false.
     */
    public function softDelete(int|string $id): bool
    {
        $this->db->query("
            UPDATE users
            SET 
            status = :status
            WHERE id = :id
        ");

        $this->db->bind('id', $id);
        $this->db->bind('status', 'DELETED');

        return $this->db->execute();
    }

    /**
     * Returns active users prepared for CSV export.
     *
     * @return array|false List of users or false on failure.
     */
    public function export(): array|false
    {
        $this->db->query("
            SELECT 
                U.id, 
                U.name, 
                U.cpf, 
                U.department, 
                U.level, 
                U.created_at, 
                U.updated_at, 
                U.status 
            FROM users U 
            WHERE 
                U.status = 'ACTIVE' 
            ORDER BY FIELD(U.level, 'BASIC', 'INTERMEDIATE', 'ADVANCED', 'ADMINISTRATOR') DESC
        ");
        return $this->db->fetchAllRows();
    }
}
