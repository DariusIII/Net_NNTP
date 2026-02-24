<?php

/**
 * Backwards-compatible constant definitions.
 *
 * These global constants are provided for legacy code that references
 * NET_NNTP_PROTOCOL_RESPONSECODE_* directly. New code should use the
 * DariusIII\NetNntp\Protocol\ResponseCode enum instead.
 *
 * @deprecated Use DariusIII\NetNntp\Protocol\ResponseCode enum instead.
 */

use DariusIII\NetNntp\Protocol\ResponseCode;

// Connection
define('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED', ResponseCode::ReadyPostingAllowed->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED', ResponseCode::ReadyPostingProhibited->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_SLAVE_RECOGNIZED', ResponseCode::SlaveRecognized->value);

// Common errors
define('NET_NNTP_PROTOCOL_RESPONSECODE_UNKNOWN_COMMAND', ResponseCode::UnknownCommand->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_SYNTAX_ERROR', ResponseCode::SyntaxError->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED', ResponseCode::NotPermitted->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NOT_SUPPORTED', ResponseCode::NotSupported->value);

// Group selection
define('NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED', ResponseCode::GroupSelected->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP', ResponseCode::NoSuchGroup->value);

// Article retrieval
define('NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS', ResponseCode::ArticleFollows->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS', ResponseCode::HeadFollows->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS', ResponseCode::BodyFollows->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED', ResponseCode::ArticleSelected->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED', ResponseCode::NoGroupSelected->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED', ResponseCode::NoArticleSelected->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE', ResponseCode::NoNextArticle->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE', ResponseCode::NoPreviousArticle->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER', ResponseCode::NoSuchArticleNumber->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID', ResponseCode::NoSuchArticleId->value);

// Transfer
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND', ResponseCode::TransferSend->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS', ResponseCode::TransferSuccess->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED', ResponseCode::TransferUnwanted->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE', ResponseCode::TransferFailure->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED', ResponseCode::TransferRejected->value);

// Posting
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND', ResponseCode::PostingSend->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS', ResponseCode::PostingSuccess->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED', ResponseCode::PostingProhibited->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE', ResponseCode::PostingFailure->value);

// Authorization
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REQUIRED', ResponseCode::AuthorizationRequired->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_CONTINUE', ResponseCode::AuthorizationContinue->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_ACCEPTED', ResponseCode::AuthorizationAccepted->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REJECTED', ResponseCode::AuthorizationRejected->value);

// Authentication
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REQUIRED', ResponseCode::AuthenticationRequired->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_CONTINUE', ResponseCode::AuthenticationContinue->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_ACCEPTED', ResponseCode::AuthenticationAccepted->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REJECTED', ResponseCode::AuthenticationRejected->value);

// Informational
define('NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS', ResponseCode::HelpFollows->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW', ResponseCode::CapabilitiesFollow->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE', ResponseCode::ServerDate->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW', ResponseCode::GroupsFollow->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS', ResponseCode::OverviewFollows->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_HEADERS_FOLLOW', ResponseCode::HeadersFollow->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW', ResponseCode::NewArticlesFollow->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW', ResponseCode::NewGroupsFollow->value);

// Misc
define('NET_NNTP_PROTOCOL_RESPONSECODE_WRONG_MODE', ResponseCode::WrongMode->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_INTERNAL_FAULT', ResponseCode::InternalFault->value);
define('NET_NNTP_PROTOCOL_RESPONSECODE_BASE64_ENCODING_ERROR', ResponseCode::Base64EncodingError->value);
