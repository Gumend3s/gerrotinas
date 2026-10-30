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
    private const SEARCHABLE_FIELDS = ['id', 'name', 'routine_id', 'status'];

    /**
     * Retrieves the action priority values defined in the database schema.
     *
     * @return string Comma-separated list of valid priority values.
     */
    public function getPriorities(): string
    {
        return $this->db->getEnumValues('routines', 'priority');
    }

    /**
     * Finds an action matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields. Include a
     * status value in $searchValues to restrict the result by status.
     *
     * @param array $searchValues Values for supported action fields.
     *
     * @return object|false Action data if found, otherwise false.
     */
    public function findBy(array $searchValues): object|false
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
                $conditions"
        );

        $this->db->bindAll($searchValues);

        return $this->db->fetchSingleRow();
    }

    /**
     * Retrieves active actions for a routine, ordered by action name.
     *
     * @param int|string $routineId Routine ID.
     *
     * @return array|false List of active actions, or false on failure.
     */
    public function getRoutineActiveActions(int|string $routineId): array|false
    {
        $this->db->query(
            "SELECT
                A.id,
                A.name,
                U.name AS creator_name,
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
     * Only supported fields are retained, and the creator ID is taken from
     * the current user's session. The method returns false if required fields
     * are missing or the insert fails.
     *
     * @param array $fields Action data.
     *
     * @return int|false New action ID, or false if validation or insertion fails.
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
     * Executes the action soft-delete update.
     *
     * @param int|string $id Action ID.
     *
     * @return bool True if the update executes successfully, otherwise false.
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
     * @return array|false List of active actions for the routine, or false on failure.
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
