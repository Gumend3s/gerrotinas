<?php

class BusinessModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'legal_name',
        'trade_name',
        'cnpj',
    ];
    private const UPDATABLE_FIELDS = [
        ...self::CREATABLE_FIELDS,
        'status'
    ];
    private const SEARCHABLE_FIELDS = [
        'id',
        'cnpj'
    ];

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
        $conditions = implode(' AND ', array_map(fn($field) => "B.$field = :$field", $fields));

        $this->db->query(
            "SELECT
                B.id,
                B.legal_name,
                B.trade_name,
                B.cnpj,
                B.status
            FROM businesses B
            WHERE
                $conditions" . ($activeOnly ? ' AND B.status = :status' : '')
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
                B.id,
                B.legal_name,
                B.trade_name,
                B.cnpj
            FROM businesses B
            WHERE
                B.status = 'ACTIVE'
            ORDER BY
                B.trade_name ASC"
        );

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
            "UPDATE businesses
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
            "INSERT INTO businesses(" . implode(', ', self::CREATABLE_FIELDS) . ")
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
            "UPDATE businesses
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
                B.id,
                B.trade_name,
                B.cnpj,
                B.created_at,
                B.updated_at,
                B.status
            FROM businesses B
            WHERE
                B.status = 'ACTIVE'
            ORDER BY
                B.trade_name ASC"
        );
        
        return $this->db->fetchAllRows();
    }
}