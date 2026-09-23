<?php

class Display
{
    /**
     * Renders a disabled form field for displaying data without allowing user input.
     *
     * The field value is retrieved from $data using $field and automatically
     * escaped before being rendered. When buttons are provided, the field and
     * buttons are wrapped in a Bootstrap input group.
     *
     * The method supports both input and textarea elements. Inputs are rendered
     * as type="text", while textareas display their value as element content.
     *
     * @param array  $data       Data containing the value to be displayed.
     * @param string $field      Field name used to retrieve the value and set the ID.
     * @param string $displayText Label text displayed above the field.
     * @param string $colClass   Bootstrap column classes for the field container.
     * @param string $element    HTML element to render.
     * @param array  $buttons    Button definitions to display alongside the field.
     * @param string $attributes Additional HTML attributes for the field.
     * @param string $classes    Additional CSS classes for the field.
     */
    private static function render(array $data, string $field, string $displayText, string $colClass, string $element, array $buttons = [], string $attributes = '', string $classes = ''): void
    {
        $value = Helper::escape($data[$field] ?? '');
        $label = $displayText !== '' ? "<label for=\"$field\">$displayText:</label>" : '';
        $buttonGroup = self::buildButtonGroup($buttons);

        $inputGroupStart = $buttonGroup !== '' ? '<div class="input-group">' : '';
        $inputGroupEnd = $buttonGroup !== '' ? '</div>' : '';

        if ($element === 'textarea') {
            $content = $value;
            $attributes .= ' rows="3"';
        } else {
            $content = '';
            $attributes .= " type=\"text\" value=\"$value\"";
        }

        echo <<<HTML
            <div class="{$colClass}">
                {$label}
                {$inputGroupStart}
                    <{$element} id="{$field}" class="form-control {$classes}" {$attributes} disabled>{$content}</{$element}>
                    {$buttonGroup}
                {$inputGroupEnd}
            </div>
        HTML;
    }

    /**
     * Builds the action buttons displayed alongside a field in a input group.
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
    private static function buildButtonGroup(array $buttons = []): string
    {
        $group = '';
        foreach ($buttons as $button) {
            $group .= '
            <a 
                class="btn ' . $button['class'] . '"
                href="' . $button['href'] . '" 
                aria-label="' . $button['aria-label'] . '" 
                ' . ($button['attributes'] ?? '') . '>
                <i class="bi ' . $button['icon'] . '"></i>
            </a>';
        }
        return $group;
    }

    /**
     * Renders a disabled single-line text field for displaying information.
     *
     * Use this method when the value should be visible to the user but should
     * not be edited in the current view. The value is automatically retrieved
     * from $data using $field and escaped before being rendered.
     *
     * Optional action buttons can be passed through $buttons. These are useful
     * for actions related to the displayed value, such as calling a phone
     * number, opening WhatsApp, or navigating to another page.
     *
     * Example:
     * Display::text($data, 'phone_primary', 'Telefone', 'col-12 col-sm-6', [
     *         [
     *             'class' => 'btn-primary',
     *             'href' => 'tel:+5511999999999',
     *             'aria-label' => 'Ligar',
     *             'icon' => 'bi-telephone',
     *         ],
     *     ]
     * );
     *
     * @param array  $data        Data containing the field value.
     * @param string $field       Field name used to retrieve the value and set the ID.
     * @param string $displayText Label text displayed above the field.
     * @param string $colClass    Bootstrap column classes for the field container.
     * @param array  $buttons     Optional action buttons displayed beside the field.
     * @param string $classes     Additional CSS classes for the field.
     */
    public static function text(array $data, string $field, string $displayText, string $colClass, array $buttons = [], string $classes = ''): void
    {
        self::render($data, $field, $displayText, $colClass, 'input', buttons: $buttons, classes: $classes);
    }

    /**
     * Renders a disabled multiline textarea for displaying longer information.
     *
     * Use this method for values such as descriptions, notes, observations,
     * or other content that may contain multiple lines. The textarea uses
     * three rows by default and receives the form-textarea class for
     * textarea-specific styling.
     *
     * Unlike text(), this method does not support action buttons because
     * multiline display fields are intended for displaying content rather
     * than providing actions related to a single-line value.
     *
     * Example:
     * Display::textarea($data, 'notes', 'Observações', 'col-12');
     *
     * @param array  $data        Data containing the field value.
     * @param string $field       Field name used to retrieve the value and set the ID.
     * @param string $displayText Label text displayed above the textarea.
     * @param string $colClass    Bootstrap column classes for the field container.
     * @param string $classes     Additional CSS classes for the textarea.
     */
    public static function textarea(array $data, string $field, string $displayText, string $colClass, string $classes = ''): void
    {
        self::render($data, $field, $displayText, $colClass, 'textarea', classes: $classes . ' form-textarea');
    }
}
