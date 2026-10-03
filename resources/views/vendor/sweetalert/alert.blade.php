@if (config('sweetalert.alwaysLoadJS') === true || Session::has('alert.config') || Session::has('alert.delete'))
    @if (config('sweetalert.animation.enable'))
        <link rel="stylesheet" href="{{ config('sweetalert.animatecss') }}">
    @endif

    @if (config('sweetalert.neverLoadJS') === false)
        <script src="{{ $cdn ?? asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (Session::has('alert.delete'))
                var deleteConfig = {!! Session::pull('alert.delete') !!};
                document.addEventListener('click', function(event) {
                    var target = event.target;
                    var confirmDeleteElement = target.closest('[data-confirm-delete]');

                    if (confirmDeleteElement && confirmDeleteElement.tagName === 'A') {
                        event.preventDefault();
                        Swal.fire(deleteConfig).then(function(result) {
                            if (result.isConfirmed) {
                                var form = document.createElement('form');
                                form.action = confirmDeleteElement.href;
                                form.method = 'POST';
                                form.innerHTML = `
                                    @csrf
                                    @method('DELETE')
                                `;
                                document.body.appendChild(form);
                                form.submit();
                            }
                        });
                    }
                });
            @endif

            @if (Session::has('alert.config'))
                Swal.fire({!! Session::pull('alert.config') !!});
            @endif

            // Global SweetAlert confirmation handler for all forms
            document.addEventListener('submit', function(event) {
                var form = event.target;
                if (form.dataset.swalConfirmed === "true") {
                    delete form.dataset.swalConfirmed;
                    return true;
                }

                var confirmText = form.getAttribute('data-confirm');
                var onsubmitAttr = form.getAttribute('onsubmit');

                if (!confirmText && onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                    var match = onsubmitAttr.match(/confirm\(['"](.+?)['"]\)/);
                    confirmText = match && match[1] ? match[1] : 'Are you sure?';
                    form.removeAttribute('onsubmit');
                }

                if (confirmText) {
                    event.preventDefault();
                    event.stopPropagation();

                    Swal.fire({
                        title: 'Confirm Deletion',
                        text: confirmText,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Delete',
                        cancelButtonText: 'Cancel'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            form.dataset.swalConfirmed = "true";
                            form.submit();
                        }
                    });
                    return false;
                }
            }, true);
        });
    </script>
@endif
