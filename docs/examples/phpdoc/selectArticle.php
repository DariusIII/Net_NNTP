$groupsummary = $nntp->selectGroup('php.pear.general');
if (\Net\NNTP\Error::isError($groupsummary)) {
    // handle error
}

$article = $nntp->selectArticle(5);
if (\Net\NNTP\Error::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
