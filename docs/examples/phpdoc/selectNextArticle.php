$article = $nntp->selectNextArticle();
if (\DariusIII\NetNntp\Error::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
