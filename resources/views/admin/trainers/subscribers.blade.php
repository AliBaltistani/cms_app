@extends('layouts.master')

@section('content')
<div class="container">
    <h1>Subscribers for {{ $trainer->name }}</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Client</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Subscribed At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscriptions as $sub)
                <tr>
                    <td>{{ optional($sub->client)->name }}</td>
                    <td>{{ optional($sub->client)->email }}</td>
                    <td>{{ optional($sub->client)->phone }}</td>
                    <td>{{ optional($sub->subscribed_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $subscriptions->links() }}
</div>
@endsection