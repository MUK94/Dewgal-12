@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Picture Privacy') }}</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('member.update_picture_privacy', Auth::user()->id) }}" method="POST">
              @csrf
              <div class="form-group mb-3">
                  <label for="name">{{translate('Profile Picture')}}</label>
                  @php $profile_pic_permission = \App\Models\Member::where('user_id',Auth::user()->id)->first()->profile_picture_privacy; @endphp
                  <select class="form-control aiz-selectpicker" data-live-search="true" name="profile_picture_privacy" required>
                    <option value="0" @if($profile_pic_permission == 0) selected @endif>{{ translate('Only Me') }}</option>
                    <option value="1" @if($profile_pic_permission == 1) selected @endif>{{ translate('All Member') }}</option>
                    <option value="2" @if($profile_pic_permission == 2) selected @endif>{{ translate('Premium Member') }}</option>
                  </select>
              </div>
              <div class="form-group mb-6">
                  <label>{{translate('Gallery Images')}}</label>
                  @php $gallery_pic_permission = \App\Models\Member::where('user_id',Auth::user()->id)->first()->gallery_image_privacy; @endphp
                  <select class="form-control aiz-selectpicker" data-live-search="true" name="gallery_image_privacy" required>
                    <option value="0" @if($gallery_pic_permission == 0) selected @endif>{{ translate('Only Me') }}</option>
                    <option value="1" @if($gallery_pic_permission == 1) selected @endif>{{ translate('All Member') }}</option>
                    <option value="2" @if($gallery_pic_permission == 2) selected @endif>{{ translate('Premium Member') }}</option>
                  </select>
              </div>

              <div class="form-group row text-right pb-7">
                  <div class="col-md-12">
                      <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                  </div>
              </div>
            </form>
        </div>
    </div>
@endsection
