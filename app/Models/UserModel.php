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
        'email',
        'status'
    ];

    /**
     * Retrieves the user levels defined by the database schema.
     *
     * @return string Comma-separated list of valid user levels.
     */
    public function getLevels(): string
    {
        return $this->db->getEnumValues('users', 'level');
    }

    /**
     * Finds a user matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields. Include a
     * status value in $searchValues to restrict the result by status.
     *
     * @param array $searchValues Values for supported user fields.
     *
     * @return object|false User data if found, otherwise false.
     */
    public function findBy(array $searchValues): object|false
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
                B.trade_name AS business_name,
                U.level,
                U.status,
                U.password
            FROM users U
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE
                $conditions"
        );

        $this->db->bindAll($searchValues);
        return $this->db->fetchSingleRow();
    }

    /**
     * Retrieves active users, ordered by access level.
     *
     * @return array|false List of active users, or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query(
            "SELECT
                U.id,
                U.name,
                U.email,
                B.trade_name AS business_name,
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
     * Retrieves active employees and administrators for a business.
     *
     * Results are ordered by access level.
     *
     * @param int|string $business_id Business ID used to filter the users.
     *
     * @return array|false Matching users, or false on failure.
     */
    public function getActiveByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT
                U.id,
                U.name,
                U.email,
                B.trade_name AS business_name,
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
     * @param int|string $id     User ID.
     * @param array      $fields Fields to update.
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
     * Creates a user from the provided data.
     *
     * The provided array must contain all required user fields.
     * Additional fields are ignored.
     *
     * @param array $fields User data.
     *
     * @return int|false New user ID, or false if validation or insertion fails.
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
     * @param int|string $id User ID.
     *
     * @return bool True if the update executes successfully, otherwise false.
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
     * Retrieves active users for CSV export, ordered by access level.
     *
     * @return array|false List of active users for export, or false on failure.
     */
    public function export(): array|false
    {
        $this->db->query(
            "SELECT
                U.id,
                U.name,
                U.email,
                B.trade_name AS business_name,
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
     * Retrieves active employees and administrators for a business export.
     *
     * Results include creation and update timestamps and status, and are
     * ordered by access level.
     *
     * @param int|string $business_id Business ID used to filter the users.
     *
     * @return array|false Matching users for export, or false on failure.
     */
    public function exportByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT
                U.id,
                U.name,
                U.email,
                B.trade_name AS business_name,
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
