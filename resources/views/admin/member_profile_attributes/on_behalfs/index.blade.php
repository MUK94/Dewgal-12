@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('On Behalves')}}</h1>
        </div>
    </div>
</div>
<div class="row">
    <div class="@if(auth()->user()->can('add_on_behalf')) col-lg-7 @else col-lg-12 @endif">
        <div class="card">
            <div class="card-header row gutters-5">
                <div class="col text-center text-md-left">
                    <h5 class="mb-md-0 h6">{{ translate('All On Behalves') }}</h5>
                </div>
                <div class="col-md-4">
                    <form class="" id="sort_on_behalves" action="" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{translate('Name')}}</th>
                            <th class="text-right" width="20%">{{translate('Options')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($on_behalves as $key => $on_behalf)
                            <tr>
                                <td>{{ ($key+1) + ($on_behalves->currentPage() - 1)*$on_behalves->perPage() }}</td>
                                <td>{{ $on_behalf->name }}</td>
                                <td class="text-right">
                                    @can('edit_on_behalf')
                                        <a href="javascript:void(0);" onclick="on_behalf_modal('{{ route('on-behalf.edit', encrypt($on_behalf->id) )}}')" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_on_behalf')
                                        <a href="javascript:void(0);" data-href="{{route('on-behalf.destroy', $on_behalf->id)}}" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" title="{{ translate('Delete') }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $on_behalves->links() }}
                </div>
            </div>
        </div>
    </div>
    @can('add_on_behalf')
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Add New On Behalf')}}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('on-behalf.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="name">{{translate('Name')}}</label>
                        <input type="text" id="name" name="name" placeholder="{{ translate('On Behalf Name') }}" class="form-control" required>
                        @error('name')
                           <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group mb-3 text-right">
                        <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
      function sort_on_behalves(el){
          $('#sort_on_behalves').submit();
      }

      function on_behalf_modal(url){
          $.get(url, function(data){
              $('.create_edit_modal_content').html(data);
              $('.create_edit_modal').modal('show');
          });
      }
    </script>
@endsection
