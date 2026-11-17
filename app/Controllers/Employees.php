<?php

class Employees extends Controller
{
    private const MODELS = [
        'employeeModel' => 'EmployeeModel',
        'routineModel' => 'RoutineModel',
    ];

    private EmployeeModel $employeeModel;
    private RoutineModel $routineModel;

    private const REQUIRED_ACCESS = ['EMPLOYEE'];

    public function __construct()
    {
        if (!Auth::hasAccess(self::REQUIRED_ACCESS)) {
            Alert::flash('index', '<strong>Acesso bloqueado</strong> <br>Método de acesso inválido ou falta de permissão', 'error');
            Helper::redirect('./');
        }

        foreach (self::MODELS as $property => $model) $this->$property = $this->model($model);
    }

    /**
     * Displays the main routine list.
     *
     * Retrieves active routines according to the current user's access level.
     * Users who can bypass business restrictions can view all active routines;
     * other users can view only routines associated with their business.
     *
     * @return void
     */
    public function routines(): void
    {
        $data = [
            'routines' => $this->employeeModel->getEmployeeRoutines($_SESSION[APP_SESSION_KEY]['user_id']),
        ];

        $this->view('employees/routines', $data);
    }

    public function actions(int|string $assigned_routine_id): void
    {
        $data = [
            'routine' => $this->employeeModel->findRoutineByAssignment($assigned_routine_id),
            'actions' => $this->employeeModel->getRoutineActions($_SESSION[APP_SESSION_KEY]['user_id'], $assigned_routine_id),
        ];

        $this->view('employees/actions', $data);
    }

    public function startAction(int|string $assigned_routine_id, int|string $assigned_action_id) {
        if ($this->employeeModel->startAction($assigned_action_id)) Alert::flash('employees/actions', 'Ação iniciada com sucesso');
        else Alert::flash('employees/actions', 'Erro: falha ao iniciar ação', 'error');

        Helper::redirect("employees/actions/$assigned_routine_id");
    }

    public function endAction(int|string $assigned_routine_id, int|string $assigned_action_id) {
        if ($this->employeeModel->endAction($assigned_action_id)) Alert::flash('employees/actions', 'Ação finalizada com sucesso');
        else Alert::flash('employees/actions', 'Erro: falha ao finalizar ação', 'error');

        Helper::redirect("employees/actions/$assigned_routine_id");
    }
}
