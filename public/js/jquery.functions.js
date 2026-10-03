jQuery(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();

    $(document).on('click', '.menu-item', function () {
        const target = $(this).data('bs-target');
        $('.collapse').not(target).collapse('hide');
    });

    $(document).on('click', 'tr[data-href]', function () {
        window.location.href = this.dataset.href;
    });

    $('#toggleIcon').on('click', function () {
        const t = $('#password').attr('type') === 'password' ? 'text' : 'password';
        $('#password').attr('type', t);
        $(this).toggleClass('bi-eye bi-eye-slash');
    });

    let searchTimeout;
    $('#search').on('keyup', function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const search = $(this).val().toLowerCase().trim();
            const table = $($(this).data('table'));

            table.find('tbody tr').each(function () {
                const rowText = ($(this).text() + $(this).data('search')).toLowerCase();
                $(this).toggle(rowText.includes(search));
            });
        }, 300);
    });

    $('.confirm-action').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        Swal.fire({
            title: $(this).data('title'),
            text: $(this).data('text'),
            html: $(this).data('html'),
            icon: $(this).data('icon'),
            showCancelButton: true,
            confirmButtonColor: "#00194F",
            cancelButtonColor: "#d33",
            confirmButtonText: $(this).data('confirm-text'),
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) window.location.href = $(this).data('href');
        });
    });

    $('.choice-action').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        Swal.fire({
            title: $(this).data('title'),
            text: $(this).data('text'),
            html: $(this).data('html'),
            icon: $(this).data('icon'),
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: "#00194F",
            denyButtonColor: "#FFA800",
            cancelButtonColor: "#d33",
            confirmButtonText: $(this).data('confirm-text'),
            denyButtonText: $(this).data('deny-text'),
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) window.location.href = $(this).data('confirm-href');
            else if (result.isDenied) window.location.href = $(this).data('deny-href');
        });
    });

    $('.zip_code').on('input', function (e) {
        const cep = e.target.value.replace(/[^\d]/g, '');

        if (cep.length >= 8) {
            const url = `https://viacep.com.br/ws/${cep}/json/`;

            fetch(url).then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            }).then(data => {
                if (data.erro) return;
                $('#address').val(data.logradouro).trigger('change');
                $('#district').val(data.bairro).trigger('change');
                $('#state').val(data.uf).trigger('change', [data.localidade]);
            }).catch(error => {
                console.error('Houve um problema na requisição:', error);
            });
        } else {
            $('#address').val('').trigger('change');
            $('#number').val('').trigger('change');
            $('#district').val('').trigger('change');
            $('#state').val('').trigger('change');
            $('#city').val('').trigger('change');
        }
    });

    $('.search-cnpj').on('click', function () {
        const cnpj = $('#cnpj').val().replace(/[^\d]/g, '');

        console.log(cnpj);
        if (cnpj.length >= 14) {
            const url = `https://api.opencnpj.org/${cnpj}`;
            fetch(url).then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            }).then(data => {
                if (data.erro) return;
                $('#legal_name').val(data.razao_social).trigger('change');
                $('#trade_name').val(data.nome_fantasia).trigger('change');
                $('#zip_code').val(data.cep.replace(/^(\d{5})(\d{3})$/, '$1-$2')).trigger('change');
                $('#address').val(data.logradouro).trigger('change');
                $('#number').val(data.numero).trigger('change');
                $('#district').val(data.bairro).trigger('change');
                $('#state').val(data.uf).trigger('change', [capitalize(data.municipio)]);
            }).catch(error => {
                console.error('Houve um problema na requisição: ', error);
            });
        } else {
            $('#legal_name').val('').trigger('change');
            $('#trade_name').val('').trigger('change');
            $('#zip_code').val('').trigger('change');
            $('#address').val('').trigger('change');
            $('#number').val('').trigger('change');
            $('#district').val('').trigger('change');
            $('#state').val('').trigger('change');
            $('#city').val('').trigger('change');
        }
    });

    $('.click-redirect').on('click', function (e) {
        e.preventDefault();

        window.location.href = $(this).data('href');
    });

    $('.image-input').on('change', function () {
        const file = this.files[0];
        const imageId = $(this).data('image');
        const image = $('#' + imageId);

        if (imageObjectUrl) URL.revokeObjectURL(imageObjectUrl);

        if (!file) {
            image.find('.image-preview')
                .attr('src', '')
                .addClass('d-none');

            image.find('.image-placeholder')
                .removeClass('d-none');

            return;
        }

        imageObjectUrl = URL.createObjectURL(file);

        image.find('.image-preview')
            .attr('src', imageObjectUrl)
            .removeClass('d-none');

        image.find('.image-placeholder')
            .addClass('d-none');
    });

    const capitalize = str => str ? str[0].toUpperCase() + str.slice(1).toLowerCase() : str;
});