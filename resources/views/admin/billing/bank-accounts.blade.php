@extends('layouts.master')

@section('content')
<div class="container">
    <h4>Trainer Bank Accounts</h4>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Trainer</th>
                <th>Account ID</th>
                <th>Verification</th>
                <th>Bank Status</th>
                <th>Bank</th>
                <th>Last4</th>
                <th>Routing</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $a)
            <tr>
                <td>{{ $a->id }}</td>
                <td>{{ optional($a->trainer)->name }}</td>
                <td>{{ $a->account_id }}</td>
                <td>{{ ucfirst($a->verification_status) }}</td>
                <td>{{ ucfirst($a->bank_verification_status) }}</td>
                <td>{{ $a->bank_name }}</td>
                <td>{{ $a->bank_account_last4 }}</td>
                <td>{{ $a->routing_number_last4 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $accounts->links() }}
</div>
@endsection

