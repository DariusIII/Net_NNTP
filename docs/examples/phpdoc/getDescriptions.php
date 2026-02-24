$descriptions = $nntp->getDescriptions('*.pear.*');
if (\Net\NNTP\Error::isError($descriptions)) {
    // handle error
}

foreach ($descriptions as $group => $description) {
    echo $group, ': ', $description, "\r\n";
}
