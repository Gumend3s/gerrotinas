<?php

use LDAP\ResultEntry;

class AssignedRoutines extends Controller
{
    private const MODELS = [
        'routineModel' => 'RoutineModel',
        'assignedRoutineModel' => 'AssignedRoutineModel',
        'userModel' => 'UserModel',
    ];

    private RoutineModel $routineModel;
    private AssignedRoutineModel $assignedRoutineModel;
    private UserModel $userModel;

    private const REQUIRED_ACCESS = ['ADMINISTRATOR', 'MASTER'];

    public function __construct()
    {
        if (!Auth::hasAccess(self::REQUIRED_ACCESS)) {
            Alert::flash('index', '<strong>Acesso bloqueado</strong> <br>Método de acesso inválido ou falta de permissão', 'error');
            Helper::redirect('./');
        }

        foreach (self::MODELS as $property => $model) $this->$property = $this->model($model);
    }

    /**
     * Retrieves an assigned routine by its ID and associated routine ID.
     *
     * If the assigned routine is not found, displays an error message
     * and redirects the user to the assigned routine list.
     *
     * @param int|string $routine_id ID of the associated routine.
     * @param int|string $id ID of the assigned routine to retrieve.
     *
     * @return object The assigned routine data object.
     */
    private function getAssignedRoutine(int|string $routine_id, int|string $id): object
    {
        if (!($assigned_routine = $this->assignedRoutineModel->findBy(['routine_id' => $routine_id, 'id' => $id]))) {
            Alert::flash('assignedRoutines', 'Erro: delegação não encontrada', 'error');
            Helper::redirect('assignedRoutines');
        }

        return $assigned_routine;
    }

    /**
     * Retrieves an active routine by its ID.
     *
     * If the routine is not found or is not active, displays an error message
     * and redirects the user to the routine list.
     *
     * @param int|string $routine_id ID of the routine to retrieve.
     *
     * @return object The active routine data object.
     */
    private function getRoutine(int|string $routine_id): object
    {
        if (!($routine = $this->routineModel->findBy(['id' => $routine_id, 'status' => 'ACTIVE']))) {
            Alert::flash('routines', 'Erro: rotina não encontrada', 'error');
            Helper::redirect('routines');
        }

        return $routine;
    }

    /**
     * Displays the assigned routine list for the specified routine.
     *
     * Retrieves the data required to display the routine's assignments,
     * including the associated routine information, available employees,
     * and existing assigned routines.
     *
     * @param int|string $routine_id ID of the routine whose assignments are displayed.
     *
     * @return void
     */
    public function index(int|string $routine_id): void
    {
        $data = [
            'routine_id' => $routine_id,
            ...$this->getViewData($routine_id),
        ];

        $this->view('assignedRoutines/index', $data);
    }

    /**
     * Creates a new assignment for the specified routine.
     *
     * Loads initial form values for GET requests. For POST requests, retrieves
     * and validates submitted data, creates the assignment if validation
     * succeeds, displays a feedback message, and redirects after processing.
     *
     * If validation fails, redisplays the form with the submitted data and
     * validation errors.
     *
     * @param int|string $routine_id ID of the routine to assign.
     *
     * @return void
     */
    public function assign(int|string $routine_id): void
    {
        $data = [
            'routine_id' => $routine_id,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $data = array_merge($data, $this->getInitialData($routine_id, $id));
        else {
            $data = array_merge($data, $this->getFormData());

            $data = $this->validateFormData($data);

            if (!array_filter(Helper::filterKeysContaining($data, '_error'))) {
                $success = $this->assignedRoutineModel->assign($data);

                if ($success) Alert::flash('assignedRoutines', 'Cadastrado com sucesso');
                else Alert::flash('assignedRoutines', 'Erro: falha ao Delegar rotina', 'error');

                Helper::redirect("assignedRoutines/index/$routine_id");
            }
        }

        $data['start_date'] = Helper::convertDateFormat($data['start_date'] ?? '');
        $data['end_date'] = Helper::convertDateFormat($data['end_date'] ?? '');
        $this->view('assignedRoutines/index', array_merge($data, $this->getViewData($routine_id)));
    }

    /**
     * Cancels an existing assigned routine.
     *
     * Verifies that the assigned routine exists and belongs to the specified
     * routine before attempting cancellation. Displays a success or error
     * message and redirects accordingly.
     *
     * @param int|string $routine_id ID of the associated routine.
     * @param int|string $id ID of the assigned routine to cancel.
     *
     * @return void
     */
    public function cancel(int|string $routine_id, int|string $id): void
    {
        $this->getassignedRoutine($routine_id, $id);

        if ($this->assignedRoutineModel->cancel($id)) {
            Alert::flash('assignedRoutines', "Delegação cancelada com sucesso");
        } else {
            Alert::flash('assignedRoutines', 'Erro: falha ao cancelar delegação', 'error');
        }
        Helper::redirect("assignedRoutines/index/$routine_id");
    }

    /**
     * Exports assigned routines associated with the specified routine to a CSV file.
     *
     * Retrieves the routine's assignments, converts completion status values
     * into their display labels, and exports the records with localized
     * column headers. The exported file name includes the associated routine name.
     *
     * If no assigned routines are returned, displays an error message and
     * redirects the user to the routine list.
     *
     * @param int|string $routine_id ID of the routine whose assignments are to be exported.
     *
     * @return void
     */
    public function export(int|string $routine_id): void
    {
        $routine = $this->getRoutine($routine_id);
        $assigned_routines = $this->assignedRoutineModel->exportByRoutineId($routine_id);

        if ($assigned_routines) {
            foreach ($assigned_routines as $assigned_routine) {
                if (isset($assigned_routine->completion_status)) $assigned_routine->completion_status = Label::enum('completion_status', $assigned_routine->completion_status);
            }

            $header = array_map(fn($field) => Label::field('assigned_routine', $field), ['id', 'assigner_name', 'employee_name', 'start_date', 'end_date', 'completion_status', 'created_at', 'updated_at',]);
            $fileName = "Delegações - [$routine->name]";

            Helper::exportCsv($assigned_routines, $header, $fileName);
        } else {
            Alert::flash('routines', 'Erro: falha ao tentar exportar as rotinas', 'error');
            Helper::redirect('routines');
        }
    }

    /**
     * Retrieves the data required to populate the assigned routine view.
     *
     * Provides the available employees, associated routine information, and
     * existing assignments. Employee options are restricted to the current
     * user's business unless access restrictions can be bypassed.
     *
     * @param int|string|null $routine_id ID of the associated routine.
     *
     * @return array Associative array containing the view data.
     */
    private function getViewData(int|string|null $routine_id): array
    {
        $routine = $this->getRoutine($routine_id);
        $employees = array_filter($this->userModel->getActiveByBusiness($routine->business_id), fn($user) => in_array($user->level, ['EMPLOYEE'], true));

        return [
            'employees' => array_column($employees, 'name', 'id'),
            'routine' => $routine,
            'assigned_routines' => $this->assignedRoutineModel->getByRoutineId($routine_id),
        ];
    }

    /**
     * Retrieves the initial values for the assigned routine form.
     *
     * When editing, populates the form with the existing assigned routine's
     * employee and scheduling data. When creating a new assignment, returns
     * empty values for the form fields.
     *
     * @param int|string $routine_id ID of the associated routine.
     * @param int|string|null $id ID of the assigned routine to retrieve, or null for a new assignment.
     *
     * @return array Associative array containing the initial form field values.
     */
    private function getInitialData(int|string $routine_id, int|string|null $id): array
    {
        $assigned_routine = $id !== null ? $this->getassignedRoutine($routine_id, $id) : null;

        return [
            'employee_id' => $assigned_routine->employee_id ?? '',
            'start_date' => $assigned_routine->start_date ?? '',
            'end_date' => $assigned_routine->end_date ?? '',
        ];
    }

    /**
     * Retrieves and normalizes assigned routine form input.
     *
     * Reads the submitted POST data, trims the employee ID, and converts the
     * start and end dates into their database format.
     *
     * @return array Associative array containing the processed assigned routine data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return [
            'employee_id' => trim($formInput['employee_id'] ?? ''),
            'start_date' => Helper::convertDateFormat($formInput['start_date'] ?? '', toDatabase: true),
            'end_date' => Helper::convertDateFormat($formInput['end_date'] ?? '', toDatabase: true),
        ];
    }

    /**
     * Validates the submitted assigned routine form data.
     *
     * Checks whether an employee has been selected and validates the start and
     * end dates. Ensures that both dates are valid and that the end date is
     * later than the start date. Also prevents assignments from starting
     * in the past.
     *
     * Adds corresponding validation error messages to the data array.
     *
     * @param array $data Assigned routine form data to validate.
     *
     * @return array Assigned routine form data with validation error fields.
     */
    private function validateFormData(array $data): array
    {
        $data['employee_id_error'] = empty($data['employee_id']) ? 'Escolha um Funcionário' : null;
        $data['start_date_error'] = empty($data['start_date']) ? 'Preencha o campo Data Início' : (!Validate::date($data['start_date']) ? 'Data Inválida' : (Helper::getDaysSince($data['start_date']) > 0 ? 'A Data não pode estar no passado' : null));
        $data['end_date_error'] = empty($data['end_date']) ? 'Preencha o campo Data Limite' : (!Validate::date($data['end_date']) ? 'Data Inválida' : (Helper::getDaysSince($data['end_date']) >= Helper::getDaysSince($data['start_date']) ? 'O Limite deve ser depois do Início' : null));

        return $data;
    }
}
