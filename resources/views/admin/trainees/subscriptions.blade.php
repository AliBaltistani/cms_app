@extends('layouts.master')

@section('content')
<div class="container">
    <h1>Subscribed Trainers for {{ $trainee->name }}</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Trainer</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Subscribed At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscriptions as $sub)
                <tr>
                    <td>{{ optional($sub->trainer)->name }}</td>
                    <td>{{ optional($sub->trainer)->email }}</td>
                    <td>{{ optional($sub->trainer)->phone }}</td>
                    <td>{{ $sub->status }}</td>
                    <td>{{ optional($sub->subscribed_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $subscriptions->links() }}
</div>
@endsection