@extends('frontend.layouts.member_panel')

@section('panel_content')
    <style>
        .btn-secondary,
        .btn-soft-secondary:hover,
        .btn-outline-secondary:hover {
            background-color: initial;
            border-color: var(--secondary);
            color: var(--white);
        }
    </style>
    <div class="border shadow-sm p-4 rounded">
        <div class="aiz-titlebar mt-2 mb-8">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <h5 class="fw-600 mb-1">{{ translate('Appointment Details') }}</h5>
                </div>
            </div>
        </div>

        <div class="row gutters-10 justify-content-center pb-4">
            <div class="bg-white rounded shadow-sm p-2">
                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="alert alert-success mt-2">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mt-2">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mt-2">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0 w-full">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ translate('Opponent') }}</th> {{-- Changed from Recipient to Opponent --}}
                                    <th>{{ translate('Scheduled At') }}</th>
                                    <th>{{ translate('Location') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th class="text-center">{{ translate('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($appointments as $key => $appointment)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            @if ($appointment->requester_id === Auth::id())
                                                {{ $appointment->recipient->first_name . ' ' . $appointment->recipient->last_name }}
                                            @else
                                                {{ $appointment->requester->first_name . ' ' . $appointment->requester->last_name }}
                                            @endif
                                        </td>
                                        <td>{{ $appointment->scheduled_at->format('d M Y, H:i') }}</td>
                                        <td>{{ $appointment->location ?? '-' }}</td>
                                        <td><span
                                                class="badge badge-inline {{ $appointment->status == 'accepted' ? 'badge-success' : ($appointment->status == 'declined' ? 'badge-danger' : ($appointment->status == 'cancelled' ? 'badge-soft-danger' : ($appointment->status == 'completed' ? 'badge-primary' : 'badge-info'))) }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            {{-- Conditional Status Actions --}}
                                            @if ($appointment->status == 'pending' && Auth::id() === $appointment->recipient_id)
                                                <form action="{{ route('appointments.accept', $appointment->id) }}"
                                                    method="POST" class="d-inline-block ms-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-success" type="submit" title="Accept">
                                                        <i class="las la-check-circle"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('appointments.reject', $appointment->id) }}"
                                                    method="POST" class="d-inline-block ms-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-warning" type="submit" title="Reject">
                                                        <i class="las la-times-circle"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Cancel Button (for requester, recipient, or admin if not finalized) --}}
                                            @if (in_array($appointment->status, ['pending', 'accepted']) &&
                                                    (Auth::id() === $appointment->requester_id || Auth::id() === $appointment->recipient_id))
                                                <form action="{{ route('appointments.cancel', $appointment->id) }}"
                                                    method="POST" class="d-inline-block ms-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-soft-info" type="submit" title="Cancel">
                                                        <i class="las la-ban"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            {{-- Currently visible to requester if pending, or admin --}}
                                            @if (
                                                ($appointment->status == 'pending' && Auth::id() === $appointment->requester_id) ||
                                                    Auth::user()->user_type === 'admin')
                                                <a href="{{ route('appointments.edit', $appointment->id) }}"
                                                    class="btn btn-sm btn-soft-primary me-1" title="Edit">
                                                    <i class="las la-edit"></i>
                                                </a>
                                            @endif
                                            @if (in_array($appointment->status, ['pending', 'completed']) && Auth::id() === $appointment->requester_id)
                                                <form action="{{ route('appointments.destroy', $appointment->id) }}"
                                                    method="POST" class="d-inline-block"
                                                    onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-soft-danger" type="submit"
                                                        title="Delete">
                                                        <i class="las la-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No appointments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Optional Pagination --}}
                        <div class="aiz-pagination">
                            {{ $appointments->links() }}
                        </div>
                    </div>
                </div>

                {{-- Appointment Request Form --}}
                <h5 class="fw-600 py-4">{{ translate('Request a New Appointment') }}</h5>
                <form action="{{ route('appointments.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="recipient_id" class="form-label">{{ translate('Recipient') }}</label>
                        <select name="recipient_id" id="recipient_id" class="form-select" required>
                            <option value="">{{ translate('--Select--') }}</option>
                            {{-- Iterate directly over the unique User objects --}}
                            @foreach ($usersForAppointmentDropdown as $userInDropdown)
                                <option value="{{ $userInDropdown->id }}"
                                    {{ old('recipient_id') == $userInDropdown->id ? 'selected' : '' }}>
                                    {{ $userInDropdown->first_name . ' ' . $userInDropdown->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="scheduled_at" class="form-label">{{ translate('Date & Time') }}</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control"
                            value="{{ old('scheduled_at') }}" required>
                        @error('scheduled_at')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="location" class="form-label">{{ translate('Location (optional)') }}</label>
                        <input type="text" id="location" name="location" class="form-control"
                            value="{{ old('location') }}">
                        @error('location')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="notes" class="form-label">{{ translate('Message') }}</label>
                        <textarea id="notes" name="notes" class="form-control" required>{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Request Appointment') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
