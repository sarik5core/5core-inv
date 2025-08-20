@extends('layouts.vertical', ['title' => 'Claim & Reimbursement'])

@section('content')
<div class="container">
    <h2>Product Purchase Form</h2>

    <form method="POST" action="">
        @csrf

        <h4>Fixed Fields</h4>
        @foreach($fixedFields as $field)
            <div class="mb-3">
                <label>{{ $field['label'] }}</label>
                @if($field['type'] === 'select')
                    <select name="{{ $field['name'] }}" class="form-control">
                        @foreach($field['options'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                @elseif($field['type'] === 'textarea')
                    <textarea name="{{ $field['name'] }}" class="form-control"></textarea>
                @else
                    <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" class="form-control" step="{{ $field['step'] ?? '' }}">
                @endif
            </div>
        @endforeach

        <h4>Dynamic Fields</h4>
        <div class="mb-3">
            <label>Product Category</label>
            <select id="category" class="form-control">
                <option value="">-- Select Category --</option>
                @foreach($dynamicFields as $cat => $fields)
                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>

        <div id="dynamic-fields-container"></div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script>
    const dynamicFields = @json($dynamicFields);

    document.getElementById('category').addEventListener('change', function() {
        const selected = this.value;
        const container = document.getElementById('dynamic-fields-container');
        container.innerHTML = '';

        if (dynamicFields[selected]) {
            dynamicFields[selected].forEach(field => {
                let html = `<div class="mb-3">
                                <label>${field.label}</label>`;
                if (field.type === 'textarea') {
                    html += `<textarea name="${field.name}" class="form-control"></textarea>`;
                } else {
                    html += `<input type="${field.type}" name="${field.name}" class="form-control" step="${field.step ?? ''}">`;
                }
                html += `</div>`;
                container.innerHTML += html;
            });
        }
    });
</script>
@endsection
