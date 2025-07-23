<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Step Three - Complete Transaction</title>
</head>
<body>

    <p><h2>Step Three: Script automatically completes the transaction <br /></h2></p>

    @if ((string)$gwResponse->result == 1)
        <p><h3> Transaction was Approved, XML response was:</h3></p>
        <pre>{{ $data }}</pre>

    @elseif ((string)$gwResponse->result == 2)
        <p><h3> Transaction was Declined.</h3>
        Decline Description : {{ (string)$gwResponse->{'result-text'} }} </p>
        <p><h3>XML response was:</h3></p>
        <pre>{{ $data }}</pre>

    @else
        <p><h3> Transaction caused an Error.</h3>
        Error Description: {{ (string)$gwResponse->{'result-text'} }} </p>
        <p><h3>XML response was:</h3></p>
        <pre>{{$data}}</pre>
    @endif

</body>
</html>