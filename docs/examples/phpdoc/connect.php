$posting = $nntp->connect('news.php.net');
if (Net_NNTP_Error::isError($posting)) {
    // handle error
}
