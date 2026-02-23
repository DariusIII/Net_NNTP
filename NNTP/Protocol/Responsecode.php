<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 *
 *
 * PHP versions 8.5 and above
 *
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C® SOFTWARE NOTICE AND LICENSE                                      |
 * | http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231   |
 * |                                                                       |
 * | This work (and included software, documentation such as READMEs,      |
 * | or other related items) is being provided by the copyright holders    |
 * | under the following license. By obtaining, using and/or copying       |
 * | this work, you (the licensee) agree that you have read, understood,   |
 * | and will comply with the following terms and conditions.              |
 * |                                                                       |
 * | Permission to copy, modify, and distribute this software and its      |
 * | documentation, with or without modification, for any purpose and      |
 * | without fee or royalty is hereby granted, provided that you include   |
 * | the following on ALL copies of the software and documentation or      |
 * | portions thereof, including modifications:                            |
 * |                                                                       |
 * | 1. The full text of this NOTICE in a location viewable to users       |
 * |    of the redistributed or derivative work.                           |
 * |                                                                       |
 * | 2. Any pre-existing intellectual property disclaimers, notices,       |
 * |    or terms and conditions. If none exist, the W3C Software Short     |
 * |    Notice should be included (hypertext is preferred, text is         |
 * |    permitted) within the body of any redistributed or derivative      |
 * |    code.                                                              |
 * |                                                                       |
 * | 3. Notice of any changes or modifications to the files, including     |
 * |    the date changes were made. (We recommend you provide URIs to      |
 * |    the location from which the code is derived.)                      |
 * |                                                                       |
 * | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
 * | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
 * | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
 * | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
 * | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
 * | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
 * |                                                                       |
 * | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
 * | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
 * | SOFTWARE OR DOCUMENTATION.                                            |
 * |                                                                       |
 * | The name and trademarks of copyright holders may NOT be used in       |
 * | advertising or publicity pertaining to the software without           |
 * | specific, written prior permission. Title to copyright in this        |
 * | software and any associated documentation will at all times           |
 * | remain with copyright holders.                                        |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * </pre>
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C® SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 * @see
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
