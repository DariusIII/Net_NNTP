<?php

declare(strict_types=1);

namespace Net\NNTP\Tests\Unit;

use Net\NNTP\Protocol\ResponseCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for both the ResponseCode enum and the backwards-compat global constants.
 */
final class ResponsecodeTest extends TestCase
{
    /* ── Enum tests ─────────────────────────────────────────── */

    public function testEnumIsIntBacked(): void
    {
        $ref = new \ReflectionEnum(ResponseCode::class);
        $this->assertTrue($ref->isBacked());
        $this->assertSame('int', (string) $ref->getBackingType());
    }

    public function testEnumCaseCount(): void
    {
        $cases = ResponseCode::cases();
        // 47 active + 4 new (DisconnectingRequested, DisconnectingForced, TlsContinue, TlsRefused, XgtitleFollows, XgtitleUnavailable)
        $this->assertGreaterThanOrEqual(47, \count($cases));
    }

    #[DataProvider('enumCasesProvider')]
    public function testEnumCaseValue(ResponseCode $case, int $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    #[DataProvider('enumCasesProvider')]
    public function testEnumFromReturnsCase(ResponseCode $case, int $expected): void
    {
        $this->assertSame($case, ResponseCode::from($expected));
    }

    #[DataProvider('enumCasesProvider')]
    public function testEnumTryFromReturnsCase(ResponseCode $case, int $expected): void
    {
        $this->assertSame($case, ResponseCode::tryFrom($expected));
    }

    public function testTryFromReturnsNullForInvalid(): void
    {
        $this->assertNull(ResponseCode::tryFrom(999));
    }

    public function testFromThrowsForInvalid(): void
    {
        $this->expectException(\ValueError::class);
        ResponseCode::from(999);
    }

    #[DataProvider('enumCasesProvider')]
    public function testDescriptionReturnsNonEmptyString(ResponseCode $case, int $expected): void
    {
        $desc = $case->description();
        $this->assertIsString($desc);
        $this->assertNotEmpty($desc);
    }

    public static function enumCasesProvider(): array
    {
        $data = [];
        foreach (ResponseCode::cases() as $case) {
            $data[$case->name] = [$case, $case->value];
        }
        return $data;
    }

    /* ── Legacy constant shim tests ─────────────────────────── */

    #[DataProvider('constantsProvider')]
    public function testLegacyConstantIsDefined(string $name, int $expected): void
    {
        $this->assertTrue(defined($name), "Constant $name should be defined");
        $this->assertSame($expected, constant($name));
    }

    public static function constantsProvider(): array
    {
        return [
            'READY_POSTING_ALLOWED'    => ['NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED', 200],
            'READY_POSTING_PROHIBITED' => ['NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED', 201],
            'SLAVE_RECOGNIZED'         => ['NET_NNTP_PROTOCOL_RESPONSECODE_SLAVE_RECOGNIZED', 202],
            'UNKNOWN_COMMAND'          => ['NET_NNTP_PROTOCOL_RESPONSECODE_UNKNOWN_COMMAND', 500],
            'SYNTAX_ERROR'             => ['NET_NNTP_PROTOCOL_RESPONSECODE_SYNTAX_ERROR', 501],
            'NOT_PERMITTED'            => ['NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED', 502],
            'NOT_SUPPORTED'            => ['NET_NNTP_PROTOCOL_RESPONSECODE_NOT_SUPPORTED', 503],
            'GROUP_SELECTED'           => ['NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED', 211],
            'NO_SUCH_GROUP'            => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP', 411],
            'NO_GROUP_SELECTED'        => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED', 412],
            'ARTICLE_FOLLOWS'          => ['NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS', 220],
            'HEAD_FOLLOWS'             => ['NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS', 221],
            'BODY_FOLLOWS'             => ['NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS', 222],
            'ARTICLE_SELECTED'         => ['NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED', 223],
            'NO_ARTICLE_SELECTED'      => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED', 420],
            'NO_NEXT_ARTICLE'          => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE', 421],
            'NO_PREVIOUS_ARTICLE'      => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE', 422],
            'NO_SUCH_ARTICLE_NUMBER'   => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER', 423],
            'NO_SUCH_ARTICLE_ID'       => ['NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID', 430],
            'TRANSFER_SEND'            => ['NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND', 335],
            'TRANSFER_SUCCESS'         => ['NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS', 235],
            'TRANSFER_UNWANTED'        => ['NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED', 435],
            'TRANSFER_FAILURE'         => ['NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE', 436],
            'TRANSFER_REJECTED'        => ['NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED', 437],
            'POSTING_SEND'             => ['NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND', 340],
            'POSTING_SUCCESS'          => ['NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS', 240],
            'POSTING_PROHIBITED'       => ['NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED', 440],
            'POSTING_FAILURE'          => ['NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE', 441],
            'AUTHORIZATION_REQUIRED'   => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REQUIRED', 450],
            'AUTHORIZATION_CONTINUE'   => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_CONTINUE', 350],
            'AUTHORIZATION_ACCEPTED'   => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_ACCEPTED', 250],
            'AUTHORIZATION_REJECTED'   => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHORIZATION_REJECTED', 452],
            'AUTHENTICATION_REQUIRED'  => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REQUIRED', 480],
            'AUTHENTICATION_CONTINUE'  => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_CONTINUE', 381],
            'AUTHENTICATION_ACCEPTED'  => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_ACCEPTED', 281],
            'AUTHENTICATION_REJECTED'  => ['NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REJECTED', 482],
            'HELP_FOLLOWS'             => ['NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS', 100],
            'CAPABILITIES_FOLLOW'      => ['NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW', 101],
            'SERVER_DATE'              => ['NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE', 111],
            'GROUPS_FOLLOW'            => ['NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW', 215],
            'OVERVIEW_FOLLOWS'         => ['NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS', 224],
            'HEADERS_FOLLOW'           => ['NET_NNTP_PROTOCOL_RESPONSECODE_HEADERS_FOLLOW', 225],
            'NEW_ARTICLES_FOLLOW'      => ['NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW', 230],
            'NEW_GROUPS_FOLLOW'        => ['NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW', 231],
            'WRONG_MODE'               => ['NET_NNTP_PROTOCOL_RESPONSECODE_WRONG_MODE', 401],
            'INTERNAL_FAULT'           => ['NET_NNTP_PROTOCOL_RESPONSECODE_INTERNAL_FAULT', 403],
            'BASE64_ENCODING_ERROR'    => ['NET_NNTP_PROTOCOL_RESPONSECODE_BASE64_ENCODING_ERROR', 504],
        ];
    }
}

