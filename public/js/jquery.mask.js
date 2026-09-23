jQuery(document).ready(function () {
    $('#cpf').mask("000.000.000-00");

    $('#cnpj').mask("00.000.000/0000-00");

    $('#zip_code').mask("00000-000");

    $('.money').mask("00.000.000,00", { reverse: true });

    $('.money').on('input', function () {
        let value = this.value.replace(/\D/g, '');

        if (!value) {
            this.value = '';
            return;
        }

        value = value.replace(/^0+/, '').slice(0, 10).padStart(3, '0');

        const integer = value.slice(0, -2);
        const decimal = value.slice(-2);

        this.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + decimal;
    });

    $('.percent').mask("00%", { reverse: true });

    var SPMaskBehavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
        spOptions = {
            onKeyPress: function (val, e, field, options) {
                field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };

    $('.phone-mask').mask(SPMaskBehavior, spOptions);

    $('.date').mask("00/00/0000");

    $('.time').on('input', function () {
        let value = this.value.replace(/\D/g, '');

        if (!value) {
            this.value = '';
            return;
        }

        value = value.replace(/^0+/, '').slice(0, 4).padStart(4, '0');

        this.value = value.slice(0, 2) + ':' + value.slice(2);
    });

    $('.small-number').mask("00");

    var cnpjBehavior = function (val) {
        return val.replace(/\D/g, '').length > 11 ? '00.000.000/0000-00' : '000.000.000-00999';
    };

    var cnpjOptions = {
        onKeyPress: function (val, e, field, options) {
            field.mask(cnpjBehavior.apply({}, arguments), options);
        }
    };

    $('.cnpj-cpf').mask(cnpjBehavior, cnpjOptions);
});