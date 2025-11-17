@extends('layouts.master')

@section('content')
<div class="container">
    <h1>All Subscriptions</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Trainer</th>
                <th>Status</th>
                <th>Subscribed At</th>
                <th>Unsubscribed At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscriptions as $sub)
                <tr>
                    <td>{{ $sub->id }}</td>
                    <td>{{ optional($sub->client)->name }}</td>
                    <td>{{ optional($sub->trainer)->name }}</td>
                    <td>{{ $sub->status }}</td>
                    <td>{{ optional($sub->subscribed_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($sub->unsubscribed_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.subscriptions.toggle', $sub->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-primary">
                                {{ $sub->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $subscriptions->links() }}
</div>
@endsection