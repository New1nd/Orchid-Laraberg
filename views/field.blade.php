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

            const options = @json($options ?? new \stdClass());

            if (options._mediaUploadEndpoint) {
                const endpoint = options._mediaUploadEndpoint;
                delete options._mediaUploadEndpoint;

                options.mediaUpload = function ({ filesList, onFileChange, onError, additionalData }) {
                    const formData = new FormData();
                    Array.from(filesList).forEach(function (f) { formData.append('files[]', f); });
                    if (additionalData) {
                        Object.keys(additionalData).forEach(function (k) {
                            formData.append(k, additionalData[k]);
                        });
                    }

                    const headers = { 'Accept': 'application/json' };
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                    const xsrfMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                    if (xsrfMatch) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfMatch[1]);

                    fetch(endpoint, {
                        method: 'POST',
                        headers: headers,
                        credentials: 'same-origin',
                        body: formData,
                    })
                    .then(function (r) {
                        if (!r.ok) {
                            return r.text().then(function (t) {
                                throw new Error('Upload failed (' + r.status + '): ' + t.slice(0, 200));
                            });
                        }
                        return r.json();
                    })
                    .then(function (data) { onFileChange(data); })
                    .catch(function (e) { (onError || console.error)(e.message); });
                };
            }

            Laraberg.init('{{ $id }}', options);
        });
    </script>
@endcomponent
