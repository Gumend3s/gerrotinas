<?php

class Employees extends Controller
{
    private const MODELS = [
        'employeeModel' => 'EmployeeModel',
    ];

    private EmployeeModel $employeeModel;

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

    public function actions(int|string $routine_id): void
    {
        $data = [
            'actions' => $this->employeeModel->getRoutineActions($_SESSION[APP_SESSION_KEY]['user_id'], $routine_id),
        ];

        $this->view('employees/actions', $data);
    }
}
