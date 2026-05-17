@component($typeForm, get_defined_vars())
    <link rel="stylesheet" href="{{asset('vendor/laraberg/css/laraberg.css')}}">

    <textarea id="{{ $id }}" name="{{ $name }}" hidden>{{ $value ?? '' }}</textarea>

    <script src="https://unpkg.com/react@17.0.2/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@17.0.2/umd/react-dom.production.min.js"></script>
    <script src="{{ asset('vendor/laraberg/js/laraberg.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try {
                if (window.Laraberg && Laraberg.wordpress && Laraberg.wordpress.hooks) {
                    Laraberg.wordpress.hooks.addFilter(
                        'blocks.pasteHandler',
                        'orchid-laraberg/clean-paste',
                        function (content) {
                            if (typeof content !== 'string') return content;
                            return content
                                .replace(/<!--\[if[\s\S]*?\[endif\]-->/gi, '')
                                .replace(/<\/?(?:o|w|m|st\d):[^>]*>/gi, '')
                                .replace(/ (?:style|class|lang|mso-[^=]*)="[^"]*"/gi, '')
                                .replace(/<\/?font[^>]*>/gi, '')
                                .replace(/<\/?span[^>]*>/gi, '');
                        }
                    );
                }
            } catch (e) {
                console.warn('orchid-laraberg paste filter not installed:', e);
            }

            Laraberg.init('{{ $id }}', @json($options ?? new \stdClass()));
        });
    </script>
@endcomponent
