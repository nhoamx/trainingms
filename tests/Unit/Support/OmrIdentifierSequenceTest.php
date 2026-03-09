<?php

namespace Tests\Unit\Support;

use App\Support\OmrIdentifierSequence;
use Tests\TestCase;

class OmrIdentifierSequenceTest extends TestCase
{
    public function test_total_combinations_matches_expected_catalog_size(): void
    {
        $this->assertSame(3854, OmrIdentifierSequence::totalCombinations());
    }

    public function test_catalog_starts_with_expected_order(): void
    {
        $catalog = OmrIdentifierSequence::catalog();

        $this->assertSame('1a', $catalog[0]);
        $this->assertSame('1e', $catalog[4]);
        $this->assertSame('2a', $catalog[5]);
        $this->assertSame('2e', $catalog[9]);
        $this->assertSame('1a1a', $catalog[10]);
    }

    public function test_validation_only_accepts_identifiers_from_catalog(): void
    {
        $this->assertTrue(OmrIdentifierSequence::isValid('1a'));
        $this->assertTrue(OmrIdentifierSequence::isValid('2e'));
        $this->assertTrue(OmrIdentifierSequence::isValid('1ab2cd'));

        $this->assertFalse(OmrIdentifierSequence::isValid('1_a'));
        $this->assertFalse(OmrIdentifierSequence::isValid('1aa'));
        $this->assertFalse(OmrIdentifierSequence::isValid('3a'));
    }

    public function test_next_available_returns_first_open_identifier(): void
    {
        $used = ['1a', '1b', '1c'];

        $this->assertSame('1d', OmrIdentifierSequence::nextAvailable($used));
    }
}
