<?php

class Input
{
    /**
     * Renders a form input or textarea with its value, validation state,
     * required indicator, validation error message, and optional action buttons.
     *
     * @param array  $data Form data containing the field value and error message.
     * @param string $field Field name used for the input name, ID, value, and error.
     * @param string $displayText Label text displayed above the field.
     * @param string $placeholder Placeholder text displayed inside the field.
     * @param string $colClass Bootstrap column classes applied to the field container.
     * @param string $element HTML element to render.
     * @param string $type Input type attribute for input elements.
     * @param string $attributes Additional HTML attributes for the element.
     * @param string $classes Additional CSS classes for the element.
     * @param bool   $required Whether the field is required.
     * @param array  $buttons Optional action buttons displayed alongside the field.
     */
    private static function render(array $data, string $field, string $displayText, string $placeholder, string $colClass, string $element, string $type = 'text', string $attributes = '', string $classes = '', bool $required = true, array $buttons = []): void
    {
        $value = $type !== 'file' ? Helper::escape($data[$field] ?? '') : '';
        $errorMessage = Helper::escape($data[$field . '_error'] ?? '');
        $hasError = empty($data[$field . '_error']) ? '' : 'is-invalid';
        $attributes .= ($required ? ' required' : '');
        $requiredIndicator = $required ? '<sup class="text-danger">*</sup>' : '';
        $buttonGroup = self::buildButtonGroup($buttons);

        $inputGroupStart = $buttonGroup !== '' ? '<div class="input-group">' : '';
        $inputGroupEnd = $buttonGroup !== '' ? '</div>' : '';

        if ($element === 'textarea') {
            $content = $value;
            $attributes .= ' rows="3"';
        } else {
            $content = '';
            $attributes .= " type=\"$type\"";

            if ($type !== 'file') {
                $attributes .= " value=\"$value\"";
            }
        }

        echo <<<HTML
            <div class="{$colClass}">
                <label for="{$field}">{$displayText}: {$requiredIndicator}</label>
                {$inputGroupStart}
                    <{$element} name="{$field}" id="{$field}" placeholder="{$placeholder}" class="form-control {$classes} {$hasError}" {$attributes}>{$content}</{$element}>
                    {$buttonGroup}
                    <div class="invalid-feedback">
                        {$errorMessage}
                    </div>
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
     * Renders a standard single-line text field.
     *
     * Use this method for general text values that do not require a specific
     * HTML input type. The field value and validation error are automatically
     * retrieved from $data using $field.
     *
     * The generated input uses type="text" and can receive additional CSS
     * classes through $classes. Pass false to $required when the field is
     * optional; this removes both the required HTML attribute and the visual
     * required indicator from the label.
     *
     * Optional action buttons can be passed through $buttons. These are
     * displayed alongside the field and can be used for actions related
     * to its value.
     *
     * Example: Input::text(
     *     $data,
     *     'name',
     *     'Nome',
     *     'Ex: João da Silva',
     *     'col-12 col-sm-8',
     *     buttons: [
     *         [
     *             'class' => 'btn-primary',
     *             'href' => '#',
     *             'aria-label' => 'Pesquisar',
     *             'icon' => 'search',
     *         ],
     *     ]
     * )
     *
     * @param array  $data Form data containing the field value and validation error.
     * @param string $field Field name used for the input name, ID, value, and error.
     * @param string $displayText Label text displayed above the field.
     * @param string $placeholder Placeholder text displayed inside the field.
     * @param string $colClass Bootstrap column classes for the field container.
     * @param string $classes Additional CSS classes for the input.
     * @param bool   $required Whether the field is required.
     * @param array  $buttons Optional action buttons displayed alongside the field.
     */
    public static function text(array $data, string $field, string $displayText, string $placeholder, string $colClass, string $classes = '', bool $required = true, array $buttons = []): void
    {
        self::render($data, $field, $displayText, $placeholder, $colClass, 'input', classes: $classes, required: $required, buttons: $buttons);
    }

    /**
     * Renders a field intended for email addresses.
     *
     * Use this method when the field stores an email address. Unlike text(),
     * the generated input uses type="email", allowing browsers to provide
     * email-specific input behavior and perform basic client-side format
     * validation.
     *
     * Server-side validation should still be performed before storing or
     * processing the submitted value.
     *
     * Optional action buttons can be passed through $buttons. These are
     * displayed alongside the field and can be used for actions related
     * to its value.
     *
     * Example: Input::email(
     *     $data,
     *     'email',
     *     'E-mail',
     *     'Ex: joaosilva@gmail.com',
     *     'col-12 col-sm-4',
     *     buttons: [
     *         [
     *             'class' => 'btn-primary',
     *             'href' => '#',
     *             'aria-label' => 'Enviar e-mail',
     *             'icon' => 'envelope',
     *         ],
     *     ]
     * )
     *
     * @param array  $data Form data containing the field value and validation error.
     * @param string $field Field name used for the input name, ID, value, and error.
     * @param string $displayText Label text displayed above the field.
     * @param string $placeholder Placeholder text displayed inside the field.
     * @param string $colClass Bootstrap column classes for the field container.
     * @param string $classes Additional CSS classes for the input.
     * @param bool   $required Whether the field is required.
     * @param array  $buttons Optional action buttons displayed alongside the field.
     */
    public static function email(array $data, string $field, string $displayText, string $placeholder, string $colClass, string $classes = '', bool $required = true, array $buttons = []): void
    {
        self::render($data, $field, $displayText, $placeholder, $colClass, 'input', type: 'email', classes: $classes, required: $required, buttons: $buttons);
    }

    /**
     * Renders a text field intended for numeric values.
     *
     * Use this method for fields whose values are primarily numeric, especially
     * when they may contain formatting characters such as masks, dots, commas,
     * or hyphens. The generated input remains type="text" so formatted
     * values can be entered normally, while inputmode="numeric" requests a
     * numeric keyboard on supported mobile devices.
     *
     * Validation and conversion of the submitted value should still be handled
     * by the application according to the specific field requirements.
     *
     * Additional classes can be used for field-specific masks or formatting.
     *
     * Optional action buttons can be passed through $buttons. These are displayed
     * alongside the field and can be used for actions related to its value.
     *
     * Example:
     * Input::numeric(
     *     $data,
     *     'cnpj',
     *     'CNPJ',
     *     '00.000.000/0000-00',
     *     'col-12 col-sm-4',
     *     buttons: [
     *         [
     *             'class' => 'btn-primary',
     *             'href' => 'javascript:void(0)',
     *             'aria-label' => 'Pesquisar CNPJ',
     *             'icon' => 'search',
     *         ],
     *     ]
     * )
     *
     * @param array  $data Form data containing the field value and validation error.
     * @param string $field Field name used for the input name, ID, value, and error.
     * @param string $displayText Label text displayed above the field.
     * @param string $placeholder Placeholder text displayed inside the field.
     * @param string $colClass Bootstrap column classes for the field container.
     * @param string $classes Additional CSS classes, such as input masks.
     * @param bool   $required Whether the field is required.
     * @param array  $buttons Optional action buttons displayed alongside the field.
     */
    public static function numeric(array $data, string $field, string $displayText, string $placeholder, string $colClass, string $classes = '', bool $required = true, array $buttons = []): void
    {
        self::render($data, $field, $displayText, $placeholder, $colClass, 'input', attributes: 'inputmode="numeric"', classes: $classes, required: $required, buttons: $buttons);
    }

    /**
     * Renders a multiline textarea field.
     *
     * Use this method for fields that can contain longer or multiline text,
     * such as notes, descriptions, observations, or other free-form content.
     *
     * The generated textarea uses three rows by default and receives the
     * form-textarea class for textarea-specific styling. Its value is placed
     * between the opening and closing textarea tags rather than in a value
     * attribute.
     *
     * The field is optional by default, but $required can be set to true when
     * the value must be provided.
     *
     * Example: Input::textarea($data, 'notes', 'Observações', 'Ex: ...', 'col-12')
     *
     * @param array  $data Form data containing the field value and validation error.
     * @param string $field Field name used for the textarea name, ID, value, and error.
     * @param string $displayText Label text displayed above the textarea.
     * @param string $placeholder Placeholder text displayed inside the textarea.
     * @param string $colClass Bootstrap column classes for the field container.
     * @param string $classes Additional CSS classes for the textarea.
     * @param bool   $required Whether the field is required.
     */
    public static function textarea(array $data, string $field, string $displayText, string $placeholder, string $colClass, string $classes = '', bool $required = false): void
    {
        self::render($data, $field, $displayText, $placeholder, $colClass, 'textarea', classes: $classes . ' form-textarea', required: $required);
    }

    /**
     * Renders a file upload field.
     *
     * Uses the standard input rendering logic with type="file" and supports
     * custom CSS classes, HTML attributes, required state, and action buttons.
     *
     * @param array  $data        Form data containing the field validation error.
     * @param string $field       Field name used for the input name, ID, and error.
     * @param string $displayText Label text displayed above the field.
     * @param string $colClass    Bootstrap column classes for the field container.
     * @param string $classes     Additional CSS classes for the input.
     * @param string $attributes  Additional HTML attributes for the input.
     * @param bool   $required    Whether the field is required.
     * @param array  $buttons     Optional action buttons displayed alongside the field.
     *
     * @return void
     */
    public static function file(array $data, string $field, string $displayText, string $colClass, string $classes = '', $attributes = '', bool $required = true, array $buttons = []): void
    {
        self::render($data, $field, $displayText, '', $colClass, 'input', type: 'file', classes: $classes, required: $required, buttons: $buttons, attributes: $attributes);
    }

    /**
     * Renders an image preview container with an optional image URL.
     *
     * Displays the current image when a source URL is provided, or a placeholder
     * when no image is available. When an image URL is present in $data, it is
     * also rendered as a hidden form field so it can be preserved across submits.
     *
     * @param array  $data     Form data containing the image URL.
     * @param string $field    Field name used for the container and image URL field.
     * @param string $colClass Bootstrap column classes for the container.
     * @param string $classes  Additional CSS classes for the container.
     * @param string $src      Image URL used for the preview.
     *
     * @return void
     */
    public static function image(array $data, string $field, string $colClass, string $classes = '', string $src = ''): void
    {
        $url = !empty($data[$field . '_url']) ? '<input type="hidden" name="' . $field . '_url" value="' . Helper::escape($data[$field . '_url']) . '">' : '';
        $previewClass = empty($src) ? 'd-none' : '';
        $placeholderClass = empty($src) ? '' : 'd-none';

        echo <<<HTML
            <div id="{$field}" class="image-container {$classes} {$colClass}">
                <img class="image-preview {$previewClass}" src="{$src}" alt="">
                <div class="image-placeholder d-flex bg-gray text-center align-items-center justify-content-center {$placeholderClass}">
                    Selecione uma imagem
                </div>
                {$url}
            </div>
        HTML;
    }

    /**
     * Renders a select field with its options, selected value, validation state,
     * required indicator, validation error message, and optional action buttons.
     *
     * The options array must use option values as keys and their display labels
     * as values. The option matching the current field value in $data is
     * automatically selected.
     *
     * When buttons are provided, the select and buttons are wrapped in a Bootstrap
     * input group. The buttons can be used for actions related to the selected
     * value, such as adding or navigating to another page.
     *
     * Additional HTML attributes and CSS classes can be provided for
     * field-specific behavior and styling.
     *
     * Example:
     * Input::select(
     *     $data,
     *     'customer_group_id',
     *     'Grupo',
     *     $customerGroups,
     *     'col-12 col-sm-4',
     *     buttons: [
     *         [
     *             'class' => 'btn-primary',
     *             'href' => APP_BASE_URL . '/customerGroups',
     *             'aria-label' => 'Adicionar grupo',
     *             'icon' => 'plus-lg',
     *         ],
     *     ]
     * )
     *
     * @param array  $data Form data containing the field value and validation error.
     * @param string $field Field name used for the select name, ID, value, and error.
     * @param string $displayText Label text displayed above the select.
     * @param array  $options Associative array of option values and their display labels.
     * @param string $colClass Bootstrap column classes for the field container.
     * @param string $classes Additional CSS classes for the select.
     * @param string $attributes Additional HTML attributes for the select.
     * @param bool   $required Whether the field is required.
     * @param array  $buttons Optional action buttons displayed alongside the select.
     */
    public static function select(array $data, string $field, string $displayText, array $options, string $colClass, string $classes = '', string $attributes = '', bool $required = true, array $buttons = []): void
    {
        $errorMessage = Helper::escape($data[$field . '_error'] ?? '');
        $hasError = empty($data[$field . '_error']) ? '' : 'is-invalid';
        $requiredIndicator = $required ? '<sup class="text-danger">*</sup>' : '';
        $buttonGroup = self::buildButtonGroup($buttons);

        $inputGroupStart = $buttonGroup !== '' ? '<div class="input-group">' : '';
        $inputGroupEnd = $buttonGroup !== '' ? '</div>' : '';

        $optionsHtml = '<option value="" selected hidden disabled>Selecionar</option>';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = (($data[$field] ?? '') == $optionValue ?  'selected' : '');

            $optionValue = Helper::escape($optionValue);
            $optionLabel = Helper::escape($optionLabel);

            $optionsHtml .= "<option value=\"$optionValue\"$selected>$optionLabel</option>";
        }

        echo <<<HTML
            <div class="{$colClass}">
                <label for="{$field}">{$displayText}: {$requiredIndicator}</label>
                {$inputGroupStart}
                    <select name="{$field}" id="{$field}" class="form-select {$classes} {$hasError}" required {$attributes}>
                    {$optionsHtml}
                    </select>
                    {$buttonGroup}
                    <div class="invalid-feedback">
                        {$errorMessage}
                    </div>
                {$inputGroupEnd}
            </div>
        HTML;
    }

    /**
     * Renders a chain of dependent select fields.
     *
     * The first select is populated with the provided options, while each
     * subsequent select is initially empty and receives its options dynamically
     * based on the value selected in the preceding select.
     *
     * Each select definition must contain `field`, `displayText`, and `colClass`.
     * The first select must also provide `options`. Every select except the last
     * must provide `loadUrl`, `loadText`, and `loadValue`, which define the URL
     * used to load the next select and the response fields used for its option
     * labels and values.
     *
     * The generated fields use the `load-options` class and corresponding
     * `data-*` attributes to enable the dynamic loading behavior.
     *
     * Selects after the first are disabled when they do not have a current value
     * in $data. When editing an existing record, the selected value of each child
     * select is passed through `data-load-selected` so that the corresponding
     * options can be selected after they are loaded.
     *
     * Each select can define its own CSS classes and required state. The `select2`
     * class is automatically applied to all fields in the chain.
     *
     * Example:
     * Input::selectChain($data, [
     *     [
     *         'field' => 'state',
     *         'displayText' => 'Estado',
     *         'options' => $stateOptions,
     *         'colClass' => 'col-12 col-sm-2',
     *         'loadUrl' => APP_BASE_URL . '/locations/cities',
     *         'loadText' => 'city',
     *         'loadValue' => 'city',
     *     ],
     *     [
     *         'field' => 'city',
     *         'displayText' => 'Cidade',
     *         'colClass' => 'col-12 col-sm-3',
     *     ],
     * ]);
     *
     * @param array $data Form data containing the current values and validation errors.
     * @param array $selects Select definitions ordered from the first to the last
     *                        field in the dependency chain.
     */
    public static function selectChain(array $data, array $selects): void
    {
        foreach ($selects as $index => $select) {
            $isFirst = $index === 0;
            $isLast = $index === array_key_last($selects);
            $options = $isFirst ? $select['options'] : [];

            $classes = 'select2 ' . ($select['classes'] ?? '') . ($isFirst ? ' chain-start' : '');
            $attributes = '';

            if (!$isLast) {
                $child = $selects[$index + 1];

                $classes .= ' load-options';

                $attributes =
                    'data-load-target="#' . Helper::escape($child['field']) . '" ' .
                    'data-load-url="' . Helper::escape($select['loadUrl']) . '" ' .
                    'data-load-text="' . Helper::escape($select['loadText']) . '" ' .
                    'data-load-value="' . Helper::escape($select['loadValue']) . '" ' .
                    'data-load-selected="' . Helper::escape($data[$child['field']] ?? '') . '"';
            }

            if (!$isFirst && empty($data[$select['field']])) $attributes .= ' disabled';

            self::select(
                $data,
                $select['field'],
                $select['displayText'],
                $options,
                $select['colClass'],
                classes: $classes,
                attributes: $attributes,
                required: $select['required'] ?? true
            );
        }
    }
}
