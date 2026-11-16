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
}
