$groupsummary = $nntp->selectGroup('php.pear.general');
if (\DariusIII\NetNntp\Error::isError($groupsummary)) {
    // handle error
}

$article = $nntp->selectArticle(5);
if (\DariusIII\NetNntp\Error::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
