<?php

namespace Tests\Unit;

use App\Support\BatchModeContext;
use PHPUnit\Framework\TestCase;

class BatchModeContextTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clear any batch mode state after each test
        BatchModeContext::clear();
        parent::tearDown();
    }

    public function test_batch_mode_starts_disabled(): void
    {
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(1));
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(999));
    }

    public function test_can_enable_batch_mode_for_organization(): void
    {
        BatchModeContext::enableForOrganization(1);

        $this->assertTrue(BatchModeContext::isEnabledForOrganization(1));
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(2));
    }

    public function test_can_disable_batch_mode_for_organization(): void
    {
        BatchModeContext::enableForOrganization(1);
        $this->assertTrue(BatchModeContext::isEnabledForOrganization(1));

        BatchModeContext::disableForOrganization(1);
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(1));
    }

    public function test_multiple_organizations_can_have_independent_batch_mode(): void
    {
        BatchModeContext::enableForOrganization(1);
        BatchModeContext::enableForOrganization(3);

        $this->assertTrue(BatchModeContext::isEnabledForOrganization(1));
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(2));
        $this->assertTrue(BatchModeContext::isEnabledForOrganization(3));
    }

    public function test_clear_removes_all_batch_mode_state(): void
    {
        BatchModeContext::enableForOrganization(1);
        BatchModeContext::enableForOrganization(2);
        BatchModeContext::enableForOrganization(3);

        BatchModeContext::clear();

        $this->assertFalse(BatchModeContext::isEnabledForOrganization(1));
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(2));
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(3));
    }

    public function test_run_with_batch_mode_enables_and_disables_automatically(): void
    {
        $callbackExecuted = false;

        $result = BatchModeContext::runWithBatchMode(1, function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            $this->assertTrue(BatchModeContext::isEnabledForOrganization(1));

            return 'test-result';
        });

        $this->assertTrue($callbackExecuted);
        $this->assertEquals('test-result', $result);
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(1));
    }

    public function test_run_with_batch_mode_disables_even_on_exception(): void
    {
        try {
            BatchModeContext::runWithBatchMode(1, function () {
                $this->assertTrue(BatchModeContext::isEnabledForOrganization(1));
                throw new \Exception('Test exception');
            });
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }

        // Batch mode should be disabled even after exception
        $this->assertFalse(BatchModeContext::isEnabledForOrganization(1));
    }
}
