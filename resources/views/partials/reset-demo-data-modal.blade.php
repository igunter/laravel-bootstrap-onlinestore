<div class="modal fade" id="resetDemoDataModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('demo-data.reset') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reset demo data?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This permanently deletes <strong>everything</strong> in the database and reseeds fresh demo data. There is no undo.</p>
                <label class="form-label" for="reset-confirm-input">
                    Type <code>RESET</code> to confirm.
                </label>
                <input type="text" id="reset-confirm-input" class="form-control" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="reset-confirm-submit" disabled>Reset everything</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('reset-confirm-input');
            const submitButton = document.getElementById('reset-confirm-submit');

            if (!input || !submitButton) return;

            input.addEventListener('input', () => {
                submitButton.disabled = input.value !== 'RESET';
            });

            document.getElementById('resetDemoDataModal')?.addEventListener('hidden.bs.modal', () => {
                input.value = '';
                submitButton.disabled = true;
            });
        })();
    </script>
@endpush
