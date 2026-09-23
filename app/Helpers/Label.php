<?php

final class Label
{
    private const ENUM_LABELS = [
        'department' => ['EXECUTIVE' => 'Diretoria', 'OPERATIONS' => 'Operação', 'HR' => 'Recrutamento e Seleção', 'PAYROLL' => 'Departamento Pessoal', 'FINANCE' => 'Financeiro', 'SALES' => 'Comercial', 'TECHNOLOGY' => 'TI'],
        'level' => ['BASIC' => 'Básico', 'INTERMEDIATE' => 'Intermediário', 'ADVANCED' => 'Avançado', 'ADMINISTRATOR' => 'Administrador'],
        'status' => ['ACTIVE' => 'Ativo', 'DELETED' => 'Deletado'],
    ];

    private const FIELD_LABELS = [
        'user' => ['id' => 'ID', 'name' => 'Nome', 'cpf' => 'CPF', 'department' => 'Departamento', 'level' => 'Nível', 'status' => 'Status', 'created_at' => 'Criado em', 'updated_at' => 'Atualizado em'],
    ];

    /**
     * Returns the pt-BR label for the specified enum value.
     * Falls back to the original value when no label is defined.
     * The returned label is always HTML-escaped.
     *
     * @param string $enumGroup Enum group name.
     * @param string $enumValue Enum value.
     *
     * @return string Translated and HTML-safe label.
     */
    public static function enum(string $enumGroup, string $enumValue): string
    {
        return Helper::escape(self::ENUM_LABELS[$enumGroup][$enumValue] ?? $enumValue);
    }

    /**
     * Returns the pt-BR label for the specified database field.
     * Falls back to the original field name when no label is defined.
     * The returned label is always HTML-escaped.
     *
     * @param string $tableName Database table name.
     * @param string $fieldName Database field name.
     *
     * @return string Translated and HTML-safe label.
     */
    public static function field(string $tableName, string $fieldName): string
    {
        return Helper::escape(self::FIELD_LABELS[$tableName][$fieldName] ?? $fieldName);
    }
}