$posting = $nntp->connect('news.php.net');
if (\DariusIII\NetNntp\Error::isError($posting)) {
    // handle error
}
