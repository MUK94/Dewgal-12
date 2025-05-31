@extends('frontend.layouts.member_panel')

@section('panel_content')
    <div class="border shadow-sm p-4 rounded">
        <div class="aiz-titlebar mt-2 mb-8">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <h5 class="fw-600 mb-1">{{ translate('Edit Appointment') }}</h5>
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

                <form action="{{ route('appointments.update', $appointment->id) }}" method="POST">
                    @csrf
                    @method('PATCH') {{-- Or @method('PATCH') --}}

                    <div class="form-group mb-3">
                        <label for="recipient_id" class="form-label">{{ translate('Recipient') }}</label>
                        <select name="recipient_id" id="recipient_id" class="form-select" required>
                            <option value="">{{ translate('--Select--') }}</option>
                            {{-- Iterate over the unique User objects for the dropdown --}}
                            @foreach ($usersForAppointmentDropdown as $userInDropdown)
                                <option value="{{ $userInDropdown->id }}" {{-- Check old input or the existing appointment's recipient_id --}}
                                    {{ old('recipient_id', $appointment->recipient_id) == $userInDropdown->id ? 'selected' : '' }}>
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
                            value="{{ old('scheduled_at', \Carbon\Carbon::parse($appointment->scheduled_at)->format('Y-m-d\TH:i')) }}"
                            required>
                        @error('scheduled_at')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="location" class="form-label">{{ translate('Location (optional)') }}</label>
                        <input type="text" id="location" name="location" class="form-control"
                            value="{{ old('location', $appointment->location) }}">
                        @error('location')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="notes" class="form-label">{{ translate('Message') }}</label>
                        <textarea id="notes" name="notes" class="form-control" required>{{ old('notes', $appointment->notes) }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Appointment') }}</button>
                </form>

            </div>
        </div>
    </div>
@endsection
