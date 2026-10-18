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
        'email',
        'password',
        'business_id',
        'level'
    ];
    private const UPDATABLE_FIELDS = [
        ...self::CREATABLE_FIELDS,
        'status'
    ];
    private const SEARCHABLE_FIELDS = [
        'id',
        'email'
    ];

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
     * Returns a user matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields and optionally
     * restricts the search to active users.
     *
     * @param array $searchValues Searchable field values.
     * @param bool  $activeOnly   Whether to restrict the search to active users.
     *
     * @return object|false User data object if found, otherwise false.
     */
    public function findBy(array $searchValues, bool $activeOnly = false): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "U.$field = :$field", $fields));

        $this->db->query(
            "SELECT 
                U.id, 
                U.name, 
                U.email, 
                U.business_id, 
                B.trade_name as business_name, 
                U.level, 
                U.status, 
                U.password 
            FROM users U 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE
                $conditions" . ($activeOnly ? ' AND U.status = :status' : '')
        );

        $this->db->bindAll($searchValues);
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
        $this->db->query(
            "SELECT 
                U.id, 
                U.name, 
                U.email, 
                B.trade_name as business_name, 
                U.level 
            FROM users U 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                U.status = 'ACTIVE' 
            ORDER BY 
                FIELD(U.level, 'EMPLOYEE', 'ADMINISTRATOR', 'MASTER') DESC"
        );

        return $this->db->fetchAllRows();
    }

    /**
     * Retrieves active employees and administrators associated with a specific business.
     *
     * Results are ordered by user access level, with administrators listed
     * after employees.
     *
     * @param int|string $business_id Business ID used to filter the users.
     *
     * @return array|false List of users associated with the business or false on failure.
     */
    public function getActiveByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT 
                U.id, 
                U.name, 
                U.email, 
                B.trade_name as business_name, 
                U.level 
            FROM users U 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                U.status = 'ACTIVE' AND
                U.level IN ('EMPLOYEE', 'ADMINISTRATOR') AND
                U.business_id = :business_id
            ORDER BY 
                FIELD(U.level, 'EMPLOYEE', 'ADMINISTRATOR') DESC"
        );

        $this->db->bind('business_id', $business_id);

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

        $this->db->query(
            "UPDATE users
            SET 
                $queryFields
            WHERE 
                id = :id"
        );

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

        $this->db->query(
            "INSERT INTO users(" . implode(', ', self::CREATABLE_FIELDS) . ") 
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
        );

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
        $this->db->query(
            "UPDATE users
            SET 
                status = :status
            WHERE 
                id = :id"
        );

        $this->db->bind('id', $id);
        $this->db->bind('status', 'DELETED');

        return $this->db->execute();
    }

    /**
     * Retrieves active users for CSV export.
     *
     * Returns the user data required for the export, ordered by user level.
     *
     * @return array|false List of users or false on failure.
     */
    public function export(): array|false
    {
        $this->db->query(
            "SELECT 
                U.id, 
                U.name, 
                U.email, 
                B.trade_name as business_name, 
                U.level, 
                U.created_at, 
                U.updated_at, 
                U.status 
            FROM users U 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                U.status = 'ACTIVE' 
            ORDER BY 
                FIELD(U.level, 'EMPLOYEE', 'ADMINISTRATOR', 'MASTER') DESC"
        );

        return $this->db->fetchAllRows();
    }

    /**
     * Retrieves active employees and administrators associated with a specific business for CSV export.
     *
     * Returns the user data required for the export, including creation and
     * update timestamps and status, ordered by user access level.
     *
     * @param int|string $business_id Business ID used to filter the users.
     *
     * @return array|false List of users for export or false on failure.
     */
    public function exportByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT 
                U.id, 
                U.name, 
                U.email, 
                B.trade_name as business_name, 
                U.level, 
                U.created_at, 
                U.updated_at, 
                U.status 
            FROM users U 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                U.status = 'ACTIVE' AND
                U.level IN ('EMPLOYEE', 'ADMINISTRATOR') AND
                U.business_id = :business_id
            ORDER BY 
                FIELD(U.level, 'EMPLOYEE', 'ADMINISTRATOR') DESC"
        );

        $this->db->bind('business_id', $business_id);

        return $this->db->fetchAllRows();
    }
}
