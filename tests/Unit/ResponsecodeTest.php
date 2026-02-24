<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Unit;

use DariusIII\NetNntp\Protocol\ResponseCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ResponseCode enum.
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

    public function testLegacyConstantsNoLongerDefined(): void
    {
        $this->assertFalse(
            defined('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED'),
            'Legacy global constants should no longer be defined'
        );
    }

    public static function enumCasesProvider(): array
    {
        $data = [];
        foreach (ResponseCode::cases() as $case) {
            $data[$case->name] = [$case, $case->value];
        }
        return $data;
    }
}

