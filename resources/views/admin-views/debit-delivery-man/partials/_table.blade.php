@foreach ($debit_records as $k => $record)
    <tr>
        <td>{{ $k + $debit_records->firstItem() }}</td>
        <td>
            @if ($record->delivery_man)
                <a href="{{ route('admin.users.delivery-man.preview', $record->delivery_man_id) }}">
                    {{ $record->delivery_man->f_name . ' ' . $record->delivery_man->l_name }}
                </a>
            @else
                <span class="text-danger text-capitalize">
                    {{ translate('messages.deliveryman_deleted') }}
                </span>
            @endif
        </td>
        <td>
            <span class="text-danger font-weight-bold">
                - {{ \App\CentralLogics\Helpers::format_currency($record->amount) }}
            </span>
        </td>
        <td>
            <span class="badge badge-soft-warning text-capitalize">
                @php
                    $reason_label = \App\Models\DebitDeliverymanReason::find($record->reason)?->reason
                        ?? \App\Models\OrderCancelReason::find($record->reason)?->reason
                        ?? translate(str_replace('_', ' ', $record->reason));
                @endphp
                {{ $reason_label }}
            </span>
        </td>
        <td>{{ $record->note ?? '—' }}</td>
        <td>{{ \App\CentralLogics\Helpers::time_date_format($record->created_at) }}</td>
    </tr>
@endforeach
