<?php

class Helper
{
    /**
     * Redirects the client to the given URL by sending a Location header,
     * then terminates script execution.
     *
     * @param string $url Relative path to append to the application base URL.
     *
     * @return void
     */
    public static function redirect(string $url): void
    {
        header("Location: " . APP_BASE_URL . DIRECTORY_SEPARATOR . $url);
        exit;
    }

    /**
     * Escapes a string for safe output in an HTML context, preventing XSS attacks.
     *
     * @param string  $value Raw string to be escaped.
     *
     * @return string HTML-safe string with special characters converted to entities.
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Abbreviates a compound name, keeping the first and last words intact,
     * reducing middle words to their initial, and dropping connectives
     * (da, de, do, das, dos, e). Returns the result HTML-escaped.
     *
     * @param string  $name
     * 
     * @return string Abbreviated Name
     */
    public static function abbreviateName(string $name): string
    {
        $ignore = ['de', 'da', 'do', 'dos', 'das', 'e'];
        $parts = explode(' ', trim($name));
        $result = [];

        foreach ($parts as $i => $part) {
            $partLower = mb_strtolower($part, 'UTF-8');
            if (in_array($partLower, $ignore)) continue;

            if ($i === 0 || $i === count($parts) - 1) $result[] = mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
            else {
                $initial = mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
                $result[] = "$initial.";
            }
        }

        return self::escape(implode(' ', $result));
    }

    /**
     * Standardizes a name by trimming extra spaces, converting words to title case,
     * preserving common lowercase particles (e.g. "de", "da") and uppercasing
     * predefined abbreviations (e.g. "LTDA", "ME", "EPP").
     *
     * This function only standardizes the string and does not escape HTML entities.
     * Apply escaping separately when displaying the value.
     *
     * @param string  $name Name to standardize.
     *
     * @return string Standardized name.
     */
    public static function standardizeName(string $name): string
    {
        $fullLower = ['de', 'da', 'do', 'dos', 'das', 'e'];
        $fullUpper = ['LTDA', 'SA', 'ME', 'EPP', 'EIRELI', 'EI', 'SS', 'SLU', 'II', 'III', 'IV'];
        $parts = explode(' ', mb_strtolower(trim($name)));
        $result = [];

        foreach ($parts as $i => $part) {
            if (in_array($part, $fullLower)) $result[] = $part;
            elseif (in_array($upperPart = mb_strtoupper(preg_replace('/[^\p{L}]/u', '', $part)), $fullUpper)) $result[] = $upperPart;
            else $result[] = mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
        }
        return implode(' ', $result);
    }

    /**
     * Converts a Brazilian-formatted decimal number to a database-compatible format.
     *
     * @param string  $number Number formatted using a comma as the decimal separator
     *                       and periods as thousand separators.
     * @return string The number with thousand separators removed and the decimal
     *                separator converted to a period.
     */
    public static function decimalFromReal(string $number)
    {
        return str_replace(',', '.', str_replace('.', '', trim($number)));
    }

    /**
     * Converts a percentage value to its decimal representation.
     *
     * @param string  $number Percentage value formatted as a whole number followed by
     *                       the percent sign.
     * @return string The percentage converted to its decimal representation, or the
     *                original value if it does not match the expected format.
     */
    public static function decimalFromPercentage(string $number)
    {
        return  preg_replace_callback('/^\s*(\d{1,2})%\s*$/', fn($matches) => '0.' . str_pad($matches[1], 2, '0', STR_PAD_LEFT), $number);
    }

    /**
     * Converts a date between Brazilian and database formats.
     *
     * By default, converts a database date (Y-m-d) to Brazilian format (d/m/Y).
     * When $toDatabase is true, converts a Brazilian date (d/m/Y) to database format (Y-m-d).
     *
     * @param string $date       Date to convert.
     * @param bool   $toDatabase Whether to convert the date to database format.
     *
     * @return string Converted date.
     */
    public static function convertDateFormat(string $date, bool $toDatabase = false): string
    {
        if ($toDatabase) return implode('-', array_reverse(explode('/', $date)));
        else return implode('/', array_reverse(explode('-', $date)));
    }

    /**
     * Appends a structured entry to a log file, creating the directory if needed.
     *
     * @param string $action  Action performed (e.g. "CREATE", "UPDATE", "DELETE").
     * @param string $table   Table or resource affected.
     * @param mixed  $content Serializable payload to log.
     * @param string $file    Log filename. Defaults to "events.log".
     *
     * @throws RuntimeException If the directory cannot be created or the write fails.
     */
    public static function log(string $action, string $table, mixed $content, string $file = 'events.log'): void
    {
        $logDir = dirname(__DIR__) . '/Logs';
        $logFile = $logDir . DIRECTORY_SEPARATOR . basename($file);

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) throw new \RuntimeException(sprintf('Log directory "%s" could not be created.', $logDir));

        $timestamp = date('Y-m-d H:i:s');
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $responsible = isset($_SESSION[APP_SESSION_KEY]['user_id']) ? $_SESSION[APP_SESSION_KEY]['user_id'] . ' - ' . mb_strtoupper((string) $_SESSION[APP_SESSION_KEY]['user_name'], 'UTF-8') : '0 - SYSTEM';

        $entry = implode(' | ', [$timestamp, strtoupper($action), strtoupper($table), $json, $responsible]);

        if (file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) throw new \RuntimeException(sprintf('Failed to write to log file "%s".', $logFile));
    }

    /**
     * Generates a random password made of uppercase and lowercase letters, 
     * numbers and special characters, shuffles and returns it
     * 
     * @return string The shuffled password
     */
    public static function generatePassword(): string
    {
        $password = '';
        $charSets = [
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'numbers' => '0123456789',
            'specialChars' => '#@$?'
        ];

        foreach ($charSets as $set) {
            $password .= $set[random_int(0, strlen($set) - 1)];
            $password .= $set[random_int(0, strlen($set) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Builds one or more CSS class names using a mapped suffix.
     *
     * The supplied value is looked up in the specified mapping. If found,
     * its mapped suffix is appended to every provided class prefix.
     * Otherwise, the "default" suffix is used.
     *
     * Examples:
     *
     *   getMappedCssClass('badge', 'level', 'BASIC')
     *   -> badge-green
     *
     *   getMappedCssClass(['badge', 'text'], 'level', 'ADVANCED')
     *   -> badge-orange text-orange
     *
     * @param string|array $classPrefixes CSS class prefix or prefixes.
     * @param string       $mapKey        Mapping identifier.
     * @param string       $value         Value to resolve in the mapping.
     *
     * @return string HTML-safe CSS class list.
     */
    public static function getMappedCssClass(string|array $classPrefixes, string $mapKey, string $value): string
    {
        $maps = [
            'level' => ['BASIC' => 'green', 'INTERMEDIATE' => 'yellow', 'ADVANCED' => 'orange', 'ADMINISTRATOR' => 'red'],
            'yes/no' => ['1' => 'green', '0' => 'red'],
        ];

        $classPrefixes = (array) $classPrefixes;
        $classVariant = ($maps[$mapKey][$value] ?? "default");
        $classes = array_map(fn($prefix) => "$prefix-$classVariant", $classPrefixes);

        return self::escape(implode(' ', $classes));
    }

    /**
     * Returns only the array entries whose keys contain the specified substring.
     *
     * The original array keys are preserved.
     *
     * @param array  $array     Array to filter.
     * @param string $substring Substring to search for in each key.
     *
     * @return array Filtered array.
     */
    public static function filterKeysContaining(array $array, string $substring): array
    {
        return array_filter($array, fn(string $key) => str_contains($key, $substring), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Extracts all values from a MySQL ENUM column definition.
     *
     * Example:
     * enum('A','B','C')
     *
     * becomes:
     * ['A', 'B', 'C']
     *
     * @param string $enumDefinition ENUM definition.
     *
     * @return string[] ENUM values.
     */
    public static function extractEnumValues(string $enumDefinition): array
    {
        if (!preg_match("/^enum\((.+)\)$/", $enumDefinition, $matches)) return [];

        return array_map(fn(string $value) => trim($value, "'"), explode(',', $matches[1]));
    }

    /**
     * Sends a CSV file to the browser for download.
     *
     * The provided header is written as the first row, followed by
     * the supplied data rows. All values are converted from UTF-8
     * to ISO-8859-1 before being written.
     *
     * This method ends script execution after sending the file.
     *
     * @param array  $rows     CSV rows.
     * @param array  $headers  Header row.
     * @param string $fileName Downloaded file name without extension.
     */
    public static function exportCsv(array $rows, array $headers, string $fileName): void
    {
        ob_clean();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
        $file = fopen("php://output", 'w');

        fputcsv($file, mb_convert_encoding($headers, 'ISO-8859-1', 'UTF-8'), ';');

        foreach ($rows as $row) fputcsv($file, array_map(fn($value) => mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'), (array) $row), ';');

        fclose($file);
        exit();
    }

    /**
     * Serves a JPEG image from the application image storage.
     *
     * Returns a 404 response when the requested image does not exist.
     *
     * @param string $directory Image storage directory.
     * @param string $imageCode Image identifier used as the filename.
     *
     * @return void
     */
    public static function serveImage(string $directory, string $imageCode): void
    {
        $filePath = dirname(APP_ROOT) . "/storage/img/$directory/$imageCode.jpg";

        if (!file_exists($filePath)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }
}