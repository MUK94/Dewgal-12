@extends('frontend.layouts.app')

@section('content')
    <div class="py-6">
        <div class="container">
            <div class="row">
                <div class="col-xxl-5 col-xl-6 col-md-8 mx-auto">
                    <div class="bg-white rounded shadow-sm p-4 text-left">
                        <h1 class="h3 fw-600 mb-3">{{ translate('Please Verify Your Account') }}</h1>

                        {{-- @if (session('resent'))
                            <div class="alert alert-success mt-2 mb-0" role="alert">
                                {{ translate('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif --}}

                        {{-- Displaying flash messages --}}
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

                        {{-- @if (session('message'))
                            <div class="alert alert-info mt-2 mb-0" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif --}}

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <p class="opacity-60">
                            {{ translate('Before proceeding, please check your email for a verification code.') }}
                            {{ translate('If you did not receive the code, you can request another.') }}
                        </p>

                        <form method="POST" action="{{ route('verify.code') }}">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="verification_code"
                                    class="form-label">{{ translate('Verification Code') }}</label>
                                <input id="code" type="text" name="code" class="form-control" required autofocus>
                                {{-- HIDDEN EMAIL FIELD ADDED HERE --}}
                                <input type="hidden" name="email" value="{{ request('email') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                {{ translate('Verify Code') }}
                            </button>
                        </form>

                        <hr>

                        {{-- Ensure email is passed to the resend route as well --}}
                        <a href="{{ route('verify.resend', ['email' => request('email')]) }}" class="btn btn-link p-0">
                            {{ translate('Click here to request another code') }}
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
