{{--
    Admin credit actions shared by the Credit page and the customer profile.
    Buttons open these with data attributes:
      .js-credit-account  data-mode (create|edit), data-user-id, data-customer, data-status, data-limit, data-days
      .js-credit-remove   data-action, data-customer, data-outstanding (formatted), data-owes (1|0)
      .js-credit-markpaid data-action, data-customer, data-order, data-amount (formatted), data-raw (number owed)
--}}
@php
    $offlineMethods = \App\Models\CreditPayment::offlineMethods();
    $creditErrorKeys = ['user_id', 'credit_status', 'credit_limit', 'credit_days'];
    $reopenAccountModal = session('open_credit_modal') || $errors->hasAny($creditErrorKeys);
@endphp

{{-- Add / edit credit account --}}
<div class="modal fade" id="creditAccountModal" tabindex="-1" role="dialog" aria-labelledby="creditAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('customers.credits.update') }}" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="user_id" class="js-credit-user-id" value="{{ old('user_id') }}">
            <input type="hidden" name="customer_label" class="js-credit-customer-label" value="{{ old('customer_label') }}">
            <input type="hidden" name="credit_mode" class="js-credit-mode" value="{{ old('credit_mode', 'edit') }}">
            <div class="modal-header">
                <h5 class="modal-title js-credit-account-title" id="creditAccountModalLabel">{{ translate('Edit credit') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group js-credit-picker">
                    <label for="credit_customer_search">{{ translate('Customer') }} <span class="text-danger">*</span></label>
                    <input type="text" id="credit_customer_search" class="form-control js-credit-customer-search"
                        placeholder="{{ translate('Type name, company, phone or email') }}">
                    <div class="list-group mt-1 js-credit-customer-results" style="max-height: 220px; overflow-y: auto;"></div>
                    <small class="text-muted js-credit-search-hint">{{ translate('Type at least 2 letters.') }}</small>
                </div>
                <div class="alert alert-light border py-2 mb-3 d-none js-credit-selected">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-600 js-credit-selected-name"></span>
                        <button type="button" class="btn btn-link btn-sm p-0 js-credit-change-customer">{{ translate('Change') }}</button>
                    </div>
                    <small class="text-warning d-none js-credit-existing">{{ translate('This customer already has credit. Saving will change it.') }}</small>
                </div>
                @error('user_id')
                    <div class="text-danger fs-12 mb-2">{{ $message }}</div>
                @enderror

                <div class="form-group">
                    <label for="credit_account_status">{{ translate('Credit Status') }}</label>
                    <select id="credit_account_status" name="credit_status" class="form-control @error('credit_status') is-invalid @enderror" required>
                        <option value="active" @selected(old('credit_status', 'active') === 'active')>{{ translate('Active') }}</option>
                        <option value="deactive" @selected(old('credit_status') === 'deactive')>{{ translate('Deactive') }}</option>
                    </select>
                    @error('credit_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="credit_account_days">{{ translate('Number of Days') }}</label>
                    <input type="number" min="0" step="1" id="credit_account_days" name="credit_days" value="{{ old('credit_days') }}" required
                        class="form-control @error('credit_days') is-invalid @enderror">
                    <small class="text-muted">{{ translate('Days the customer has to pay, counted from dispatch.') }}</small>
                    @error('credit_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-0">
                    <label for="credit_account_limit">{{ translate('Credit Limit') }}</label>
                    <input type="number" min="0" step="1" id="credit_account_limit" name="credit_limit" value="{{ old('credit_limit') }}" required
                        class="form-control @error('credit_limit') is-invalid @enderror">
                    <small class="text-muted">{{ translate('The most the customer can owe at one time. The customer is notified of changes.') }}</small>
                    @error('credit_limit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-primary js-credit-account-save">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Remove credit account --}}
<div class="modal fade" id="creditRemoveModal" tabindex="-1" role="dialog" aria-labelledby="creditRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="creditRemoveModalLabel">{{ translate('Remove credit') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="fw-600 mb-2 js-credit-customer"></p>
                <div class="js-credit-remove-ok">
                    <p class="mb-0">{{ translate('Credit will be switched off and the limit and days set to 0. Past credit records and any top up balance are kept. The customer is notified.') }}</p>
                </div>
                <div class="alert alert-warning mb-0 d-none js-credit-remove-blocked">
                    {{ translate('This customer still owes') }} <span class="fw-600 js-credit-outstanding"></span>.
                    {{ translate('It must be paid before the credit account can be removed. You can switch credit to Deactive instead.') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-danger js-credit-remove-submit">{{ translate('Remove') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Mark credit on an order as paid --}}
<div class="modal fade" id="creditMarkPaidModal" tabindex="-1" role="dialog" aria-labelledby="creditMarkPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="" class="js-credit-markpaid-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="creditMarkPaidModalLabel">{{ translate('Record credit payment') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <span class="fw-600 js-credit-customer"></span><br>
                        <span class="text-muted">{{ translate('Order') }}:</span> <span class="js-credit-order"></span><br>
                        <span class="text-muted">{{ translate('Amount') }}:</span> <span class="fw-600 js-credit-amount"></span>
                    </p>
                    <div class="form-group">
                        <label for="credit_paid_amount">{{ translate('Amount received') }}</label>
                        <input type="number" lang="en" step="0.01" min="0.01" id="credit_paid_amount" name="amount" class="form-control js-credit-paid-amount">
                        <small class="text-muted">{{ translate('Leave as is to clear it in full. A smaller amount is a part payment: it goes back into the available credit and the rest stays owed.') }}</small>
                    </div>
                    <div class="form-group">
                        <label for="credit_paid_method">{{ translate('Paid by') }}</label>
                        <select id="credit_paid_method" name="payment_method" class="form-control">
                            @foreach ($offlineMethods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="credit_paid_reference">{{ translate('Reference') }}</label>
                        <input type="text" id="credit_paid_reference" name="reference" maxlength="100" class="form-control"
                            placeholder="{{ translate('Receipt, UTR or cheque number') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label for="credit_paid_note">{{ translate('Note') }}</label>
                        <textarea id="credit_paid_note" name="note" maxlength="500" rows="2" class="form-control"></textarea>
                        <small class="text-muted">{{ translate('The payment is recorded and the customer is notified.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ translate('Mark paid') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var searchUrl = @json(route('credits.customer_search'));
        var text = {
            add: @json(translate('Add credit account')),
            edit: @json(translate('Edit credit')),
            none: @json(translate('No customer found')),
            hasCredit: @json(translate('Has credit')),
            pick: @json(translate('Choose a customer first.'))
        };
        var searchTimer = null;

        function accountModal() {
            return document.getElementById('creditAccountModal');
        }

        function showSelected(name, hasCredit) {
            var modal = accountModal();
            var picker = modal.querySelector('.js-credit-picker');
            var selected = modal.querySelector('.js-credit-selected');
            var isCreate = modal.querySelector('.js-credit-mode').value === 'create';
            modal.querySelector('.js-credit-selected-name').textContent = name || '';
            modal.querySelector('.js-credit-customer-label').value = name || '';
            selected.classList.toggle('d-none', !name);
            picker.classList.toggle('d-none', !!name);
            modal.querySelector('.js-credit-change-customer').classList.toggle('d-none', !isCreate);
            modal.querySelector('.js-credit-existing').classList.toggle('d-none', !(isCreate && hasCredit));
        }

        function openAccount(mode, data) {
            var modal = accountModal();
            modal.querySelector('.js-credit-mode').value = mode;
            modal.querySelector('.js-credit-account-title').textContent = mode === 'create' ? text.add : text.edit;
            modal.querySelector('.js-credit-user-id').value = data.userId || '';
            modal.querySelector('#credit_account_status').value = data.status === 'deactive' ? 'deactive' : 'active';
            modal.querySelector('#credit_account_days').value = data.days !== undefined ? data.days : '';
            modal.querySelector('#credit_account_limit').value = data.limit !== undefined ? data.limit : '';
            modal.querySelector('.js-credit-customer-search').value = '';
            modal.querySelector('.js-credit-customer-results').innerHTML = '';
            showSelected(data.customer || '', false);
            $(modal).modal('show');
            if (mode === 'create' && !data.userId) {
                setTimeout(function () { modal.querySelector('.js-credit-customer-search').focus(); }, 300);
            }
        }

        function renderResults(items) {
            var box = accountModal().querySelector('.js-credit-customer-results');
            box.innerHTML = '';
            if (!items.length) {
                var empty = document.createElement('div');
                empty.className = 'list-group-item text-muted fs-13';
                empty.textContent = text.none;
                box.appendChild(empty);
                return;
            }
            items.forEach(function (item) {
                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'list-group-item list-group-item-action py-2';
                var name = document.createElement('div');
                name.className = 'fw-600';
                name.textContent = item.name;
                var meta = document.createElement('small');
                meta.className = 'text-muted';
                meta.textContent = (item.contact || '') + (item.has_credit ? ' · ' + text.hasCredit : '');
                row.appendChild(name);
                row.appendChild(meta);
                row.addEventListener('click', function () {
                    var modal = accountModal();
                    modal.querySelector('.js-credit-user-id').value = item.id;
                    if (item.has_credit) {
                        modal.querySelector('#credit_account_status').value = item.status;
                        modal.querySelector('#credit_account_days').value = item.days;
                        modal.querySelector('#credit_account_limit').value = item.limit;
                    }
                    box.innerHTML = '';
                    showSelected(item.name, item.has_credit);
                });
                box.appendChild(row);
            });
        }

        document.addEventListener('input', function (event) {
            if (!event.target.classList.contains('js-credit-customer-search')) {
                return;
            }
            var term = event.target.value.trim();
            clearTimeout(searchTimer);
            if (term.length < 2) {
                accountModal().querySelector('.js-credit-customer-results').innerHTML = '';
                return;
            }
            searchTimer = setTimeout(function () {
                $.getJSON(searchUrl, { q: term }).done(renderResults);
            }, 250);
        });

        document.addEventListener('submit', function (event) {
            var modal = accountModal();
            if (event.target.closest('#creditAccountModal') && !modal.querySelector('.js-credit-user-id').value) {
                event.preventDefault();
                alert(text.pick);
            }
        });

        document.addEventListener('click', function (event) {
            var account = event.target.closest('.js-credit-account');
            if (account) {
                openAccount(account.dataset.mode || 'edit', {
                    userId: account.dataset.userId,
                    customer: account.dataset.customer,
                    status: account.dataset.status,
                    limit: account.dataset.limit,
                    days: account.dataset.days
                });
                return;
            }

            if (event.target.closest('.js-credit-change-customer')) {
                accountModal().querySelector('.js-credit-user-id').value = '';
                showSelected('', false);
                accountModal().querySelector('.js-credit-customer-search').focus();
                return;
            }

            var remove = event.target.closest('.js-credit-remove');
            if (remove) {
                var removeModal = document.getElementById('creditRemoveModal');
                var owes = remove.dataset.owes === '1';
                removeModal.querySelector('form').setAttribute('action', remove.dataset.action);
                removeModal.querySelector('.js-credit-customer').textContent = remove.dataset.customer || '';
                removeModal.querySelector('.js-credit-outstanding').textContent = remove.dataset.outstanding || '';
                removeModal.querySelector('.js-credit-remove-ok').classList.toggle('d-none', owes);
                removeModal.querySelector('.js-credit-remove-blocked').classList.toggle('d-none', !owes);
                removeModal.querySelector('.js-credit-remove-submit').disabled = owes;
                $(removeModal).modal('show');
                return;
            }

            var paid = event.target.closest('.js-credit-markpaid');
            if (paid) {
                var paidModal = document.getElementById('creditMarkPaidModal');
                paidModal.querySelector('form').reset();
                paidModal.querySelector('form').setAttribute('action', paid.dataset.action);
                paidModal.querySelector('.js-credit-customer').textContent = paid.dataset.customer || '';
                paidModal.querySelector('.js-credit-order').textContent = paid.dataset.order || '-';
                paidModal.querySelector('.js-credit-amount').textContent = paid.dataset.amount || '';
                var paidAmount = paidModal.querySelector('.js-credit-paid-amount');
                paidAmount.value = paid.dataset.raw || '';
                paidAmount.max = paid.dataset.raw || '';
                $(paidModal).modal('show');
            }
        });

        @if ($reopenAccountModal)
            // Saving failed: show the form again with what was typed and the errors.
            // jQuery loads after this partial, so wait for the page.
            document.addEventListener('DOMContentLoaded', function () {
                var modal = accountModal();
                var mode = modal.querySelector('.js-credit-mode').value;
                modal.querySelector('.js-credit-account-title').textContent = mode === 'create' ? text.add : text.edit;
                showSelected(modal.querySelector('.js-credit-customer-label').value, false);
                $(modal).modal('show');
            });
        @endif
    })();
</script>
