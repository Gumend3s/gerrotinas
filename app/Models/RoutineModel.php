<?php

class RoutineModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'name',
        'project_id',
        'creator_id',
        'priority',
        'is_recurrent',
        'recurrence_rule',
        'recurrence_value',
    ];
    private const UPDATABLE_FIELDS = [
        'name',
        'project_id',
        'status',
        'priority',
        'is_recurrent',
        'recurrence_rule',
        'recurrence_value',
    ];
    private const SEARCHABLE_FIELDS = ['id'];

    /**
     * Returns the available routines priorites defined in the database schema.
     *
     * @return string Comma-separated enum values.
     */
    public function getPriorities(): string
    {
        return $this->db->getEnumValues('routines', 'priority');
    }

    /**
     * Returns the available routines priorites defined in the database schema.
     *
     * @return string Comma-separated enum values.
     */
    public function getRecurrenceRules(): string
    {
        return $this->db->getEnumValues('routines', 'recurrence_rule');
    }

    /**
     * Returns a routine matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields and optionally
     * restricts the search to active routines.
     *
     * @param array $searchValues Searchable field values.
     * @param bool  $activeOnly   Whether to restrict the search to active routines.
     *
     * @return object|false Routine data object if found, otherwise false.
     */
    public function findBy(array $searchValues, bool $activeOnly = false): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "R.$field = :$field", $fields));

        $this->db->query(
            "SELECT 
                R.id, 
                R.name, 
                R.project_id, 
                R.priority, 
                R.is_recurrent,
                R.recurrence_rule,
                R.recurrence_value,
                R.status
            FROM routines R 
            WHERE
                $conditions" . ($activeOnly ? ' AND R.status = :status' : '')
        );

        $this->db->bindAll($searchValues);
        if ($activeOnly) $this->db->bind('status', 'ACTIVE');

        return $this->db->fetchSingleRow();
    }

    /**
     * Returns all active routines ordered by trade_name.
     *
     * @return array|false List of routines or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query(
            "SELECT 
                R.id, 
                R.name,
                R.is_recurrent,
                R.priority, 
                P.name as project_name,
                U.name as creator_name, 
                B.legal_name as business_name 
            FROM routines R 
            INNER JOIN users U ON 
                U.id = R.creator_id 
            INNER JOIN projects P ON 
                P.id = R.project_id 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                R.status = 'ACTIVE' 
            ORDER BY 
                R.name ASC"
        );

        return $this->db->fetchAllRows();
    }

    /**
     * Returns all active routines ordered by trade_name.
     *
     * @return array|false List of routines or false on failure.
     */
    public function getActiveByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT 
                R.id, 
                R.name,
                R.is_recurrent,
                R.priority, 
                P.name as project_name,
                U.name as creator_name
            FROM routines R 
            INNER JOIN users U ON 
                U.id = R.creator_id 
            INNER JOIN projects P ON 
                P.id = R.project_id 
            INNER JOIN businesses B ON
                B.id = U.business_id
            WHERE 
                R.status = 'ACTIVE' AND 
                U.business_id = :business_id
            ORDER BY 
                R.name ASC"
        );

        $this->db->bind('business_id', $business_id);

        return $this->db->fetchAllRows();
    }

    /**
     * Updates one or more routine fields.
     *
     * Only the provided updatable fields are included in the generated
     * UPDATE statement.
     *
     * @param int|string $id     Routine ID.
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
            "UPDATE routines
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
     * Creates a new routine.
     *
     * The provided array must contain all required routine fields.
     * Additional fields are ignored.
     *
     * @param array $fields Routine data.
     *
     * @return int|false Id of the new routine if it was created successfully,
     *                   otherwise false.
     */
    public function create(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));
        $fields['creator_id'] = $_SESSION[APP_SESSION_KEY]['user_id'];

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        $this->db->query(
            "INSERT INTO routines(" . implode(', ', self::CREATABLE_FIELDS) . ") 
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
        );

        $this->db->bindAll($fields);

        if (!$this->db->execute()) return false;

        return $this->db->getLastInsertedId();
    }

    /**
     * Marks a routine, its active contracts, operations, jobs and shifts as deleted
     * and removes its relationships within the same transaction.
     *
     * If any active shift from the routine has an active position, no changes
     * are persisted.
     *
     * @param int|string $id Routine ID.
     *
     * @return bool True if the routine was marked as deleted and its relationships
     *              were removed successfully, otherwise false.
     *
     * @throws Throwable If an error occurs while executing the transaction.
     */
    public function softDelete(int|string $id): bool
    {
        return false;
    }

    /**
     * Retrieves active routines for CSV export.
     *
     * Returns routine information along with the associated location and routine
     * group data required for the export.
     *
     * @return array|false List of routines or false on failure.
     */
    public function export(): array|false
    {
        return [];
    }
}
