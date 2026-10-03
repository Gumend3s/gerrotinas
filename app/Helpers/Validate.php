<?php

final class Validate
{
    /**
     * Validates whether a string can be used as a name.
     *
     * Accepted characters:
     * - Unicode letters
     * - Spaces
     * - Apostrophes (')
     * - Hyphens (-)
     * - Periods (.)
     *
     * @param string $name Name to validate.
     *
     * @return bool True if the name matches the allowed character set.
     */
    public static function name(string $name): bool
    {
        return preg_match('/^[\p{L}\' .-]+$/u', $name) === 1;
    }

    /**
     * Validates whether a string can be used as a legal name.
     *
     * Accepted characters:
     * - Unicode letters
     * - Spaces
     * - Apostrophes (')
     * - Hyphens (-)
     * - Periods (.)
     * - Ampersands (&)
     *
     * @param string $name Legal name to validate.
     *
     * @return bool True if the legal name contains only allowed characters, otherwise false.
     */
    public static function legalName(string $name): bool
    {
        return preg_match("/^[\p{L}&' .-]+$/u", $name) === 1;
    }

    /**
     * Validates a Brazilian CPF number.
     * 
     * @param string $cpf CPF number to validate.
     *
     * @return bool True if the CPF is valid.
     */
    public static function cpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $calculateCheckDigit = function (string $base): int {
            $sum = 0;
            $len = strlen($base) + 1;
            for ($i = 0; $i < $len - 1; $i++) $sum += $base[$i] * ($len - $i);

            return ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);
        };

        return $cpf[9] == $calculateCheckDigit(substr($cpf, 0, 9)) && $cpf[10] == $calculateCheckDigit(substr($cpf, 0, 10));
    }

    /**
     * Validates a Brazilian CNPJ number.
     *
     * @param string $cnpj CNPJ number to validate.
     *
     * @return bool True if the CNPJ is valid, otherwise false.
     */
    public static function cnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];

        $digit = function ($cnpj, $weights, $length) {
            $sum = 0;
            for ($i = 0; $i < $length; $i++) $sum += $cnpj[$i] * $weights[$i];

            $remainder = $sum % 11;

            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        return $cnpj[12] == $digit($cnpj, $weights[0], 12) && $cnpj[13] == $digit($cnpj, $weights[1], 13);
    }

    /**
     * Validates a date in the Y-m-d format.
     *
     * @param string $date Date to validate.
     *
     * @return bool True if the date is valid and matches the expected format,
     *              otherwise false.
     */
    public static function date(string $date): bool
    {
        return ($parsedDate = DateTime::createFromFormat('Y-m-d', $date)) && $parsedDate->format('Y-m-d') === $date;
    }

    /**
     * Validates a time in the H:i format.
     *
     * @param string $time Time to validate.
     *
     * @return bool True if the time is valid and matches the expected format,
     *              otherwise false.
     */
    public static function time(string $time): bool
    {
        return ($parsedTime = DateTime::createFromFormat('H:i', $time)) && $parsedTime->format('H:i') === $time;
    }

    /**
     * Validates a CPF or CNPJ number based on its length.
     *
     * Values with up to 14 characters are validated as CPF; longer values are
     * validated as CNPJ.
     *
     * @param string $cnpj_cpf CPF or CNPJ number to validate.
     *
     * @return bool True if the number is valid, otherwise false.
     */
    public static function cnpjCPF(string $cnpj_cpf): bool
    {
        return strlen($cnpj_cpf) <= 14 ? self::cpf($cnpj_cpf) : self::cnpj($cnpj_cpf);
    }

    /**
     * Validates and loads an uploaded JPEG or PNG image.
     *
     * Validates the image format, file size, and dimensions, then creates an
     * image resource for further processing.
     *
     * @param array  $image Uploaded image data from $_FILES.
     *
     * @return array Contains an 'image' resource on success or an 'error' message
     *               when validation fails.
     */
    public static function image(array $image): array
    {
        $imageType = @exif_imagetype($image['tmp_name']);

        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return ['error' => 'Formato de imagem inválido'];
        }

        if ($image['size'] < 1024) {
            return ['error' => 'Imagem pequena demais'];
        }

        if ($image['size'] > 200 * 1024) {
            return ['error' => 'Imagem grande demais'];
        }

        [$width, $height] = getimagesize($image['tmp_name']);

        if ($width > 2000 || $height > 2000) {
            return ['error' => 'A imagem possui dimensões muito grandes'];
        }

        $imageResource = $imageType === IMAGETYPE_JPEG
            ? @imagecreatefromjpeg($image['tmp_name'])
            : @imagecreatefrompng($image['tmp_name']);

        if ($imageResource === false) {
            return ['error' => 'Imagem inválida ou corrompida'];
        }

        return ['image' => $imageResource];
    }
}