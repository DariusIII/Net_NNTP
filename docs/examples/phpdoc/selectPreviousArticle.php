$article = $nntp->selectPreviousArticle();
if (\Net\NNTP\Error::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
