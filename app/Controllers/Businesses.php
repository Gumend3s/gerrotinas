<?php

class Businesses extends Controller
{
    private const MODELS = [
        'businessModel' => 'BusinessModel',
    ];
    
    private BusinessModel $businessModel;

    private const REQUIRED_ACCESS = ['MASTER'];

    public function __construct()
    {
        if (!Auth::hasAccess(self::REQUIRED_ACCESS)) {
            Alert::flash('index', '<strong>Acesso bloqueado</strong> <br>Método de acesso inválido ou falta de permissão', 'error');
            Helper::redirect('./');
        }

        foreach (self::MODELS as $property => $model) $this->$property = $this->model($model);
    }

    private function getBusiness(int|string $id): object
    {
        if (!($business = $this->businessModel->findBy(['id' => $id], activeOnly: true))) {
            Alert::flash('businessses', 'Erro: empresa não encontrada', 'error');
            Helper::redirect('businessses');
        }

        return $business;
    }

    /**
     * Displays the main business list.
     *
     * Retrieves all active businesses and loads the business management view.
     * This page allows businesses to access individual business details.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('businesses/index', ['businesses' => $this->businessModel->getActive()]);
    }

    /**
     * Creates or updates a business based on the provided ID.
     *
     * Handles business validation, permission checks, business creation,
     * updates and automatic reactivation of deleted businesses with the same E-mail.
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
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $data = array_merge($data, $this->getInitialData($id));
        else {
            $data = array_merge($data, $this->getFormData());

            $data = $this->validateFormData($data);

            if (!array_filter(Helper::filterKeysContaining($data, '_error'))) {
                [$success, $message] = $this->save($data);

                if ($success) Alert::flash('businesses', $message);
                else Alert::flash('businesses', 'Erro: falha ao ' . ($data['isEditing'] ? 'editar' : 'cadastrar') . ' empresa', 'error');

                Helper::redirect('businesses');
            }
        }

        $this->view('businesses/upsert', array_merge($data, $this->getViewData()));
    }

    /**
     * Soft deletes an accessible business.
     *
     * @param int|string $id User ID.
     *
     * @return void
     */
    public function delete(int|string $id): void
    {
        $this->getBusiness($id);

        if ($this->businessModel->softDelete($id)) {
            Alert::flash('businesses', "Empresa deletada com sucesso");
            Helper::redirect('businesses');
        } else {
            Alert::flash('businesses/upsert', 'Erro: falha ao deletar empresa', 'error');
            Helper::redirect("businesses/upsert/$id");
        }
    }

    /**
     * Exports active businesses to a CSV file.
     *
     * Retrieves the business data, converts business, level, and status values to their
     * display labels, and exports the records using the corresponding field labels.
     *
     * @return void
     */
    public function export(): void
    {
        $businesses = $this->businessModel->export();
        if ($businesses) {
            foreach ($businesses as $business)if (isset($business->status)) $business->status = Label::enum('status', $business->status);

            $header = array_map(fn($field) => Label::field('business', $field), ['id', 'trade_name', 'cnpj', 'status', 'updated_at', 'created_at']);
            $fileName = "Empresas";

            Helper::exportCsv($businesses, $header, $fileName);
        } else {
            Alert::flash('businesses', 'Erro: falha ao tentar exportar as empresas', 'error');
            Helper::redirect('businesses');
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
        return [];
    }

    /**
     * Returns the initial form values.
     *
     * Populates the form with existing business data when editing,
     * or returns empty values when creating a new business.
     *
     * @param int|string|null $id User ID for editing, or null for creation.
     *
     * @return array Form field values.
     */
    private function getInitialData(int|string|null $id): array
    {
        $business = $id !== null ? $this->getBusiness($id) : null;

        return [
            'trade_name' => $business->trade_name ?? '',
            'legal_name' => $business->legal_name ?? '',
            'cnpj' => $business->cnpj ?? '',
        ];
    }

    /**
     * Processes raw form input into business data.
     *
     * Applies input sanitization and formatting before validation
     * and persistence.
     *
     * @return array Processed business data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return [
            'shouldReactivate' => false,
            'trade_name' => Helper::standardizeName($formInput['trade_name'] ?? ''),
            'legal_name' => Helper::standardizeName($formInput['legal_name'] ?? ''),
            'cnpj' => trim($formInput['cnpj'] ?? ''),
        ];
    }

    /**
     * Validates business form data.
     *
     * Adds validation errors, checks for duplicate business information,
     * and handles automatic reactivation of deleted businesses with the same E-mail.
     *
     * @param array  $data User form data.
     *
     * @return array User data with validation results.
     */
    private function validateFormData(array $data): array
    {
        $data['legal_name_error'] = empty($data['legal_name']) ? 'Preencha o campo Razão Social' : (!Validate::legalName($data['legal_name']) ? 'Razão Social possui caracteres inválidos' : null);
        $data['trade_name_error'] = empty($data['trade_name']) ? 'Preencha o campo Nome Fantasia' : (!Validate::legalName($data['trade_name']) ? 'Nome Fantasia possui caracteres inválidos' : null);
        $data['cnpj_error'] = empty($data['cnpj']) ? 'Preencha o campo CNPJ' : (!Validate::cnpj($data['cnpj']) ? 'Insira um CNPJ válido' : null);
        
        if ($data['cnpj_error'] === null && ($existingBusiness = $this->businessModel->findBy(['cnpj' => $data['cnpj']])) && $existingBusiness->id != $data['id']) {
            if ($existingBusiness->status === 'DELETED' && !$data['isEditing']) {
                $data['shouldReactivate'] = true;
                $data['existingBusiness'] = $existingBusiness;
            } else $data['cnpj_error'] = 'Uma empresa já possui esse cnpj';
        }

        return $data;
    }

    /**
     * Creates or updates a business based on the provided data.
     *
     * Handles business creation, updates and automatic reactivation
     * of previously deleted businesses with the same E-mail.
     *
     * @param array  $data Validated business data.
     *
     * @return array Contains the save status, feedback message, and alert variant.
     *               The first element indicates success, the second contains the
     *               corresponding feedback message, and the third contains the
     *               alert variant.
     */
    private function save(array $data): array
    {
        if ($data['isEditing']) {
            $this->getBusiness($data['id']);

            $success = $this->businessModel->update($data['id'], $data);

            $message = 'Editado com sucesso';
        } else {
            if ($data['shouldReactivate']) {
                $data['status'] = 'ACTIVE';

                $success = $this->businessModel->update($data['existingUser']->id, $data);
            } else $success = $this->businessModel->create($data);

            $message = "Cadastrado com sucesso";
        }

        return [$success, $message];
    }
}
