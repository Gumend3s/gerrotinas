<?php

class BusinessModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private const CREATABLE_FIELDS = [
        'legal_name',
        'trade_name',
        'cnpj',
    ];
    private const UPDATABLE_FIELDS = [
        ...self::CREATABLE_FIELDS,
        'status'
    ];
    private const SEARCHABLE_FIELDS = [
        'id',
        'cnpj',
        'status'
    ];

    /**
     * Finds a business matching the specified searchable fields.
     *
     * Filters the search values to the supported searchable fields. Include a
     * status value in $searchValues to restrict the result by status.
     *
     * @param array $searchValues Searchable field values.
     *
     * @return object|false Business data if found, otherwise false.
     */
    public function findBy(array $searchValues): object|false
    {
        $searchValues = array_intersect_key($searchValues, array_flip(self::SEARCHABLE_FIELDS));

        if (empty($searchValues)) return false;

        $fields = array_keys($searchValues);
        $conditions = implode(' AND ', array_map(fn($field) => "B.$field = :$field", $fields));

        $this->db->query(
            "SELECT
                B.id,
                B.legal_name,
                B.trade_name,
                B.cnpj,
                B.status
            FROM businesses B
            WHERE
                $conditions"
        );

        $this->db->bindAll($searchValues);

        return $this->db->fetchSingleRow();
    }

    /**
     * Retrieves all active businesses ordered by trade name.
     *
     * @return array|false List of businesses, or false on failure.
     */
    public function getActive(): array|false
    {
        $this->db->query(
            "SELECT
                B.id,
                B.legal_name,
                B.trade_name,
                B.cnpj
            FROM businesses B
            WHERE
                B.status = 'ACTIVE'
            ORDER BY
                B.trade_name ASC"
        );

        return $this->db->fetchAllRows();
    }

    /**
     * Updates one or more business fields.
     *
     * Only the provided updatable fields are included in the generated
     * UPDATE statement.
     *
     * @param int|string $id     Business ID.
     * @param array      $fields Fields to update.
     *
     * @return bool True if the update was executed successfully, otherwise false.
     */
    public function update(int|string $id, array $fields): bool
    {
        $fields = array_intersect_key($fields, array_flip(self::UPDATABLE_FIELDS));

        if (empty($fields)) return false;

        $keys = array_keys($fields);
        $queryFields = implode(', ', array_map(fn($k) => "$k = :$k", $keys));

        $this->db->query(
            "UPDATE businesses
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
     * Creates a new business.
     *
     * The provided array must contain all required business fields.
     * Additional fields are ignored.
     *
     * @param array $fields Business data.
     *
     * @return int|false New business ID, or false if validation or insertion fails.
     */
    public function create(array $fields): int|false
    {
        $fields = array_intersect_key($fields, array_flip(self::CREATABLE_FIELDS));

        if (count($fields) !== count(self::CREATABLE_FIELDS)) return false;

        $this->db->query(
            "INSERT INTO businesses(" . implode(', ', self::CREATABLE_FIELDS) . ")
            VALUES (:" . implode(', :', self::CREATABLE_FIELDS) . ")"
        );

        $this->db->bindAll($fields);

        if (!$this->db->execute()) return false;

        return $this->db->getLastInsertedId();
    }

    /**
     * Marks a business as deleted without permanently removing it.
     *
     * @param int|string $id Business ID.
     *
     * @return bool True if the update executes successfully, otherwise false.
     */
    public function softDelete(int|string $id): bool
    {
        $this->db->query(
            "UPDATE businesses
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
     * Retrieves active businesses for CSV export.
     *
     * Returns business data ordered by trade name.
     *
     * @return array|false List of businesses, or false on failure.
     */
    public function export(): array|false
    {
        $this->db->query(
            "SELECT
                B.id,
                B.trade_name,
                B.cnpj,
                B.created_at,
                B.updated_at,
                B.status
            FROM businesses B
            WHERE
                B.status = 'ACTIVE'
            ORDER BY
                B.trade_name ASC"
        );

        return $this->db->fetchAllRows();
    }
}
