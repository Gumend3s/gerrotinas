jQuery(document).ready(function () {
    $('.select2').select2({
        theme: "bootstrap-5",
        placeholder: 'Selecionar',
        width: '100%',
        language: {
            noResults: function () {
                return 'Nenhum resultado encontrado';
            }
        }
    });

    $('.load-options').on('change', function (e, selected = '') {
        const $source = $(this);
        const $target = $($source.data('load-target'));

        loadOptions($source, $target, selected);
    });

    $('.chain-start').each(function () {
        const $source = $(this);
        const $target = $($source.data('load-target'));
        const selected = $source.data('load-selected') || '';

        loadOptions($source, $target, selected);
    });

    function loadOptions($source, $target, selected = '') {
        const sourceValue = $source.val();

        $target.prop('disabled', true);
        $target.empty().append(new Option('', ''));

        if (!sourceValue) {
            $target.val('').trigger('change');
            return;
        }

        const url = $source.data('load-url');
        const textFields = $source.data('load-text').split(',');
        const valueField = $source.data('load-value');

        $.getJSON(`${url}/${sourceValue}`, function (items) {
            items.forEach(item => {
                const text = textFields
                    .map(field => item[field])
                    .join(' - ');

                $target.append(
                    new Option(text, item[valueField])
                );
            });

            $target.prop('disabled', false);

            if (selected && items.some(item => item[valueField] == selected)) {
                $target.val(selected);
            } else {
                $target.val('');
            }

            $target.trigger('change', [$target.data('load-selected') || '']);
        });
    }
});