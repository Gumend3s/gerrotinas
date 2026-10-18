<?php

class Users extends Controller
{
    private const MODELS = [
        'userModel' => 'UserModel',
        'businessModel' => 'BusinessModel',
    ];

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
     * Retrieves a user and verifies whether the current user can access it.
     *
     * Redirects to the users list if the user does not exist,
     * is inactive, or cannot be accessed by the current user.
     *
     * @param int|string $id User ID.
     *
     * @return object User data.
     */
    private function getAccessibleUser(int|string $id): object
    {
        if (!($user = $this->userModel->findBy(['id' => $id], activeOnly: true))) {
            Alert::flash('users', 'Erro: usuário não encontrado', 'error');
            Helper::redirect('users');
        }

        if (Auth::canBypass()) return $user;

        $currentUser = $_SESSION[APP_SESSION_KEY];

        $hasRestrictedLevel = in_array($user->level, ['MASTER'], true);
        $isDifferentBusiness = $user->business_id !== $currentUser['user_business_id'];
        $isOwnUser = $user->id == $currentUser['user_id'];

        if ($hasRestrictedLevel || $isDifferentBusiness || $isOwnUser) {
            Alert::flash('users', 'Você não possui permissão para editar esse usuário', 'error');
            Helper::redirect('users');
        }

        return $user;
    }

    /**
     * Displays the main user list.
     *
     * Retrieves all active users and loads the user management view.
     * This page allows users to access individual user details.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('users/index', ['users' => $_SESSION[APP_SESSION_KEY]['user_level'] === 'MASTER' ? $this->userModel->getActive() : $this->userModel->getActiveByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id'])]);
    }

    /**
     * Creates or updates a user based on the provided ID.
     *
     * Handles user validation, permission checks, user creation,
     * updates and automatic reactivation of deleted users with the same E-mail.
     *
     * @param int|string|null $id User ID for update, or null for creation.
     *
     * @return void
     */
    public function upsert(int|string|null $id = null): void
    {
        $data = [
            'id' => $id,
            'isEditing' => $id !== null,
            'canChangeBusiness' => Auth::canBypass(),
            'isOwnUser' => $_SESSION[APP_SESSION_KEY]['user_id'] == $id,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $data = array_merge($data, $this->getInitialData($id));
        else {
            $data = array_merge($data, $this->getFormData());

            $data = $this->validateFormData($data);

            if (!array_filter(Helper::filterKeysContaining($data, '_error'))) {
                [$success, $message, $variant] = $this->save($data);

                if ($success) Alert::flash('users', $message, variant: $variant);
                else Alert::flash('users', 'Erro: falha ao ' . ($data['isEditing'] ? 'editar' : 'cadastrar') . ' usuário', 'error');

                Helper::redirect('users');
            }
        }

        $this->view('users/upsert', array_merge($data, $this->getViewData()));
    }

    /**
     * Generates a new temporary password for a user.
     *
     * @param int|string|null $id User ID.
     *
     * @return void
     */
    public function resetPassword(int|string $id): void
    {
        $this->getAccessibleUser($id);

        $password = Helper::generatePassword();
        $fields = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ];

        if ($this->userModel->update($id, $fields)) Alert::flash('users', "A senha temporária do usuário é: <strong>$password</strong>", 'success', 'withCopy');
        else Alert::flash('users', 'Erro: falha ao redefinir a senha', 'error');

        Helper::redirect('users');
    }

    /**
     * Soft deletes an accessible user.
     *
     * @param int|string $id User ID.
     *
     * @return void
     */
    public function delete(int|string $id): void
    {
        $this->getAccessibleUser($id);

        if ($this->userModel->softDelete($id)) {
            Alert::flash('users', "Usuário deletado com sucesso");
            Helper::redirect('users');
        } else {
            Alert::flash('users/upsert', 'Erro: falha ao deletar usuário', 'error');
            Helper::redirect("users/upsert/$id");
        }
    }

    /**
     * Exports active users to a CSV file.
     *
     * Retrieves the user data, converts business, level, and status values to their
     * display labels, and exports the records using the corresponding field labels.
     *
     * @return void
     */
    public function export(): void
    {
        $users = $_SESSION[APP_SESSION_KEY]['user_level'] === 'MASTER' ? $this->userModel->export() : $this->userModel->exportByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id']);
        if ($users) {
            foreach ($users as $user) {
                if (isset($user->level)) $user->level = Label::enum('level', $user->level);
                if (isset($user->status)) $user->status = Label::enum('status', $user->status);
            }

            $header = array_map(fn($field) => Label::field('user', $field), ['id', 'name', 'email', 'business_name', 'level', 'status', 'updated_at', 'created_at']);
            $fileName = "Usuários" . ($_SESSION[APP_SESSION_KEY]['user_level'] !== 'MASTER' ? " - {$_SESSION[APP_SESSION_KEY]['user_business_name']}" : '');

            Helper::exportCsv($users, $header, $fileName);
        } else {
            Alert::flash('users', 'Erro: falha ao tentar exportar os usuários', 'error');
            Helper::redirect('users');
        }
    }

    /**
     * Returns the data required to initialize the view.
     *
     * Includes values that are not directly related to the form fields,
     * such as available business_ids and permission levels.
     *
     * @return array View initialization data.
     */
    private function getViewData(): array
    {
        $businesses = Auth::canBypass() ? $this->businessModel->getActive() : [];
        $levels = Helper::extractEnumValues($this->userModel->getLevels());

        if (!Auth::canBypass()) $levels = array_diff($levels, ["MASTER"]);

        return [
            'businesses' => array_column($businesses, 'trade_name', 'id'),
            'levels' => array_combine($levels, array_map(fn($level) => Label::enum('level', $level), $levels)),
        ];
    }

    /**
     * Returns the initial form values.
     *
     * Populates the form with existing user data when editing,
     * or returns empty values when creating a new user.
     *
     * @param int|string|null $id User ID for editing, or null for creation.
     *
     * @return array Form field values.
     */
    private function getInitialData(int|string|null $id): array
    {
        $user = $id !== null ? $this->getAccessibleUser($id) : null;

        return [
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'business_id' => Auth::canBypass() ? ($user->business_id ?? '') : '',
            'business_name' => !Auth::canBypass() ? $_SESSION[APP_SESSION_KEY]['user_business_name'] : '',
            'level' => $user->level ?? '',
        ];
    }

    /**
     * Processes raw form input into user data.
     *
     * Applies input sanitization and formatting before validation
     * and persistence.
     *
     * @return array Processed user data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return [
            'shouldReactivate' => false,
            'name' => Helper::standardizeName($formInput['name'] ?? ''),
            'email' => trim($formInput['email'] ?? ''),
            'business_id' => (Auth::canBypass() ? trim($formInput['business_id'] ?? '') : $_SESSION[APP_SESSION_KEY]['user_business_id']),
            'level' => trim($formInput['level'] ?? ''),
        ];
    }

    /**
     * Validates user form data.
     *
     * Adds validation errors, checks for duplicate user information,
     * and handles automatic reactivation of deleted users with the same E-mail.
     *
     * @param array  $data User form data.
     *
     * @return array User data with validation results.
     */
    private function validateFormData(array $data): array
    {
        $data['name_error'] = empty($data['name']) ? "Preencha o campo Nome" : (!Validate::name($data['name']) ? 'O nome possui caracteres inválidos' : null);
        $data['email_error'] = empty($data['email']) ? "Preencha o campo E-mail" : (!Validate::email($data['email']) ? 'Insira um E-mail válido' : null);
        $data['business_id_error'] = empty($data['business_id']) ? "Escolha uma empresa" : null;
        $data['level_error'] = empty($data['level']) ? "Escolha um nível de permissão" : (!Auth::canBypass() && in_array($data['level'], ['MASTER'], true) ? 'Você não possui permissão para isso' : null);

        if ($data['email_error'] === null && ($existingUser = $this->userModel->findBy(['email' => $data['email']])) && $existingUser->id != $data['id']) {
            if ($existingUser->status === 'DELETED' && !$data['isEditing']) {
                $data['shouldReactivate'] = true;
                $data['existingUser'] = $existingUser;
            } else $data['email_error'] = 'Um usuário já possui esse E-mail';
        }

        return $data;
    }

    /**
     * Creates or updates a user based on the provided data.
     *
     * Handles user creation, updates and automatic reactivation
     * of previously deleted users with the same E-mail.
     *
     * @param array  $data Validated user data.
     *
     * @return array Contains the save status, feedback message, and alert variant.
     *               The first element indicates success, the second contains the
     *               corresponding feedback message, and the third contains the
     *               alert variant.
     */
    private function save(array $data): array
    {
        if ($data['isEditing']) {
            $this->getAccessibleUser($data['id']);

            $success = $this->userModel->update($data['id'], $data);

            $message = 'Editado com sucesso';
            $variant = 'default';
        } else {
            $password = Helper::generatePassword();
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);

            if ($data['shouldReactivate']) {
                $data['status'] = 'ACTIVE';

                $success = $this->userModel->update($data['existingUser']->id, $data);
            } else $success = $this->userModel->create($data);

            $message = "Cadastrado com sucesso <br>A senha temporária do usuário é: <strong>$password</strong>";
            $variant = 'withCopy';
        }

        return [$success, $message, $variant];
    }
}