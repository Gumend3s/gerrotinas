<?php

class ActionModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = ['name', 'priority', 'creator_id', 'routine_id'];
    private const UPDATABLE_FIELDS = ['name', 'priority', 'status'];
    private const SEARCHABLE_FIELDS = ['id', 'name', 'routine_id'];

    /**
     * Retrieves the available priority values defined in the database schema.
     *
     * @return string Comma-separated list of valid priority values.
     */
    public function getPriorities(): string
    {
        return $this->db->getEnumValues('routines', 'priority');
    }

    /**
     * Returns an action matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields and optionally
     * restricts the search to active actions.
     *
     * @param array $searchValues Searchable field values.
     * @param bool  $activeOnly   Whether to restrict the search to active actions.
     *
     * @return object|false Action data object if found, otherwise false.
     */
    public function findBy(array $searchValues, bool $activeOnly = false): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "A.$field = :$field", $fields));

        $this->db->query(
            "SELECT
                A.id,
                A.name,
                A.routine_id,
                A.priority,
                A.status
            FROM actions A
            WHERE
                $conditions" . ($activeOnly ? ' AND A.status = :status' : '')
        );

        $this->db->bindAll($searchValues);

        if ($activeOnly) $this->db->bind('status', 'ACTIVE');

        return $this->db->fetchSingleRow();
    }

    /**
     * Returns all active actions from a routine ordered by name.
     *
     * @return array|false List of actions or false on failure.
     */
    public function getRoutineActiveActions(int|string $routineId): array|false
    {
        $this->db->query(
            "SELECT
                A.id,
                A.name,
                U.name as creator_name, 
                A.priority
            FROM actions A
            INNER JOIN users U ON
                U.id = A.creator_id
            WHERE
                A.status = 'ACTIVE' AND
                A.routine_id = :routine_id
            ORDER BY
                A.name ASC"
        );

        $this->db->bind('routine_id', $routineId);

        return $this->db->fetchAllRows();
    }

    /**
     * Updates one or more action fields.
     *
     * Only the provided updatable fields are included in the generated
     * UPDATE statement.
     *
     * @param int|string $id     Action ID.
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
            "UPDATE actions
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
     * Creates a new action.
     *
     * The provided array must contain all required action fields.
     * Additional fields are ignored.
     *
     * @param array $fields Action data.
     * 
     *  @return int|false Id of the new action if it was created successfully,
     *                   otherwise false.
     */
    public function create(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));
        $fields['creator_id'] = $_SESSION[APP_SESSION_KEY]['user_id'];

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        $this->db->query(
            "INSERT INTO actions(" . implode(', ', self::CREATABLE_FIELDS) . ")
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
        );

        $this->db->bindAll($fields);

        if (!$this->db->execute()) return false;

        return $this->db->getLastInsertedId();
    }

    /**
     * Marks an action, its active jobs and active shifts as deleted and
     * removes all relationships from its jobs within the same transaction.
     *
     * If any active shift from the action has an active position, no changes
     * are persisted.
     *
     * @param int|string $id Action ID.
     *
     * @return bool True if the action was marked as deleted and its jobs'
     *              relationships were removed successfully, otherwise false.
     *
     * @throws Throwable If an error occurs while executing the transaction.
     */
    public function softDelete(int|string $id): bool
    {
        $this->db->query(
            "UPDATE actions
            SET
                A.status = :status
            WHERE
                A.id = :id"
        );

        return $this->db->execute();
    }

    /**
     * Retrieves active actions from a routine for CSV export.
     *
     * Returns the active actions associated with the specified routine.
     *
     * @param int|string $routine_id Routine ID.
     *
     * @return array|false List of actions or false on failure.
     */
    public function exportRoutineActions(int|string $routine_id): array|false
    {
        $this->db->query(
            "SELECT
                A.id,
                A.name,
                U.name AS creator_name,
                A.priority,
                A.status,
                A.created_at,
                A.updated_at
            FROM actions A
            INNER JOIN users U ON
                U.id = A.creator_id
            WHERE
                A.status = 'ACTIVE' AND
                A.routine_id = :routine_id
            ORDER BY
                A.name ASC"
        );

        $this->db->bind('routine_id', $routine_id);

        return $this->db->fetchAllRows();
    }
}
