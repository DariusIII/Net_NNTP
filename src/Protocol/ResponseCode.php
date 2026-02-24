<?php

namespace DariusIII\NetNntp\Protocol;

/**
 * NNTP Response Code enum
 *
 * PHP versions 8.5 and above
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C SOFTWARE NOTICE AND LICENSE
 * @since      File available since release 1.3.0
 */
enum ResponseCode: int
{
    // Connection
    case ReadyPostingAllowed = 200;
    case ReadyPostingProhibited = 201;
    case SlaveRecognized = 202;

    // Common errors
    case UnknownCommand = 500;
    case SyntaxError = 501;
    case NotPermitted = 502;
    case NotSupported = 503;

    // Group selection
    case GroupSelected = 211;
    case NoSuchGroup = 411;

    // Article retrieval
    case ArticleFollows = 220;
    case HeadFollows = 221;
    case BodyFollows = 222;
    case ArticleSelected = 223;
    case NoGroupSelected = 412;
    case NoArticleSelected = 420;
    case NoNextArticle = 421;
    case NoPreviousArticle = 422;
    case NoSuchArticleNumber = 423;
    case NoSuchArticleId = 430;

    // Transferring
    case TransferSend = 335;
    case TransferSuccess = 235;
    case TransferUnwanted = 435;
    case TransferFailure = 436;
    case TransferRejected = 437;

    // Posting
    case PostingSend = 340;
    case PostingSuccess = 240;
    case PostingProhibited = 440;
    case PostingFailure = 441;

    // Authorization
    case AuthorizationRequired = 450;
    case AuthorizationContinue = 350;
    case AuthorizationAccepted = 250;
    case AuthorizationRejected = 452;

    // Authentication
    case AuthenticationRequired = 480;
    case AuthenticationContinue = 381;
    case AuthenticationAccepted = 281;
    case AuthenticationRejected = 482;

    // Misc
    case HelpFollows = 100;
    case CapabilitiesFollow = 101;
    case ServerDate = 111;
    case GroupsFollow = 215;
    case OverviewFollows = 224;
    case HeadersFollow = 225;
    case NewArticlesFollow = 230;
    case NewGroupsFollow = 231;
    case WrongMode = 401;
    case InternalFault = 403;
    case Base64EncodingError = 504;
}
