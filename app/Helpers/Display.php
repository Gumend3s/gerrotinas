<?php

class Display
{
    /**
     * Renders a disabled form field for displaying data without allowing user input.
     *
     * The provided value is escaped before being rendered unless it has already
     * been escaped. Null values are treated as empty strings. When buttons are
     * provided, the field and buttons are wrapped in a Bootstrap input group.
     *
     * The method supports both input and textarea elements. Inputs are rendered
     * as type="text", while textareas display their value as element content.
     * The field ID and label association are determined by $field.
     *
     * @param ?string $value          Value to display in the field.
     * @param string  $field          Field name used to set the element ID.
     * @param string  $displayText    Label text displayed above the field.
     * @param string  $colClass       Bootstrap column classes for the field container.
     * @param string  $element        HTML element to render.
     * @param array   $buttons        Button definitions to display alongside the field.
     * @param string  $classes        Additional CSS classes for the field.
     * @param bool    $valueIsEscaped Whether the value has already been HTML-escaped.
     *
     * @return void
     */
    private static function render(
        ?string $value,
        string $field,
        string $displayText,
        string $colClass,
        string $element,
        array $buttons,
        string $classes,
        bool $valueIsEscaped = false
    ): void {
        $value ??= '';
        $value = $valueIsEscaped ? $value : Helper::escape($value);

        $label = $displayText !== '' ? "<label for=\"$field\">$displayText:</label>" : '';
        $buttonGroup = self::buildButtonGroup($buttons);

        $inputGroupStart = $buttonGroup !== '' ? '<div class="input-group">' : '';
        $inputGroupEnd = $buttonGroup !== '' ? '</div>' : '';

        if ($element === 'textarea') {
            $content = $value;
            $attributes = 'rows="3"';
        } else {
            $content = '';
            $attributes = "type=\"text\" value=\"$value\"";
        }

        echo <<<HTML
            <div class="$colClass">
                $label
                $inputGroupStart
                    <$element id="$field" class="form-control $classes" $attributes disabled>$content</$element>
                    $buttonGroup
                $inputGroupEnd
            </div>
        HTML;
    }

    /**
     * Builds the action buttons displayed alongside a field in an input group.
     *
     * Each button must provide a CSS class, URL, accessible label, and Bootstrap
     * icon class. Additional HTML attributes can be provided through the optional
     * 'attributes' key, allowing options such as target="_blank".
     *
     * Example:
     * [
     *     [
     *         'class' => 'btn-primary',
     *         'href' => 'tel:+5511999999999',
     *         'aria-label' => 'Ligar',
     *         'icon' => 'bi-telephone',
     *     ],
     * ]
     *
     * @param array $buttons Button definitions to render.
     *
     * @return string Generated HTML for the button group.
     */
    private static function buildButtonGroup(array $buttons): string
    {
        $group = '';

        foreach ($buttons as $button) {
            $group .= "
            <a
                class='btn {$button['class']}'
                href='{$button['href']}'
                aria-label='{$button['aria-label']}'
                " . ($button['attributes'] ?? '') . ">
                <i class='bi {$button['icon']}'></i>
            </a>";
        }

        return $group;
    }

    /**
     * Renders a disabled single-line text field for displaying information.
     *
     * Use this method when the value should be visible to the user but should
     * not be edited in the current view. The provided value is escaped before
     * being rendered, and null values are displayed as empty strings.
     *
     * Optional action buttons can be passed through $buttons. These are useful
     * for actions related to the displayed value, such as calling a phone
     * number, opening WhatsApp, or navigating to another page.
     *
     * Example:
     * Display::text(
     *     $phone_primary,
     *     'phone_primary',
     *     'Telefone',
     *     'col-12 col-sm-6',
     *     buttons: [[
     *         'class' => 'btn-primary',
     *         'href' => 'tel:+5511999999999',
     *         'aria-label' => 'Ligar',
     *         'icon' => 'bi-telephone',
     *     ]]
     * );
     *
     * @param ?string $value       Value to display in the input.
     * @param string  $field       Field name used to set the input ID and label association.
     * @param string  $displayText Label text displayed above the input.
     * @param string  $colClass    Bootstrap column classes for the field container.
     * @param array   $buttons     Optional action buttons displayed beside the field.
     * @param string  $classes     Additional CSS classes for the input.
     *
     * @return void
     */
    public static function text(
        ?string $value,
        string $field,
        string $displayText,
        string $colClass,
        array $buttons = [],
        string $classes = ''
    ): void {
        self::render(
            value: $value,
            field: $field,
            displayText: $displayText,
            colClass: $colClass,
            element: 'input',
            buttons: $buttons,
            classes: $classes
        );
    }

    /**
     * Renders a disabled multiline textarea for displaying longer information.
     *
     * Use this method for values such as descriptions, notes, observations,
     * or other content that may contain multiple lines. The provided value is
     * escaped before being rendered, and null values are displayed as empty strings.
     *
     * The textarea uses three rows by default and receives the form-textarea
     * class for textarea-specific styling.
     *
     * Unlike text(), this method does not support action buttons because
     * multiline display fields are intended for displaying content rather
     * than providing actions related to a single-line value.
     *
     * @param ?string $value       Value to display in the textarea.
     * @param string  $field       Field name used to set the textarea ID and label association.
     * @param string  $displayText Label text displayed above the textarea.
     * @param string  $colClass    Bootstrap column classes for the field container.
     * @param string  $classes     Additional CSS classes for the textarea.
     *
     * @return void
     */
    public static function textarea(
        ?string $value,
        string $field,
        string $displayText,
        string $colClass,
        string $classes = ''
    ): void {
        self::render(
            value: $value,
            field: $field,
            displayText: $displayText,
            colClass: $colClass,
            element: 'textarea',
            buttons: [],
            classes: "$classes form-textarea"
        );
    }

    /**
     * Renders an enum value using its corresponding translated label.
     *
     * By default, the field name is used as the enum group. Specify $enum when
     * the enum group differs from the field name. The translated label is already
     * escaped by Label::enum() and is not escaped a second time.
     *
     * @param ?string $value       Enum value to translate and display.
     * @param string  $field       Field name used to set the input ID and label association.
     * @param string  $displayText Label text displayed above the input.
     * @param string  $colClass    Bootstrap column classes for the field container.
     * @param ?string $enum        Optional enum group; defaults to $field.
     * @param array   $buttons     Optional action buttons displayed beside the field.
     * @param string  $classes     Additional CSS classes for the input.
     *
     * @return void
     */
    public static function enum(
        ?string $value,
        string $field,
        string $displayText,
        string $colClass,
        ?string $enum = null,
        array $buttons = [],
        string $classes = ''
    ): void {
        $value = Label::enum($enum ?? $field, $value ?? '');

        self::render(
            value: $value,
            field: $field,
            displayText: $displayText,
            colClass: $colClass,
            element: 'input',
            buttons: $buttons,
            classes: $classes,
            valueIsEscaped: true
        );
    }

    /**
     * Renders a date or timestamp in a disabled text field using the specified format.
     *
     * - 'date': Converts a database date (Y-m-d) to Brazilian format (d/m/Y).
     * - 'timestamp': Converts a database timestamp to a Brazilian date without the time.
     * - 'timestamp_full': Converts a database timestamp to a Brazilian date and time.
     *
     * Null and empty values are displayed as empty strings.
     *
     * @param ?string $value       Date or timestamp to convert and display.
     * @param string  $field       Field name used to set the input ID and label association.
     * @param string  $displayText Label text displayed above the input.
     * @param string  $colClass    Bootstrap column classes for the field container.
     * @param string  $format      Display format: 'date', 'timestamp', or 'timestamp_full'.
     * @param array   $buttons     Optional action buttons displayed beside the field.
     * @param string  $classes     Additional CSS classes for the input.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the specified date format is unsupported.
     */
    public static function date(
        ?string $value,
        string $field,
        string $displayText,
        string $colClass,
        string $format = 'date',
        array $buttons = [],
        string $classes = ''
    ): void {
        if (!in_array($format, ['date', 'timestamp', 'timestamp_full'], true)) {
            throw new InvalidArgumentException("Unsupported date format: $format");
        }

        if ($value !== null && $value !== '') {
            $value = match ($format) {
                'date' => Helper::convertDateFormat($value),
                'timestamp' => Helper::convertTimestampFormat($value, includeTime: false),
                'timestamp_full' => Helper::convertTimestampFormat($value),
            };
        }

        self::render(
            value: $value,
            field: $field,
            displayText: $displayText,
            colClass: $colClass,
            element: 'input',
            buttons: $buttons,
            classes: $classes
        );
    }
}