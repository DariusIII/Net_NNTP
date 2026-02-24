$authenticated = $nntp->authenticate('somebody', 'secret');
if (\Net\NNTP\Error::isError($authenticated)) {
    // handle error
}

if ($authenticated) {
    // success
} else {
    // failure
}
