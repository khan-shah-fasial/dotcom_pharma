
<div class="modal-header">
    <h5 class="modal-title h6">{{translate('Contact Query')}}</h5>
    <button type="button" class="close" data-dismiss="modal">
    </button>
</div>
<div class="modal-body">
    @php
        $isSupport = $contact->type === 'support';
        $data = $isSupport && $contact->data ? json_decode($contact->data, true) : [];
        $customer = $data['customer'] ?? [];
        $staff = $data['staff'] ?? [];
        $channel = $data['channel'] ?? null;
    @endphp

    <table class="table table-striped table-bordered" >
        <tbody>
            <tr>
                <td>{{ translate('Name') }}</td>
                <td>{{ $customer['name'] ?? $contact->name }}</td>
            </tr>
            <tr>
                <td>{{ translate('Phone') }}</td>
                <td>{{ $customer['phone'] ?? $contact->phone }}</td>
            </tr>
            <tr>
                <td>{{ translate('Email') }}</td>
                <td>{{ $customer['email'] ?? $contact->email }}</td>
            </tr>

            @if ($isSupport)
                <tr>
                    <td>{{ translate('Support Staff') }}</td>
                    <td>{{ $staff['name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td>{{ translate('Channel') }}</td>
                    <td>
                        @if ($channel === 'video')
                            {{ translate('Video Meet') }}
                        @elseif ($channel === 'callback')
                            {{ translate('Call Back') }}
                        @else
                            {{ $channel ?? '-' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ translate('Preferred Date & Time') }}</td>
                    <td>{{ $data['scheduled_at'] ?? '-' }}</td>
                </tr>
            @endif

            @if (!$isSupport)
                <tr>
                    <td>{{ $isSupport ? translate('Notes') : translate('Query') }}</td>
                    <td>{!! str_replace("\n", "<br>", $contact->content) !!}</td>
                </tr>
            @endif

            @if ($isSupport && !is_null($contact->review))
                <tr>
                    <td>{{ translate('Review') }}</td>
                    <td>
                        @for ($i = 0; $i < (int) $contact->review; $i++)
                            <i class="las la-star text-warning"></i>
                        @endfor
                        @for ($i = (int) $contact->review; $i < 5; $i++)
                            <i class="lar la-star text-muted"></i>
                        @endfor
                        <span class="ml-2">({{ $contact->review }}/5)</span>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
<div class="modal-footer">
    {{-- @can('reply_to_contact')
        @if ($contact->reply == null)
            <a href="javascript:void(1)" onclick="showReplyModal({{ $contact->id }})" class="btn btn-primary">{{translate('Reply')}}</a>
        @endif
    @endcan --}}
    <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Cancel')}}</button>
</div>
