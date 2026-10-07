<?php

class Page extends Controller
{
    private $models = [
        'userModel' => 'UserModel'
    ];

    public function __construct()
    {
        foreach ($this->models as $model => $modelName) $this->$model = $this->model($modelName);
    }

    public function index(): void
    {
        if (!Auth::isLoggedIn()) {
            session_unset();
            session_destroy();
            Helper::redirect('login');
        } else $this->view('index', [
            'modules' => [
            ],
            'features' => [
            ]
        ]);
    }

    public function login(): void
    {
        if (Auth::isLoggedIn()) Helper::redirect('./');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) Helper::redirect('./');
            $data = [
                'cpf' => isset($_POST['cpf']) ? trim(filter_var($_POST['cpf'], FILTER_SANITIZE_SPECIAL_CHARS)) : '',
                'password' => isset($_POST['password']) ? (string)$_POST['password'] : ''
            ];

            $data['error'] = empty($data['cpf']) || empty($data['password']) || !($user = $this->userModel->findBy(['cpf' => $data['cpf']])) || !password_verify($data['password'], $user->password) || $user->status !== 'ACTIVE';

            if ($data['error']) {
                if (!empty($data['cpf']) && !empty($data['password'])) Helper::log('LOGIN', 'FAILED', ['cpf' => $data['cpf'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'], 'auth.log');

                $data['csrf_token'] = Auth::generateCsrf();
                $this->view('login', $data);
            } else {
                Helper::log('LOGIN', 'SUCCESS', ['user_id' => $user->id, 'cpf' => $data['cpf'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'], 'auth.log');

                session_regenerate_id(true);
                unset($_SESSION[APP_SESSION_KEY]['csrf_token']);
                $_SESSION[APP_SESSION_KEY]['user_id'] = $user->id;
                $_SESSION[APP_SESSION_KEY]['user_name'] = $user->name;
                $_SESSION[APP_SESSION_KEY]['user_business_id'] = $user->business_id;
                $_SESSION[APP_SESSION_KEY]['user_business_name'] = $user->business_name;
                $_SESSION[APP_SESSION_KEY]['user_level'] = $user->level;
                Helper::redirect('./');
            }
        } else $this->view('login', ['csrf_token' => Auth::generateCsrf()]);
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        Helper::redirect('./');
    }
}