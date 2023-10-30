@component($typeForm, get_defined_vars())
    <link rel="stylesheet" href="{{asset('vendor/laraberg/css/laraberg.css')}}">
    {{ $value ?? '' }}
    <textarea id="{{ $id }}" name="{{ $name }}" hidden>{{ $value ?? '' }}</textarea>

    <script src="https://unpkg.com/react@17.0.2/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@17.0.2/umd/react-dom.production.min.js"></script>
    <script src="{{ asset('vendor/laraberg/js/laraberg.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Laraberg.init('{{ $id }}');
        })

    </script>
@endcomponent
