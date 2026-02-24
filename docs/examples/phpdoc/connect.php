$posting = $nntp->connect('news.php.net');
if (\Net\NNTP\Error::isError($posting)) {
    // handle error
}
