<?php

class AssignedRoutineModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'routine_id',
        'employee_id',
        'assigner_id',
        'start_date',
        'end_date',
    ];
    private const SEARCHABLE_FIELDS = ['id', 'routine_id', 'employee_id', 'assigner_id', 'completion_status'];

    /**
     * Retrieves completion status values defined in the database schema.
     *
     * @return string Comma-separated list of valid completion status values.
     */
    public function getCompletionStatuses(): string
    {
        return $this->db->getEnumValues('assigned_routines', 'completion_status');
    }

    /**
     * Finds an assigned routine by the specified searchable fields.
     *
     * Only supported searchable fields are considered. If no valid search fields
     * are provided, the method returns false.
     *
     * @param array $searchValues Values for supported assigned routine fields.
     *
     * @return object|false The assigned routine data object if found, or false otherwise.
     */
    public function findBy(array $searchValues): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "AR.$field = :$field", $fields));

        $this->db->query(
            "SELECT
                AR.id,
                UE.name as employee_name,
                UA.name as assigner_name,
                AR.completion_status,
                AR.start_date,
                AR.end_date,
                AR.employee_id
            FROM assigned_routines AR
            INNER JOIN users UE ON
                UE.id = AR.employee_id
            INNER JOIN users UA ON
                UA.id = AR.assigner_id
            WHERE
                $conditions"
        );

        $this->db->bindAll($searchValues);

        return $this->db->fetchSingleRow();
    }

    /**
     * Retrieves assigned routines associated with the specified routine ID,
     * including their employee and assigner names, start and end dates, and
     * completion status.
     *
     * Only assignments associated with active routines are considered.
     * Results are ordered by assignment creation date.
     *
     * @param int|string $routine_id ID of the routine whose assignments are to be retrieved.
     *
     * @return array|false List of assigned routines, or false on failure.
     */
    public function getByRoutineId(int|string $routine_id): array|false
    {
        $this->db->query(
            "SELECT
                AR.id,
                UE.name as employee_name,
                UA.name as assigner_name,
                AR.start_date,
                AR.end_date,
                AR.completion_status
            FROM assigned_routines AR
            INNER JOIN routines R ON
                R.id = AR.routine_id
            INNER JOIN users UE ON
                UE.id = AR.employee_id
            INNER JOIN users UA ON
                UA.id = AR.assigner_id
            WHERE
                R.status = 'ACTIVE' AND
                AR.routine_id = :routine_id
            ORDER BY
                AR.created_at ASC"
        );

        $this->db->bind('routine_id', $routine_id);

        return $this->db->fetchAllRows();
    }


    /**
     * Creates a new routine assignment for the specified employee.
     *
     * Only supported creatable fields are retained. The assigner ID is set from
     * the current user's session, overriding any supplied assigner ID. After
     * creating the assignment, the method automatically creates assigned action
     * records for all active actions associated with the routine.
     *
     * Both operations are executed within a transaction to ensure that the
     * routine assignment and its associated action assignments are created
     * together. If either operation fails, the transaction is rolled back.
     *
     * @param array $fields Assigned routine data.
     *
     * @return int|false New assigned routine ID, or false if validation or insertion fails.
     */
    public function assign(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));
        $fields['assigner_id'] = $_SESSION[APP_SESSION_KEY]['user_id'];

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        try {
            $this->db->beginTransaction();

            $this->db->query(
                "INSERT INTO assigned_routines(" . implode(', ', self::CREATABLE_FIELDS) . ")
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
            );

            $this->db->bindAll($fields);

            if (!$this->db->execute()) {
                $this->db->rollback();
                return false;
            }

            $assignedRoutineId = $this->db->getLastInsertedId();

            $this->db->query(
                "INSERT INTO assigned_actions(assigned_routine_id, action_id)
                SELECT
                    :assigned_routine_id,
                    A.id
                FROM
                    actions A
                WHERE
                    A.routine_id = :routine_id AND 
                    A.status = 'ACTIVE'"
            );

            $this->db->bind('assigned_routine_id', $assignedRoutineId);
            $this->db->bind('routine_id', $fields['routine_id']);

            if (!$this->db->execute()) {
                $this->db->rollback();
                return false;
            }

            $this->db->commit();
            return $assignedRoutineId;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Cancels an assigned routine without permanently removing its record.
     *
     * Updates the completion status of the specified assigned routine to
     * indicate that the assignment has been canceled.
     *
     * @param int|string $id Assigned routine ID.
     *
     * @return bool True if the update executes successfully, otherwise false.
     */
    public function cancel(int|string $id): bool
    {
        $this->db->query(
            "UPDATE assigned_routines AR 
            SET
                AR.completion_status = :status
            WHERE
                id = :id"
        );

        $this->db->bind('id', $id);
        $this->db->bind('status', 'CANCELED');

        return $this->db->execute();
    }

    /**
     * Retrieves assigned routines for export, including their assigner and
     * employee names, start and end dates, completion status, and timestamps.
     *
     * Only assignments associated with the specified routine are considered.
     * Results are ordered by assignment creation date.
     *
     * @param int|string $routine_id ID of the routine whose assignments are to be exported.
     *
     * @return array|false List of assigned routines for export, or false on failure.
     */
    public function exportByRoutineId(int|string $routine_id): array|false
    {
        $this->db->query(
            "SELECT
                AR.id,
                UA.name as assigner_name,
                UE.name as employee_name,
                AR.start_date,
                AR.end_date,
                AR.completion_status,
                AR.created_at,
                AR.updated_at
            FROM assigned_routines AR
            INNER JOIN routines R ON
                R.id = AR.routine_id
            INNER JOIN users UE ON
                UE.id = AR.employee_id
            INNER JOIN users UA ON
                UA.id = AR.assigner_id
            WHERE
                R.status = 'ACTIVE' AND
                AR.routine_id = :routine_id
            ORDER BY
                AR.created_at ASC"
        );

        $this->db->bind('routine_id', $routine_id);

        return $this->db->fetchAllRows();
    }
}
