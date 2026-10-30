<?php

class ProjectModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'name',
        'business_id',
        'creator_id',
    ];
    private const UPDATABLE_FIELDS = [
        'name',
        'status'
    ];
    private const SEARCHABLE_FIELDS = [
        'id',
        'name'
    ];

    /**
     * Returns a project matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields and optionally
     * restricts the search to active projects.
     *
     * @param array $searchValues Searchable field values.
     * @param bool  $activeOnly   Whether to restrict the search to active projects.
     *
     * @return object|false Project data object if found, otherwise false.
     */
    public function findBy(array $searchValues, bool $activeOnly = false): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "P.$field = :$field", $fields));

        $this->db->query(
            "SELECT
                P.id,
                P.name,
                P.status
            FROM projects P
            WHERE
                $conditions" . ($activeOnly ? ' AND P.status = :status' : '')
        );

        $this->db->bindAll($searchValues);
        if ($activeOnly) $this->db->bind('status', 'ACTIVE');

        return $this->db->fetchSingleRow();
    }

    /**
     * Returns all active projects ordered by name.
     *
     * @return array|false List of projects or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query(
            "SELECT
                P.id,
                P.name,
                U.name AS creator_name,
                B.legal_name AS business_name
            FROM projects P
            INNER JOIN users U ON
                U.id = P.creator_id
            INNER JOIN businesses B ON
                B.id = P.business_id
            WHERE
                P.status = 'ACTIVE'
            ORDER BY
                P.name"
        );

        return $this->db->fetchAllRows();
    }

    /**
     * Returns all active projects ordered by name.
     *
     * @return array|false List of projects or false on failure.
     */
    public function getActiveByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT
                P.id,
                P.name,
                U.name AS creator_name
            FROM projects P
            INNER JOIN users U ON
                U.id = P.creator_id
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE
                P.status = 'ACTIVE' AND
                U.business_id = :business_id
            ORDER BY
                P.name"
        );

        $this->db->bind('business_id', $business_id);

        return $this->db->fetchAllRows();
    }

    /**
     * Updates one or more project fields.
     *
     * Only the provided updatable fields are included in the generated
     * UPDATE statement.
     *
     * @param int|string $id     Project ID.
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
            "UPDATE projects
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
     * Creates a new project.
     *
     * The provided array must contain all required project fields.
     * Additional fields are ignored.
     *
     * @param array $fields Project data.
     *
     * @return int|false Id of the new project if it was created successfully,
     *                   otherwise false.
     */
    public function create(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));
        $fields['business_id'] = $_SESSION[APP_SESSION_KEY]['user_business_id'];
        $fields['creator_id'] = $_SESSION[APP_SESSION_KEY]['user_id'];

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        $this->db->query(
            "INSERT INTO projects(" . implode(', ', self::CREATABLE_FIELDS) . ")
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
        );

        $this->db->bindAll($fields);

        if (!$this->db->execute()) return false;

        return $this->db->getLastInsertedId();
    }

    /**
     * Soft deletes a project and removes its customer associations.
     *
     * Marks the project as deleted and sets the customer_group_id of
     * all associated routines to null.
     *
     * @param int|string $id Project ID.
     *
     * @return bool True if both operations were executed successfully, otherwise false.
     */
    public function softDelete(int|string $id): bool
    {
        $this->db->query(
            "UPDATE projects P
            LEFT JOIN routines R ON
                P.id = R.project_id
            SET
                P.status = :status,
                R.project_id = NULL
            WHERE
                P.id = :id"
        );

        $this->db->bind('id', $id);
        $this->db->bind('status', 'DELETED');

        return $this->db->execute();
    }
}
