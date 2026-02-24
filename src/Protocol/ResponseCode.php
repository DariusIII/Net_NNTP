<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Protocol;

/**
 * NNTP protocol response codes as defined in RFC 977, RFC 2980, RFC 3977, and RFC 4642.
 */
enum ResponseCode: int
{
    // Session / Connection
    case ReadyPostingAllowed     = 200;
    case ReadyPostingProhibited  = 201;
    case SlaveRecognized         = 202;
    case DisconnectingRequested  = 205;
    case DisconnectingForced     = 400;

    // Common errors
    case UnknownCommand    = 500;
    case SyntaxError       = 501;
    case NotPermitted      = 502;
    case NotSupported      = 503;
    case Base64EncodingError = 504;

    // Group selection
    case GroupSelected   = 211;
    case NoSuchGroup     = 411;
    case NoGroupSelected = 412;

    // Article retrieval
    case ArticleFollows        = 220;
    case HeadFollows           = 221;
    case BodyFollows           = 222;
    case ArticleSelected       = 223;
    case NoArticleSelected     = 420;
    case NoNextArticle         = 421;
    case NoPreviousArticle     = 422;
    case NoSuchArticleNumber   = 423;
    case NoSuchArticleId       = 430;

    // Transfer
    case TransferSend     = 335;
    case TransferSuccess  = 235;
    case TransferUnwanted = 435;
    case TransferFailure  = 436;
    case TransferRejected = 437;

    // Posting
    case PostingSend       = 340;
    case PostingSuccess    = 240;
    case PostingProhibited = 440;
    case PostingFailure    = 441;

    // Authorization (RFC 2980)
    case AuthorizationRequired = 450;
    case AuthorizationContinue = 350;
    case AuthorizationAccepted = 250;
    case AuthorizationRejected = 452;

    // Authentication (RFC 2980)
    case AuthenticationRequired = 480;
    case AuthenticationContinue = 381;
    case AuthenticationAccepted = 281;
    case AuthenticationRejected = 482;

    // Informational
    case HelpFollows        = 100;
    case CapabilitiesFollow = 101;
    case ServerDate         = 111;
    case GroupsFollow       = 215;
    case OverviewFollows    = 224;
    case HeadersFollow      = 225;
    case NewArticlesFollow  = 230;
    case NewGroupsFollow    = 231;

    // Miscellaneous
    case WrongMode     = 401;
    case InternalFault = 403;

    // TLS (RFC 4642)
    case TlsContinue = 382;
    case TlsRefused  = 580;

    // Non-standard extensions
    case XgtitleFollows      = 282;
    case XgtitleUnavailable  = 481;

    /**
     * Human-readable description of this response code per RFC.
     */
    public function description(): string
    {
        return match ($this) {
            self::ReadyPostingAllowed     => 'Server ready - posting allowed',
            self::ReadyPostingProhibited  => 'Server ready - no posting allowed',
            self::SlaveRecognized         => 'Slave status noted',
            self::DisconnectingRequested  => 'Closing connection - goodbye',
            self::DisconnectingForced     => 'Service discontinued',
            self::UnknownCommand          => 'Command not recognized',
            self::SyntaxError             => 'Command syntax error',
            self::NotPermitted            => 'Access restriction or permission denied',
            self::NotSupported            => 'Program fault - command not performed',
            self::Base64EncodingError     => 'Error in base64-encoding of an argument',
            self::GroupSelected           => 'Group selected',
            self::NoSuchGroup             => 'No such news group',
            self::NoGroupSelected         => 'No newsgroup has been selected',
            self::ArticleFollows          => 'Article retrieved - head and body follow',
            self::HeadFollows             => 'Article retrieved - head follows',
            self::BodyFollows             => 'Article retrieved - body follows',
            self::ArticleSelected         => 'Article retrieved - request text separately',
            self::NoArticleSelected       => 'No current article has been selected',
            self::NoNextArticle           => 'No next article in this group',
            self::NoPreviousArticle       => 'No previous article in this group',
            self::NoSuchArticleNumber     => 'No such article number in this group',
            self::NoSuchArticleId         => 'No such article found',
            self::TransferSend            => 'Send article to be transferred',
            self::TransferSuccess         => 'Article transferred ok',
            self::TransferUnwanted        => 'Article not wanted - do not send it',
            self::TransferFailure         => 'Transfer failed - try again later',
            self::TransferRejected        => 'Article rejected - do not try again',
            self::PostingSend             => 'Send article to be posted',
            self::PostingSuccess          => 'Article posted ok',
            self::PostingProhibited       => 'Posting not allowed',
            self::PostingFailure          => 'Posting failed',
            self::AuthorizationRequired   => 'Authorization required for this command',
            self::AuthorizationContinue   => 'Continue with authorization sequence',
            self::AuthorizationAccepted   => 'Authorization accepted',
            self::AuthorizationRejected   => 'Authorization rejected',
            self::AuthenticationRequired  => 'Authentication required',
            self::AuthenticationContinue  => 'More authentication information required',
            self::AuthenticationAccepted  => 'Authentication accepted',
            self::AuthenticationRejected  => 'Authentication rejected',
            self::HelpFollows             => 'Help text follows',
            self::CapabilitiesFollow      => 'Capabilities list follows',
            self::ServerDate              => 'Server date and time',
            self::GroupsFollow            => 'Information follows',
            self::OverviewFollows         => 'Overview information follows',
            self::HeadersFollow           => 'Headers follow',
            self::NewArticlesFollow       => 'List of new articles follows',
            self::NewGroupsFollow         => 'List of new newsgroups follows',
            self::WrongMode               => 'Server is in the wrong mode',
            self::InternalFault           => 'Internal fault or problem preventing action',
            self::TlsContinue             => 'Continue with TLS negotiation',
            self::TlsRefused              => 'Can not initiate TLS negotiation',
            self::XgtitleFollows          => 'List of groups and descriptions follows',
            self::XgtitleUnavailable      => 'Groups and descriptions unavailable',
        };
    }
}

