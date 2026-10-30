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
    private const SEARCHABLE_FIELDS = ['id', 'name', 'project_id', 'status'];

    /**
     * Retrieves the routine priority values defined in the database schema.
     *
     * @return string Comma-separated list of valid priority values.
     */
    public function getPriorities(): string
    {
        return $this->db->getEnumValues('routines', 'priority');
    }

    /**
     * Retrieves recurrence rule values defined in the database schema.
     *
     * @return string Comma-separated list of valid recurrence rule values.
     */
    public function getRecurrenceRules(): string
    {
        return $this->db->getEnumValues('routines', 'recurrence_rule');
    }

    /**
     * Finds a routine by the specified searchable fields.
     *
     * Only supported searchable fields are considered. If no valid search fields
     * are provided, the method returns false. Optionally, the search can be
     * restricted by including a status value in $searchValues.
     *
     * @param array $searchValues Values for supported routine fields.
     *
     * @return object|false The routine data object if found, or false otherwise.
     */
    public function findBy(array $searchValues): object|false
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
                P.name AS project_name,
                R.priority,
                R.is_recurrent,
                R.recurrence_rule,
                R.recurrence_value,
                P.business_id,
                R.status
            FROM routines R
            INNER JOIN projects P ON
                P.id = R.project_id
            WHERE
                $conditions"
        );

        $this->db->bindAll($searchValues);

        return $this->db->fetchSingleRow();
    }

    /**
     * Retrieves active routines with their project, creator, and business names.
     *
     * Results are ordered by routine name.
     *
     * @return array|false List of active routines, or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query(
            "SELECT
                R.id,
                R.name,
                R.is_recurrent,
                R.priority,
                P.name AS project_name,
                U.name AS creator_name,
                B.legal_name AS business_name
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
     * Retrieves active routines associated with the specified business.
     *
     * Results include project and creator names and are ordered by routine name.
     *
     * @param int|string $business_id Business ID used to filter the routines.
     *
     * @return array|false List of matching active routines, or false on failure.
     */
    public function getActiveByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT
                R.id,
                R.name,
                R.is_recurrent,
                R.priority,
                P.name AS project_name,
                U.name AS creator_name
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
     * Updates the specified fields of a routine.
     *
     * Only supported fields are included in the UPDATE statement. Returns false
     * if no valid fields are provided.
     *
     * @param int|string $id     Routine ID.
     * @param array      $fields Fields to update.
     *
     * @return bool True if the update executes successfully, otherwise false.
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
     * Creates a new routine from the provided data.
     *
     * Only supported creatable fields are retained. The creator ID is set from
     * the current user's session, overriding any supplied creator ID. The method
     * returns false if required fields are missing or the insert fails.
     *
     * @param array $fields Routine data.
     *
     * @return int|false New routine ID, or false if validation or insertion fails.
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
     * Marks a routine as deleted without permanently removing the record.
     *
     * @param int|string $id Routine ID.
     *
     * @return bool True if the update executes successfully, otherwise false.
     */
    public function softDelete(int|string $id): bool
    {
        $this->db->query(
            "UPDATE routines
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
     * Retrieves active routines for export, including project, creator, and
     * business names, recurrence settings, priority, status, and timestamps.
     *
     * Results are ordered by routine name.
     *
     * @return array|false List of active routines for export, or false on failure.
     */
    public function export(): array|false
    {
        $this->db->query(
            "SELECT
                R.id,
                R.name,
                R.is_recurrent,
                R.recurrence_rule,
                R.recurrence_value,
                R.priority,
                P.name AS project_name,
                U.name AS creator_name,
                B.legal_name AS business_name,
                R.status,
                R.created_at,
                R.updated_at
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
     * Retrieves active routines for a business export, including project and
     * creator names, recurrence settings, priority, status, and timestamps.
     *
     * Results are ordered by routine name.
     *
     * @param int|string $business_id Business ID used to filter the routines.
     *
     * @return array|false Matching active routines for export, or false on failure.
     */
    public function exportByBusiness(int|string $business_id): array|false
    {
        $this->db->query(
            "SELECT
                R.id,
                R.name,
                R.is_recurrent,
                R.recurrence_rule,
                R.recurrence_value,
                R.priority,
                P.name AS project_name,
                U.name AS creator_name,
                R.status,
                R.created_at,
                R.updated_at
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
}
