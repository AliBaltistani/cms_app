@extends('layouts.master')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Payment Gateways</h4>
        <a href="{{ route('admin.billing.gateways.create') }}" class="btn btn-primary">Add Gateway</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Default</th>
                <th>Status</th>
                <th>Commission %</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gateways as $g)
            <tr>
                <td>{{ $g->id }}</td>
                <td>{{ $g->gateway_name }}</td>
                <td>{{ ucfirst($g->gateway_type) }}</td>
                <td>{{ $g->is_default ? 'Yes' : 'No' }}</td>
                <td>{{ $g->status ? 'Enabled' : 'Disabled' }}</td>
                <td>{{ number_format($g->commission_rate, 2) }}</td>
                <td>
                    <a href="{{ route('admin.billing.gateways.edit', $g) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form action="{{ route('admin.billing.gateways.toggle', $g) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-outline-warning" type="submit">Toggle</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $gateways->links() }}
</div>
@endsection
