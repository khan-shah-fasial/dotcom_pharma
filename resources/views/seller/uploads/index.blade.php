@extends('seller.layouts.app')

@section('panel_content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('All uploaded files')}}</h1>
		</div>
		<div class="col-md-6 text-md-right">
			<a href="{{ route('seller.uploads.create') }}" class="btn btn-primary">
				<span>{{translate('Upload New File')}}</span>
			</a>
		</div>
	</div>
</div>

<div class="card">
    <form id="sort_uploads" action="">
        <div class="card-header row gutters-5">
            <div class="col">
                <h5 class="mb-0 h6">{{translate('All files')}}</h5>
            </div>
			<div class="dropdown mb-2 mb-md-0">
                <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                    {{translate('Bulk Action')}}
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item confirm-alert" href="javascript:void(0)"  data-target="#bulk-delete-modal"> {{translate('Delete selection')}}</a>
                </div>
            </div>
            <div class="col-auto ml-auto">
                <button type="button" class="btn border" id="toggle-upload-filters">
                    <i class="las la-filter"></i> {{ translate('Filters') }}
                </button>
            </div>
        </div>
        @php
            $filtersOpen = ($search ?? '') !== '' || ($typeFilter ?? '') !== '' || (($sortBy ?? 'created_at') !== 'created_at') || (($sortOrder ?? 'desc') !== 'desc');
        @endphp
        <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy ?? 'created_at' }}">
        <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder ?? 'desc' }}">
        <div id="upload-filters-panel" class="px-3 pb-3 border-bottom {{ $filtersOpen ? '' : 'd-none' }}">
            <div class="row gutters-5 align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="type_filter" class="form-control form-control-xs aiz-selectpicker" name="type" data-live-search="true">
                        <option value="">{{ translate('All types') }}</option>
                        @foreach([
                            'image' => translate('Images'),
                            'video' => translate('Videos'),
                            'audio' => translate('Audio'),
                            'pdf' => translate('PDF'),
                            'doc' => translate('Word / Doc'),
                            'docx' => translate('Word / Docx'),
                            'excel' => translate('Excel'),
                            'xls' => translate('Excel (XLS)'),
                            'xlsx' => translate('Excel (XLSX)'),
                            'csv' => translate('CSV'),
                            'archive' => translate('Archive'),
                            'document' => translate('Documents'),
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($typeFilter ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="sort_select" class="form-control form-control-xs aiz-selectpicker">
                        <option value="created_at|desc" @selected(($sortBy ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'desc')>{{ translate('Newest first') }}</option>
                        <option value="created_at|asc" @selected(($sortBy ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'asc')>{{ translate('Oldest first') }}</option>
                        <option value="name|asc" @selected(($sortBy ?? '') === 'name' && ($sortOrder ?? '') === 'asc')>{{ translate('Name A-Z') }}</option>
                        <option value="name|desc" @selected(($sortBy ?? '') === 'name' && ($sortOrder ?? '') === 'desc')>{{ translate('Name Z-A') }}</option>
                        <option value="size|desc" @selected(($sortBy ?? '') === 'size' && ($sortOrder ?? '') === 'desc')>{{ translate('Size large-small') }}</option>
                        <option value="size|asc" @selected(($sortBy ?? '') === 'size' && ($sortOrder ?? '') === 'asc')>{{ translate('Size small-large') }}</option>
                        <option value="type|asc" @selected(($sortBy ?? '') === 'type' && ($sortOrder ?? '') === 'asc')>{{ translate('Type A-Z') }}</option>
                        <option value="type|desc" @selected(($sortBy ?? '') === 'type' && ($sortOrder ?? '') === 'desc')>{{ translate('Type Z-A') }}</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <input type="text" class="form-control form-control-xs" name="search" placeholder="{{ translate('Search your files') }}" value="{{ $search }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary mr-2">{{ translate('Apply') }}</button>
                    <button type="button" class="btn btn-secondary" id="reset-filters">{{ translate('Reset') }}</button>
                </div>
            </div>
        </div>
    
		<div class="card-body">
			<div class="form-group">
				<div class="aiz-checkbox-inline">
					<label class="aiz-checkbox">
						{{ translate('Select All')}}
						<input type="checkbox" class="check-all">
						<span class="aiz-square-check"></span>
					</label>
				</div>
			</div>

			<div class="row gutters-5">
				@foreach($all_uploads as $key => $file)
					@php
						if($file->file_original_name == null){
							$file_name = translate('Unknown');
						}else{
							$file_name = $file->file_original_name;
						}
					@endphp
					<div class="col-auto w-120px w-lg-150px">
						<div class="aiz-file-box">
							<div class="dropdown-file" >
								<a class="dropdown-link" data-toggle="dropdown">
									<i class="la la-ellipsis-v"></i>
								</a>
								<div class="dropdown-menu dropdown-menu-right">
									<a href="javascript:void(0)" class="dropdown-item" onclick="detailsInfo(this)" data-id="{{ $file->id }}">
										<i class="las la-info-circle mr-2"></i>
										<span>{{ translate('Details Info') }}</span>
									</a>
									<a href="{{ my_asset($file->file_name) }}" target="_blank" download="{{ $file_name }}.{{ $file->extension }}" class="dropdown-item">
										<i class="la la-download mr-2"></i>
										<span>{{ translate('Download') }}</span>
									</a>
									<a href="javascript:void(0)" class="dropdown-item" onclick="copyUrl(this)" data-url="{{ my_asset($file->file_name) }}">
										<i class="las la-clipboard mr-2"></i>
										<span>{{ translate('Copy Link') }}</span>
									</a>
									<a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('seller.my_uploads.destroy', $file->id ) }}" data-target="#delete-modal">
										<i class="las la-trash mr-2"></i>
										<span>{{ translate('Delete') }}</span>
									</a>
								</div>
							</div>

							<div class="select-box">
								<div class="aiz-checkbox-inline">
									<label class="aiz-checkbox">
										<input type="checkbox" class="check-one" name="id[]" value="{{$file->id}}">
										<span class="aiz-square-check"></span>
									</label>
								</div>
							</div>

							<div class="card card-file aiz-uploader-select c-default" title="{{ $file_name }}.{{ $file->extension }}">
								<div class="card-file-thumb">
									@if($file->type == 'image')
										<img src="{{ my_asset($file->file_name) }}" class="img-fit">
									@elseif($file->type == 'video')
										<i class="las la-file-video"></i>
									@else
										<i class="las la-file"></i>
									@endif
								</div>
								<div class="card-body">
									<h6 class="d-flex">
										<span class="text-truncate title">{{ $file_name }}</span>
										<span class="ext">.{{ $file->extension }}</span>
									</h6>
									<p>{{ formatBytes($file->file_size) }}</p>
								</div>
							</div>
						</div>
					</div>
				@endforeach
			</div>
			<div class="aiz-pagination mt-3">
				{{ $all_uploads->appends(request()->input())->links() }}
			</div>
		</div>
	</form>
</div>
@endsection
@section('modal')
<div id="info-modal" class="modal fade">
	<div class="modal-dialog modal-dialog-right">
			<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title h6">{{ translate('File Info') }}</h5>
				<button type="button" class="close" data-dismiss="modal">
				</button>
			</div>
			<div class="modal-body c-scrollbar-light position-relative" id="info-modal-content">
				<div class="c-preloader text-center absolute-center">
                    <i class="las la-spinner la-spin la-3x opacity-70"></i>
                </div>
			</div>
		</div>
	</div>
</div>
<!-- Delete modal -->
@include('modals.delete_modal')
<!-- Bulk Delete modal -->
@include('modals.bulk_delete_modal')

@endsection
@section('script')
	<script type="text/javascript">
		function detailsInfo(e){
            $('#info-modal-content').html('<div class="c-preloader text-center absolute-center"><i class="las la-spinner la-spin la-3x opacity-70"></i></div>');
			var id = $(e).data('id')
			$('#info-modal').modal('show');
			$.post('{{ route('seller.my_uploads.info') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                $('#info-modal-content').html(data);
				// console.log(data);
			});
		}
		function copyUrl(e) {
			var url = $(e).data('url');
			var $temp = $("<input>");
		    $("body").append($temp);
		    $temp.val(url).select();
		    try {
			    document.execCommand("copy");
			    AIZ.plugins.notify('success', '{{ translate('Link copied to clipboard') }}');
			} catch (err) {
			    AIZ.plugins.notify('danger', '{{ translate('Oops, unable to copy') }}');
			}
		    $temp.remove();
		}
        function sort_uploads(el){
            $('#sort_uploads').submit();
        }

        $('#toggle-upload-filters').on('click', function () {
            $('#upload-filters-panel').toggleClass('d-none');
            if (!$('#upload-filters-panel').hasClass('d-none') && $.fn.selectpicker) {
                $('#upload-filters-panel .aiz-selectpicker').selectpicker('refresh');
            }
        });

        $('#sort_select').on('change', function () {
            var parts = $(this).val().split('|');
            $('#sort_by').val(parts[0]);
            $('#sort_order').val(parts[1]);
            $('#sort_uploads').submit();
        });

        $('#reset-filters').on('click', function () {
            $('input[name="search"]').val('');
            $('#type_filter').val('');
            $('#sort_by').val('created_at');
            $('#sort_order').val('desc');
            $('#sort_select').val('created_at|desc');
            $('#sort_uploads').submit();
        });

		$(document).on("change", ".check-all", function() {
			if(this.checked) {
				// Iterate each checkbox
				$('.check-one:checkbox').each(function() {
					this.checked = true;
				});
			} else {
				$('.check-one:checkbox').each(function() {
					this.checked = false;
				});
			}
		});

		function bulk_delete() {
            var data = new FormData($('#sort_uploads')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('seller.bulk-uploaded-files-delete')}}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    if(response == 1) {
						location.reload();
                    }
					else{
						AIZ.plugins.notify('danger', '{{ translate('Something Went Wrong.') }}');
					}
                }
            });
        }
	</script>
@endsection