$authenticated = $nntp->authenticate('somebody', 'secret');
if (\DariusIII\NetNntp\Error::isError($authenticated)) {
    // handle error
}

if ($authenticated) {
    // success
} else {
    // failure
}
