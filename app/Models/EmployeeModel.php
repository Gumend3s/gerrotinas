<?php

class EmployeeModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getEmployeeRoutines(int|string $employee_id): array|false
    {
        $this->db->query(
            "SELECT
                AR.id,
                AR.routine_id,
                R.name,
                R.priority,
                P.name AS project_name,
                U.name AS assigner_name,
                AR.completion_status,
                AR.start_date,
                AR.end_date
            FROM assigned_routines AR
            INNER JOIN routines R ON
                R.id = AR.routine_id
            INNER JOIN users U ON
                U.id = AR.assigner_id
            INNER JOIN projects P ON
                P.id = R.project_id
            WHERE
                R.status = 'ACTIVE' AND
                AR.employee_id = :employee_id AND
                AR.completion_status != 'CANCELED'
            ORDER BY
                FIELD(R.priority, 'HIGH', 'MEDIUM', 'LOW') ASC,
                FIELD(AR.completion_status, 'EXECUTION', 'PENDING', 'COMPLETED') ASC,
                AR.end_date DESC"
        );

        $this->db->bind('employee_id', $employee_id);

        return $this->db->fetchAllRows();
    }

    public function getRoutineActions(int|string $employee_id, int|string $assigned_routine_id): array|false
    {
        $this->db->query(
            "SELECT
                A.name,
                AA.action_id,
                AA.id,
                A.priority,
                A.completion_rule,
                AA.completion_status
            FROM assigned_actions AA
            INNER JOIN actions A ON
                A.id = AA.action_id
            INNER JOIN assigned_routines AR ON
                AR.id = AA.assigned_routine_id
            INNER JOIN routines R ON
                R.id = AR.routine_id
            WHERE
                R.status = 'ACTIVE' AND
                AA.assigned_routine_id = :assigned_routine_id AND
                AR.employee_id = :employee_id AND
                AR.completion_status != 'CANCELED'
            ORDER BY
                FIELD(A.priority, 'HIGH', 'MEDIUM', 'LOW') ASC,
                FIELD(AA.completion_status, 'EXECUTION', 'PENDING', 'COMPLETED') ASC"
        );

        $this->db->bind('employee_id', $employee_id);
        $this->db->bind('assigned_routine_id', $assigned_routine_id);

        return $this->db->fetchAllRows();
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
    public function findRoutineByAssignment(int|string $assigned_routine_id): object|false
    {
        $this->db->query(
            "SELECT
                AR.id,
                R.name,
                P.name AS project_name,
                R.priority,
                AR.completion_status
            FROM assigned_routines AR
            INNER JOIN routines R ON
                R.id = AR.routine_id
            INNER JOIN projects P ON
                P.id = R.project_id
            WHERE
                AR.id = :assigned_routine_id"
        );

        $this->db->bind('assigned_routine_id', $assigned_routine_id);

        return $this->db->fetchSingleRow();
    }

    public function startAction(int|string $assigned_action_id): bool
    {
        $this->db->query(
            "UPDATE assigned_actions AA
            LEFT JOIN assigned_routines AR ON
                AR.id = AA.assigned_routine_id AND
                AR.completion_status = 'PENDING'
            SET
                AA.completion_status = :completion_status,
                AR.completion_status = :completion_status
            WHERE
                AA.id = :assigned_action_id"
        );

        $this->db->bind('completion_status', 'EXECUTION');
        $this->db->bind('assigned_action_id', $assigned_action_id);

        return $this->db->execute();
    }

    public function endAction(int|string $assigned_action_id): bool
    {
        $this->db->query(
            "UPDATE assigned_actions AA
            LEFT JOIN assigned_routines AR
                ON AR.id = AA.assigned_routine_id
            SET
                AA.completion_status = :action_status,
                AR.completion_status = CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM assigned_actions AA2
                        WHERE AA2.assigned_routine_id = AA.assigned_routine_id
                        AND AA2.id <> AA.id
                        AND AA2.completion_status IN ('PENDING', 'EXECUTION')
                    ) THEN AR.completion_status
                    ELSE :routine_status
                END
            WHERE AA.id = :assigned_action_id"
        );

        $this->db->bind('action_status', 'FINISHED');
        $this->db->bind('routine_status', 'FINISHED');
        $this->db->bind('assigned_action_id', $assigned_action_id);

        return $this->db->execute();
    }
}
