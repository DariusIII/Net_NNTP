$descriptions = $nntp->getDescriptions('*.pear.*');
if (\DariusIII\NetNntp\Error::isError($descriptions)) {
    // handle error
}

foreach ($descriptions as $group => $description) {
    echo $group, ': ', $description, "\r\n";
}
