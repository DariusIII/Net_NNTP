$article = $nntp->selectNextArticle();
if (Net_NNTP_Error::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
