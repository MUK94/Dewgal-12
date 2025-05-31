@extends('admin.layouts.app')
@section('content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Appointments') }}</h1>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header row gutters-5">
                    <div class="col text-center text-md-left">
                        <h5 class="mb-md-0 h6">{{ translate('All Appointments') }}</h5>
                    </div>
                    <div class="col-md-3">
                        <form class="" id="sort_appointments" action="" method="GET">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="search"
                                    name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                                    placeholder="{{ translate('Search by name or ID') }}">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Requester') }}</th>
                                <th>{{ translate('Recipient') }}</th>
                                <th data-breakpoints="md">{{ translate('Scheduled At') }}</th>
                                <th data-breakpoints="md">{{ translate('Location') }}</th>
                                <th data-breakpoints="md">{{ translate('Status') }}</th>
                                <th data-breakpoints="md">{{ translate('Created At') }}</th>
                                <th class="text-center">{{ translate('Options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $key => $appointment)
                                <tr>
                                    <td>{{ $key + 1 + ($appointments->currentPage() - 1) * $appointments->perPage() }}</td>
                                    <td>
                                        @if ($appointment->requester)
                                            {{ $appointment->requester->first_name . ' ' . $appointment->requester->last_name }}
                                            <br>
                                            <small>{{ $appointment->requester->email }}</small>
                                        @else
                                            {{ translate('N/A') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($appointment->recipient)
                                            {{ $appointment->recipient->first_name . ' ' . $appointment->recipient->last_name }}
                                            <br>
                                            <small>{{ $appointment->recipient->email }}</small>
                                        @else
                                            {{ translate('N/A') }}
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $appointment->scheduled_at ? $appointment->scheduled_at->format('d M Y h:i A') : 'N/A' }}</small>
                                    </td>
                                    <td><small>{{ $appointment->location ?? 'N/A' }}</small></td>
                                    <td>
                                        <span
                                            class="badge badge-inline {{ $appointment->status == 'accepted' ? 'badge-success' : ($appointment->status == 'declined' ? 'badge-danger' : ($appointment->status == 'cancelled' ? 'badge-soft-danger' : ($appointment->status == 'completed' ? 'badge-primary' : 'badge-info'))) }}">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </td>
                                    <td><small>{{ $appointment->created_at->format('d-m-Y') }}</small></td>
                                    <td class="text-center">
                                        @if (Auth::user()->user_type === 'admin')
                                            {{-- <form action="{{ route('appointments.admin.accept', $appointment->id) }}"
                                                method="POST" class="d-inline-block ms-1">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-success" type="submit" title="Accept">
                                                    <i class="las la-check-circle"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('appointments.admin.reject', $appointment->id) }}"
                                                method="POST" class="d-inline-block ms-1">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-warning" type="submit" title="Reject">
                                                    <i class="las la-times-circle"></i>
                                                </button>
                                            </form> --}}


                                            {{-- Cancel Button (for requester, recipient, or admin if not finalized) --}}
                                            {{-- <form action="{{ route('appointments.admin.cancel', $appointment->id) }}"
                                                method="POST" class="d-inline-block ms-1">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-soft-info" type="submit" title="Cancel">
                                                    <i class="las la-ban"></i>
                                                </button>
                                            </form> --}}
                                            <form action="{{ route('appointments.admin.complete', $appointment->id) }}"
                                                method="POST" class="d-inline-block ms-1">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-soft-success" type="submit"
                                                    title="Mark as Completed">
                                                    <i class="las la-check-double"></i>
                                                </button>
                                            </form>

                                            <form action="{{ route('appointments.admin.destroy', $appointment->id) }}"
                                                method="POST" class="d-inline-block"
                                                onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-soft-danger" type="submit" title="Delete">
                                                    <i class="las la-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="aiz-pagination">
                        {{ $appointments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    {{-- Appointment Accept Modal --}}
    {{-- <div class="modal fade appointment-accept-modal" id="modal-basic">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Accept Appointment') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <form class="form-horizontal appointment-accept" action="{{ route('appointments.admin.accept') }}" method="POST">
                        @csrf
                        <input type="hidden" name="appointment_id" id="accept_appointment_id" value="">
                        <div class="form-group">
                            <label>{{ translate('Location (if changing)') }}</label>
                            <input type="text" class="form-control" name="location" placeholder="{{ translate('Optional') }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Notes (optional)') }}</label>
                            <textarea class="form-control" name="notes" placeholder="{{ translate('Additional notes') }}"></textarea>
                        </div>
                        <button type="button" class="btn btn-light mt-2"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary mt-2">{{ translate('Accept Appointment') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- Appointment Decline Modal --}}
    {{-- <div class="modal fade appointment-decline-modal" id="modal-basic">
        <div class="modal-dialog">
            <div class="modal-content">
                <form class="form-horizontal appointment-decline" action="{{ route('appointments.admin.decline') }}" method="POST">
                    @csrf
                    <input type="hidden" name="appointment_id" id="decline_appointment_id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6">{{ translate('Decline Appointment') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Reason for Declining') }}</label>
                            <textarea class="form-control" name="decline_reason" placeholder="{{ translate('Please provide a reason') }}" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-danger">{{ translate('Decline Appointment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}

    {{-- Appointment Cancel Modal --}}
    {{-- <div class="modal fade appointment-cancel-modal" id="modal-basic">
        <div class="modal-dialog">
            <div class="modal-content">
                <form class="form-horizontal appointment-cancel" action="{{ route('appointments.admin.cancel') }}" method="POST">
                    @csrf
                    <input type="hidden" name="appointment_id" id="cancel_appointment_id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6">{{ translate('Cancel Appointment') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Cancellation Reason') }}</label>
                            <textarea class="form-control" name="cancel_reason" placeholder="{{ translate('Please provide a reason') }}" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-secondary">{{ translate('Cancel Appointment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}

    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function sort_appointments(el) {
            $('#sort_appointments').submit();
        }

        function accept_appointment(id) {
            $('.appointment-accept-modal').modal('show');
            $('#accept_appointment_id').val(id);
        }

        function decline_appointment(id) {
            $('.appointment-decline-modal').modal('show');
            $('#decline_appointment_id').val(id);
        }

        function cancel_appointment(id) {
            $('.appointment-cancel-modal').modal('show');
            $('#cancel_appointment_id').val(id);
        }
    </script>
@endsection
