@extends('layouts.vertical', ['title' => 'RFQ Form'])

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ $rfqForm->title }}</h4>
            @if($rfqForm->subtitle)
                <small class="text-light">{{ $rfqForm->subtitle }}</small>
            @endif
        </div>

        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    @foreach($fields as $field)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ $field }} <span class="text-danger">*</span></label>
                                
                                @if(str_contains(strtolower($field), 'image') || str_contains(strtolower($field), 'url'))
                                    <input type="url" name="fields[{{ $field }}]" class="form-control" placeholder="Enter {{ $field }}">
                                    <img id="{{ Str::slug($field) }}Preview" class="img-fluid mt-2" style="max-height:150px; display:none;">
                                @elseif(str_contains(strtolower($field), 'kg') || str_contains(strtolower($field), 'price') || str_contains(strtolower($field), 'cbm') || str_contains(strtolower($field), 'moq'))
                                    <input type="number" step="any" name="fields[{{ $field }}]" class="form-control" placeholder="Enter {{ $field }}">
                                @else
                                    <input type="text" name="fields[{{ $field }}]" class="form-control" placeholder="Enter {{ $field }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i> Submit Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Optional JS for image preview --}}
<script>
document.querySelectorAll('input[type="url"]').forEach(input => {
    input.addEventListener('change', function() {
        const imgId = this.name.match(/\[(.*)\]/)[1].replace(/\s+/g, '-') + 'Preview';
        const img = document.getElementById(imgId);
        if(img && this.value) {
            img.src = this.value;
            img.style.display = 'block';
        }
    });
});
</script>
@endsection
